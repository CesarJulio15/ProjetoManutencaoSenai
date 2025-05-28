<?php
session_start();
if (!isset($_SESSION['id']) && $_SESSION['tipo'] !== 'coordenador') {
    header("Location: index.php");
    exit();
}
include 'conexao.php'; 
require_once 'enviar_email.php';

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("CSRF token inválido.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome  = htmlspecialchars($_POST['nome'] ?? '');
    $email = htmlspecialchars($_POST['email'] ?? '');
    $nif   = str_replace('sn', '', htmlspecialchars($_POST["nif"]));
    $senha = password_hash($_POST['senha'] ?? '', PASSWORD_DEFAULT);
    $senhaUsuario = $_POST['senha']; // Usada no e-mail
    $tipo  = $_POST['tipo'] ?? 'docente';

    // Verificação se já existe
    $checkStmt = $conn->prepare("SELECT id FROM docentes WHERE email = ? OR nif = ?");
    $checkStmt->bind_param('ss', $email, $nif);
    $checkStmt->execute();
    $checkStmt->store_result();

    if ($checkStmt->num_rows > 0) {
        header("Location: cadastro.php?erro=duplicado");
        exit;
    }
    $checkStmt->close();

    // Realiza cadastro
    $insertStmt = $conn->prepare("INSERT INTO docentes (nome, email, nif, senha, tipo) VALUES (?, ?, ?, ?, ?)");
    $insertStmt->bind_param('sssss', $nome, $email, $nif, $senha, $tipo);

    if ($insertStmt->execute()) {
        $paginaLogin = "https://devgomes.com/projetos/SENAImanutencao/php/";

        // Gera QR code
        $qrOptions = new QROptions([
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel'   => QRCode::ECC_L,
            'scale'      => 5,
        ]);
        $qrCode = (new QRCode($qrOptions))->render($paginaLogin);
        $qrCodeBase64 = 'data:image/png;base64,' . base64_encode($qrCode);

        // Envia e-mail com QR
        enviarEmailCadastroComQR($email, $nome, $senhaUsuario, $paginaLogin, $qrCodeBase64);

        header("Location: panelTecnico.php");
        exit;
    } else {
        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro!',
                    text: 'Erro ao cadastrar funcionário.',
                    confirmButtonColor: '#dc3545',
                });
            });
        </script>";
    }

    $insertStmt->close();
    $conn->close();
}
?>
