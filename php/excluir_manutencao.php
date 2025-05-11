<?php
session_start();                                        // 1) Inicia a sessão
include 'conexao.php';

if (!isset($_SESSION['id']) || !in_array($_SESSION['tipo'], ['coordenador','técnico'])) {
    // 2) Se não estiver logado OU não for coordenador/técnico, volta ao login
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['titulo'])) {
    $titulo = $_POST['titulo'];                         // 3) Pega diretamente do POST

    $stmt = $conn->prepare("DELETE FROM manutencao WHERE titulo = ?");
    $stmt->bind_param("s", $titulo);

    if ($stmt->execute()) {
        // Você pode definir uma mensagem de sucesso em $_SESSION para exibir no painel
        header("Location: panelTecnico.php");
        exit;                                           // 4) Para o script após o redirect
    } else {
        echo "<p>Erro ao excluir a manutenção: " . $conn->error . "</p>";
    }
    $stmt->close();
} else {
    echo "<p>Título não fornecido.</p>";
}

$conn->close();
