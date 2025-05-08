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
    <link rel="stylesheet" href="../css/detalhes.css">
</head>
<body>
    <aside class="sidebar">
        <img src="../img/LogoPortalSenai.svg" alt="Portal Senai Manutenções" class="logo-senai">
        <div class="spacer"></div>
        <img src="../img/TechFix.svg" alt="Techfix Facilities" class="logo-techfix">
    </aside>

    <div class="content">
        <div class="detalhe-container">
            <h1><?php echo htmlspecialchars($row['titulo']); ?></h1>
            <p><strong>Descrição:</strong> <?php echo nl2br(htmlspecialchars($row['descricao'])); ?></p>
            <p><strong>Responsável:</strong> <?php echo htmlspecialchars($row['responsavel']); ?></p>

            <?php
                if (!empty($row['foto'])) {
                    $fotoBase64 = base64_encode($row['foto']);
                    echo "<img src='data:image/jpeg;base64,{$fotoBase64}' alt='Imagem da manutenção'>";
                }
            ?>

            
            <input type="hidden" id="titulo" value="<?php echo htmlspecialchars($row['titulo']); ?>">

          
            <footer>
                <a href="manutencao.php" class="btn-voltar">Voltar</a>
                <div class="status-buttons">
                    <button class="status-btn pending" data-status="Pendente">Pendente</button>
                    <button class="status-btn ongoing" data-status="Em Andamento">Em Andamento</button>
                    <button class="status-btn completed" data-status="Concluído">Concluído</button>
                </div>
            </footer>
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
            .then(data => alert(data)) // Mostra se atualizou
            .catch(error => alert('Erro: ' + error));
        });
    });
</script>

</body>
</html>
<?php
    } else {
        echo "<p>Manutenção não encontrada.</p>";
    }
    $stmt->close();
} else {
    echo "<p>Título não fornecido.</p>";
}
$conn->close();
?>
