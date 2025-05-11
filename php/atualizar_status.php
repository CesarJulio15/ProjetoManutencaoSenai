<?php
if (!isset($_SESSION['id'])) {
    header("Location: index.php");
    exit();
}
include 'conexao.php'; // Certifique que a conexão ($conn) está funcionando

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
    } else {
        echo "Erro ao atualizar o status: " . $stmt->error;
    }

    $stmt->close();
} else {
    echo "Dados inválidos.";
}

$conn->close();
?>