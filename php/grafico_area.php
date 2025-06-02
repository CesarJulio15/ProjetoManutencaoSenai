<?php
error_reporting(0); // ou use E_ERROR | E_PARSE
ini_set('display_errors', 0);
header('Content-Type: application/json');
include 'conexao.php';

function obterDadosPrioridadeStatus($conn, $ano, $mes = null) {
    if ($mes) {
        $sql = "
            SELECT 
                DATE_FORMAT(data_abertura, '%Y-%m-%d') AS periodo,
                prioridade,
                status,
                COUNT(*) AS total
            FROM manutencao
            WHERE YEAR(data_abertura) = ? AND MONTH(data_abertura) = ?
            GROUP BY periodo, prioridade, status
            ORDER BY periodo ASC
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $ano, $mes);
    } else {
        $sql = "
            SELECT 
                DATE_FORMAT(data_abertura, '%Y-%m') AS periodo,
                prioridade,
                status,
                COUNT(*) AS total
            FROM manutencao
            WHERE YEAR(data_abertura) = ?
            GROUP BY periodo, prioridade, status
            ORDER BY periodo ASC
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $ano);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $dados = [];
    $periodos = [];

    while ($row = $result->fetch_assoc()) {
        $periodo = $row['periodo'];
        $prioridade = $row['prioridade'];
        $status = $row['status'];
        $total = (int)$row['total'];

        if (!in_array($periodo, $periodos)) {
            $periodos[] = $periodo;
        }

        $chave = "$prioridade - $status";
        if (!isset($dados[$chave])) {
            $dados[$chave] = [];
        }
        $dados[$chave][$periodo] = $total;
    }

    foreach ($dados as &$serie) {
        foreach ($periodos as $p) {
            if (!isset($serie[$p])) {
                $serie[$p] = 0;
            }
        }
        ksort($serie);
    }
    unset($serie);

    return [$periodos, $dados];
}

$anoSelecionado = (int)($_GET['ano'] ?? date('Y'));
$mesSelecionado = isset($_GET['mes']) ? (int)$_GET['mes'] : null;

list($labels, $dados) = obterDadosPrioridadeStatus($conn, $anoSelecionado, $mesSelecionado);
echo json_encode(['labels' => $labels, 'dados' => $dados]);
