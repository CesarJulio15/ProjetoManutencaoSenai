<?php
require_once 'conexao.php';
require_once 'enviar_email.php'; // usa sua função enviarEmailUsuario

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];

    $stmt = $conn->prepare("SELECT id, nome FROM docentes WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 0) {
        exit("E-mail não encontrado.");
    }

    $usuario = $resultado->fetch_assoc();
    $token = bin2hex(random_bytes(32));
    $expira = date("Y-m-d H:i:s", strtotime("+40 minutes"));

    // salva token e validade
    $stmt = $conn->prepare("UPDATE docentes SET reset_token = ?, reset_token_expiry = ? WHERE email = ?");
    $stmt->bind_param("sss", $token, $expira, $email);
    $stmt->execute();

    // monta o e-mail
    $link = "http://{$_SERVER['HTTP_HOST']}/php/resetar_senha.php?token=$token";
    $mensagemHtml = "
        <h2>Recuperação de Senha</h2>
        <p>Olá, {$usuario['nome']}!</p>
        <p>Recebemos uma solicitação para redefinir sua senha.</p>
        <p><a href='$link'>Clique aqui para redefinir sua senha</a></p>
        <p>Este link é válido por 40 minutos.</p>
    ";

    // envia
    $resultado = enviarEmailUsuario($email, "Recuperação de Senha", $mensagemHtml);
    echo $resultado === true ? "E-mail enviado com sucesso." : $resultado;
}
