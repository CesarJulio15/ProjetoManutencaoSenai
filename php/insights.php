<?php
  session_start();
  if (!isset($_SESSION['tipo'])) {
      // redirecione ou trate o erro
      header("Location: index.php");
      exit();
  }
  include 'conexao.php';
  $tipo = $_SESSION['tipo'];

// Obter anos disponíveis
$sqlAnos = "SELECT DISTINCT YEAR(data_abertura) as ano FROM manutencao ORDER BY ano DESC";
$resultAnos = $conn->query($sqlAnos);

$anosDisponiveis = [];
if ($resultAnos && $resultAnos->num_rows > 0) {
    while ($row = $resultAnos->fetch_assoc()) {
        $anosDisponiveis[] = $row['ano'];
    }
}
if (empty($anosDisponiveis)) {
    $anosDisponiveis = ['2021', '2022'];
}

// Definir anos para comparação (padrão ou selecionados pelo usuário)
$ano1 = isset($_POST['ano1']) && is_numeric($_POST['ano1']) ? (int)$_POST['ano1'] : (int)$anosDisponiveis[0];
$ano2 = isset($_POST['ano2']) && is_numeric($_POST['ano2']) ? (int)$_POST['ano2'] : ((count($anosDisponiveis) > 1) ? (int)$anosDisponiveis[1] : (int)$anosDisponiveis[0]);

// Inicializar valores padrão caso não haja dados
$tempoCiclo1 = $tempoCiclo2 = 1;
$eficiencia1 = $eficiencia2 = 1;
$eficacia1 = $eficacia2 = 1;
$produtividade1 = $produtividade2 = 1;


try {
    // Função auxiliar para executar uma query segura com 1 parâmetro de ano
    function fetchSingleValue($conn, $sql, $ano) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $ano);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // Consultas com segurança
    $row = fetchSingleValue($conn, "SELECT AVG(DATEDIFF(data_encerramento, data_abertura)) as tempo_medio FROM manutencao WHERE YEAR(data_abertura) = ? AND data_encerramento IS NOT NULL", $ano1);
    if ($row && $row['tempo_medio'] !== null) $tempoCiclo1 = round($row['tempo_medio']);

    $row = fetchSingleValue($conn, "SELECT AVG(DATEDIFF(data_encerramento, data_abertura)) as tempo_medio FROM manutencao WHERE YEAR(data_abertura) = ? AND data_encerramento IS NOT NULL", $ano2);
    if ($row && $row['tempo_medio'] !== null) $tempoCiclo2 = round($row['tempo_medio']);

    $row = fetchSingleValue($conn, "SELECT (COUNT(CASE WHEN status = 'Concluído' THEN 1 END) / COUNT(*)) * 100 as eficiencia FROM manutencao WHERE YEAR(data_abertura) = ?", $ano1);
    if ($row && $row['eficiencia'] !== null) $eficiencia1 = round($row['eficiencia']);

    $row = fetchSingleValue($conn, "SELECT (COUNT(CASE WHEN status = 'Concluído' THEN 1 END) / COUNT(*)) * 100 as eficiencia FROM manutencao WHERE YEAR(data_abertura) = ?", $ano2);
    if ($row && $row['eficiencia'] !== null) $eficiencia2 = round($row['eficiencia']);

    $row = fetchSingleValue($conn, "SELECT (COUNT(CASE WHEN tipo_manutencao = 'Preventiva' THEN 1 END) / COUNT(*)) * 100 as eficacia FROM manutencao WHERE YEAR(data_abertura) = ?", $ano1);
    if ($row && $row['eficacia'] !== null) $eficacia1 = round($row['eficacia']);

    $row = fetchSingleValue($conn, "SELECT (COUNT(CASE WHEN tipo_manutencao = 'Preventiva' THEN 1 END) / COUNT(*)) * 100 as eficacia FROM manutencao WHERE YEAR(data_abertura) = ?", $ano2);
    if ($row && $row['eficacia'] !== null) $eficacia2 = round($row['eficacia']);

    // PRODUTIVIDADE com subquery
    function fetchProdutividade($conn, $ano) {
        $sql = "
        SELECT AVG(total) AS produtividade FROM (
            SELECT COUNT(*) AS total
            FROM manutencao
            WHERE YEAR(data_abertura) = ?
            AND executante IS NOT NULL
            AND status = 'Concluído'
            GROUP BY executante
        ) AS sub";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $ano);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    $row = fetchProdutividade($conn, $ano1);
    if ($row && $row['produtividade'] !== null) $produtividade1 = round($row['produtividade']);

    $row = fetchProdutividade($conn, $ano2);
    if ($row && $row['produtividade'] !== null) $produtividade2 = round($row['produtividade']);

} catch (Exception $e) {
    error_log("Erro ao consultar dados: " . $e->getMessage());
}

