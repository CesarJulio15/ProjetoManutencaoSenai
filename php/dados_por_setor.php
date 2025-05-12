<?php
    session_start();
    if (!isset($_SESSION['id']) || $_SESSION['tipo'] !== 'coordenador') {
        header("Location: index.php");
        exit();
    }
include 'conexao.php';
$result = $conn->query("SELECT bloco, COUNT(*) as total FROM manutencao GROUP BY bloco");
$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = ['bloco' => $row['bloco'], 'total' => (int)$row['total']];
}

header('Content-Type: application/json');
echo json_encode($data);
