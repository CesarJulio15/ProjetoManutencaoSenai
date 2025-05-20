<?php
session_start();
if (!isset($_SESSION['id'])) {
    header("Location: index.php");
    exit();
}

require 'conexao.php';
require 'enviar_email.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['numero_os'], $_POST['status'])) {
    $numero_os = trim($_POST['numero_os']);
    $status = trim($_POST['status']);

    // Verifica se a OS existe e obtém status atual
    $verifica = $conn->prepare("SELECT status FROM manutencao WHERE numero_os = ?");
    $verifica->bind_param("s", $numero_os);
    $verifica->execute();
    $resultado = $verifica->get_result();

    if ($resultado->num_rows === 0) {
        echo "Número da OS não encontrado.";
        exit();
    }

    $dados = $resultado->fetch_assoc();
    if ($dados['status'] === $status) {
        echo "O status já está definido como <strong>$status</strong>.";
        exit();
    }

    // Atualiza o status e define data_encerramento se necessário
    $sql = "
        UPDATE manutencao
           SET status = ?,
               data_encerramento = IF(? = 'Concluído', CURDATE(), NULL)
         WHERE numero_os = ?
    ";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die('Erro ao preparar a consulta: ' . $conn->error);
    }

    $stmt->bind_param('sss', $status, $status, $numero_os);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo "Status atualizado com sucesso!";

            // Buscar dados do executante e do responsável para e-mail
            $sql = "
                SELECT 
                    m.titulo,
                    d_exec.nome AS nome_exec, d_exec.email AS email_exec,
                    d_resp.nome AS nome_resp, d_resp.email AS email_resp
                FROM manutencao m
                LEFT JOIN docentes d_exec ON m.executante = d_exec.id
                LEFT JOIN docentes d_resp ON m.responsavel = d_resp.id
                WHERE m.numero_os = ?
            ";
            $buscar = $conn->prepare($sql);
            $buscar->bind_param("s", $numero_os);
            $buscar->execute();
            $result = $buscar->get_result();

            if ($row = $result->fetch_assoc()) {
                $assunto = "Atualização de Status: {$row['titulo']}";
                $dataHoje = date("d/m/Y");

                // Email para EXECUTANTE
                if (!empty($row['email_exec'])) {
                    $msgExec = "
                        <p>Olá <strong>{$row['nome_exec']}</strong>,</p>
                        <p>O status da OS <strong>\"{$row['titulo']}\"</strong> foi atualizado para <strong>$status</strong> em <strong>$dataHoje</strong>.</p>
                        <p>Atenciosamente,<br>Sistema de Manutenção</p>
                    ";
                    enviarEmailUsuario($row['email_exec'], $assunto, $msgExec);
                }

                // Email para RESPONSÁVEL
                if (!empty($row['email_resp'])) {
                    $msgResp = "
                        <p>Olá <strong>{$row['nome_resp']}</strong>,</p>
                        <p>A OS <strong>\"{$row['titulo']}\"</strong> que você abriu foi atualizada para o status <strong>$status</strong> em <strong>$dataHoje</strong>.</p>
                        <p>Atenciosamente,<br>Sistema de Manutenção</p>
                    ";
                    enviarEmailUsuario($row['email_resp'], $assunto, $msgResp);
                }
            }
        } else {
            echo "Nenhuma linha foi alterada. Verifique os dados enviados.";
        }
    } else {
        echo "Erro ao atualizar o status: " . $stmt->error;
    }

    $stmt->close();
} else {
    echo "Dados inválidos.";
}

$conn->close();
?>
