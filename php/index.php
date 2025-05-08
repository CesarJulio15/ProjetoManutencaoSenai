<?php
session_start();
include 'conexao.php';

// Se o formulário for enviado
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $credencial = $_POST['credencial'];
    $senha = $_POST['senha'];

    $sql = "SELECT id, nome, senha, tipo FROM usuarios WHERE credencial = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $credencial);
    $stmt->execute();
    $stmt->store_result();
    echo "<script>console.log('Credenciais encontradas: " . $stmt->num_rows . "');</script>";

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($id, $nome, $senha_hash, $tipo);
        $stmt->fetch();

        if (password_verify($senha, $senha_hash)) {
            $tipo_normalizado = strtolower(trim($tipo));

            $_SESSION['id'] = $id;
            $_SESSION['credencial'] = $credencial;
            $_SESSION['nome'] = $nome;
            $_SESSION['tipo'] = $tipo_normalizado;

            // Redirecionamento com base no tipo
            if ($tipo_normalizado === 'funcionario') {
                header("Location: solicita.php");
            } elseif ($tipo_normalizado === 'manutencao') {
                header("Location: pagina_dos_profissionais_manutencao.php");
            } else {
                header("Location: manutencao.php");
            }
            exit();
        } else {
            echo "<script>alert('Senha incorreta!');</script>";
        }
    } else {
        echo "<script>alert('Credencial inválida!');</script>";
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
