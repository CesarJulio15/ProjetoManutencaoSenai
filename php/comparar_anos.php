<?php
include 'conexao.php';

function getDadosAno($conn, $ano) {
    // Total de OS abertas no ano
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM manutencao WHERE YEAR(data_abertura) = ?");
    $stmt->bind_param("i", $ano);
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['total'] ?: 1;

    // OS Concluídas
    $stmt = $conn->prepare("SELECT COUNT(*) as concluidos FROM manutencao WHERE status = 'Concluído' AND YEAR(data_abertura) = ?");
    $stmt->bind_param("i", $ano);
    $stmt->execute();
    $concluidos = $stmt->get_result()->fetch_assoc()['concluidos'] ?: 0;

    // Tempo médio de atendimento (dias)
    $stmt = $conn->prepare("SELECT AVG(DATEDIFF(data_encerramento, data_abertura)) as media FROM manutencao WHERE YEAR(data_abertura) = ? AND data_encerramento IS NOT NULL");
    $stmt->bind_param("i", $ano);
    $stmt->execute();
    $mediaTempo = round($stmt->get_result()->fetch_assoc()['media'] ?? 0);

    // Eficiência por técnico: total concluídos / nº de executantes distintos
    $stmt = $conn->prepare("SELECT executante, COUNT(*) as total FROM manutencao WHERE YEAR(data_abertura) = ? AND executante IS NOT NULL AND status = 'Concluído' GROUP BY executante");
    $stmt->bind_param("i", $ano);
    $stmt->execute();
    $res = $stmt->get_result();
    $soma = 0;
    $tecnicos = 0;
    while ($r = $res->fetch_assoc()) {
        $soma += $r['total'];
        $tecnicos++;
    }
    $eficiencia = $tecnicos ? round($soma / $tecnicos, 1) : 0;

    return [
        'tempo_medio' => $mediaTempo,
        'total_os' => $total,
        'concluido_pct' => round(($concluidos / $total) * 100),
        'eficiencia_tecnico' => $eficiencia
    ];
}

// Anos disponíveis
$anosDisponiveis = [];
$res = $conn->query("SELECT DISTINCT YEAR(data_abertura) as ano FROM manutencao ORDER BY ano");
while ($row = $res->fetch_assoc()) {
    $anosDisponiveis[] = $row['ano'];
}
$ano1 = $_GET['ano1'] ?? $anosDisponiveis[0];
$ano2 = $_GET['ano2'] ?? $anosDisponiveis[1];

$dados = [
    $ano1 => getDadosAno($conn, $ano1),
    $ano2 => getDadosAno($conn, $ano2)
];
$maxTempo = 0;
$maxTotal = 0;
$maxConcluido = 0;
$maxEficiencia = 0;
foreach ($anosDisponiveis as $ano) {
    $anoDados = getDadosAno($conn, $ano);
    $maxTempo = max($maxTempo, $anoDados['tempo_medio']);
    $maxTotal = max($maxTotal, $anoDados['total_os']);
    $maxConcluido = max($maxConcluido, $anoDados['concluido_pct']);
    $maxEficiencia = max($maxEficiencia, $anoDados['eficiencia_tecnico']);
}

function barraProporcional($valor, $valorMax, $texto = '', $classe = '') {
    $largura = $valorMax > 0 ? ($valor / $valorMax) * 100 : 0;
    $largura = min($largura, 100);
    return "<div class='bar $classe' style='width:{$largura}%'>{$texto}</div>";
}

