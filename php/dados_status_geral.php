<?php
  session_start();
  if (!isset($_SESSION['tipo'])) {
      // redirecione ou trate o erro
      header("Location: index.php");
      exit();
  }
require_once 'conexao.php';

// Inicializa os status com zero
$dados = [
    "Pendente" => 0,
    "Em Andamento" => 0,
    "Concluído" => 0
];

// Consulta os dados reais
$sql = "SELECT status, COUNT(*) as total FROM manutencao GROUP BY status";
$result = $conn->query($sql);

while ($row = $result->fetch_assoc()) {
    if (isset($dados[$row['status']])) {
        $dados[$row['status']] = (int)$row['total'];
    }
}

header('Content-Type: application/json');
echo json_encode($dados);
