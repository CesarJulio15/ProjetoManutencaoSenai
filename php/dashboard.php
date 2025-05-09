<?php
session_start();
include 'conexao.php';

// Verifica se o usuário está logado e se é do tipo 'adm'
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'adm') {
  header("Location: login.php");
  exit();
}

// Projetos concluídos
$sql_concluidos = "SELECT titulo, descricao, responsavel, executante 
                   FROM manutencao 
                   WHERE status = 'Concluído'";
$result_concluidos = $conn->query($sql_concluidos);

// Ranking de responsáveis
$sql_ranking = "SELECT responsavel, COUNT(*) as total 
                FROM manutencao 
                GROUP BY responsavel 
                ORDER BY total DESC";
$result_ranking = $conn->query($sql_ranking);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0"> <!-- Responsividade -->
  <title>Dashboard de Manutenções</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="icon" type="image/png" href="../img/senai-logo 5.svg">
</head>
<body>
  <div class="container py-5">
    <h2 class="mb-4 text-center">Dashboard de Manutenções</h2>

    <!-- Abas -->
    <ul class="nav nav-tabs" id="dashboardTabs" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link active" id="historico-tab" data-bs-toggle="tab" data-bs-target="#historico" type="button" role="tab">Projetos Concluídos</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="ranking-tab" data-bs-toggle="tab" data-bs-target="#ranking" type="button" role="tab">Ranking de Responsáveis</button>
      </li>
    </ul>

    <div class="tab-content mt-4">
      <!-- Aba Projetos Concluídos -->
      <div class="tab-pane fade show active" id="historico" role="tabpanel">
        <div class="table-responsive">
          <table class="table table-striped">
            <thead>
              <tr>
                <th>Título</th>
                <th>Descrição</th>
                <th>Responsável</th>
                <th>Executante</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($result_concluidos->num_rows > 0): ?>
                <?php while ($row = $result_concluidos->fetch_assoc()): ?>
                  <tr>
                    <td><?= htmlspecialchars($row['titulo']) ?></td>
                    <td><?= htmlspecialchars($row['descricao']) ?></td>
                    <td><?= htmlspecialchars($row['responsavel']) ?></td>
                    <td><?= htmlspecialchars($row['executante']) ?></td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr><td colspan="4">Nenhum projeto concluído encontrado.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

<!-- Aba Ranking de Responsáveis -->
<div class="tab-pane fade" id="ranking" role="tabpanel">
  <div class="row">
    <div class="col-12 col-lg-6 mb-4">
      <div class="table-responsive">
        <table class="table table-bordered table-hover">
          <thead class="table-dark">
            <tr>
              <th>Responsável</th>
              <th>Quantidade de Problemas Reportados</th>
            </tr>
          </thead>
          <tbody>
            <?php
              $responsaveis = [];
              $quantidades = [];
              if ($result_ranking->num_rows > 0):
                while ($row = $result_ranking->fetch_assoc()):
                  $responsaveis[] = $row['responsavel'];
                  $quantidades[] = $row['total'];
            ?>
              <tr>
                <td><?= htmlspecialchars($row['responsavel']) ?></td>
                <td><?= $row['total'] ?></td>
              </tr>
            <?php
                endwhile;
              else:
            ?>
              <tr><td colspan="2">Nenhum dado encontrado.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="col-12 col-lg-6">
      <canvas id="graficoRanking"></canvas>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  // Dados do gráfico enviados do PHP
  const labels = <?= json_encode($responsaveis) ?>;
  const data = <?= json_encode($quantidades) ?>;

  const ctx = document.getElementById('graficoRanking').getContext('2d');
  const grafico = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: labels,
      datasets: [{
        label: 'Total de Problemas',
        data: data,
        backgroundColor: 'rgba(102, 16, 242, 0.6)',
        borderColor: 'rgba(102, 16, 242, 1)',
        borderWidth: 2,
        borderRadius: 5
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { display: false },
        title: {
          display: true,
          text: 'Ranking de Responsáveis',
          font: {
            size: 18
          }
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: { stepSize: 1 }
        }
      }
    }
  });
</script>

  <!-- Botão Voltar fixado na parte inferior -->
  <div style="position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); z-index: 1000;">
    <a href="manutencao.php" class="btn btn-secondary">Voltar</a>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php $conn->close(); ?>
