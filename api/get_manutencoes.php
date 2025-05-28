<?php
session_start();
if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Não autorizado"]);
    exit;
}
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../php/conexao.php';

$sql = "
SELECT
  m.id,
  m.numero_os,
  m.titulo,
  m.descricao,
  m.localizacao_ambiente,
  d.nome AS responsavel_nome,
  d.nif AS responsavel_nif,
  m.status,
  m.tipo_manutencao,
  m.origem,
  m.data_abertura,
  m.data_encerramento,
  m.executante,
  m.prioridade,
  m.bloco
FROM manutencao m
JOIN docentes d ON m.responsavel = d.id";

$result = $conn->query($sql);
$manutencoes = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $manutencoes[] = $row;
    }
    echo json_encode($manutencoes, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} else {
    echo json_encode(["mensagem" => "Nenhuma ordem de serviço encontrada"]);
}
