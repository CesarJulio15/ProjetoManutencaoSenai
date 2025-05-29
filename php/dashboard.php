<?php
  session_start();
  if (!isset($_SESSION['tipo'])) {
      // redirecione ou trate o erro
      header("Location: index.php");
      exit();
  }
  $tipo = $_SESSION['tipo'];
    
    include 'conexao.php';
    // Função para buscar totais por status hoje (aberturas + conclusões)
    function buscarTotaisHoje($conn) {
      $hoje = date('Y-m-d');
      $totaisHoje = ["Pendente"=>0,"Em Andamento"=>0,"Concluído"=>0];
      $sql = "
        SELECT status, COUNT(*) AS total
            FROM manutencao
            WHERE (status = 'Concluído'   AND data_encerramento = ?)
            OR (status <> 'Concluído'  AND data_abertura      = ?)
            GROUP BY status
        ";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("ss", $hoje, $hoje);
      $stmt->execute();
      $res = $stmt->get_result();
      while ($row = $res->fetch_assoc()) {
        $totaisHoje[$row['status']] = (int)$row['total'];
      }
      $stmt->close();
      return $totaisHoje;
    }
    // Função para obter totais por status
    function obterTotaisPorStatus($conn) {
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
      return [$pendentes, $emAndamento, $finalizados, $rejeitados];
    }
    // Função para obter ranking de responsáveis
    function obterRankingResponsaveis($conn, $limite = 5) {
          $sql = "
            SELECT d.nome AS responsavel_nome, COUNT(*) as total
            FROM manutencao m
            JOIN docentes d ON m.responsavel = d.id
            GROUP BY d.nome
            ORDER BY total DESC
            LIMIT ?
          ";
          $stmt = $conn->prepare($sql);
          $stmt->bind_param('i', $limite);
          $stmt->execute();
          $res = $stmt->get_result();
    
          $responsaveis = [];
          $totais = [];
          while ($r = $res->fetch_assoc()) {
            $responsaveis[] = $r['responsavel_nome'];
            $totais[] = $r['total'];
          }
          $stmt->close();
          return [$responsaveis, $totais];
        }
        
    // Função para calcular a média de dias de conclusão
    function calcularMediaDiasConclusao($conn) {
          $sqlMedia = "
            SELECT AVG(
              GREATEST(
                DATEDIFF(data_encerramento, data_abertura),
                0
              )
            ) AS media_dias
            FROM manutencao
            WHERE status = 'Concluído'
              AND data_encerramento IS NOT NULL
          ";
          $resultMedia = $conn->query($sqlMedia);
    
          $mediaDias = 0;
          if ($row = $resultMedia->fetch_assoc()) {
            $mediaBruta = $row['media_dias'];
            $mediaDias = $mediaBruta !== null
              ? round($mediaBruta, 1)
              : 0;
          }
          return $mediaDias;
        }
    
    // Função para buscar dados por mês e status (visão mensal)
    function buscarDadosMesStatus($conn, $anoSelecionado) {
      $dadosMesStatus = [
        'Pendente'      => array_fill(0, 12, 0),
        'Em Andamento'  => array_fill(0, 12, 0),
        'Concluído'     => array_fill(0, 12, 0),
        'Rejeitado'     => array_fill(0, 12, 0),
      ];
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
        if (isset($dadosMesStatus[$row['status']])) {
          $dadosMesStatus[$row['status']][$idx] = (int)$row['total'];
        }
      }
      $stmtMes->close();
      return $dadosMesStatus;
    }
    // Função para buscar aberturas agrupadas por mês para um ano específico
    function buscarAberturasPorMes($conn, $ano) {
      $resultados = array_fill(0, 12, 0);
      $sql = "
        SELECT MONTH(data_abertura) AS mes, COUNT(*) AS cnt
        FROM manutencao
        WHERE YEAR(data_abertura) = ?
        GROUP BY mes
      ";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param('i', $ano);
      $stmt->execute();
      $res = $stmt->get_result();
      while ($row = $res->fetch_assoc()) {
        $idx = (int)$row['mes'] - 1;
        $resultados[$idx] = (int)$row['cnt'];
      }
      $stmt->close();
      return $resultados;
    }
    // Função para buscar fechamentos agrupados por mês para um ano específico
    function buscarFechamentosPorMes($conn, $ano) {
      $resultados = array_fill(0, 12, 0);
      $sql = "
      SELECT MONTH(data_encerramento) AS mes, COUNT(*) AS cnt
      FROM manutencao
      WHERE status = 'Concluído'
        AND data_encerramento IS NOT NULL
        AND YEAR(data_encerramento) = ?
      GROUP BY mes
      ";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param('i', $ano);
      $stmt->execute();
      $res = $stmt->get_result();
      while ($row = $res->fetch_assoc()) {
      $idx = (int)$row['mes'] - 1;
      $resultados[$idx] = (int)$row['cnt'];
      }
      $stmt->close();
      return $resultados;
    }
    // Função para calcular SLA (% concluído em <= 3 dias)
    function calcularSLA($conn) {
      $sqlSLA = "
      SELECT COUNT(*) as total,
           SUM(CASE WHEN DATEDIFF(data_encerramento, data_abertura) <= 3 THEN 1 ELSE 0 END) as dentro_prazo
      FROM manutencao
      WHERE status = 'Concluído' AND data_encerramento IS NOT NULL
      ";
      $resSLA = $conn->query($sqlSLA);
      $rowSLA = $resSLA->fetch_assoc();
      $sla = $rowSLA['total'] > 0 ? round(($rowSLA['dentro_prazo'] / $rowSLA['total']) * 100, 2) : 0;
      return $sla;
    }
    // Função para buscar tickets por atendente (executante) com paginação
    function buscarAtendentes($conn, $pagina = 1, $limite = 8) {
      $offset = ($pagina - 1) * $limite;
      $totalRegistros = $conn->query("SELECT COUNT(DISTINCT d.id) AS total FROM docentes d WHERE d.tipo = 'Técnico'")->fetch_assoc()['total'];
      $totalPaginas = ceil($totalRegistros / $limite);

      $sqlAtendentes = "
      SELECT d.nome,
        SUM(CASE WHEN m.status = 'Pendente' THEN 1 ELSE 0 END) AS pendente,
        SUM(CASE WHEN m.status = 'Em Andamento' THEN 1 ELSE 0 END) AS andamento,
        SUM(CASE WHEN m.status = 'Concluído' THEN 1 ELSE 0 END) AS concluido
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
      return [$atendentes, $totalPaginas];
    }
    // --- Backlog e envelhecimento ---
    function calcularBacklogAge($conn) {
      $labelsAge = ['0–3 dias','4–7 dias','>7 dias'];
      $dataAge   = array_fill(0, count($labelsAge), 0);

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
      return [$labelsAge, $dataAge];
    }
    // Função para calcular SLA por prioridade (% concluído em ≤3 dias)
    function calcularSlaPorPrioridade($conn, $prioridades) {
      // Monta a lista de prioridades para o IN
      $prioridadesEscapadas = array_map([$conn, 'real_escape_string'], $prioridades);
      $prioridadesIn = "'" . implode("','", $prioridadesEscapadas) . "'";

      $sqlSLAprioridade = "
        SELECT
          prioridade,
          SUM(CASE WHEN DATEDIFF(data_encerramento, data_abertura) <= 3 THEN 1 ELSE 0 END) AS dentro_prazo,
          COUNT(*) AS total
        FROM manutencao
        WHERE prioridade IN ($prioridadesIn)
          AND status = 'Concluído'
        GROUP BY prioridade
      ";
      $resSLAprioridade = $conn->query($sqlSLAprioridade);

      // Inicializa com zeros
      $slaPorPrioridade = array_fill(0, count($prioridades), 0);
      while ($row = $resSLAprioridade->fetch_assoc()) {
        $p = $row['prioridade'];
        $idx = array_search($p, $prioridades);
        if ($idx !== false && $row['total'] > 0) {
          $slaPorPrioridade[$idx] = round(($row['dentro_prazo'] / $row['total']) * 100, 2);
        }
      }
      return $slaPorPrioridade;
    }
    // Função para calcular médias de horas e totais por prioridade para o Cross-Trend
    function calcularCrossTrend($conn, $prioridades) {
      // Monta a lista de prioridades para o IN
      $prioridadesEscapadas = array_map([$conn, 'real_escape_string'], $prioridades);
      $prioridadesIn = "'" . implode("','", $prioridadesEscapadas) . "'";
      $sqlCross = "
        SELECT
        prioridade,
        AVG(TIMESTAMPDIFF(HOUR, data_abertura, data_encerramento)) AS media_h,
        COUNT(*) AS tot
        FROM manutencao
        WHERE prioridade IN ($prioridadesIn)
        AND status = 'Concluído'
        GROUP BY prioridade
      ";
      $resCross = $conn->query($sqlCross);
      // Inicializa com zeros
      $mediaHoras = [];
      $totaisCross = [];
      foreach ($prioridades as $p) {
        $mediaHoras[$p] = 0;
        $totaisCross[$p] = 0;
      }
      while ($row = $resCross->fetch_assoc()) {
        $p = $row['prioridade'];
        $mediaHoras[$p] = round((float)$row['media_h'], 1);
        $totaisCross[$p] = (int)$row['tot'];
      }
      // Garante a ordem correta
      $mediaHoras = array_values(array_replace(array_fill(0, count($prioridades), 0), array_intersect_key($mediaHoras, array_flip($prioridades))));
      $totaisCross = array_values(array_replace(array_fill(0, count($prioridades), 0), array_intersect_key($totaisCross, array_flip($prioridades))));
      return [$mediaHoras, $totaisCross];
    }
    // Função para obter pedidos por mês para um responsável e ano
    function obterPedidosPorMesResponsavel($conn, $responsavel, $ano) {
          $stmt = $conn->prepare("
            SELECT MONTH(data_abertura) - 1 AS idx, COUNT(*) AS total
            FROM manutencao m
            JOIN docentes d ON m.responsavel = d.id
            WHERE d.nome = ? 
             AND YEAR(m.data_abertura) = ?
            GROUP BY idx
          ");
          $stmt->bind_param('si', $responsavel, $ano);
          $stmt->execute();
          $res = $stmt->get_result();

          // preenche zeros para todos os 12 meses
          $arr = array_fill(0, 12, 0);
          while ($r = $res->fetch_assoc()) {
            $arr[(int) $r['idx']] = (int) $r['total'];
          }
          $stmt->close();
          return $arr;
    }
    // Função para obter ranking de responsáveis por status e ano
    function obterRankingPorStatus($conn, $status, $ano) {
          $stmt = $conn->prepare("
            SELECT d.nome AS label, COUNT(*) AS value
            FROM manutencao m
            JOIN docentes d ON m.responsavel = d.id
            WHERE m.status = ? 
              AND YEAR(m.data_abertura) = ?
            GROUP BY d.nome
            ORDER BY value DESC
            LIMIT 5
          ");
          $stmt->bind_param('si', $status, $ano);
          $stmt->execute();
          $res = $stmt->get_result();

          $labels = [];
          $data   = [];
          while ($r = $res->fetch_assoc()) {
            $labels[] = $r['label'];
            $data[]   = (int) $r['value'];
          }
          $stmt->close();
          return ['labels' => $labels, 'data' => $data];
    }
    // Função para buscar detalhamento diário por status em um mês/ano
    function buscarDetalhePorDia($conn, $ano, $mes) {
      $stmt = $conn->prepare("
            SELECT DAY(data_abertura) AS period, status, COUNT(*) AS total
            FROM manutencao
            WHERE YEAR(data_abertura) = ?
             AND MONTH(data_abertura) = ?
            GROUP BY period, status
            ORDER BY period
      ");
          $stmt->bind_param('ii', $ano, $mes);
          $stmt->execute();
          $res = $stmt->get_result();

          $statusList  = ['Pendente','Em Andamento','Concluído','Rejeitado'];
          $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $mes, $ano);
          $labels      = range(1, $daysInMonth);
          $datasets    = [];

          // inicializa todos os status com zeros
          foreach ($statusList as $s) {
            $datasets[$s] = array_fill(0, $daysInMonth, 0);
          }

          while ($row = $res->fetch_assoc()) {
            $idx = (int) $row['period'] - 1;
            $datasets[$row['status']][$idx] = (int) $row['total'];
          }
          $stmt->close();

          return ['labels' => $labels, 'datasets' => $datasets];
    }
    
    // =====================
    // ENDPOINTS AJAX PARA DRILL-DOWN/UP
    // =====================
    if (isset($_GET['action'])) {
      header('Content-Type: application/json');
      $action      = $_GET['action'];
      // define o ano a partir do parâmetro ou do ano corrente
      $yearSelected = isset($_GET['year'])
                     ? (int) $_GET['year']
                     : (int) date('Y');
    
      if ($action === 'ranking_por_status') {
         $status = $_GET['status'];
         $result = obterRankingPorStatus($conn, $status, $yearSelected);
         echo json_encode($result);
         exit;
      }
      if ($action === 'mes_por_responsavel') {
         $resp = $_GET['responsavel'];
         $data = obterPedidosPorMesResponsavel($conn, $resp, $yearSelected);
         echo json_encode(['data' => $data]);
         exit;
      }
      if ($action === 'drill_mes') {
         $ano  = isset($_GET['year'])  ? (int) $_GET['year']  : (int) date('Y');
         $mes = isset($_GET['month']) ? (int) $_GET['month'] : (int) date('n');
         $resultado = buscarDetalhePorDia($conn, $ano, $mes);
         echo json_encode($resultado);
         exit;
      }
    
      echo json_encode(['error' => 'Ação inválida']);
      exit;
    }

    // Uso das funções agrupadas
    list($pendentes, $emAndamento, $finalizados, $rejeitados) = obterTotaisPorStatus($conn);
    list($responsaveis, $totais) = obterRankingResponsaveis($conn);
    $mediaDias = calcularMediaDiasConclusao($conn);
    $labelsMeses = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
    $anoSelecionado = (int)($_GET['ano'] ?? date('Y'));
    $dadosMesStatus = buscarDadosMesStatus($conn, $anoSelecionado);

    $totaisHoje = buscarTotaisHoje($conn);
    $totalHoje = array_sum($totaisHoje);

    $sla = calcularSLA($conn);

    $pagina = isset($_GET['pagina_atendentes']) ? (int)$_GET['pagina_atendentes'] : 1;
    list($atendentes, $totalPaginas) = buscarAtendentes($conn, $pagina);

    // --- Tendência mensal de aberturas vs. fechamentos (sem consultas no loop) ---
    $labelsTrend  = [];
    for ($m = 1; $m <= 12; $m++) {
      $labelsTrend[] = sprintf('%02d', $m);
    }
    $abertosMes   = buscarAberturasPorMes($conn, $anoSelecionado);

    // Busca fechamentos do ano selecionado agrupadas por mês
    $fechadosMes = buscarFechamentosPorMes($conn, $anoSelecionado);

    list($labelsAge, $dataAge) = calcularBacklogAge($conn);

    // array de prioridades
    $prioridades = ['Alta','Média','Baixa'];
    // SLA por prioridade (% concluído em ≤3 dias)
    $slaPorPrioridade = calcularSlaPorPrioridade($conn, $prioridades);

    // para o comparativo ano a ano (sem consultas dentro do loop)
    $meses = range(1,12);
    $anoAtual   = (int) date('Y');
    $anoAnterior = $anoAtual - 1;
    // Busca aberturas do ano anterior agrupadas por mês
    $anterior = buscarAberturasPorMes($conn, $anoAnterior);
    $abertosMes   = buscarAberturasPorMes($conn, $anoAtual);
    $fechadosMes  = buscarAberturasPorMes($conn, $anoAnterior);

    // Uso da função
    list($mediaHoras, $totaisCross) = calcularCrossTrend($conn, $prioridades);
    ?>
<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <title>Dashboard Gerencial</title>
        <link rel="stylesheet" href="../css/padrao.css">
        <link rel="stylesheet" href="../css/dashboard.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css" integrity="sha512-MV7K8+y+gLIBoVD59lQIYicR65iaqukzvf/nwasF0nqhPay5w/9lJmVM2hMDcnK1OnMGCdVK+iQrJ7lzPJQd1w==" crossorigin="anonymous" referrerpolicy="no-referrer" />
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js" integrity="sha512-CQBWl4fJHWbryGE+Pc7UAxWMUMNMWzWxF4SQo9CgkJIN1kx6djDQZjh3Y8SZ1d+6I+1zze6Z7kHXO7q3UyZAWw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    </head>
    <body class="d-flex flex-md-row w-100 h-100">
        <aside class="sidebar">
            <img src="../img/LogoPortalSenai.svg" alt="Portal Senai Manutenções" class="logo-senai">
            <div class="spacer"></div>
            <img src="../img/TechFix.svg" alt="Techfix Facilities" class="logo-techfix">
        </aside>
        <main class="main-content d-flex flex-md-column w-100 h-100">
            <header class="top-bar mt-2 animate__animated animate__fadeInDown">
                <a href="panelTecnico.php" class="botao-voltar" title="Voltar"><i class="fa-solid fa-arrow-left"></i></a>
                <h2 class="title text-center mb-4">Painel de Indicadores de Manutenção</h2>
                <div class="welcome-msg me-3 text-dark fw-bold">
                  <a href="editar_funcionario.php?id=<?= $_SESSION['id'] ?>" style="text-decoration: none; color: #212529;" title="Editar meus dados" class="username">
                      <?= htmlspecialchars($_SESSION['nome']) ?>
                  </a><br>
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
            <div class="dashboard-container d-flex flex-wrap flex-md-column justify-content-evenly gap-4 mt-3 w-100">
                <div class="dashboard-group d-flex flex-row justify-content-evenly align-items-start align-top gap-4 w-100" id="topo">
                    <div class="card p-3 m-auto shadow-sm w-25 h-75 animate__animated animate__fadeInLeft" style="flex:1; min-width:300px"  id="status-hoje">
                        <h5 class="title"><i class="fa-solid fa-list-ul"></i> Status de Hoje</h5>
                        <div class="box ">
                            <ul class="list-group ">
                                <li class="list-group-item "><b>Pendente:</b> <?= $totaisHoje['Pendente'] ?></li>
                                <li class="list-group-item"><b>Em Andamento:</b> <?= $totaisHoje['Em Andamento'] ?></li>
                                <li class="list-group-item"><b>Concluído:</b> <?= $totaisHoje['Concluído'] ?></li>
                                <li class="list-group-item fw-bold">Total: <?= $totalHoje ?></li>
                                <li class="list-group-item text-success">SLA (<= 3 dias): <?= $sla ?>%</li>
                            </ul>
                        </div>
                    </div>
                    <div class="card p-3 m-auto shadow-sm h-100 d-flex flex-column" id="tecnicos">
                        <h5 class="title"><i class="fa-solid fa-ticket"></i> Tickets por Atendente</h5>
                        <div class="table-wrapper" style="overflow-x:auto;">
                                <table class="" style="border-radius:15px 15px 0 0; ">
                                    <thead>
                                        <tr>
                                            <th>Nome</th>
                                            <th class="status-pendente">Pendente</th>
                                            <th class="status-andamento">Andamento</th>
                                            <th class="status-concluido">Concluído</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($atendentes as $a): ?>
                                        <tr>
                                            <td class="nomeAtendente"><?= htmlspecialchars($a['nome']) ?></td>
                                            <td data-label="Pendentes" class="status-pendente"><b><?= $a['pendente'] ?></b></td>
                                            <td data-label="Em Andamento" class="status-andamento"><?= $a['andamento'] ?></td>
                                            <td data-label="Concluídos" class="status-concluido"><?= $a['concluido'] ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <nav class="mt-2">
                                    <ul class="pagination paginacao justify-content-center">
                                        <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                                        <li class="page-item <?= $i === $pagina ? 'active' : '' ?>">
                                            <a class="page-link" href="?pagina_atendentes=<?= $i ?>#tickets-atendentes"><?= $i ?></a>
                                        </li>
                                        <?php endfor; ?>
                                    </ul>
                                </nav>
                          </div>
                    </div>
                    <div class="dashboard-cards p-3 m-auto w-50 animate__animated animate__fadeInRight" id="cards-metricas">
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
                <div class="dashboard-group d-flex flex-row justify-content-evenly align-items-start gap-4 w-100 flex-wrap">
                    <!-- Gráfico de Pizza (à esquerda) -->
                    <div class="p-2 m-auto animate__animated animate__fadeInUp">
                        <div class="chart-container card p-3 shadow-sm" style="height: 420px;">
                            <h5 class="title text-center"><i class="fa-solid fa-chart-pie"></i> Distribuição por Status</h5>
                            <canvas id="statusChart"></canvas>
                        </div>
                    </div>
                    <div class="p-2 m-auto animate__animated animate__fadeInUp">
                        <!-- Gráfico de Atendimentos por Mês -->
                        <div class="chart-container card p-3 shadow-sm" style="width: 700px;">
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
                </div>
                <div class="dashboard-group d-flex flex-row justify-content-evenly align-items-start gap-4 w-100 flex-wrap" >
                    <!-- Gráfico de Pizza (à esquerda) -->
                    <div class="p-2 m-auto animate__animated animate__fadeInUp">
                        <div class="chart-container card p-3 shadow-sm" style="height: 420px;">
                            <h5 class="title text-center">
                              <span class="material-symbols-outlined">pie_chart</span> 
                              Pedidos por bloco</h5>
                            <canvas id="graficoSetor"></canvas>
                        </div>
                    </div>
                    <div class="p-2 m-auto animate__animated animate__fadeInUp">
                        <div class="chart-container card p-3 shadow-sm" style="width: 700px;">
                            <h5 class="title"><i class="fa-solid fa-chart-line"></i> Tendência Mensal</h5>
                            <canvas id="trendChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="dashboard-group d-flex flex-row justify-content-evenly align-items-start gap-4 w-100 flex-wrap">
                <div class="p-2 m-auto animate__animated animate__fadeInUp">
                        <div class="chart-container card p-3 shadow-sm" style="width: 700px;">
                            <h5 class="title text-center"><i class="fa-solid fa-chart-simple"></i> Top 5 Responsáveis</h5>
                            <div class="chart-wrapper">
                                <canvas id="rankingChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="p-2 m-auto animate__animated animate__fadeInUp">
                        <div class="chart-container card p-3 shadow-sm" style="width: 700px;">
                            <h5 class="title"><i class="fa-solid fa-hourglass-half"></i> Backlog (idade)</h5>
                            <div class="chart-wrapper">
                                <canvas id="backlogChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="p-2 m-auto animate__animated animate__fadeInUp">
                        <div class="chart-container card p-3 shadow-sm" style="width: 700px;">
                            <h5 class="title">
                                <span class="material-symbols-outlined">release_alert</span> 
                                Compliance por Prioridade
                            </h5>
                            <div class="chart-wrapper w-100">
                                <canvas id="chartSLA"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="dashboard-group d-flex flex-row justify-content-evenly align-items-start gap-4 w-100 flex-wrap">
                    <div class="p-2  m-auto animate__animated animate__fadeInUp">
                        <div class="chart-container card p-3 shadow-sm">
                            <h5 class="title">
                                <span class="material-symbols-outlined">date_range</span> 
                                Comparação anual
                            </h5>
                            <div class="chart-wrapper">
                                <canvas id="chartYoY"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="p-2  m-auto animate__animated animate__fadeInUp">
                        <div class="chart-container card p-3 shadow-sm">
                            <h5 class="title"><i class="fa-solid fa-hourglass-half"></i> Tempo Médio de Resolução × Nº de Chamados por Prioridade</h5>
                            <div class="chart-wrapper">
                                <canvas id="chartCross"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            </div>
            </div>
            </div>
            </div>
            <?php if ($_SESSION['tipo'] == 'coordenador'): ?>
            <a href="listar_funcionarios.php" class="" title="Visualizar funcionários">
        <img src="../img/gerenciar-grupo.png" alt="add-user" class="add-user-img animate__animated animate__fadeInUp">
    </a>
            <a href="export_data.php" class="" title="Exportar dados">
            <img src="../img/exportar-arquivo.png" alt="add-user" class="add-dash-img animate__animated animate__fadeInUp">
            </a>
            <a href="solicita.php" class="" title="Abrir ordem de serviço">
        <img src="../img/documento.png" alt="Adicionar" class="add-button-img animate__animated animate__fadeInUp">
    </a>
    <?php else :?>
            <a href="solicita.php" class="">
        <img src="../img/documento.png" alt="Adicionar" class="add-button-img animate__animated animate__fadeInUp">
    </a>

  <?php endif; ?>            
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
                    borderColor: infoCyan,
                    borderWidth: 1
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
                maintainAspectRatio: true,  
                scales: { y: { beginAtZero: true, precision: 0 } }
                }
                });  
                
                                // 1) SLA Compliance por Prioridade
                                new Chart(
                  document.getElementById('chartSLA').getContext('2d'),
                  {
                    type: 'bar',
                    data: {
                      labels: <?= json_encode($prioridades) ?>,
                      datasets: [{
                        label: '% Compliance SLA',
                        data: <?= json_encode($slaPorPrioridade) ?>,
                      }]
                    },
                    options: {
                      plugins: {
                        title: {
                          display: true,
                          text: 'SLA Compliance por Prioridade'
                        }
                      },
                      scales: {
                        y: {
                          beginAtZero: true,
                          max: 100,
                          ticks: {
                            callback: v => v + '%'
                          }
                        }
                      }
                    }
                  }
                );
                
                // 2) Comparativo Ano a Ano (Aberturas)
                new Chart(
                  document.getElementById('chartYoY').getContext('2d'),
                  {
                    type: 'line',
                    data: {
                      labels: <?= json_encode(array_map(fn($m)=>sprintf('%02d',$m), $meses)) ?>,
                      datasets: [
                        {
                          label: '<?= $anoAtual ?>',
                          data: <?= json_encode($abertosMes) ?>,
                          fill: false
                        },
                        {
                          label: '<?= $anoAnterior ?>',
                          data: <?= json_encode($fechadosMes) ?>,
                          fill: false
                        }
                      ]
                    },
                    options: {
                      plugins: {
                        title: {
                          display: true,
                          text: 'Comparativo Ano a Ano (aberturas)'
                        }
                      },
                      scales: {
                        y: { beginAtZero: true }
                      }
                    }
                  }
                );
                
                // 3) Cross-Trend: Tempo Médio × Volume de Chamados
                new Chart(
                  document.getElementById('chartCross').getContext('2d'),
                  {
                    type: 'bar',
                    data: {
                      labels: <?= json_encode($prioridades) ?>,
                      datasets: [
                        {
                          type: 'line',
                          label: 'Tempo Médio (h)',
                          yAxisID: 'A',
                          data: <?= json_encode($mediaHoras) ?>,
                        },
                        {
                          type: 'bar',
                          label: 'Total de Chamados',
                          yAxisID: 'B',
                          data: <?= json_encode($totais) ?>,
                        }
                      ]
                    },
                    options: {
                      plugins: {
                        title: {
                          display: true,
                          text: 'Resolução (h) × Volume de Chamados'
                        }
                      },
                      scales: {
                        A: {
                          type: 'linear',
                          position: 'left',
                          title: { display: true, text: 'Horas' }
                        },
                        B: {
                          type: 'linear',
                          position: 'right',
                          title: { display: true, text: 'Chamados' },
                          grid: { drawOnChartArea: false }
                        }
                      }
                    }
                  }
                );
            </script>
  <script>
      fetch('dados_por_setor.php')
        .then(res => res.json())
        .then(data => {
          const labels = data.map(item => item.bloco);
          const valores = data.map(item => item.total);
          const total = valores.reduce((a, b) => a + b, 0);

          const cores = [
            '#000000', // Preto
        '#e6b5aa', // Rosa claro
        '#004080', // Azul escuro
        '#007080', // Ciano escuro
        '#30a0a0', // Ciano médio
        '#38a850', // Verde claro
        '#70b080', // Verde médio
        '#a0b870', // Verde oliva
        '#f0e850', // Amarelo claro
        '#f8a800', // Laranja
        '#b01030', // Vermelho escuro
        '#8c003c'  // Magenta escuro
      ];

          new Chart(document.getElementById('graficoSetor'), {
            type: 'doughnut',
            data: {
              labels: labels,
              datasets: [{
                data: valores,
                backgroundColor: cores.slice(0, valores.length),
                borderWidth: 0
              }]
            },
            options: {
              responsive: true,
              rotation: -90,
              circumference: 180,
              cutout: '55%',
              plugins: {
                legend: {
                  display: false
                },
                tooltip: {
                  enabled: false
                },
                datalabels: {
                  color: '#fff',
                  font: {
                    weight: 'bold',
                    size: 14
                  },
                  formatter: function(value, ctx) {
                    const label = ctx.chart.data.labels[ctx.dataIndex];
                    const percent = ((value / total) * 100).toFixed(1) + '%';
                    return `${label}\n${percent}`;
                  }
                }
              }
            },
            plugins: [ChartDataLabels]
          });
        });
  </script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/js/fontawesome.min.js" integrity="sha512-j12pXc2gXZL/JZw5Mhi6LC7lkiXL0e2h+9ZWpqhniz0DkDrO01VNlBrG3LkPBn6DgG2b8CDjzJT+lxfocsS1Vw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        </main>

    </body>
</html>