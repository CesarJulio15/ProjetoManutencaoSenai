<?php
session_start();
if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Não autorizado"]);
    exit;
}
header("Content-Type: application/json");
require_once '../php/conexao.php';

$ano = (int)date('Y');
$abertos = [];
$concluidos = [];

for ($mes = 1; $mes <= 12; $mes++) {
    $stmtA = $conn->prepare("SELECT COUNT(*) AS cnt FROM manutencao WHERE YEAR(data_abertura) = ? AND MONTH(data_abertura) = ?");
    $stmtA->bind_param('ii', $ano, $mes);
    $stmtA->execute();
    $cntA = $stmtA->get_result()->fetch_assoc()['cnt'];
    $abertos[] = (int)$cntA;
    $stmtA->close();

    $stmtF = $conn->prepare("SELECT COUNT(*) AS cnt FROM manutencao WHERE status = 'Concluído' AND YEAR(data_encerramento) = ? AND MONTH(data_encerramento) = ?");
    $stmtF->bind_param('ii', $ano, $mes);
    $stmtF->execute();
    $cntF = $stmtF->get_result()->fetch_assoc()['cnt'];
    $concluidos[] = (int)$cntF;
    $stmtF->close();
}

echo json_encode([
  "labels" => array_map(fn($m) => str_pad($m, 2, '0', STR_PAD_LEFT), range(1, 12)),
  "abertos" => $abertos,
  "fechados" => $concluidos
]);
