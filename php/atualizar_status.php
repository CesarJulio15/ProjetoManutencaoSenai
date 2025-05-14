<?php
session_start();
if (!isset($_SESSION['id'])) {
    header("Location: index.php");
    exit();
}

require 'conexao.php'; // Certifique que a conexão ($conn) está funcionando

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['titulo'], $_POST['status'])) {
    $titulo = $_POST['titulo'];
    $status = $_POST['status'];

    // Atualiza status e, se for "Concluído", grava data_encerramento = hoje; senão limpa o campo.
    $sql = "
        UPDATE manutencao
           SET status            = ?,
               data_encerramento = IF( ? = 'Concluído', CURDATE(), NULL)
         WHERE titulo = ?
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die('Erro ao preparar a consulta: ' . $conn->error);
    }

    // bind_param(types, status_para_status, status_para_IF, titulo)
    $stmt->bind_param('sss', $status, $status, $titulo);

    if ($stmt->execute()) {
        echo "Status atualizado com sucesso!";

        // 🔍 Buscar dados do executante e do responsável
        $sql = "
            SELECT 
                d_exec.nome AS nome_exec, d_exec.email AS email_exec,
                d_resp.nome AS nome_resp, d_resp.email AS email_resp
            FROM manutencao m
            LEFT JOIN docentes d_exec ON m.executante = d_exec.id
            LEFT JOIN docentes d_resp ON m.responsavel = d_resp.id
            WHERE m.titulo = ?
        ";
        $buscar = $conn->prepare($sql);
        $buscar->bind_param("s", $titulo);
        $buscar->execute();
        $result = $buscar->get_result();

        if ($row = $result->fetch_assoc()) {
            $assunto = "Atualização de Status: $titulo";
            $dataHoje = date("d/m/Y");

            // Email para o EXECUTANTE
            if (!empty($row['email_exec'])) {
                $msgExec = "
                    <p>Olá <strong>{$row['nome_exec']}</strong>,</p>
                    <p>O status da OS <strong>\"$titulo\"</strong> foi atualizado para <strong>$status</strong> em <strong>$dataHoje</strong>.</p>
                    <p>Atenciosamente,<br>Sistema de Manutenção</p>
                ";
                enviarEmailUsuario($row['email_exec'], $assunto, $msgExec);
            }

            // Email para o RESPONSÁVEL
            if (!empty($row['email_resp'])) {
                $msgResp = "
                    <p>Olá <strong>{$row['nome_resp']}</strong>,</p>
                    <p>A OS <strong>\"$titulo\"</strong> que você abriu foi atualizada para o status <strong>$status</strong> em <strong>$dataHoje</strong>.</p>
                    <p>Atenciosamente,<br>Sistema de Manutenção</p>
                ";
                enviarEmailUsuario($row['email_resp'], $assunto, $msgResp);
            }
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