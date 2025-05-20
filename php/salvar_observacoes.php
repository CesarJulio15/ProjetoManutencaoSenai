<?php
session_start();
require_once 'conexao.php';

if (!isset($_SESSION['id']) || !in_array($_SESSION['tipo'], ['Coordenador', 'Técnico'])) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $os_id = intval($_POST['os_id']);
    $observacoes = trim($_POST['observacoes']);

    $stmt = $conn->prepare("UPDATE manutencao SET observacoes = ? WHERE id = ?");
    $stmt->bind_param("si", $observacoes, $os_id);
    
    if ($stmt->execute()) {
        $_SESSION['mensagem'] = "Observações salvas com sucesso.";
    } else {
        $_SESSION['erro'] = "Erro ao salvar observações.";
    }

    header("Location: detalhe.php?id=" . $os_id);
    exit;
}
