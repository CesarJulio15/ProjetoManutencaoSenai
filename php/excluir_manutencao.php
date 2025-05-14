<?php
session_start();
include 'conexao.php';
require_once 'enviar_email.php';

if (!isset($_SESSION['id']) || !in_array($_SESSION['tipo'], ['coordenador', 'técnico'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['titulo'])) {
    $titulo = $_POST['titulo'];

    // Buscar dados da OS antes de excluir
    $sql = "
        SELECT m.titulo, m.numero_os,
               d1.nome AS nome_responsavel, d1.email AS email_responsavel,
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
        $assunto = "OS Excluída: {$row['titulo']}";

        // Enviar para o responsável
        if (!empty($row['email_responsavel'])) {
            $mensagemResp = "
                <p>Olá <strong>{$row['nome_responsavel']}</strong>,</p>
                <p>A ordem de serviço <strong>\"{$row['titulo']}\"</strong> (OS nº <strong>{$numero_os}</strong>) foi removida do sistema.</p>
                <p>Se isso foi um engano, por favor, entre em contato com a coordenação.</p>
                <p>Atenciosamente,<br>Sistema de Manutenção</p>
            ";
            enviarEmailUsuario($row['email_responsavel'], $assunto, $mensagemResp);
        }

        // Enviar para o técnico, se houver
        if (!empty($row['email_exec'])) {
            $mensagemExec = "
                <p>Olá <strong>{$row['nome_exec']}</strong>,</p>
                <p>A ordem de serviço <strong>\"{$row['titulo']}\"</strong> que estava atribuída a você foi excluída do sistema.</p>
                <p>Se necessário, entre em contato com a coordenação para mais informações.</p>
                <p>Atenciosamente,<br>Sistema de Manutenção</p>
            ";
            enviarEmailUsuario($row['email_exec'], $assunto, $mensagemExec);
        }

        // Executar a exclusão
        $delete = $conn->prepare("DELETE FROM manutencao WHERE titulo = ?");
        $delete->bind_param("s", $titulo);
        if ($delete->execute()) {
            $_SESSION['sucesso'] = "Ordem de serviço excluída com sucesso.";
        } else {
            $_SESSION['erro'] = "Erro ao excluir a ordem de serviço.";
        }

        $delete->close();
    } else {
        $_SESSION['erro'] = "Ordem de serviço não encontrada.";
    }

    $stmt->close();
    header("Location: panelTecnico.php");
    exit();
}
?>
