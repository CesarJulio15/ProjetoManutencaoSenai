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
