<?php
session_start();
if (isset($_SESSION['id'])) {
    header("Location: panelTecnico.php");
    exit();
}
require 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identificacao = trim($_POST['login']);
    $senha = $_POST['senha'];

    // Limpa o NIF se tiver prefixo 'sn'
    $nif_limpo = str_ireplace('sn', '', $identificacao);

    $sql = "SELECT id, nome, nif, email, senha, tipo FROM docentes 
            WHERE (nif = ? OR email = ?) AND docente_status = 'Ativo'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $nif_limpo, $identificacao);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($id, $nome, $nif_db, $email_db, $senha_hash, $tipo);
        $stmt->fetch();

        if (password_verify($senha, $senha_hash)) {
            $_SESSION['id'] = $id;
            $_SESSION['nome'] = $nome;
            $_SESSION['tipo'] = strtolower($tipo);

            // Redireciona com base no tipo
            switch (strtolower($tipo)) {
                case 'docente':
                    header("Location: solicita.php");
                    break;
                case 'técnico':
                    header("Location: panelTecnico.php");
                    break;
                case 'coordenador':
                    header("Location: panelTecnico.php");
                    break;
                default:
                    echo "<script>alert('Tipo de usuário inválido.');</script>";
                    break;
            }
            exit();
        } else {
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: 'Dados invalídos',
                    confirmButtonColor: '#d33'
                });
            });
        </script>";
        }
    } else {
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'error',
                title: 'Erro',
                text: 'Dados invalídos',
                confirmButtonColor: '#d33'
            });
        });
    </script>";
    }

    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Senai Manutenções</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="icon" type="image/png" href="../img/senai-logo5.svg">
</head>
<body class="d-flex align-items-center justify-content-center w-100 px-3">

    <div class="container text-center px-0">
        <img src="../img/Mask group.svg" alt="Logo" class="logo img-fluid mb-2" style="user-select: none;">
        <h1 style="user-select: none;">
            <span class="portal">Portal Senai</span><br>
            <span class="manutencao">Manutenções</span>
        </h1>

        <div class="form-box bg-secondary bg-opacity-25 p-4 rounded-3 mx-auto mt-4 h-50 w-75" style="height:max-content;user-select: none;">
            <form method="POST" autocomplete="off" class="d-flex flex-md-column">
                <div class="mb-2">
                    <input type="text" name="login" class="form-control" placeholder="E-mail ou NIF" required>
                </div>
                <div class="mb-3">
                    <input type="password" name="senha" class="form-control" placeholder="Senha" required>
                </div>
                <button type="submit" class="btn btn-dark w-100">Entrar</button>
                <div class="form-footer mt-1">
                    <p><a href="#" onclick="abrirModalRecuperacaoSenha()" class="forgot text-muted">Esqueceu a senha?</a></p>

                </div>
            </form>
        </div>

        <div class="logos d-flex justify-content-center align-items-center flex-wrap mt-3" style="user-select:none;">
            <img src="../img/Clip path group.svg" alt="Techfix" class="img-fluid mx-2" style="height: 70px; user-select:none;">
            <img src="../img/senai-logo5.svg" alt="Senai" class="img-fluid mx-2" style="height: 50px; user-select:none;">
        </div>
    </div> 
    <script>
function abrirModalRecuperacaoSenha() {
    Swal.fire({
        title: 'Recuperar Senha',
        html: `
            <input type="email" id="emailRecuperacao" class="swal2-input" placeholder="Digite seu e-mail">
        `,
        confirmButtonText: 'Enviar',
        focusConfirm: false,
        preConfirm: () => {
            const email = document.getElementById('emailRecuperacao').value;
            if (!email) {
                Swal.showValidationMessage('Informe um e-mail válido');
                return false;
            }

            return fetch('enviar_token_recuperacao.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'email=' + encodeURIComponent(email)
            })
            .then(response => response.text())
            .then(res => {
                Swal.fire('Sucesso', res, 'success');
            })
            .catch(() => {
                Swal.fire('Erro', 'Erro ao enviar e-mail. Tente novamente mais tarde.', 'error');
            });

        }
    });
}
</script>
</body>
</html>