// Cálculo de percentuais
$maxTempoCiclo = max($tempoCiclo1, $tempoCiclo2);
$maxProdutividade = max($produtividade1, $produtividade2);
$maxEficiencia = $maxEficacia = 100;

$tempoCiclo1Percent = ($tempoCiclo1 / $maxTempoCiclo) * 100;
$tempoCiclo2Percent = ($tempoCiclo2 / $maxTempoCiclo) * 100;
$eficiencia1Percent = ($eficiencia1 / $maxEficiencia) * 100;
$eficiencia2Percent = ($eficiencia2 / $maxEficiencia) * 100;
$eficacia1Percent = ($eficacia1 / $maxEficacia) * 100;
$eficacia2Percent = ($eficacia2 / $maxEficacia) * 100;
$produtividade1Percent = ($produtividade1 / $maxProdutividade) * 100;
$produtividade2Percent = ($produtividade2 / $maxProdutividade) * 100;

// Geração de insights
$insights = [];
$insights[] = $tempoCiclo2 > $tempoCiclo1
    ? "Tempo médio de manutenção aumentou em $ano2 comparado a $ano1"
    : "Tempo médio de manutenção diminuiu em $ano2 comparado a $ano1";

$insights[] = $eficiencia2 > $eficiencia1
    ? "Eficiência de conclusão de tarefas melhorou em $ano2"
    : "Eficiência de conclusão de tarefas diminuiu em $ano2";

$insights[] = $eficacia2 > $eficacia1
    ? "Percentual de manutenções preventivas aumentou em $ano2"
    : "Percentual de manutenções preventivas diminuiu em $ano2";
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard de Desempenho de Manutenção</title>
    <link rel="stylesheet" href="../css/padrao.css">
    <link rel="stylesheet" href="../css/insights.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js" integrity="sha512-CQBWl4fJHWbryGE+Pc7UAxWMUMNMWzWxF4SQo9CgkJIN1kx6djDQZjh3Y8SZ1d+6I+1zze6Z7kHXO7q3UyZAWw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/js/all.min.js" integrity="sha512-b+nQTCdtTBIRIbraqNEwsjB6UvL3UEMkXnhzd8awtCYh0Kcsjl9uEgwVFVbhoj3uu1DO1ZMacNvLoyJJiNfcvg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <style>
            form{
                flex-direction: row;
                background: transparent;
                align-items: flex-end;
            }
            header{
                height: 80px;
            }
            .card{
                background: #f8f9fa;
            }
            main {
            width: 100%;
            max-width: 100vw;
            overflow-x: hidden;
            }

        </style>
</head>
<body class="d-flex flex-md-row w-100 h-100">
<aside class="sidebar animate__animated animate__fadeInLeft">
            <img src="../img/LogoPortalSenai.svg" alt="Portal Senai Manutenções" class="logo-senai">
            <div class="spacer"></div>
            <img src="../img/TechFix.svg" alt="Techfix Facilities" class="logo-techfix">
        </aside>
<main class="main-content d-flex flex-md-column w-100 h-100">

