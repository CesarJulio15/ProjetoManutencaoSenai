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

$labelsMeses = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
$dadosMesStatus = [
    'Pendente' => array_fill(0, 12, 0),
    'Em Andamento' => array_fill(0, 12, 0),
    'Concluído' => array_fill(0, 12, 0),
    'Rejeitado' => array_fill(0, 12, 0),
];

$sql = "
    SELECT MONTH(data_abertura) AS mes, status, COUNT(*) AS total
    FROM manutencao
    WHERE data_abertura IS NOT NULL
    GROUP BY mes, status
";

$res = $conn->query($sql);
while ($row = $res->fetch_assoc()) {
    $indiceMes = (int)$row['mes'] - 1;
    $status = $row['status'];
    $dadosMesStatus[$status][$indiceMes] = (int)$row['total'];
}

$anoSelecionado = isset($_GET['ano']) ? (int)$_GET['ano'] : date('Y');

$sql = "
    SELECT MONTH(data_abertura) AS mes, status, COUNT(*) AS total
    FROM manutencao
    WHERE data_abertura IS NOT NULL AND YEAR(data_abertura) = $anoSelecionado
    GROUP BY mes, status
";

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
.botao-voltar {
  display: inline-flex;
  width: fit-content;
  height: fit-content;
  padding: 10px;
  align-content: center;
  justify-content: space-evenly;
  align-items: center;
  color: white;
  font-size: larger;
  text-align: center;
  text-decoration: none;
  border: none;
  border-radius: 100%;

  cursor: pointer;
}
.btn-confirma,
.botao-voltar {
  width: 48px;
  height: 48px;
}
.botao-voltar {
  background: #f24848;
}
.botao-voltar:hover {
  background-color: #751818;
}

.box {
  /*width: 300px; */
  border-bottom: 20px solid #03a9f4;
  border-bottom-left-radius: 10px;
  border-bottom-right-radius: 10px;
}
.box h2 {
  color: #fff;
  background: #03a9f4;
  padding: 10px 20px;
  font-size: 20px;
  font-weight: 700;
  border-top-left-radius: 10px;
  border-top-right-radius: 10px;
}
.box ul {
  position: relative;
  background: #fff;
}
.box ul:hover li {
  opacity: 0.2;
}
.box ul li {
  list-style: none;
  padding: 10px;
  width: 100%;
  background: #fff;
  box-shadow: 0 5px 25px rgba(0, 0, 0, 0.1);
  transition: transform 0.5s;
}
.box ul li:hover {
  transform: scale(1.1);
  z-index: 5;
  background: #25bcff;
  box-shadow: 0 5px 25px rgba(0, 0, 0, 0.2);
  color: #fff;
  opacity: 1;
}
.box ul li span {
  width: 20px;
  height: 20px;
  text-align: center;
  line-height: 20px;
  background: #25bcff;
  color: #fff;
  display: inline-block;
  border-radius: 50%;
  margin-right: 10px;
  font-size: 12px;
  font-weight: 600;
  transform: translateY(-2px);
}
.box ul li:hover span {
  background: #fff;
  color: #25bcff;
}

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
            <a href="index.php" class="btn btn-secondary botao-voltar"><i class="fa-solid fa-arrow-left"></i></a>
            <h2 class="text-center mb-4">Painel de Indicadores de Manutenção</h2>
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

    <div class="dashboard-container d-flex flex-wrap flex-column justify-content-between gap-4">
      <div class="cards-container d-flex flex-wrap flex-column justify-content-between mt-3 gap-4"  style="flex: 1 1 150px; max-height: 160px;" id="cards-container">
        <div class="card-metric bg-pendente text-center">
            <h4>Pendentes</h4>
            <h2><?= $pendentes ?></h2>
        </div>
        <div class="card-metric bg-andamento text-center">
            <h4>Em Andamento</h4>
            <h2><?= $emAndamento ?></h2>
        </div>
        <div class="card-metric bg-finalizado text-center">
            <h4>Finalizados</h4>
            <h2><?= $finalizados ?></h2>
        </div>
        <div class="card-metric bg-rejeitado text-center">
            <h4>Rejeitados</h4>
            <h2><?= $rejeitados ?></h2>
        </div>
      </div>
      <div class="d-flex flex-row justify-content-between gap-4">
  <div class="card p-3 shadow-sm" style="flex:1; min-width:300px">
    <h5 class="title"><i class="fa-solid fa-list-ul"></i> Status de Hoje</h5>
    <div class="box">
    <ul class="list-group">
      <li class="list-group-item">Pendente: <?= $totaisHoje['Pendente'] ?></li>
      <li class="list-group-item">Em Andamento: <?= $totaisHoje['Em Andamento'] ?></li>
      <li class="list-group-item">Concluído: <?= $totaisHoje['Concluido'] ?></li>
      <li class="list-group-item fw-bold">Total: <?= $totalHoje ?></li>
      <li class="list-group-item text-success">SLA (<= 3 dias): <?= $sla ?>%</li>
    </ul>        
    </div>

  </div>

  <div class="card p-3 shadow-sm" style="flex:2; min-width:400px">
  <h5 class="title"><i class="fa-solid fa-ticket"></i> Tickets por Atendente</h5>
  <table>
    <thead><tr><th>Nome</th><th>Pendente</th><th>Andamento</th><th>Concluído</th></tr></thead>
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

          <!-- Coluna central com tempo médio -->
                  <div class="card-metric bg-secondary text-center " style="max-width:300px; height: 160px;">
              <h4>Tempo Médio de Conclusão</h4>
              <h2><?= $mediaDias ?> dias</h2>
          </div> 

      </div>
      <div class="d-flex flex-row justify-content-around">
  <div class="p-2">            
    <!-- Gráfico de Pizza (à esquerda) -->
          <div class="chart-container card p-3 shadow-sm" style="height: 400px;">
            <h5 class="title text-center"><i class="fa-solid fa-chart-pie"></i> Distribuição por Status</h5>
              <canvas id="statusChart"></canvas>
          </div>
        </div>
  <div class="p-2">
                                    <!-- Gráfico de Atendimentos por Mês -->
                  <div class="card p-3 shadow-sm" style="width: 700px;">
                    <div class="d-flex flex-row justify-content-around">
          <h5 class="title text-center"><i class="fa-solid fa-chart-simple"></i> Pedidos por Mês</h5>           
                    </div>

          
          <canvas id="mesChart"></canvas>
      </div>
        </div>
  <div class="p-2"> 

           <div class="card p-3 shadow-sm w-100">
            <h5 class="title text-center"><i class="fa-solid fa-chart-simple"></i> Top 5 Responsáveis</h5>
            <canvas id="rankingChart"></canvas>
        </div></div>
