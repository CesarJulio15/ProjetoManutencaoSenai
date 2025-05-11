<?php
   session_start();
   if (!isset($_SESSION['id']) && $_SESSION['tipo'] !== 'coordenador') {
       header("Location: index.php");
       exit();
   }
   include 'conexao.php';
   // Totais por status
   $dados = $conn->query("SELECT status, COUNT(*) as total FROM manutencao GROUP BY status");
   $pendentes = $emAndamento = $finalizados = $rejeitados = 0;
   while ($row = $dados->fetch_assoc()) {
       switch ($row['status']) {
           case 'Pendente': $pendentes = $row['total']; break;
           case 'Em Andamento': $emAndamento = $row['total']; break;
           case 'Concluído': $finalizados = $row['total']; break;
           case 'Rejeitado': $rejeitados = $row['total']; break;
       }
   }
   
   // Ranking de responsáveis
   $ranking = $conn->query("
       SELECT d.nome AS responsavel_nome, COUNT(*) as total
       FROM manutencao m
       JOIN docentes d ON m.responsavel = d.id
       GROUP BY d.nome
       ORDER BY total DESC
       LIMIT 5
   ");
   
   $responsaveis = [];
   $totais = [];
   
   while ($r = $ranking->fetch_assoc()) {
       $responsaveis[] = $r['responsavel_nome'];
       $totais[] = $r['total'];
   }
   
   $mediaDias = 0;
   $sqlMedia = "
       SELECT AVG(DATEDIFF(data_encerramento, data_abertura)) as media_dias
       FROM manutencao
       WHERE status = 'Concluído'
       AND data_encerramento IS NOT NULL
   ";
   $resultMedia = $conn->query($sqlMedia);
   if ($row = $resultMedia->fetch_assoc()) {
       $mediaDias = round($row['media_dias'], 1); // arredondado para 1 casa decimal
   }
   // =====================
   // ENDPOINTS AJAX PARA DRILL-DOWN/UP
   // =====================
   if (isset($_GET['action'])) {
       header('Content-Type: application/json');
       $action = $_GET['action'];
       // no topo, dentro do bloco if(isset($_GET['action'])):
   if ($action === 'ranking_por_status') {
       $status = $_GET['status'];
       $stmt = $conn->prepare("
         SELECT d.nome AS label, COUNT(*) AS value
         FROM manutencao m
         JOIN docentes d ON m.responsavel = d.id
         WHERE m.status = ? AND YEAR(m.data_abertura)=?
         GROUP BY d.nome
         ORDER BY value DESC
         LIMIT 5
       ");
       $stmt->bind_param('si', $status, $yearSelected);
       $stmt->execute();
       $res = $stmt->get_result();
       $labels=[]; $data=[];
       while($r=$res->fetch_assoc()){
         $labels[]=$r['label'];
         $data[]  =(int)$r['value'];
       }
       echo json_encode(['labels'=>$labels,'data'=>$data]);
       exit;
   }
   if ($action === 'mes_por_responsavel') {
       $resp = $_GET['responsavel'];
       $stmt = $conn->prepare("
         SELECT MONTH(data_abertura)-1 AS idx, COUNT(*) AS total
         FROM manutencao m
         JOIN docentes d ON m.responsavel = d.id
         WHERE d.nome = ? AND YEAR(data_abertura)=?
         GROUP BY idx
       ");
       $stmt->bind_param('si', $resp, $yearSelected);
       $stmt->execute();
       $res = $stmt->get_result();
       $arr = ArrayFill(12,0); // helper pra preencher zeros
       while($r=$res->fetch_assoc()){
         $arr[(int)$r['idx']] = (int)$r['total'];
       }
       echo json_encode(['data'=>$arr]);
       exit;
   }
       if ($action === 'drill_mes') {
           $year  = (int)($_GET['year']  ?? date('Y'));
           $month = (int)($_GET['month'] ?? date('n'));
           $stmt = $conn->prepare(
               "SELECT DAY(data_abertura) AS period, status, COUNT(*) AS total
                FROM manutencao
                WHERE YEAR(data_abertura)=? AND MONTH(data_abertura)=?
                GROUP BY period, status
                ORDER BY period"
           );
           $stmt->bind_param('ii', $year, $month);
           $stmt->execute();
           $res = $stmt->get_result();
           $statusList = ['Pendente','Em Andamento','Concluído','Rejeitado'];
           $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
           $labels = [];
           $data = [];
           foreach ($statusList as $s) {
               $data[$s] = array_fill(0, $daysInMonth, 0);
           }
           for ($d = 1; $d <= $daysInMonth; $d++) {
               $labels[] = $d;
           }
           while ($row = $res->fetch_assoc()) {
               $idx = (int)$row['period'] - 1;
               $data[$row['status']][$idx] = (int)$row['total'];
           }
           echo json_encode(['labels' => $labels, 'datasets' => $data]);
           exit;
       }
       echo json_encode(['error' => 'Ação inválida']);
       exit;
   }
   
   // Dados por mês e status (visão mensal)
   $labelsMeses = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
   $dadosMesStatus = [
       'Pendente'      => array_fill(0, 12, 0),
       'Em Andamento'  => array_fill(0, 12, 0),
       'Concluído'     => array_fill(0, 12, 0),
       'Rejeitado'     => array_fill(0, 12, 0),
   ];
   
   // Definindo ano selecionado antes do bind
   $anoSelecionado = (int)($_GET['ano'] ?? date('Y'));
   
   $sqlMes = 
       "SELECT MONTH(data_abertura) AS mes, status, COUNT(*) AS total
        FROM manutencao
        WHERE data_abertura IS NOT NULL AND YEAR(data_abertura) = ?
        GROUP BY mes, status";
   $stmtMes = $conn->prepare($sqlMes);
   $stmtMes->bind_param('i', $anoSelecionado);
   $stmtMes->execute();
   $resMes = $stmtMes->get_result();
   while ($row = $resMes->fetch_assoc()) {
       $idx = (int)$row['mes'] - 1;
       $dadosMesStatus[$row['status']][$idx] = (int)$row['total'];
   }
   
   // Totais por status hoje
   $hoje = date('Y-m-d');
   $totaisHoje = ["Pendente" => 0, "Em Andamento" => 0, "Concluido" => 0];
   $sqlHoje = "SELECT status, COUNT(*) as total FROM manutencao WHERE data_abertura = ? GROUP BY status";
   $stmtHoje = $conn->prepare($sqlHoje);
   $stmtHoje->bind_param("s", $hoje);
   $stmtHoje->execute();
   $resHoje = $stmtHoje->get_result();
   while ($row = $resHoje->fetch_assoc()) {
       $totaisHoje[$row['status']] = $row['total'];
   }
   $totalHoje = array_sum($totaisHoje);
   
   // SLA exemplo: % concluído em menos de 3 dias
   $sqlSLA = "SELECT COUNT(*) as total, SUM(CASE WHEN DATEDIFF(data_encerramento, data_abertura) <= 3 THEN 1 ELSE 0 END) as dentro_prazo FROM manutencao WHERE status = 'Concluido' AND data_encerramento IS NOT NULL";
   $resSLA = $conn->query($sqlSLA);
   $rowSLA = $resSLA->fetch_assoc();
   $sla = $rowSLA['total'] > 0 ? round(($rowSLA['dentro_prazo'] / $rowSLA['total']) * 100, 2) : 0;
   
   // Tickets por atendente (executante)
   // Paginação para atendentes
   $pagina = isset($_GET['pagina_atendentes']) ? (int)$_GET['pagina_atendentes'] : 1;
   $limite = 5;
   $offset = ($pagina - 1) * $limite;
   
   $totalRegistros = $conn->query("SELECT COUNT(DISTINCT d.id) AS total FROM docentes d WHERE d.tipo = 'Técnico'")->fetch_assoc()['total'];
   $totalPaginas = ceil($totalRegistros / $limite);
   
   $sqlAtendentes = "
   SELECT d.nome,
     SUM(CASE WHEN m.status = 'Pendente' THEN 1 ELSE 0 END) AS pendente,
     SUM(CASE WHEN m.status = 'Em Andamento' THEN 1 ELSE 0 END) AS andamento,
     SUM(CASE WHEN m.status = 'Concluido' THEN 1 ELSE 0 END) AS concluido
   FROM docentes d
   LEFT JOIN manutencao m ON d.id = m.executante
   WHERE d.tipo = 'Técnico'
   GROUP BY d.nome
   ORDER BY d.nome
   LIMIT $limite OFFSET $offset";
   
   $resAtendentes = $conn->query($sqlAtendentes);
   $atendentes = [];
   while ($row = $resAtendentes->fetch_assoc()) {
       $atendentes[] = $row;
   }
   
   // --- Tendência mensal de aberturas vs. fechamentos ---
   $labelsTrend = [];
   $abertosMes  = [];
   $fechadosMes = [];
   
   $sqlTrend = "
     SELECT 
       DATE_FORMAT(data_abertura,'%Y-%m') AS mes,
       SUM(CASE WHEN status <> 'Concluído' THEN 1 ELSE 0 END) AS abertos,
       SUM(CASE WHEN status =  'Concluído' THEN 1 ELSE 0 END) AS fechados
     FROM manutencao
     WHERE YEAR(data_abertura) = ?
     GROUP BY mes
     ORDER BY mes
   ";
   $stmtTrend = $conn->prepare($sqlTrend);
   $stmtTrend->bind_param('i', $anoSelecionado);
   $stmtTrend->execute();
   $resTrend = $stmtTrend->get_result();
   while($r = $resTrend->fetch_assoc()){
       $labelsTrend[]  = $r['mes'];
       $abertosMes[]   = (int)$r['abertos'];
       $fechadosMes[]  = (int)$r['fechados'];
   }
   
   // --- Backlog e envelhecimento ---
   $labelsAge = [];
   $dataAge   = [];
   
   // Definindo as faixas
   $faixas = ['0–3 dias','4–7 dias','>7 dias'];
   foreach($faixas as $f) {
     $labelsAge[] = $f;
     $dataAge[]   = 0;
   }
   
   $sqlBacklog = "
     SELECT 
       CASE 
         WHEN DATEDIFF(CURDATE(), data_abertura) <= 3 THEN 1
         WHEN DATEDIFF(CURDATE(), data_abertura) <= 7 THEN 2
         ELSE 3
       END AS faixa,
       COUNT(*) AS total
     FROM manutencao
     WHERE status <> 'Concluído'
     GROUP BY faixa
   ";
   $resBacklog = $conn->query($sqlBacklog);
   while($r = $resBacklog->fetch_assoc()){
       $idx = (int)$r['faixa'] - 1;
       $dataAge[$idx] = (int)$r['total'];
   }
   
   ?>
<!DOCTYPE html>
<html lang="pt-BR">
   <head>
      <meta charset="UTF-8">
      <title>Dashboard Gerencial</title>
      <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
      <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
      <link rel="stylesheet" href="../css/dashboard.css">
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css" integrity="sha512-MV7K8+y+gLIBoVD59lQIYicR65iaqukzvf/nwasF0nqhPay5w/9lJmVM2hMDcnK1OnMGCdVK+iQrJ7lzPJQd1w==" crossorigin="anonymous" referrerpolicy="no-referrer" />
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
      <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
      <style>
      </style>
   </head>
   <body class="">
      <aside class="sidebar">
         <img src="../img/LogoPortalSenai.svg" alt="Portal Senai Manutenções" class="logo-senai">
         <div class="spacer"></div>
         <img src="../img/TechFix.svg" alt="Techfix Facilities" class="logo-techfix">
      </aside>
      <main class="main-content">
         <header class="top-bar">
            <a href="index.php" class="btn btn-secondary botao-voltar" title="Voltar"><i class="fa-solid fa-arrow-left"></i></a>
            <h2 class="title text-center mb-4">Painel de Indicadores de Manutenção</h2>
            <div class="welcome-msg me-3 text-dark fw-bold">
               <?php echo htmlspecialchars($_SESSION['nome']); ?><br>
               <span style="font-weight: 300; font-size: 0.85rem; color: #6c757d;">
               <?php echo htmlspecialchars($_SESSION['tipo']); ?>
               </span>
            </div>
            <div class="logout-btn">
               <a href="logout.php" title="Sair">
               <img src="../img/logout.svg" alt="Logout" style="width: 24px; height: 24px;">
               </a>
            </div>
         </header>
         <div class="dashboard-container d-flex flex-wrap flex-column justify-content-between gap-4 mt-3">
            <div class="d-flex flex-row justify-content-between gap-4">
               <div class="card p-3 m-auto shadow-sm w-25" style="flex:1; min-width:300px">
                  <h5 class="title"><i class="fa-solid fa-list-ul"></i> Status de Hoje</h5>
                  <div class="box">
                     <ul class="list-group">
                        <li class="list-group-item"><b>Pendente:</b> <?= $totaisHoje['Pendente'] ?></li>
                        <li class="list-group-item"><b>Em Andamento:</b> <?= $totaisHoje['Em Andamento'] ?></li>
                        <li class="list-group-item"><b>Concluído:</b> <?= $totaisHoje['Concluido'] ?></li>
                        <li class="list-group-item fw-bold">Total: <?= $totalHoje ?></li>
                        <li class="list-group-item text-success">SLA (<= 3 dias): <?= $sla ?>%</li>
                     </ul>
                  </div>
               </div>
               <div class="card p-3 m-auto shadow-sm w-100 h-75" style="flex:2; min-width:400px">
                  <h5 class="title"><i class="fa-solid fa-ticket"></i> Tickets por Atendente</h5>
                  <table>
                     <thead>
                        <tr>
                           <th>Nome</th>
                           <th>Pendente</th>
                           <th>Andamento</th>
                           <th>Concluído</th>
                        </tr>
                     </thead>
                     <tbody>
                        <?php foreach ($atendentes as $a): ?>
                        <tr>
                           <td><?= htmlspecialchars($a['nome']) ?></td>
                           <td><?= $a['pendente'] ?></td>
                           <td><?= $a['andamento'] ?></td>
                           <td><?= $a['concluido'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                     </tbody>
                  </table>
                  <nav class="mt-2">
                     <ul class="pagination justify-content-center">
                        <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                        <li class="page-item <?= $i === $pagina ? 'active' : '' ?>">
                           <a class="page-link" href="?pagina_atendentes=<?= $i ?>#tickets-atendentes"><?= $i ?></a>
                        </li>
                        <?php endfor; ?>
                     </ul>
                  </nav>
               </div>
               <div class="p-3 m-auto w-25">
                  <div class="info-box bg-pink hover-expand-effect">
                     <div class="icon">
                        <span class="material-symbols-outlined">
                        pending_actions
                        </span>
                     </div>
                     <div class="content">
                        <div class="text">Pendentes</div>
                        <div class="number count-to" data-from="0" data-to="125" data-speed="15" data-fresh-interval="20"><?= $pendentes ?></div>
                     </div>
                  </div>
                  <div class="info-box bg-orange hover-expand-effect">
                     <div class="icon">
                        <span class="material-symbols-outlined">
                        content_paste
                        </span>
                     </div>
                     <div class="content">
                        <div class="text">Em Andamento</div>
                        <div class="number count-to" data-from="0" data-to="1225" data-speed="1000" data-fresh-interval="20"><?= $emAndamento ?></div>
                     </div>
                  </div>
                  <div class="info-box bg-light-green hover-expand-effect">
                     <div class="icon">
                        <span class="material-symbols-outlined">
                        assignment_turned_in
                        </span>
                     </div>
                     <div class="content">
                        <div class="text">Finalizados</div>
                        <div class="number count-to" data-from="0" data-to="243" data-speed="1000" data-fresh-interval="20"><?= $finalizados ?></div>
                     </div>
                  </div>
                  <div class="info-box bg-cyan hover-expand-effect">
                     <div class="icon">
                        <span class="material-symbols-outlined">
                        hourglass_bottom
                        </span>
                     </div>
                     <div class="content">
                        <div class="text">Tempo Médio de Conclusão</div>
                        <div class="number count-to" data-from="0" data-to="257" data-speed="1000" data-fresh-interval="20"><?= $mediaDias ?> dias</div>
                     </div>
                  </div>
               </div>
            </div>
            <div class="d-flex flex-row justify-content-around">
               <div class="p-2 m-auto w-100 h-100">
                  <!-- Gráfico de Pizza (à esquerda) -->
                  <div class="chart-container card p-3 shadow-sm" style="height: 420px;">
                     <h5 class="title text-center"><i class="fa-solid fa-chart-pie"></i> Distribuição por Status</h5>
                     <canvas id="statusChart"></canvas>
                  </div>
               </div>
               <div class="p-2 m-auto w-100 h-100">
                  <!-- Gráfico de Atendimentos por Mês -->
                  <div class="chart-card card p-3 shadow-sm" style="width: 700px;">
                     <div class="d-flex flex-row justify-content-around">
                        <h5 class="title text-center"><i class="fa-solid fa-chart-simple"></i> Pedidos por Mês 
                           <button id="backMes" class="btn btn-sm btn-outline-secondary ms-auto" style="display:none;"><span class="material-symbols-outlined">
                           filter_alt_off
                           </span></button>
                        </h5>
                     </div>
                     <div class="chart-wrapper">
                     <canvas id="mesChart"></canvas>                        
                     </div>

                  </div>
               </div>
               <div class="p-2 m-auto w-100 h-100">
                  <div class="chart-card card p-3 shadow-sm">
                     <h5 class="title text-center"><i class="fa-solid fa-chart-simple"></i> Top 5 Responsáveis</h5>
                     <div class="chart-wrapper">
                     <canvas id="rankingChart"></canvas>                        
                     </div>

                  </div>
               </div>
            </div>
            <div class="d-flex flex-row justify-content-around">
               <div class="p-2 m-auto w-100">
                  <div class="chart-card card p-3 shadow-sm">
                     <h5 class="title"><i class="fa-solid fa-chart-line"></i> Tendência Mensal</h5>
                     <canvas id="trendChart"></canvas>
                  </div>

               </div>
                <div class="p-2  m-auto w-100">
                  <div class="chart-card card p-3 shadow-sm">
                     <h5 class="title"><i class="fa-solid fa-hourglass-half"></i> Backlog (idade)</h5>
                     <div class="chart-wrapper">
                     <canvas id="backlogChart"></canvas>                        
                     </div>

                  </div>
            </div>
               </div>

               </div>
            </div>

         </div>
         </div>
         </div>
         <script>
            // captura as cores diretamente das info-box
            const infoPink       = getComputedStyle(document.querySelector('.bg-pink')).backgroundColor;
            const infoOrange     = getComputedStyle(document.querySelector('.bg-orange')).backgroundColor;
            const infoLightGreen = getComputedStyle(document.querySelector('.bg-light-green')).backgroundColor;
            const infoCyan       = getComputedStyle(document.querySelector('.bg-cyan')).backgroundColor;
            
            // array de cores para statusChart e mesChart
            const infoColors = [
            infoPink,       // Pendente
            infoOrange,     // Em andamento
            infoLightGreen, // Concluído
            infoCyan        // Rejeitado (você pode realocar se quiser outro mapeamento)
            ];
            
            // Ano atualmente selecionado (usado em todas as chamadas AJAX)
            const yearSelected = <?= json_encode($anoSelecionado) ?>;
            
            // -----------------------------
            // 1) Instância os 3 charts em variáveis
            // -----------------------------
            const statusCtx   = document.getElementById('statusChart').getContext('2d');
            const mesCtx      = document.getElementById('mesChart').getContext('2d');
            const rankingCtx  = document.getElementById('rankingChart').getContext('2d');
            
            const statusChart = new Chart(statusCtx, {
            type: 'doughnut',
            data: {
              labels: ['Pendente', 'Em andamento', 'Concluído', 'Rejeitado'],
              datasets: [{
                data: [<?= $pendentes ?>, <?= $emAndamento ?>, <?= $finalizados ?>, <?= $rejeitados ?>],
                backgroundColor: infoColors
              }]
            },
            options: {
              responsive: true,
              maintainAspectRatio: true,  
              plugins: {
                legend: { position: 'bottom' },
                datalabels: {
                  formatter: (v, ctx) => {
                    let sum = ctx.chart.data.datasets[0].data.reduce((a,b)=>a+b,0);
                    return (v*100/sum).toFixed(1)+'%';
                  },
                  color: '#fff', font:{weight:'bold',size:14}
                }
              },
              onClick: (evt, elems) => {
                if (!elems.length) return;
                const status = statusChart.data.labels[elems[0].index];
                // filtra mesChart
                mesChart.data.datasets.forEach(ds => ds.hidden = ds.label !== status);
                mesChart.update();
                // atualiza rankingChart via AJAX
                fetch(`?action=ranking_por_status&status=${encodeURIComponent(status)}&year=${yearSelected}`)
                  .then(r=>r.json()).then(json=>{
                    rankingChart.data.labels = json.labels;
                    rankingChart.data.datasets[0].data = json.data;
                    rankingChart.update();
                  });
              }
            },
            plugins: [ChartDataLabels]
            });
            
            const mesChart = new Chart(mesCtx, {
            type: 'bar',
            data: {
              labels: <?= json_encode($labelsMeses) ?>,
              datasets: [
                { label: 'Pendente',      data: <?= json_encode($dadosMesStatus['Pendente']) ?>,      backgroundColor: infoPink },
                { label: 'Em Andamento',  data: <?= json_encode($dadosMesStatus['Em Andamento']) ?>,  backgroundColor: infoOrange },
                { label: 'Concluído',     data: <?= json_encode($dadosMesStatus['Concluído']) ?>,     backgroundColor: infoLightGreen },
                { label: 'Rejeitado',     data: <?= json_encode($dadosMesStatus['Rejeitado']) ?>,     backgroundColor:'gray' }
              ]
            },
            options: {
              responsive: true,
              maintainAspectRatio: true,  
              plugins: { legend: { position: 'top' } },
              scales: { x:{stacked:true}, y:{stacked:true} },
              onClick: (evt, elems) => {
                if (!elems.length) return;
                const month = elems[0].index + 1;
                // detalha dia-a-dia
                fetch(`?action=drill_mes&year=${yearSelected}&month=${month}`)
                  .then(r=>r.json()).then(json=>{
                    mesChart.data.labels = json.labels;
                    mesChart.data.datasets = Object.entries(json.datasets).map(
                      ([label,data])=>({ label, data, backgroundColor: mesChart.data.datasets.find(ds=>ds.label===label).backgroundColor })
                    );
                    mesChart.options.scales.x.stacked = false;
                    mesChart.options.scales.y.stacked = false;
                    mesChart.update();
                    document.getElementById('backMes').style.display = 'inline-block';
                  });
              }
            }
            });
            
            // botão de voltar ao mês (drill-up)
            document.getElementById('backMes').addEventListener('click', ()=>{
            mesChart.data.labels = <?= json_encode($labelsMeses) ?>;
            mesChart.data.datasets.forEach(ds => {
              ds.data = <?= json_encode($dadosMesStatus) ?>[ds.label];
            });
            mesChart.options.scales.x.stacked = true;
            mesChart.options.scales.y.stacked = true;
            mesChart.update();
            document.getElementById('backMes').style.display = 'none';
            });
            
            const rankingChart = new Chart(rankingCtx, {
            type: 'bar',
            data: {
              labels: <?= json_encode($responsaveis) ?>,
              datasets: [{
                label: 'Problemas Reportados',
                data: <?= json_encode($totais) ?>,
                backgroundColor: infoCyan,
                borderColor: infoCyan, borderWidth:1
              }]
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,  
              plugins: { legend:{display:false} },
              scales: { y:{beginAtZero:true,precision:0} },
              onClick: (evt, elems) => {
                if (!elems.length) return;
                const resp = rankingChart.data.labels[elems[0].index];
                // pedidos por mês para esse responsável
                fetch(`?action=mes_por_responsavel&responsavel=${encodeURIComponent(resp)}&year=${yearSelected}`)
                  .then(r=>r.json()).then(json=>{
                    mesChart.data.datasets.forEach(ds => ds.data = json.data);
                    mesChart.options.scales.x.stacked = false;
                    mesChart.options.scales.y.stacked = false;
                    mesChart.update();
                  });
                // status para esse responsável
                fetch(`?action=ranking_por_status&status=${encodeURIComponent(resp)}&year=${yearSelected}`)
                  .then(r=>r.json()).then(json=>{
                    statusChart.data.labels = json.labels;
                    statusChart.data.datasets[0].data = json.data;
                    statusChart.update();
                  });
              }
            }
            });
            
            const trendCtx = document.getElementById('trendChart').getContext('2d');
            new Chart(trendCtx, {
            type: 'line',
            data: {
            labels: <?= json_encode($labelsTrend) ?>,
            datasets: [
              {
                label: 'Abertos',
                data: <?= json_encode($abertosMes) ?>,
                fill: false,
                borderWidth: 2,
                borderDash: []
              },
              {
                label: 'Fechados',
                data: <?= json_encode($fechadosMes) ?>,
                fill: false,
                borderWidth: 2,
                borderDash: [5,5]
              }
            ]
            },
            options: {
            responsive: true,
            scales: { y: { beginAtZero: true, precision: 0 } }
            }
            });
            
            const backlogCtx = document.getElementById('backlogChart').getContext('2d');
            new Chart(backlogCtx, {
            type: 'bar',
            data: {
            labels: <?= json_encode($labelsAge) ?>,
            datasets: [{
              label: 'Tickets Pendentes',
              data: <?= json_encode($dataAge) ?>,
              borderWidth: 1
            }]
            },
            options: {
            responsive: true,
            scales: { y: { beginAtZero: true, precision: 0 } }
            }
            });            
         </script>
      </main>
   </body>
</html>