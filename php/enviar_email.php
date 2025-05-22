<?php
    session_start();
    if (!isset($_SESSION['id'])) {
     header("Location: index.php");
     exit();
    }
    require_once __DIR__ . '/../../../vendor/autoload.php';
    require 'conexao.php';

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;
    use chillerlan\QRCode\QRCode;
    use chillerlan\QRCode\QROptions;

    const SMTP_HOST = 'smtp.titan.email';
    const SMTP_USER = 'contato@devgomes.com';
    const SMTP_PASS = 'Fgs@991554618';
    
    function configurarSMTP(PHPMailer $mail) {
        $mail->isSMTP();
        $mail->Host       = gethostbyname(SMTP_HOST);
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // usa 'ssl'
        $mail->Port       = 465;
        $mail->Timeout    = 20; // segundos
        $mail->SMTPKeepAlive = false;

        $mail->CharSet = 'UTF-8';
        $mail->isHTML(true);
        $mail->setFrom(SMTP_USER, 'Sistema de Manutenção');
        $mail->SMTPDebug = 0;
        $mail->Debugoutput = 'html';
    }

    function enviarEmailUsuario($destinatario, $assunto, $mensagemHtml) {
        $mail = new PHPMailer(true);

        try {
            configurarSMTP($mail);
            $mail->addAddress($destinatario);
            $mail->Subject = $assunto;
            $mail->Body    = $mensagemHtml;

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Erro ao enviar e-mail para $destinatario: {$mail->ErrorInfo}");
            return false;
        }
    }

    function enviarEmailCadastro($destinatario, $nome, $senha, $linkLogin) {
        $mail = new PHPMailer(true);

        try {
            // QR Code como base64
            $qrOptions = new QROptions([
                'outputType' => QRCode::OUTPUT_IMAGE_PNG,
                'eccLevel'   => QRCode::ECC_L,
                'scale'      => 5,
            ]);

            $qrCode = (new QRCode($qrOptions))->render($linkLogin);
            $qrCodeBase64 = 'data:image/png;base64,' . base64_encode($qrCode);

            configurarSMTP($mail);
            $mail->addAddress($destinatario);
            $mail->Subject = 'Bem-vindo! Acesso ao Portal Senai Manutenções';
            $mail->Body    = "
                <h2>Olá, {$nome}!</h2>
                <p>Seu acesso ao sistema foi criado com sucesso.</p>
                <p><strong>Login:</strong> {$destinatario}</p>
                <p><strong>Senha:</strong> {$senha}</p>
                <p>Você pode acessar o sistema pelo link abaixo:</p>
                <p><a href='{$linkLogin}'>{$linkLogin}</a></p>
                <p><strong>Ou escaneie o QR Code:</strong></p>
                <p><img src='{$qrCodeBase64}' alt='QR Code de acesso'></p>
                <br><p>Atenciosamente,<br>Equipe de Suporte</p>
            ";

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Erro ao enviar e-mail de cadastro: {$mail->ErrorInfo}");
            return false;
        }
    }
