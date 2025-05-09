<?php
session_start();
include 'conexao.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = htmlspecialchars($_POST["nome"]);
    $tipo = $_POST["tipo"];
    $nif = htmlspecialchars($_POST["nif"]);
    $senha = password_hash($_POST["senha"], PASSWORD_DEFAULT);

    $sql = "INSERT INTO docentes (nome, tipo, nif, senha) 
            VALUES (?, ?, ?, ?)";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ssss", $nome, $tipo, $nif, $senha);
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
