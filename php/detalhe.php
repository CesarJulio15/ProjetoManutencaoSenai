<?php
session_start();
include 'conexao.php';

if (isset($_GET['titulo'])) {
    $titulo = urldecode($_GET['titulo']);

    $sql = "SELECT * FROM manutencao WHERE titulo = ?";
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
</head>
<body class="d-flex flex-column flex-md-row">

    <!-- Sidebar -->
    <aside class="sidebar d-flex flex-column justify-content-between align-items-center">
        <img src="../img/LogoPortalSenai.svg" alt="Portal Senai Manutenções" class="logo-senai">
        <img src="../img/TechFix.svg" alt="Techfix Facilities" class="logo-techfix">
    </aside>



    <main class="content d-flex justify-content-center align-items-center min-vh-100 py-5 bg-light">
        
        <div class="detalhe-container mx-auto">
            <h1 class="mb-3"><?php echo htmlspecialchars($row['titulo']); ?></h1>
            <p><strong>Descrição:</strong> <?php echo nl2br(htmlspecialchars($row['descricao'])); ?></p>
            <p><strong>Responsável:</strong> <?php echo htmlspecialchars($row['responsavel']); ?></p>

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
        
        <!-- Voltar -->
        <div class="flex-shrink-0">
            <a href="manutencao.php" class="btn btn-danger">Voltar</a>
        </div>

        <!-- Botões de status -->
        <div class="d-flex gap-2 flex-wrap justify-content-center">
            <button class="status-btn btn btn-danger" data-status="Pendente">Pendente</button>
            <button class="status-btn btn btn-warning text-white" data-status="Em Andamento">Em Andamento</button>
            <button class="status-btn btn btn-success" data-status="Concluído">Concluído</button>
        </div>

        <!-- Excluir -->
        <div class="flex-shrink-0">
            <form method="POST" action="excluir_manutencao.php" onsubmit="return confirm('Tem certeza que deseja excluir esta manutenção?');">
                <input type="hidden" name="titulo" value="<?php echo htmlspecialchars($row['titulo']); ?>">
                <button type="submit" class="btn p-0">
                    <img src="../img/trash.png" alt="Excluir" width="24" height="24">
                </button>
            </form>
        </div>

    </div>
</footer>

        </div>
    </main>

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
