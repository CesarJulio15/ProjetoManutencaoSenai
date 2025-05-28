<?php
session_start();
if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Não autorizado"]);
    exit;
}
header("Content-Type: application/json");

require_once '../php/conexao.php';

$hoje = date('Y-m-d');

// Totais por status hoje
$totaisHoje = ["Pendente"=>0,"Em Andamento"=>0,"Concluído"=>0];
$sqlHoje = "
   SELECT status, COUNT(*) AS total
   FROM manutencao
   WHERE (status = 'Concluído' AND data_encerramento = ?)
      OR (status <> 'Concluído' AND data_abertura = ?)
   GROUP BY status";
$stmt = $conn->prepare($sqlHoje);
$stmt->bind_param("ss", $hoje, $hoje);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $totaisHoje[$row['status']] = (int)$row['total'];
}
$stmt->close();
$totalHoje = array_sum($totaisHoje);

// SLA
$sqlSLA = "
SELECT COUNT(*) as total,
       SUM(CASE WHEN DATEDIFF(data_encerramento, data_abertura) <= 3 THEN 1 ELSE 0 END) as dentro_prazo
FROM manutencao
WHERE status = 'Concluído' AND data_encerramento IS NOT NULL
";
$resSLA = $conn->query($sqlSLA);
$rowSLA = $resSLA->fetch_assoc();
$sla = $rowSLA['total'] > 0 ? round(($rowSLA['dentro_prazo'] / $rowSLA['total']) * 100, 2) : 0;

echo json_encode([
  "totaisHoje" => $totaisHoje,
  "sla" => $sla,
  "totalHoje" => $totalHoje, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
]);
