<?php
session_start();
include 'conexao.php';

if (isset($_POST['titulo'])) {
    $titulo = urldecode($_POST['titulo']);

    // Verifica se o usuário tem permissão para excluir (opcional)
    if (!isset($_SESSION['nif']) || !in_array($_SESSION['tipo'], ['adm'])) {
        echo "Acesso negado!";
        exit;
    }

    // Exclui o registro da manutenção
    $sql = "DELETE FROM manutencao WHERE titulo = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $titulo);

    if ($stmt->execute()) {
        header("Location: manutencao.php"); // Redireciona para a página principal
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
