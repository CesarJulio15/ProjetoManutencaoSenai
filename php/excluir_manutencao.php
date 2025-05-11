<?php
if (!isset($_SESSION['id']) && $_SESSION['tipo'] !== 'coordenador') {
    header("Location: index.php");
    exit();
}
include 'conexao.php';

if (isset($_POST['titulo'])) {
    $titulo = urldecode($_POST['titulo']);

    // Exclui o registro da manutenção
    $sql = "DELETE FROM manutencao WHERE titulo = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $titulo);

    if ($stmt->execute()) {
        header("Location: panelTecnico.php"); // Redireciona para a página principal
        exit;
    } else {
        echo "<p>Erro ao excluir a manutenção.</p>";
    }

    $stmt->close();
} else {
    echo "<p>Título não fornecido.</p>";
}

$conn->close();
?>
