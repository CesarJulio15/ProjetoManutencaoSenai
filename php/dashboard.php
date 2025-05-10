<?php
include 'conexao.php';
session_start();
if (!isset($_SESSION['id']) && $_SESSION['tipo'] !== 'coordenador') {
    header("Location: index.php");
    exit();
}
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

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Gerencial</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="">

<aside class="sidebar">
    <img src="../img/LogoPortalSenai.svg" alt="Portal Senai Manutenções" class="logo-senai">
    <div class="spacer"></div>
    <img src="../img/TechFix.svg" alt="Techfix Facilities" class="logo-techfix">
</aside>
<main class="main-content"> 
      <header class="top-bar">
            <div class="text-center mt-4">
            <a href="index.php" class="btn btn-secondary">Voltar</a>
        </div>
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
      <div class="cards-container d-flex flex-wrap flex-column justify-content-between gap-4"  style="flex: 1 1 150px; max-height: 160px;" id="cards-container">
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
      <div class="d-flex flex-row justify-content-around">
  <div class="p-2">            <!-- Gráfico de Pizza (à esquerda) -->
       
          <div class="chart-container card p-3 shadow-sm" style="height: 400px;">
            <h5 class="text-center">Distribuição por Status</h5>
              <canvas id="statusChart"></canvas>
          </div></div>
  <div class="p-2">
                                    <!-- Gráfico de Atendimentos por Mês -->

                  <div class="card p-3 shadow-sm" style="width: 700px;">
          <h5 class="text-center">Pedidos por Mês</h5>
          <canvas id="mesChart"></canvas>
      </div>
        </div>
  <div class="p-2"> 
          <!-- Coluna central com tempo médio -->
                  <div class="card-metric bg-secondary text-center " style="max-width:300px; height: 160px;">
              <h4>Tempo Médio de atendimento</h4>
              <h2><?= $mediaDias ?> dias</h2>
          </div> 
           <div class="card p-3 shadow-sm w-100">
            <h5 class="text-center">Top 5 Responsáveis</h5>
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