function bolhaFixa($texto, $classe = '') {
    return "<div class='bolha $classe'>{$texto}</div>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Comparação de Anos</title>
    <style>
        body { font-family: Arial; background: #f8f9fa; padding: 20px; }
        .container { max-width: 900px; margin: auto; background: white; padding: 30px; border-radius: 10px; }
        .row {
        display: flex;
        align-items: center;
        margin: 15px 0;
        }

        .col {
        width: 40%;
        display: flex;
        justify-content: flex-end;
        }

        .col-right {
        width: 40%;
        display: flex;
        justify-content: flex-start;
        }

        .label {
        width: 20%;
        text-align: center;
        font-weight: bold;
        }

        .bar {
        display: inline-block;
        padding: 10px;
        border-radius: 30px;
        color: white;
        text-align: center;
        min-width: 80px;
        font-weight: bold;
        }

        .bar-round {
        border-radius: 50%;
        min-width: 60px;
        height: 60px;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 0;
        }

        .blue { background-color: #0277bd; }
        .green { background-color: #8bc34a; }
        .label { width: 20%; text-align: center; font-weight: bold; padding:10px 20px;}
        select { padding: 5px; }
        .insights { margin-top: 30px; background: #eef; padding: 15px; border-left: 5px solid #2196f3; }
        .bolha {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  background-color: #ccc;
  color: white;
  font-weight: bold;
  display: flex;
  justify-content: center;
  align-items: center;
  text-align: center;
}
.bolha.blue {
  background-color: #0277bd;
}
.bolha.green {
  background-color: #8bc34a;
}
    </style>
</head>
<body>
<div class="container">
    <h1>Comparação de Desempenho Anual</h1>
    <form method="get" style="text-align:center; margin-bottom:20px;">
        <label for="ano1">Ano 1: </label>
        <select name="ano1" id="ano1">
            <?php foreach ($anosDisponiveis as $ano): ?>
                <option value="<?= $ano ?>" <?= $ano1 == $ano ? 'selected' : '' ?>><?= $ano ?></option>
            <?php endforeach; ?>
        </select>
        <label for="ano2">Ano 2: </label>
        <select name="ano2" id="ano2">
            <?php foreach ($anosDisponiveis as $ano): ?>
                <option value="<?= $ano ?>" <?= $ano2 == $ano ? 'selected' : '' ?>><?= $ano ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Comparar</button>
    </form>
<!-- Tempo Médio -->
<div class="row">
  <div class="col"><?= barraProporcional($dados[$ano1]['tempo_medio'], $maxTempo, "{$dados[$ano1]['tempo_medio']} dias", 'blue') ?></div>
  <div class="label">Tempo Médio</div>
  <div class="col-right">  <?= barraProporcional($dados[$ano2]['tempo_medio'], $maxTempo, "{$dados[$ano2]['tempo_medio']} dias", 'green') ?></div>
</div>

<!-- Total de OS -->
<div class="row">
  <div class="col"><?= barraProporcional($dados[$ano1]['total_os'], $maxTotal, "{$dados[$ano1]['total_os']} OS", 'blue') ?></div>
  <div class="label">Total de OS</div>
  <div class="col-right"><?= barraProporcional($dados[$ano2]['total_os'], $maxTotal, "{$dados[$ano2]['total_os']} OS", 'green') ?></div>
</div>

<!-- % Concluídas -->
<div class="row">
  <div class="col"><?= bolhaFixa("{$dados[$ano1]['concluido_pct']}%", 'blue') ?></div>
  <div class="label">% Concluídas</div>
  <div class="col-right"><?= bolhaFixa("{$dados[$ano2]['concluido_pct']}%", 'green') ?></div>
</div>

<!-- Eficiência Técnica -->
<div class="row">
  <div class="col"><?= bolhaFixa("{$dados[$ano1]['eficiencia_tecnico']} OS", 'blue') ?></div>
  <div class="label">Eficiência Técnica</div>
  <div class="col-right"><?= bolhaFixa("{$dados[$ano2]['eficiencia_tecnico']} OS", 'green') ?></div>
</div>
    <div class="insights">
  <strong>Resumo:</strong>
  <ul>
    <?php if ($ano1 == $ano2): ?>
      <li>Comparando o ano de <b><?= $ano1 ?></b> consigo mesmo.</li>
      <li>Todos os dados apresentados referem-se ao mesmo período.</li>
    <?php else: ?>
      <li>Comparando <?= $ano1 ?> com <?= $ano2 ?>.</li>
      <li>Tempo médio de atendimento foi menor em <b><?= $dados[$ano1]['tempo_medio'] < $dados[$ano2]['tempo_medio'] ? $ano1 : $ano2 ?></b>.</li>
      <li>Eficiência técnica maior em <b><?= $dados[$ano1]['eficiencia_tecnico'] > $dados[$ano2]['eficiencia_tecnico'] ? $ano1 : $ano2 ?></b>.</li>
    <?php endif; ?>
  </ul>
</div>
</div>
</body>
</html>
