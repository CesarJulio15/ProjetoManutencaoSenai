<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Caminhos corrigidos conforme estrutura local
require_once __DIR__ . '/../../../PHPMailer-6.10.0/src/Exception.php';
require_once __DIR__ . '/../../../PHPMailer-6.10.0/src/PHPMailer.php';
require_once __DIR__ . '/../../../PHPMailer-6.10.0/src/SMTP.php';

function enviarEmailUsuario($destinatario, $assunto, $mensagemHtml) {
    $mail = new PHPMailer(true);
    $mail->CharSet = 'UTF-8';
    try {
        $mail->isSMTP();
        $mail->SMTPDebug = 0; // altere para 2 para ver detalhes no console
        $mail->Debugoutput = 'html';
        $mail->SMTPAuth   = true;
        $mail->Host       = 'smtp.titan.email';
        $mail->Username   = 'contato@devgomes.com'; // seu e-mail
        $mail->Password   = 'Fgs@991554618';         // sua senha
        $mail->SMTPSecure = 'ssl';    
        $mail->Port       = 465;
        $mail->setFrom('contato@devgomes.com', 'Sistema de Manutenção');
        $mail->addAddress($destinatario); // destinatário

        $mail->isHTML(true);
        $mail->Subject = $assunto;
        $mail->Body    = $mensagemHtml;

        $mail->send();
        return true;
    } catch (Exception $e) {
        return "Erro ao enviar e-mail: {$mail->ErrorInfo}";
    }
}

function enviarEmailCadastro($destinatario, $nome, $senha, $linkLogin) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.seudominio.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'seuemail@seudominio.com';
        $mail->Password   = 'suasenhaemail';
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('no-reply@seudominio.com', 'Equipe Senai Manutenções');
        $mail->addAddress($destinatario, $nome);

        $mail->isHTML(true);
        $mail->Subject = 'Bem-vindo! Acesso ao Portal Senai Manutenções';
        $mail->Body    = "
            <h2>Olá, {$nome}!</h2>
            <p>Seu acesso ao sistema foi criado com sucesso.</p>
            <p><strong>Login:</strong> {$destinatario}</p>
            <p><strong>Senha:</strong> {$senha}</p>
            <p>Você pode acessar o sistema pelo seguinte link:</p>
            <p><a href='{$linkLogin}'>{$linkLogin}</a></p>
            <p>Recomendamos alterar sua senha após o primeiro acesso.</p>
            <br>
            <p>Atenciosamente,<br>Equipe de Suporte</p>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Erro ao enviar e-mail: {$mail->ErrorInfo}");
        return false;
    }
}