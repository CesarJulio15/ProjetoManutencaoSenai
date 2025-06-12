<?php
session_start();
if (isset($_SESSION['id'])) {
    header("Location: panelTecnico.php");
    exit();
}
require 'conexao.php';

function limparNif($identificacao) {
    return str_ireplace('sn', '', trim($identificacao));
}

function buscarUsuario($conn, $nif, $identificacao) {
    $sql = "SELECT id, nome, nif, email, senha, tipo FROM docentes 
            WHERE (nif = ? OR email = ?) AND docente_status = 'Ativo'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $nif, $identificacao);
    $stmt->execute();
    $stmt->store_result();
    return $stmt;
}

function autenticarUsuario($stmt, $senha) {
    if ($stmt->num_rows === 1) {
        $stmt->bind_result($id, $nome, $nif_db, $email_db, $senha_hash, $tipo);
        $stmt->fetch();
        if (password_verify($senha, $senha_hash)) {
            $_SESSION['id'] = $id;
            $_SESSION['nome'] = $nome;
            $_SESSION['tipo'] = strtolower($tipo);
            redirecionarPorTipo($tipo);
            exit();
        } else {
            exibirErro('Dados inválidos');
        }
    } else {
        exibirErro('Dados inválidos');
    }
}

function redirecionarPorTipo($tipo) {
    switch (strtolower($tipo)) {
        case 'docente':
            header("Location: solicita.php");
            break;
        case 'técnico':
        case 'coordenador':
            header("Location: panelTecnico.php");
            break;
        default:
            echo "<script>alert('Tipo de usuário inválido.');</script>";
            break;
    }
}

function exibirErro($mensagem) {
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'error',
                title: 'Erro',
                text: '$mensagem',
                confirmButtonColor: '#d33'
            });
        });
    </script>";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identificacao = $_POST['login'];
    $senha = $_POST['senha'];
    $nif_limpo = limparNif($identificacao);
    $stmt = buscarUsuario($conn, $nif_limpo, $identificacao);
    autenticarUsuario($stmt, $senha);
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
    <link rel="icon" type="image/png" href="../img/senai-logo5.svg">    
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="preload" as="font" href="https://fonts.gstatic.com/s/materialsymbolsoutlined/v249/kJF1BvYX7BgnkSrUwT8OhrdQw4oELdPIeeII9v6oDMzByHX9rA6RzaxHMPdY43zj-jCxv3fzvRNU22ZXGJpEpjC_1v-p_4MrImHCIJIZrDCvHOej.woff2" type="font/woff2" crossorigin="anonymous">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="d-flex align-items-center justify-content-center w-100 px-3">

    <div class="container text-center px-0 animate__animated animate__fadeInUp">
        <img src="../img/Mask group.svg" alt="Logo" class="logo img-fluid mb-2" style="user-select: none;">
        <h1 style="user-select: none;">
            <span class="portal">Portal Senai</span><br>
            <span class="manutencao">Manutenções</span>
        </h1>

        <div class="form-box bg-secondary bg-opacity-25 p-4 rounded-3 mx-auto mt-4 h-50 w-75 animate__animated animate__fadeInUp" style="max-width:25%; max-height:fit-content;user-select: none;">
            <form method="POST" autocomplete="off" class="d-flex flex-md-column">
                <div class="mb-2 d-flex">
                    <input type="text" name="login" class="form-control rounded" placeholder="E-mail ou NIF" required>
                </div>
                <div class="mb-3 d-flex">
                <div class="d-flex input-group align-items-sm-center">
                    <input type="password" name="senha" id="senhaInput" class="form-control rounded-right" placeholder="Senha"  autocomplete="off"  oninput="verificarSenha()" required>
                    <button type="button" id="toggle" class="btn btn-dark rounded-end d-flex justify-content-xl-evenly align-items-sm-center" onclick="toggleSenha(this)" tabindex="-1">
                    <span id="iconeSenha" class="material-symbols-outlined">visibility</span>
                    </button>
                </div>
                </div>
                <button type="submit" class="btn btn-dark w-100">Entrar</button>
                <div class="form-footer mt-1" style="line-height: normal;">
                    <p><a href="#" onclick="abrirModalRecuperacaoSenha()" class="forgot text-muted">Esqueceu a senha?</a></p>
                </div>
            </form>
        </div>

        <div class="logos d-flex justify-content-center align-items-center flex-wrap mt-3  animate__animated animate__fadeInUp" style="user-select:none;">
            <img src="../img/Clip path group.svg" alt="Techfix" class="img-fluid mx-2" style="height: 70px; user-select:none;">
            <img src="../img/senai-logo5.svg" alt="Senai" class="img-fluid mx-2" style="height: 50px; user-select:none;">
        </div>
    </div> 
    <script src="../js/login.js"></script>
</body>
</html>