<header class="top-bar mt-2 animate__animated animate__fadeInDown" style="height: 80px;">
            <a href="panelTecnico.php" class="botao-voltar" title="Voltar"><i class="fa-solid fa-arrow-left"></i></a>
            <h2 class="title text-center mb-4">Dashboard de Desempenho de Manutenção</h2>
            <div class="welcome-msg me-3 text-dark fw-bold">
              <a href="editar_funcionario.php?id=<?= $_SESSION['id'] ?>" style="text-decoration: none; color: #212529;" title="Editar meus dados" class="username">
                  <?= htmlspecialchars($_SESSION['nome']) ?>
              </a><br>
                <span style="font-weight: 300; font-size: 0.85rem; color: #6c757d;">
                    <?php echo htmlspecialchars($_SESSION['tipo']); ?>
                </span>
            </div>
            <div class="logout-btn">
                <a href="logout.php" title="Sair">
                    <img src="../img/logout.svg" alt="Logout" style="width: 24px; height: 24px;">
                </a>
            </div>
</header>
<section class="card">
<div class="year-selector animate__animated animate__fadeInRight" style="box-shadow: none; background: transparent;">
        <div>
            <h2 class="title">Comparação de Cenários</h2>
            <p class="subtitle">Este dashboard está vinculado ao banco de dados e muda automaticamente com base nos dados. Selecione os anos para comparação.</p>
        </div>
        <form method="post" action="" class="d-flex flex-row form-inline" style="background: transparent; box-shadow: none;">
            <div>
                <label for="ano1">Primeiro Ano:</label>
                <select name="ano1" id="ano1">
                    <?php foreach ($anosDisponiveis as $anoOpcao): ?>
                        <option value="<?php echo $anoOpcao; ?>" <?php echo ($anoOpcao == $ano1) ? 'selected' : ''; ?>>
                            <?php echo $anoOpcao; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label for="ano2">Segundo Ano:</label>
                <select name="ano2" id="ano2">
                    <?php foreach ($anosDisponiveis as $anoOpcao): ?>
                        <option value="<?php echo $anoOpcao; ?>" <?php echo ($anoOpcao == $ano2) ? 'selected' : ''; ?>>
                            <?php echo $anoOpcao; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <button type="submit" title="Atualizar Comparação"><i class="fa-solid fa-rotate"></i> </button>
        </form>
    </div>

    <div class="years-container">
        <div class="year-column animate__animated animate__fadeInLeft">
        <?php if ($ano1 != $ano2): ?>
            <div class="year-label year-previous">
                <div class="year-border"></div>
                <span><?php echo $ano1; ?></span>
            </div>
            <?php endif; ?>
        </div>

        <div class="year-column animate__animated animate__fadeInRight">
            <div class="year-label year-next">
                <div class="year-border"></div>
                <span><?php echo $ano2; ?></span>
            </div>
        </div>

    </div>

    <div class="metrics-container animate__animated animate__fadeInBottomRight">
        <!-- Tempo de Ciclo de Manutenção -->
        <div class="metric-row">
        <?php if ($ano1 != $ano2): ?>
            <div class="left-metric">
                <div class="slider-container">
                    <div class="slider-track teal-track" style="width: <?php echo $tempoCiclo1Percent; ?>%">
                        <div class="slider-circle">
                            <span><?php echo $tempoCiclo1; ?> Dias</span>
                             
                        </div>
                    </div>
                    <div class="slider-dot"></div>
                </div>
            </div>
            <?php endif; ?>
            <div class="center-metric">
                <div class="icon-container">
                <i class="fa-solid fa-clock"></i>
                </div>
                <div class="metric-label">
                    <p>Tempo de Manutenção</p>
                </div>
            </div>

            <div class="right-metric">
                <div class="slider-container">
                    <div class="slider-track green-track" style="width: <?php echo $tempoCiclo2Percent; ?>%">
                        <div class="slider-circle green-circle">
                            <span><?php echo $tempoCiclo2; ?> Dias</span>

                        </div>
                    </div>
                    <div class="slider-dot"></div>
                </div>
            </div>

        </div>

        <!-- Eficácia (% Preventivas) -->
        <div class="metric-row">
        <?php if ($ano1 != $ano2): ?>
            <div class="left-metric">
                <div class="slider-container">
                    <div class="slider-track teal-track" style="width: <?php echo $eficacia1Percent; ?>%">
                        <div class="slider-circle">
                            <span><?php echo $eficacia1; ?>%</span>
                             
                        </div>
                    </div>
                    <div class="slider-dot"></div>
                </div>
            </div>
            <?php endif; ?>
            <div class="center-metric">
                <div class="icon-container">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="metric-label">
                    <p>Manutenções Preventivas</p>
                </div>
            </div>

            <div class="right-metric">
                <div class="slider-container">
                    <div class="slider-track green-track" style="width: <?php echo $eficacia2Percent; ?>%">
                        <div class="slider-circle green-circle">
                            <span><?php echo $eficacia2; ?>%</span>
                             
                        </div>
                    </div>
                    <div class="slider-dot"></div>
                </div>
            </div>

        </div>

        <!-- Eficiência de Tarefas -->
        <div class="metric-row">
        <?php if ($ano1 != $ano2): ?>
            <div class="left-metric">
                <div class="slider-container">
                    <div class="slider-track teal-track" style="width: <?php echo $eficiencia1Percent; ?>%">
                        <div class="slider-circle">
                            <span><?php echo $eficiencia1; ?>%</span>
                             
                        </div>
                    </div>
                    <div class="slider-dot"></div>
                </div>
            </div>
            <?php endif; ?>
            <div class="center-metric">
                <div class="icon-container">
                    <i class="fas fa-cogs"></i>
                </div>
                <div class="metric-label">
                    <p>Eficiência de Conclusão</p>
                </div>
            </div>

            <div class="right-metric">
                <div class="slider-container">
                    <div class="slider-track green-track" style="width: <?php echo $eficiencia2Percent; ?>%">
                        <div class="slider-circle green-circle">
                            <span><?php echo $eficiencia2; ?>%</span>
                             
                        </div>
                    </div>
                    <div class="slider-dot"></div>
                </div>
            </div>

        </div>

        <!-- Produtividade -->
        <div class="metric-row">
        <?php if ($ano1 != $ano2): ?>
            <div class="left-metric">
                <div class="slider-container">
                    <div class="slider-track teal-track" style="width: <?php echo $produtividade1Percent; ?>%">
                        <div class="slider-circle">
                            <span><?php echo $produtividade1; ?></span>
                             
                        </div>
                    </div>
                    <div class="slider-dot"></div>
                </div>
            </div>
            <?php endif; ?>
            <div class="center-metric">
                <div class="icon-container">
                    <i class="fas fa-users"></i>
                </div>
                <div class="metric-label">
                    <p>Manutenções por Técnico</p>
                </div>
            </div>

            <div class="right-metric">
                <div class="slider-container">
                    <div class="slider-track green-track" style="width: <?php echo $produtividade2Percent; ?>%">
                        <div class="slider-circle green-circle">
                            <span><?php echo $produtividade2; ?></span>
                             
                        </div>
                    </div>
                    <div class="slider-dot"></div>
                </div>
            </div>

        </div>
    </div>

    <div class="insights-container d-flex flex-column">
        <div class="insights-header w-25 animate__animated animate__fadeInBottomLeft">
            <div class="insights-border"></div>
            <span>Insights</span>
        </div>
        <div class="insights-content animate__animated animate__fadeInBottomLeft">
        <?php if ($ano1 == $ano2): ?>
            <p><i class="fas fa-angle-right"></i>Comparando o ano de <b><?= $ano1 ?></b> consigo mesmo.</p>
            <p><i class="fas fa-angle-right"></i>Todos os dados apresentados referem-se ao mesmo período.</p>
            <?php else: ?>
            <?php foreach ($insights as $insight): ?>
                <p><i class="fas fa-angle-right"></i> <?php echo $insight; ?></p>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

</main>

 
</body>
</html>
