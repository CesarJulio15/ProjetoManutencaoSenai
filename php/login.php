<?php
session_start();
include 'conexao.php';

// Se o formulário for enviado
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $credencial = $_POST['credencial'];
    $senha = $_POST['senha'];

    // Consulta para verificar se o usuário existe
    $query = "SELECT * FROM usuarios WHERE credencial = '$credencial' AND senha = '$senha'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) == 1) {
        // Login válido
        $_SESSION['credencial'] = $credencial; // Guarda o usuário na sessão

        header('Location: manutencao.php');
        exit();
    } else {
        echo "<script>alert('Credenciais inválidas!');</script>";
    }
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
<?php include 'conexao.php'; 
session_start();
?>
    <div class="container">
        <img src="../img/Mask group.svg" alt="Logo" class="logo">

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
