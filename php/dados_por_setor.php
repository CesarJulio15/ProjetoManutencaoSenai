<?php
include 'conexao.php';

$ano = isset($_GET['ano']) && is_numeric($_GET['ano']) ? (int)$_GET['ano'] : date('Y');

$stmt = $conn->prepare("
  SELECT bloco, COUNT(*) as total
  FROM manutencao
  WHERE bloco IS NOT NULL
    AND YEAR(data_abertura) = ?
  GROUP BY bloco
");
$stmt->bind_param('i', $ano);
$stmt->execute();
$res = $stmt->get_result();

$dados = [];
while ($row = $res->fetch_assoc()) {
    $dados[] = $row;
}

header('Content-Type: application/json');
echo json_encode($dados);
