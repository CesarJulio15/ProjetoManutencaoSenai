<?php
session_start();
include 'conexao.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = htmlspecialchars($_POST["nome"]);
    $tipo = $_POST["tipo"];
    $credencial = htmlspecialchars($_POST["credencial"]);
    $senha = password_hash($_POST["senha"], PASSWORD_DEFAULT);

    $sql = "INSERT INTO usuarios (nome, tipo, credencial, senha) 
            VALUES (?, ?, ?, ?)";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ssss", $nome, $tipo, $credencial, $senha);
        $stmt->execute();
        $stmt->close();
    }

    $conn->close();

    header("Location: manutencao.php");
    exit();
} else {
    header("Location: manutencao.php");
    exit();
}
?>
