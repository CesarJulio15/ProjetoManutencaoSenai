<?php
session_start();
include 'conexao.php';
if (!isset($_SESSION['id']) && $_SESSION['tipo'] !== 'coordenador') {
    header("Location: index.php");
    exit();
}

if (isset($_GET['titulo'])) {
    $titulo = urldecode($_GET['titulo']);

    // JOIN para trazer o nome do responsável
    $sql = "SELECT m.*, d1.nome AS nome_responsavel, d2.nome AS nome_executante
FROM manutencao m
JOIN docentes d1 ON m.responsavel = d1.id
LEFT JOIN docentes d2 ON m.executante = d2.id
WHERE m.titulo = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $titulo);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Detalhe da Manutenção</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/detalhes.css">
    <link rel="icon" type="image/png" href="../img/senai-logo 5.svg">
</head>
<body class="d-flex flex-column flex-md-row">

    <!-- Sidebar -->
    <aside class="sidebar d-flex flex-column justify-content-between align-items-center flex-md-column flex-sm-row p-3 gap-3">
        <img src="../img/LogoPortalSenai.svg" alt="Portal Senai Manutenções" class="logo-senai">
        <img src="../img/TechFix.svg" alt="Techfix Facilities" class="logo-techfix">
    </aside>

    <main class="content d-flex justify-content-center align-items-center min-vh-100 py-5 bg-light">
        <header class="top-bar position-fixed top-0 end-0 p-3 d-flex align-items-center" style="z-index: 1050;">
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

        <div class="detalhe-container mx-auto">
                                <!-- Voltar -->
            <div class="text-start mb-4">
                <a href="manutencao.php" class="btn btn-dark">← Voltar</a>
            </div>
<div class="info-detalhe mb-4 text-start">
    <h1 class="titulo-manutencao"><?php echo htmlspecialchars($row['titulo']); ?></h1>
<div class="d-flex flex-column flex-md-row gap-4 align-items-start mt-2">
    <div class="d-flex">
        <label class="info-label me-1">Responsável:</label>
        <p class="info-text mb-0"><?php echo htmlspecialchars($row['nome_responsavel']); ?></p>
    </div>
    <div class="d-flex">
        <label class="info-label me-1">Executante:</label>
        <p class="info-text mb-0">
            <?php echo !empty($row['nome_executante']) 
                ? htmlspecialchars($row['nome_executante']) 
                : '<span class="text-muted fst-italic">Não designado</span>'; ?>
        </p>
    </div>
</div>
    <div class="info-item mt-3">
        <label class="info-label">Descrição:</label>
        <p class="info-text"><?php echo nl2br(htmlspecialchars($row['descricao'])); ?></p>
    </div>


            <?php
                if (!empty($row['foto'])) {
                    $fotoBase64 = base64_encode($row['foto']);
                    echo "<img src='data:image/jpeg;base64,{$fotoBase64}' alt='Imagem da manutenção' class='img-fluid rounded mb-3 d-block mx-auto'>";
                }
            ?>
            <input type="hidden" id="titulo" value="<?php echo htmlspecialchars($row['titulo']); ?>">
            <!-- Rodapé com botões -->
            <footer class="border-top pt-4 mt-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3 flex-wrap">
                    <!-- Botões de status -->
                    <div class="d-flex gap-2 flex-wrap justify-content-center">
                        <button class="status-btn btn btn-danger" data-status="Pendente">Pendente</button>
                        <button class="status-btn btn btn-warning text-white" data-status="Em Andamento">Em Andamento</button>
                        <button class="status-btn btn btn-success" data-status="Concluído">Concluído</button>
                    </div>

                    <!-- Botão Designar (apenas para admin) -->
                    <?php if ($_SESSION['tipo'] === 'adm') : ?>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#designarModal">
                            Designar
                        </button>
                    <?php endif; ?>

                    <!-- Excluir -->
                    <div class="flex-shrink-0">
                        <form method="POST" action="excluir_manutencao.php" onsubmit="return confirm('Tem certeza que deseja excluir esta manutenção?');">
                            <input type="hidden" name="titulo" value="<?php echo htmlspecialchars($row['titulo']); ?>">
                            <button type="submit" class="btn p-0 btn-excluir">
                                <img src="../img/trash.png" alt="Excluir" width="24" height="24">
                            </button>
                        </form>
                    </div>
                </div>
            </footer>
        </div>
    </main>

    <!-- Modal Designar -->
    <div class="modal fade" id="designarModal" tabindex="-1" aria-labelledby="designarModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <form method="POST" action="designar_executante.php">
            <div class="modal-header">
              <h5 class="modal-title" id="designarModalLabel">Designar Executante</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
              <input type="hidden" name="titulo" value="<?php echo htmlspecialchars($row['titulo']); ?>">

              <?php
                $docentes = $conn->query("SELECT id, nome FROM docentes WHERE tipo = 'manutencao'");
                echo '<div class="form-group mb-3">';
                echo '<label for="executante">Selecionar usuário:</label>';
                echo '<select name="executante" id="executante" class="form-select" required>';
                echo '<option value="">Selecione</option>';
                while ($docente = $docentes->fetch_assoc()) {
                    echo "<option value='{$docente['id']}'>{$docente['nome']}</option>";
                }
                echo '</select>';
                echo '</div>';
              ?>

              <?php if (!empty($row['executante'])): ?>
                  <div class="alert alert-warning mt-3">
                      Já existe um executante designado. Você pode substituí-lo ou removê-lo abaixo.
                  </div>
                  <div class="form-check">
                      <input class="form-check-input" type="checkbox" name="remover_executante" value="1" id="remover_executante">
                      <label class="form-check-label" for="remover_executante">Remover executante atual</label>
                  </div>
              <?php endif; ?>
            </div>
            <div class="modal-footer">
              <button type="submit" class="btn btn-success">Salvar</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <script>
        document.querySelectorAll('.status-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const status = btn.dataset.status;
                const titulo = document.getElementById('titulo').value;

                fetch('atualizar_status.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'titulo=' + encodeURIComponent(titulo) + '&status=' + encodeURIComponent(status)
                })
                .then(response => response.text())
                .then(data => alert(data))
                .catch(error => alert('Erro: ' + error));
            });
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
    } else {
        echo "<p class='text-center mt-5 text-danger'>Manutenção não encontrada.</p>";
    }
    $stmt->close();
} else {
    echo "<p class='text-center mt-5 text-warning'>Título não fornecido.</p>";
}
$conn->close();
?>
