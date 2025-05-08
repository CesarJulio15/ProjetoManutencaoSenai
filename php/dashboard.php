<?php
session_start();

// Verifica se o usuário está logado e se é administrador
if (!isset($_SESSION['usuario']) || $_SESSION['tipo'] !== 'adm') {
    header("Location: index.php"); // Redireciona para login se não for ADM
    exit();
}

require_once 'conexao.php'; // Usa conexão existente

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
  <title>Dashboard de Manutenções</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
  <div class="container py-5">
    <h2 class="mb-4">Dashboard de Manutenções</h2>

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

      <!-- Aba Ranking de Responsáveis -->
      <div class="tab-pane fade" id="ranking" role="tabpanel">
        <table class="table table-bordered">
          <thead>
            <tr>
              <th>Responsável</th>
              <th>Quantidade de Problemas Reportados</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($result_ranking->num_rows > 0): ?>
              <?php while ($row = $result_ranking->fetch_assoc()): ?>
                <tr>
                  <td><?= htmlspecialchars($row['responsavel']) ?></td>
                  <td><?= $row['total'] ?></td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr><td colspan="2">Nenhum dado encontrado.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php $conn->close(); ?>
