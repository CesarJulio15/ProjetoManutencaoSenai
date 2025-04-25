<?php
session_start();
include 'conexao.php';

// Se o formulário for enviado
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $credencial = $_POST['credencial'];
    $senha = $_POST['senha'];

    // Preparar a consulta usando prepared statements para evitar SQL Injection
    $sql = "SELECT nome, senha FROM usuarios WHERE credencial = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $credencial);
    $stmt->execute();
    $stmt->store_result();

    // Verifica se o usuário existe
    if ($stmt->num_rows === 1) {
        $stmt->bind_result($nome, $senha_hash);
        $stmt->fetch();

        // Verifica se a senha corresponde ao hash armazenado
        if (password_verify($senha, $senha_hash)) {
            $_SESSION['credencial'] = $credencial;
            $_SESSION['nome'] = $nome; // Armazena o nome para saudação
            header("Location: manutencao.php"); // Redireciona após login
            exit();
        } else {
            echo "<script>alert('Senha incorreta!');</script>"; // Alerta de erro
        }
    } else {
        echo "<script>alert('Credencial inválida!');</script>"; // Alerta de erro
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Portal Senai Manutenções</title>
    <link rel="stylesheet" href="../css/cadastro.css">
</head>
<body>
    <div class="container">
        <img src="../img/Mask group.svg" alt="Logo" class="logo">

        <h1><span class="portal">Portal Senai</span><br><span class="manutencao">Manutenções</span></h1>

        <div class="form-box">
            <form method="POST">
                <input type="text" name="credencial" placeholder="Credencial" required>
                <input type="password" name="senha" placeholder="Senha" required>

                <button type="submit">Entrar</button>
            </form>
        </div>

        <div class="logos">
            <img src="../img/Clip path group.svg" alt="Techfix" class="Techfix">
            <img src="../img/senai-logo 5.svg" alt="Senai">
        </div>
    </div>
</body>
</html>
