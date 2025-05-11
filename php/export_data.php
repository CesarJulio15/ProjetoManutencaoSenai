<?php
session_start();
// Restrição de acesso
if (!isset($_SESSION['id']) || $_SESSION['tipo'] !== 'coordenador') {
    header("Location: index.php");
    exit();
}

require_once 'conexao.php';

// Configura cabeçalhos para download no formato Excel
header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment;filename="dados_manutencao.xls"');
// BOM para UTF-8
echo "\xEF\xBB\xBF";

// Consulta dos dados de manutenção
$sql = "
SELECT
    m.titulo,
    m.descricao,
    d1.nome AS responsavel,
    IFNULL(d2.nome, '') AS executante,
    m.status,
    m.data_abertura,
    m.data_encerramento
FROM manutencao m
JOIN docentes d1 ON m.responsavel = d1.id
LEFT JOIN docentes d2 ON m.executante = d2.id
";
$result = $conn->query($sql);

// Gera tabela HTML que o Excel aceitará
echo "<table border='1'>";
echo "<tr>";
echo "<th>Título</th>";
echo "<th>Descrição</th>";
echo "<th>Responsável</th>";
echo "<th>Executante</th>";
echo "<th>Status</th>";
echo "<th>Data Abertura</th>";
echo "<th>Data Fechamento</th>";
echo "</tr>";

while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['titulo']) . "</td>";
    echo "<td>" . htmlspecialchars($row['descricao']) . "</td>";
    echo "<td>" . htmlspecialchars($row['responsavel']) . "</td>";
    echo "<td>" . htmlspecialchars($row['executante']) . "</td>";
    echo "<td>" . htmlspecialchars($row['status']) . "</td>";
    echo "<td>" . $row['data_abertura'] . "</td>";
    echo "<td>" . ($row['data_encerramento'] ?? '') . "</td>";
    echo "</tr>";
}

echo "</table>";
exit;
