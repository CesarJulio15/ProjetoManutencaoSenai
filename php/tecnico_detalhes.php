<?php
session_start();
require 'conexao.php';

if (!isset($_GET['nif'])) {
    echo "Técnico não especificado.";
    exit;
}

$nif = $_GET['nif'];

// Função para buscar dados do técnico
function buscarTecnico($conn, $nif) {
    $stmt = $conn->prepare("SELECT id, nome, nif,email,docente_status FROM docentes WHERE nif = ?");
    $stmt->bind_param("s", $nif);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Função para status dos pedidos feitos hoje
function buscarStatusHoje($conn, $tecnico_id) {
  $hoje = date('Y-m-d');

  $todosStatus = ['Pendente', 'Em Andamento', 'Concluído', 'Rejeitado'];
  $resultado = array_fill_keys($todosStatus, 0);

  $stmt = $conn->prepare("
      SELECT status, COUNT(*) AS total
      FROM manutencao
      WHERE executante = ? AND (
          (status = 'Concluído' AND data_encerramento = ?) OR 
          (status <> 'Concluído' AND data_abertura = ?)
      )
      GROUP BY status
  ");
  $stmt->bind_param("iss", $tecnico_id, $hoje, $hoje);
  $stmt->execute();
  $res = $stmt->get_result();

  while ($row = $res->fetch_assoc()) {
      $resultado[$row['status']] = $row['total'];
  }

  // Retorna como array de arrays para manter compatibilidade
  $saida = [];
  foreach ($resultado as $status => $total) {
      $saida[] = ['status' => $status, 'total' => $total];
  }

  return $saida;
}

// Função para status geral dos pedidos
function buscarStatusGeral($conn, $tecnico_id) {
    $stmt = $conn->prepare("
        SELECT status, COUNT(*) AS total
        FROM manutencao
        WHERE executante = ?
        GROUP BY status
    ");
    $stmt->bind_param("i", $tecnico_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Função para pedidos por mês e status
function buscarPedidosMesStatus($conn, $tecnico_id) {
    $stmt = $conn->prepare("
        SELECT DATE_FORMAT(data_abertura, '%Y-%m') AS mes, status, COUNT(*) AS total
        FROM manutencao
        WHERE executante = ?
        GROUP BY mes, status
        ORDER BY mes DESC
    ");
    $stmt->bind_param("i", $tecnico_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Função para pedidos por bloco
function buscarPedidosPorBloco($conn, $tecnico_id) {
    $stmt = $conn->prepare("
        SELECT bloco, COUNT(*) AS total
        FROM manutencao
        WHERE executante = ?
        GROUP BY bloco
    ");
    $stmt->bind_param("i", $tecnico_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Execução
$tecnico = buscarTecnico($conn, $nif);
if (!$tecnico) {
    echo "Técnico não encontrado.";
    exit;
}
$tecnico_id = $tecnico['id'];

$statusHoje      = buscarStatusHoje($conn, $tecnico_id);
$statusGeral     = buscarStatusGeral($conn, $tecnico_id);
$dadosMesStatus  = buscarPedidosMesStatus($conn, $tecnico_id);
$dadosBloco      = buscarPedidosPorBloco($conn, $tecnico_id);


// Consulta os pedidos por status e mês (últimos 6 meses)
$sql = "
  SELECT 
    DATE_FORMAT(data_abertura, '%m-%Y') AS mes,
    status,
    COUNT(*) AS total
  FROM manutencao
  WHERE data_abertura >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH)
  GROUP BY mes, status
  ORDER BY STR_TO_DATE(CONCAT('01-', mes), '%d-%m-%Y') ASC
";
$result = $conn->query($sql);

$dados = [];
$labels = [];

while ($row = $result->fetch_assoc()) {
    $mes = $row['mes'];
    $status = $row['status'];
    $total = (int)$row['total'];

    if (!in_array($mes, $labels)) {
        $labels[] = $mes;
    }

    $dados[$status][$mes] = $total;
}

// Preenche os buracos com zero
foreach ($dados as $status => $valores) {
    foreach ($labels as $mes) {
        if (!isset($dados[$status][$mes])) {
            $dados[$status][$mes] = 0;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Painel de indicadores por técnico</title>

    <link rel="stylesheet" href="../css/padrao.css">
    <link rel="stylesheet" href="../css/dashboard.css">    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js" integrity="sha512-CQBWl4fJHWbryGE+Pc7UAxWMUMNMWzWxF4SQo9CgkJIN1kx6djDQZjh3Y8SZ1d+6I+1zze6Z7kHXO7q3UyZAWw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
        <style>
            .flag {
    position: absolute;
    top: 0px;
    right: 0px;
    width: 48px;
    height: 32px;
    border-bottom-left-radius: 5px;
    background: gray;
}
        </style>
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
                <h2 class="title text-center mb-4">Painel de indicadores por técnico</h2>
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
            <section class="dashboard-container d-flex flex-wrap flex-md-column justify-content-evenly gap-4 mt-3 w-100">

            <div class="dashboard-group d-flex flex-row justify-content-evenly align-items-start align-top gap-4 w-100">
              <div class="info card p-3 m-auto shadow-sm w-25 h-25 animate__animated animate__fadeInLeft"  style="flex:1; min-width:50px; border: 1px solid <?= $tecnico['docente_status'] === 'Ativo' ? '#5b7f2e' : '#a0352b' ?>;">
              <h5 class="title"><i class="fa-solid fa-id-badge"></i>Informações de cadastro</h5>
              <div class="flag" style="background-color: <?= strtolower($tecnico['docente_status']) === 'ativo' ? '#5b7f2e' : '#a0352b' ?>;"></div>
                    <p><strong>Nome:</strong> <?= htmlspecialchars($tecnico['nome']) ?></p>
                    <p><strong>NIF:</strong> <?= htmlspecialchars($tecnico['nif']) ?></p>
                    <p><strong>Status:</strong> <?= htmlspecialchars($tecnico['docente_status']) ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($tecnico['email']) ?></p>
              </div>
              <div class="card p-3 m-auto shadow-sm w-25 animate__animated animate__fadeInRight">
              <h5 class="title"><i class="fa-solid fa-list-ul"></i>Status de Hoje</h5>
                                  <ul class="list-group">
                                      <?php foreach ($statusHoje as $linha): ?>
                                          <li class="list-group-item "><strong><?= $linha['status'] ?>:</strong> <?= $linha['total'] ?></li>
                                      <?php endforeach; ?>
                                  </ul>
                    </div>
            </div>
            <div class="dashboard-group d-flex flex-row gap-4">
                    <!-- Gráfico de Pizza (à esquerda) -->
                    <div class="p-2 m-auto animate__animated animate__fadeInUp">
                        <div class="chart-container card p-3 shadow-sm" style="height: 420px;">
                            <h5 class="title text-center">
                              <span class="material-symbols-outlined">pie_chart</span> 
                              Distribuição geral dos pedidos por status</h5>
                              <canvas id="gaugeStatusGeral" height="300"></canvas>
                        </div>
                    </div>
            <div class="card p-3 m-auto shadow-sm w-100 h-75 animate__animated animate__fadeInRight">
                <h5 class="title"><i class="fa-solid fa-chart-line"></i>Pedidos por Mês e Status</h5>
                      <canvas id="graficoPedidos"  width="600" height="300"></canvas>
                  </div>
            </div>
            <div class="dashboard-group d-flex flex-row gap-4">
                  <div class="card p-3 m-auto shadow-sm w-100 h-75 animate__animated animate__fadeInUp">
                    <h5 class="title">
                    <i class="fa-solid fa-chart-pie"></i> Pedidos por bloco
                    </h5>
                      <canvas id="gaugeBloco" height="200"></canvas>
                  </div>
            </div>

    </section>
  </main>
 
    <script>
        const ctxBloco = document.getElementById('gaugeBloco').getContext('2d');
        new Chart(ctxBloco, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_column($dadosBloco, 'bloco')) ?>,
                datasets: [{
                    data: <?= json_encode(array_column($dadosBloco, 'total')) ?>,
                    backgroundColor: ['#000000', '#e6b8b8', '#8bc34a', '#ff9800', '#03a9f4', '#9c27b0']
                }]
            },
            options: {
                plugins: {
                    legend: { position: 'bottom' },
                    datalabels: {
                        color: '#fff',
                        font: { weight: 'bold', size: 12 },
                        formatter: (value, ctx) => {
                            const data = ctx.chart.data.datasets[0].data;
                            const total = data.reduce((a, b) => a + b, 0);
                            const percent = total > 0 ? (value / total) * 100 : 0;
                            return `${percent.toFixed(1)}%`;
                        }
                    }
                },
                cutout: '60%',
                responsive: true,
            },
            plugins: [ChartDataLabels]
        });

        const dadosMes = <?= json_encode($dadosMesStatus) ?>;
        const agrupado = {};
        dadosMes.forEach(d => {
            if (!agrupado[d.mes]) agrupado[d.mes] = {};
            agrupado[d.mes][d.status] = d.total;
        });

        const meses = Object.keys(agrupado).sort();
        const statusUnicos = [...new Set(dadosMes.map(d => d.status))];
        const datasets = statusUnicos.map(status => ({
            label: status,
            data: meses.map(m => agrupado[m][status] || 0),
        }));

        const ctxStatusGeral = document.getElementById('gaugeStatusGeral').getContext('2d');
        new Chart(ctxStatusGeral, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_column($statusGeral, 'status')) ?>,
                datasets: [{
                    data: <?= json_encode(array_column($statusGeral, 'total')) ?>,
                    backgroundColor: ['#e91e63','#ff9800','#8bc34a','#9e9e9e']
                }]
            },
            options: {
                plugins: {
                    legend: { position: 'bottom' },
                    datalabels: {
                        color: '#fff',
                        font: { weight: 'bold', size: 12 },
                        formatter: (value, ctx) => {
                            const data = ctx.chart.data.datasets[0].data;
                            const total = data.reduce((a, b) => a + b, 0);
                            const percent = total > 0 ? (value / total) * 100 : 0;
                            return `${percent.toFixed(1)}%`;
                        }
                    }
                },
                cutout: '60%',
                responsive: true,
            },
            plugins: [ChartDataLabels]
        });
    </script>
    
<script>
document.addEventListener("DOMContentLoaded", function () {
  const labels = <?php echo json_encode($labels); ?>;
  const dados = <?php echo json_encode($dados); ?>;

  const cores = {
  'Pendente': '#e91e63',
  'Em Andamento': '#ff9800',
  'Concluído': '#8bc34a'
};

  const datasets = Object.keys(dados).map(status => ({
    label: status,
    data: labels.map(m => dados[status][m]),
    borderColor: cores[status],
    backgroundColor: cores[status],
    pointBackgroundColor: '#000',
    pointRadius: 6,
    tension: 0.4,
    datalabels: {
      align: 'top',
      anchor: 'end',
      backgroundColor: cores[status],
      borderRadius: 4,
      color: '#fff',
      font: { weight: 'bold' },
      formatter: (v) => v > 0 ? v : ''
    }
  }));

  new Chart(document.getElementById('graficoPedidos').getContext('2d'), {
    type: 'line',
    data: {
      labels: labels,
      datasets: datasets
    },
    options: {
      plugins: {
        datalabels: { display: true },
        tooltip: { enabled: true }
      },
      scales: {
        y: { beginAtZero: true }
      }
    },
    plugins: [ChartDataLabels]
  });
});
</script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/js/all.min.js" integrity="sha512-b+nQTCdtTBIRIbraqNEwsjB6UvL3UEMkXnhzd8awtCYh0Kcsjl9uEgwVFVbhoj3uu1DO1ZMacNvLoyJJiNfcvg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        
</body>
</html>
