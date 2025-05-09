<?php
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
$ranking = $conn->query("SELECT responsavel, COUNT(*) as total FROM manutencao GROUP BY responsavel ORDER BY total DESC LIMIT 5");
$responsaveis = []; $totais = [];
while ($r = $ranking->fetch_assoc()) {
    $responsaveis[] = $r['responsavel'];
    $totais[] = $r['total'];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Gerencial</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .card-metric {
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .bg-pendente { background: #f39c12; }
        .bg-andamento { background: #3498db; }
        .bg-finalizado { background: #2ecc71; }
        .bg-rejeitado { background: #e74c3c; }
        .dashboard-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
    </style>
</head>
<body class="bg-light p-4">
<div class="text-center mt-4">
        <a href="index.php" class="btn btn-secondary">Voltar</a>
    </div>

    <h2 class="text-center mb-4">Painel de Indicadores de Manutenção</h2>

    <div class="dashboard-container">
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

    <div class="row mt-5">
        <div class="col-md-6">
            <div class="card p-3 shadow-sm">
                <h5 class="text-center">Distribuição por Status</h5>
                <canvas id="statusChart"></canvas>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card p-3 shadow-sm">
                <h5 class="text-center">Top 5 Responsáveis</h5>
                <canvas id="rankingChart"></canvas>
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
                title: { display: false }
            }
        }
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
        }
    });
    </script>
</body>
</html>
