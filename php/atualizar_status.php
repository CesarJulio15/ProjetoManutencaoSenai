<?php
include 'conexao.php'; // Certifique que a conexão ($conn) está funcionando

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['titulo'], $_POST['status'])) {
    $titulo = $_POST['titulo'];
    $status = $_POST['status'];

    $sql = "UPDATE manutencao SET status = ? WHERE titulo = ?";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("Erro ao preparar a consulta: " . $conn->error);
    }

    $stmt->bind_param("ss", $status, $titulo);

    if ($stmt->execute()) {
        echo "Status atualizado com sucesso!";
    } else {
        echo "Erro ao atualizar o status.";
    }

    $stmt->close();
} else {
    echo "Dados inválidos.";
}
$conn->close();
?>
