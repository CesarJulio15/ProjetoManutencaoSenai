<?php
session_start();
include 'conexao.php';

// Se o formulário for enviado
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nif = $_POST['nif'];
    $senha = $_POST['senha'];

    $sql = "SELECT id, nome, senha, tipo FROM docentes WHERE nif = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $nif);
    $stmt->execute();
    $stmt->store_result();
    echo "<script>console.log('Credenciais encontradas: " . $stmt->num_rows . "');</script>";

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($id, $nome, $senha_hash, $tipo);
        $stmt->fetch();

        if (password_verify($senha, $senha_hash)) {
            $tipo_normalizado = strtolower(trim($tipo));

            $_SESSION['id'] = $id;
            $_SESSION['nif'] = $nif;
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
        echo "<script>alert('NIF inválido!');</script>";
    }

    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <!-- essencial para responsividade -->
    <title>Portal Senai Manutenções</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="icon" type="image/png" href="../img/senai-logo 5.svg">
</head>
<body class="bg-light d-flex align-items-center justify-content-center min-vh-100 px-3"> <!-- min-vh + padding horizontal -->

    <div class="container text-center px-0">
        <img src="../img/Mask group.svg" alt="Logo" class="logo img-fluid mb-3">

        <h1>
            <span class="portal">Portal Senai</span><br>
            <span class="manutencao">Manutenções</span>
        </h1>

        <div class="form-box bg-secondary bg-opacity-25 p-4 rounded-3 mx-auto mt-4">
            <form method="POST">
                <input type="text" name="nif" class="form-control mb-3" placeholder="NIF" required>
                <input type="password" name="senha" class="form-control mb-3" placeholder="Senha" required>
                <button type="submit" class="btn btn-dark w-100">Entrar</button>
            </form>
        </div>

        <div class="logos d-flex justify-content-center align-items-center flex-wrap mt-4">
            <img src="../img/Clip path group.svg" alt="Techfix" class="img-fluid mx-2" style="height: 70px;">
            <img src="../img/senai-logo 5.svg" alt="Senai" class="img-fluid mx-2" style="height: 50px;">
        </div>
    </div> 

</body>
</html>
