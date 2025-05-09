<?php
session_start();
include 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['titulo'])) {
    $titulo = $_POST['titulo'];
    $responsavel = $_SESSION['nome'] ?? 'Desconhecido';
    $executante = $_SESSION['nif'] ?? null;

    // Atualiza o status, o responsável (nome) e o executante (NIF)
    $sql = "UPDATE manutencao SET status = 'Em Andamento', responsavel = ?, executante = ? WHERE titulo = ? AND executante IS NULL";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $responsavel, $executante, $titulo);

    if ($stmt->execute()) {
        header("Location: candidatar.php?titulo=" . urlencode($titulo));
        exit();
    } else {
        echo "<p>Erro ao atualizar status.</p>";
    }

    $stmt->close();
} else {
    echo "<p>Título não fornecido.</p>";
}

$conn->close();
