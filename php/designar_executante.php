<?php
session_start();
include 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = $_POST['titulo'] ?? '';
    $executante = $_POST['executante'] ?? '';

    $sql = "UPDATE manutencao SET executante = ? WHERE titulo = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $executante, $titulo);

    if ($stmt->execute()) {
        $_SESSION['executante_sucesso'] = true;
    } else {
        $_SESSION['executante_erro'] = true;
    }

    header("Location: detalhe.php?titulo=" . urlencode($titulo));
    exit();
}
?>
