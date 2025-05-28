<?php
session_start();
if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Não autorizado"]);
    exit;
}

header("Content-Type: application/json; charset=UTF-8");
require_once '../php/conexao.php';

$sql = "
    SELECT d.nome,
      SUM(CASE WHEN m.status = 'Pendente' THEN 1 ELSE 0 END) AS pendente,
      SUM(CASE WHEN m.status = 'Em Andamento' THEN 1 ELSE 0 END) AS andamento,
      SUM(CASE WHEN m.status = 'Concluído' THEN 1 ELSE 0 END) AS concluido
    FROM docentes d
    LEFT JOIN manutencao m ON d.id = m.executante
    WHERE d.tipo = 'Técnico'
    GROUP BY d.nome
    ORDER BY d.nome
";

$res = $conn->query($sql);
$atendentes = [];

while ($row = $res->fetch_assoc()) {
    $atendentes[] = $row;
}

echo json_encode($atendentes, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
