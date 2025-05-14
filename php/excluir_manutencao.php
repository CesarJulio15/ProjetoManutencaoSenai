<?php
session_start();
include 'conexao.php';
require_once 'enviar_email.php'; // PHPMailer com smtp.titan.email

if (!isset($_SESSION['id']) || !in_array($_SESSION['tipo'], ['coordenador', 'técnico'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['titulo'])) {
    $titulo = $_POST['titulo'];

    // Buscar informações antes de deletar
    $sql = "
        SELECT m.titulo, m.numero_os, d1.nome AS nome_responsavel, d1.email AS email_responsavel,
               d2.nome AS nome_exec, d2.email AS email_exec
        FROM manutencao m
        JOIN docentes d1 ON m.responsavel = d1.id
        LEFT JOIN docentes d2 ON m.executante = d2.id
        WHERE m.titulo = ?
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $titulo);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $numero_os = $row['numero_os'];
        $assunto = "OS removida: {$row['titulo']}";

        // ✉ E-mail para o responsável
        if (!empty($row['email_responsavel'])) {
            $mensagemResp = "
                <p>Olá <strong>{$row['nome_responsavel']}</strong>,</p>
                <p>A ordem de serviço <strong>\"{$row['titulo']}\"</strong> (OS nº <strong>{$numero_os}</strong>) foi removida do sistema.</p>
                <p>Se isso foi um engano, entre em contato com a coordenação.</p>
                <p>Atenciosamente,<br>Sistema de Manutenção</p>
            ";
            enviarEmailUsuario($row['email_responsavel'], $assunto, $mensagemResp);
        }

        // ✉ E-mail para o técnico, se houver
        if (!empty($row['email_exec'])) {
            $mensagemExec = "
                <p>Olá <strong>{$row['nome_exec']}</strong>,</p>
                <p>A OS <strong>\"{$row['titulo']}\"</strong> atribuída a você foi removida do sistema.</p>
                <p>Atenciosamente,<br>Sistema de Manutenção</p>
            ";
            enviarEmailUsuario($row['email_exec'], $assunto, $mensagemExec);
        }

        // Após e-mails, deletar a OS
        $delete = $conn->prepare("DELETE FROM manutencao WHERE titulo = ?");
        $delete->bind_param("s", $titulo);
        if ($delete->execute()) {
            $_SESSION['sucesso'] = "Ordem de serviço excluída com sucesso.";
        } else {
            $_SESSION['erro'] = "Erro ao excluir a OS.";
        }

        $delete->close();
    } else {
        $_SESSION['erro'] = "OS não encontrada para exclusão.";
    }

    $stmt->close();
    header("Location: panelTecnico.php");
    exit();
}
?>
