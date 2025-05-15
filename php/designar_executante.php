<?php
session_start();
include 'conexao.php';
require 'enviar_email.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = $_POST['titulo'] ?? '';
    $executante = $_POST['executante'] ?? '';

    $sql = "UPDATE manutencao SET executante = ? WHERE titulo = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $executante, $titulo);

    if ($stmt->execute()) {
        $_SESSION['executante_sucesso'] = true;
        
        // Buscar dados do executante e responsável
        $busca = $conn->prepare("
            SELECT 
                m.titulo,
                d_exec.nome AS nome_exec, d_exec.email AS email_exec,
                d_resp.nome AS nome_resp, d_resp.email AS email_resp
            FROM manutencao m
            LEFT JOIN docentes d_exec ON m.executante = d_exec.id
            LEFT JOIN docentes d_resp ON m.responsavel = d_resp.id
            WHERE m.titulo = ?
        ");
        $busca->bind_param("s", $titulo);
        $busca->execute();
        $resultado = $busca->get_result();

        if ($row = $resultado->fetch_assoc()) {
            $assunto = "Designação de Ordem de Serviço: {$row['titulo']}";

            // E-mail para o técnico designado
            if (!empty($row['email_exec'])) {
                $mensagemExec = "
                    <p>Olá <strong>{$row['nome_exec']}</strong>,</p>
                    <p>Você foi designado como técnico responsável pela ordem de serviço <strong>\"{$row['titulo']}\"</strong>.</p>
                    <p>Por favor, acesse o sistema para iniciá-la.</p>
                    <p>Atenciosamente,<br>Sistema de Manutenção</p>
                ";
                if (!empty($row['email_resp']) && isset($mensagem)) {
                    enviarEmailUsuario($row['email_resp'], 'Assunto', $mensagem);
                }
            }

            // E-mail para o responsável pela OS
            if (!empty($row['email_resp'])) {
                $mensagemResp = "
                    <p>Olá <strong>{$row['nome_resp']}</strong>,</p>
                    <p>Sua ordem de serviço <strong>\"{$row['titulo']}\"</strong> foi atribuída ao técnico <strong>{$row['nome_exec']}</strong>.</p>
                    <p>Atenciosamente,<br>Sistema de Manutenção</p>
                ";
                if (isset($destinatario) && isset($mensagem)) {
                    enviarEmailUsuario($row['email_resp'],$assunto, $mensagemResp);
                }
            }
        }

    } else {
        $_SESSION['executante_erro'] = true;
    }

    header("Location: detalhe.php?titulo=" . urlencode($titulo));
    exit();
}
?>