</div>

        </div>
  </div>

    </div>


    <script>
new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
        labels: ['Pendente', 'Em andamento', 'Finalizado', 'Rejeitado'],
        datasets: [{
            data: [<?= $pendentes ?>, <?= $emAndamento ?>, <?= $finalizados ?>, <?= $rejeitados ?>],
            backgroundColor: ['#f39c12', '#3498db', '#2ecc71', '#e74c3c']
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom' },
            datalabels: {
                formatter: (value, ctx) => {
                    let sum = ctx.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                    let percentage = (value * 100 / sum).toFixed(1) + "%";
                    return percentage;
                },
                color: '#fff',
                font: {
                    weight: 'bold',
                    size: 14
                }
            }
        }
    },
    plugins: [ChartDataLabels]
});


new Chart(document.getElementById('rankingChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($responsaveis) ?>,
        datasets: [{
            label: 'Problemas Reportados',
            data: <?= json_encode($totais) ?>,
            backgroundColor: 'rgba(155, 89, 182, 0.5)',
            borderColor: 'rgba(142, 68, 173, 1)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            title: { display: false }
        },
        scales: {
            y: {
                beginAtZero: true,
                precision: 0
            }
        }
    }
});


new Chart(document.getElementById('mesChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($labelsMeses) ?>,
        datasets: [
            {
                label: 'Pendente',
                backgroundColor: '#f39c12',
                data: <?= json_encode($dadosMesStatus['Pendente']) ?>
            },
            {
                label: 'Em Andamento',
                backgroundColor: '#3498db',
                data: <?= json_encode($dadosMesStatus['Em Andamento']) ?>
            },
            {
                label: 'Concluído',
                backgroundColor: '#2ecc71',
                data: <?= json_encode($dadosMesStatus['Concluído']) ?>
            },
            {
                label: 'Rejeitado',
                backgroundColor: '#e74c3c',
                data: <?= json_encode($dadosMesStatus['Rejeitado']) ?>
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'top' },
            title: { display: false }
        },
        scales: {
            x: { stacked: true },
            y: { stacked: true }
        }
    }
});

    </script>
</main>
</body>
</html>
