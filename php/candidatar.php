<?php
session_start();
include 'conexao.php';

$usuario_credencial = $_SESSION['credencial'] ?? null;

// Candidatar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['titulo']) && isset($_POST['acao'])) {
    $titulo = $_POST['titulo'];

    if ($_POST['acao'] === 'candidatar') {
        $executante = $usuario_credencial;

        $sql = "UPDATE manutencao SET status = 'em andamento', executante = ? WHERE titulo = ? AND executante IS NULL";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $executante, $titulo);
        $stmt->execute();
        $stmt->close();
    }

    // Concluir
    if ($_POST['acao'] === 'concluir') {
        $sql = "UPDATE manutencao SET status = 'concluido' WHERE titulo = ? AND executante = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $titulo, $usuario_credencial);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: pagina_dos_profissionais_manutencao.php");
    exit();
}

// Exibe os detalhes da manutenção
if (isset($_GET['titulo'])) {
    $titulo = urldecode($_GET['titulo']);
    $sql = "SELECT * FROM manutencao WHERE titulo = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $titulo);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $jaExecutando = ($row['executante'] === $usuario_credencial);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Detalhe da Manutenção</title>
    <link rel="stylesheet" href="../css/candidatar.css">
</head>
<body>
    <aside class="sidebar">
        <img src="../img/LogoPortalSenai.svg" alt="Portal Senai Manutenções" class="logo-senai">
        <div class="spacer"></div>
        <img src="../img/TechFix.svg" alt="Techfix Facilities" class="logo-techfix">
    </aside>

    <div class="content">
        <div class="detalhe-container">
            <h1><?= htmlspecialchars($row['titulo']) ?></h1>
            <p><strong>Descrição:</strong> <?= nl2br(htmlspecialchars($row['descricao'])) ?></p>
            <p><strong>Responsável:</strong> <?= htmlspecialchars($row['responsavel']) ?></p>
            <p><strong>Executante:</strong> <?= htmlspecialchars($row['executante'] ?? 'Nenhum') ?></p>
            <p><strong>Status:</strong> <?= htmlspecialchars($row['status']) ?></p>

            <?php
            if (!empty($row['foto'])) {
                $fotoBase64 = base64_encode($row['foto']);
                echo "<img src='data:image/jpeg;base64,{$fotoBase64}' alt='Imagem da manutenção'>";
            }
            ?>

            <footer class="btn-group">
                <a href="pagina_dos_profissionais_manutencao.php" class="btn-voltar">Voltar</a>

                <?php if ($row['executante'] === null): ?>
                    <!-- Exibir botão "Candidatar" se não há executante -->
                    <form method="post">
                        <input type="hidden" name="titulo" value="<?= htmlspecialchars($row['titulo']) ?>">
                        <input type="hidden" name="acao" value="candidatar">
                        <button type="submit" class="btn-candidatar">Candidatar</button>
                    </form>
                <?php elseif ($jaExecutando && $row['status'] !== 'concluido'): ?>
                    <!-- Se o usuário atual for o executante, exibir "Concluir" -->
                    <form method="post">
                        <input type="hidden" name="titulo" value="<?= htmlspecialchars($row['titulo']) ?>">
                        <input type="hidden" name="acao" value="concluir">
                        <button type="submit" class="btn-concluir">Concluir</button>
                    </form>
                <?php endif; ?>

                <form action="rejeitar.php" method="post">
    <input type="hidden" name="titulo" value="<?= htmlspecialchars($row['titulo']) ?>">
    <button type="submit" class="btn-rejeitar">Rejeitar</button>
</form>

            </footer>
        </div>
    </div>
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
