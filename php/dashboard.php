<?php
  session_start();
  if (!isset($_SESSION['tipo'])) {
      // redirecione ou trate o erro
      header("Location: index.php");
      exit();
  }
  $tipo = $_SESSION['tipo'];
    
    include 'conexao.php';

    $anosDisponiveis = [];
    $sqlAnos = "SELECT DISTINCT YEAR(data_abertura) as ano FROM manutencao ORDER BY ano DESC";
    $resAnos = $conn->query($sqlAnos);
    while ($r = $resAnos->fetch_assoc()) {
        $anosDisponiveis[] = (int)$r['ano'];
    }
    $anoSelecionado = (int)($_GET['ano'] ?? date('Y'));
    // Função para buscar totais por status hoje (aberturas + conclusões)
    function buscarTotaisHoje($conn) {
      $hoje = date('Y-m-d');
      $totaisHoje = ["Pendente"=>0,"Em Andamento"=>0,"Concluído"=>0];
      $sql = "
        SELECT status, COUNT(*) AS total
            FROM manutencao
            WHERE (status = 'Concluído'   AND data_encerramento = ?)
            OR (status <> 'Concluído'  AND data_abertura      = ?)
            GROUP BY status
        ";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("ss", $hoje, $hoje);
      $stmt->execute();
      $res = $stmt->get_result();
      while ($row = $res->fetch_assoc()) {
        $totaisHoje[$row['status']] = (int)$row['total'];
      }
      $stmt->close();
      return $totaisHoje;
    }
    // Função para obter totais por status
    function obterTotaisPorStatus($conn, $ano) {
      $stmt = $conn->prepare("
        SELECT status, COUNT(*) AS total
        FROM manutencao
        WHERE YEAR(data_abertura) = ?
        GROUP BY status
      ");
      $stmt->bind_param('i', $ano);
      $stmt->execute();
      $res = $stmt->get_result();
    
      $pendentes = $emAndamento = $finalizados = $rejeitados = 0;
      while ($row = $res->fetch_assoc()) {
        switch ($row['status']) {
          case 'Pendente': $pendentes = $row['total']; break;
          case 'Em Andamento': $emAndamento = $row['total']; break;
          case 'Concluído': $finalizados = $row['total']; break;
          case 'Rejeitado': $rejeitados = $row['total']; break;
        }
      }
      $stmt->close();
      return [$pendentes, $emAndamento, $finalizados, $rejeitados];
    }
    
    // Função para obter ranking de responsáveis
    function obterRankingResponsaveis($conn, $ano) {
      $stmt = $conn->prepare("
        SELECT d.nome AS responsavel_nome, COUNT(*) as total
        FROM manutencao m
        JOIN docentes d ON m.responsavel = d.id
        WHERE YEAR(m.data_abertura) = ?
        GROUP BY d.nome
        ORDER BY total DESC
      ");
      $stmt->bind_param('i', $ano);
      $stmt->execute();
      $res = $stmt->get_result();
    
      $responsaveis = [];
      $totais = [];
      while ($r = $res->fetch_assoc()) {
        $responsaveis[] = $r['responsavel_nome'];
        $totais[] = $r['total'];
      }
      $stmt->close();
      return [$responsaveis, $totais];
    }
    function obterRankingExecutantes($conn, $limite = 5) {
          $sql = "
            SELECT d.nome AS responsavel_nome, v.total
            FROM vw_ranking_executantes v
            JOIN docentes d ON d.id = v.executante
            ORDER BY v.total DESC
            LIMIT ?
          ";
          $stmt = $conn->prepare($sql);
          $stmt->bind_param("i", $limite);
          $stmt->execute();
          $res = $stmt->get_result();
          $responsaveis = [];
          $totais = [];
          while ($row = $res->fetch_assoc()) {
            $responsaveis[] = $row['responsavel_nome'];
            $totais[] = $row['total'];
          }
          return [$responsaveis, $totais];
        }    
    // Função para calcular a média de dias de conclusão
    function calcularMediaDiasConclusao($conn, $ano) {
      $stmt = $conn->prepare("
        SELECT AVG(GREATEST(DATEDIFF(data_encerramento, data_abertura), 0)) AS media_dias
        FROM manutencao
        WHERE status = 'Concluído'
          AND data_encerramento IS NOT NULL
          AND YEAR(data_encerramento) = ?
      ");
      $stmt->bind_param('i', $ano);
      $stmt->execute();
      $res = $stmt->get_result();
      $mediaDias = 0;
    
      if ($row = $res->fetch_assoc()) {
        $mediaBruta = $row['media_dias'];
        $mediaDias = $mediaBruta !== null ? round($mediaBruta, 1) : 0;
      }
      $stmt->close();
      return $mediaDias;
    }
    
        function buscarDadosPorSemana($conn, $anos, $status, $prioridade) {
          $placeholders = implode(',', array_fill(0, count($anos), '?'));
          $tipos = str_repeat('i', count($anos)) . 'ss';
          $sql = "
              SELECT 
                  YEAR(data_abertura) AS ano,
                  WEEK(data_abertura, 1) AS semana,
                  COUNT(*) AS total
              FROM manutencao
              WHERE YEAR(data_abertura) IN ($placeholders)
                AND status = ?
                AND prioridade = ?
              GROUP BY ano, semana
              ORDER BY ano, semana
          ";
          $stmt = $conn->prepare($sql);
          $params = array_merge($anos, [$status, $prioridade]);
          $stmt->bind_param($tipos, ...$params);
          $stmt->execute();
          $res = $stmt->get_result();
      
          $dados = [];
          while ($row = $res->fetch_assoc()) {
              $dados[$row['ano']][$row['semana']] = (int)$row['total'];
          }
      
          return $dados;
      }
      
    // Função para buscar dados por mês e status (visão mensal)
    function buscarDadosMesStatus($conn, $anoSelecionado) {
      $dados = [
        'Pendente' => array_fill(0, 12, 0),
        'Em Andamento' => array_fill(0, 12, 0),
        'Concluído' => array_fill(0, 12, 0),
        'Rejeitado' => array_fill(0, 12, 0),
      ];
    
      $ano = strval($anoSelecionado);
      $res = $conn->prepare("SELECT ano_mes, status, total FROM vw_pedidos_mes_status WHERE ano_mes LIKE ?");
      $like = "$ano-%";
      $res->bind_param("s", $like);
      $res->execute();
      $resultado = $res->get_result();
    
      while ($row = $resultado->fetch_assoc()) {
        $mes = intval(substr($row['ano_mes'], 5, 2)) - 1;
        $status = $row['status'];
        if (isset($dados[$status])) {
          $dados[$status][$mes] = (int)$row['total'];
        }
      }
      $res->close();
      return $dados;
    }
    // Função para buscar aberturas agrupadas por mês para um ano específico
    function buscarAberturasPorMes($conn, $ano) {
      $resultados = array_fill(0, 12, 0);
      $sql = "
        SELECT MONTH(data_abertura) AS mes, COUNT(*) AS cnt
        FROM manutencao
        WHERE YEAR(data_abertura) = ?
        GROUP BY mes
      ";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param('i', $ano);
      $stmt->execute();
      $res = $stmt->get_result();
      while ($row = $res->fetch_assoc()) {
        $idx = (int)$row['mes'] - 1;
        $resultados[$idx] = (int)$row['cnt'];
      }
      $stmt->close();
      return $resultados;
    }
    // Função para buscar fechamentos agrupados por mês para um ano específico
    function buscarFechamentosPorMes($conn, $ano) {
      $resultados = array_fill(0, 12, 0);
      $sql = "
      SELECT MONTH(data_encerramento) AS mes, COUNT(*) AS cnt
      FROM manutencao
      WHERE status = 'Concluído'
        AND data_encerramento IS NOT NULL
        AND YEAR(data_encerramento) = ?
      GROUP BY mes
      ";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param('i', $ano);
      $stmt->execute();
      $res = $stmt->get_result();
      while ($row = $res->fetch_assoc()) {
      $idx = (int)$row['mes'] - 1;
      $resultados[$idx] = (int)$row['cnt'];
      }
      $stmt->close();
      return $resultados;
    }
    // Função para buscar tickets por atendente (executante) com paginação
    function buscarAtendentes($conn, $ano, $pagina = 1, $limite = 8) {
      $offset = ($pagina - 1) * $limite;
    
      $totalRegistros = $conn->query("
        SELECT COUNT(DISTINCT d.id) AS total 
        FROM docentes d 
        WHERE d.tipo = 'Técnico'
      ")->fetch_assoc()['total'];
      
      $totalPaginas = ceil($totalRegistros / $limite);
    
      $stmt = $conn->prepare("
        SELECT d.nome, d.nif,
          SUM(CASE WHEN m.status = 'Pendente' THEN 1 ELSE 0 END) AS pendente,
          SUM(CASE WHEN m.status = 'Em Andamento' THEN 1 ELSE 0 END) AS andamento,
          SUM(CASE WHEN m.status = 'Concluído' THEN 1 ELSE 0 END) AS concluido
        FROM docentes d
        LEFT JOIN manutencao m 
          ON d.id = m.executante AND YEAR(m.data_abertura) = ?
        WHERE d.tipo = 'Técnico'
        GROUP BY d.nome
        ORDER BY d.nome
        LIMIT ? OFFSET ?
      ");
    
      $stmt->bind_param('iii', $ano, $limite, $offset);
      $stmt->execute();
      $res = $stmt->get_result();
    
      $atendentes = [];
      while ($row = $res->fetch_assoc()) {
        $atendentes[] = $row;
      }
    
      $stmt->close();
      return [$atendentes, $totalPaginas];
    }
    
    // --- Backlog e envelhecimento ---
    function calcularBacklogAge($conn, $anoSelecionado) {
      $labelsAge = ['0–3 dias','4–7 dias','>7 dias'];
      $dataAge   = array_fill(0, count($labelsAge), 0);
    
      $sqlBacklog = "
        SELECT 
          CASE 
            WHEN DATEDIFF(CURDATE(), data_abertura) <= 3 THEN 1
            WHEN DATEDIFF(CURDATE(), data_abertura) <= 7 THEN 2
            ELSE 3
          END AS faixa,
          COUNT(*) AS total
        FROM manutencao
        WHERE status <> 'Concluído'
          AND YEAR(data_abertura) = ?
        GROUP BY faixa
      ";
      $stmt = $conn->prepare($sqlBacklog);
      $stmt->bind_param('i', $anoSelecionado);
      $stmt->execute();
      $resBacklog = $stmt->get_result();
    
      while ($r = $resBacklog->fetch_assoc()) {
        $idx = (int)$r['faixa'] - 1;
        $dataAge[$idx] = (int)$r['total'];
      }
    
      $stmt->close();
      return [$labelsAge, $dataAge];
    }
    
        // Função para calcular SLA (% concluído em <= 3 dias)
    function calcularSLA($conn, $ano) {
          $stmt = $conn->prepare("
            SELECT COUNT(*) as total,
                   SUM(CASE WHEN DATEDIFF(data_encerramento, data_abertura) <= 3 THEN 1 ELSE 0 END) as dentro_prazo
            FROM manutencao
            WHERE status = 'Concluído'
              AND data_encerramento IS NOT NULL
              AND YEAR(data_encerramento) = ?
          ");
          $stmt->bind_param('i', $ano);
          $stmt->execute();
          $res = $stmt->get_result();
          $row = $res->fetch_assoc();
          $stmt->close();
        
          return $row['total'] > 0 ? round(($row['dentro_prazo'] / $row['total']) * 100, 2) : 0;
    }
    // Função para calcular SLA por prioridade (% concluído em ≤3 dias)
    function calcularSlaPorPrioridade($conn, $prioridades, $ano) {
      $prioridadesEscapadas = array_map([$conn, 'real_escape_string'], $prioridades);
      $prioridadesIn = "'" . implode("','", $prioridadesEscapadas) . "'";
    
      $stmt = $conn->prepare("
        SELECT
          prioridade,
          SUM(CASE WHEN DATEDIFF(data_encerramento, data_abertura) <= 3 THEN 1 ELSE 0 END) AS dentro_prazo,
          COUNT(*) AS total
        FROM manutencao
        WHERE prioridade IN ($prioridadesIn)
          AND status = 'Concluído'
          AND data_encerramento IS NOT NULL
          AND YEAR(data_encerramento) = ?
        GROUP BY prioridade
      ");
      $stmt->bind_param('i', $ano);
      $stmt->execute();
      $res = $stmt->get_result();
    
      $slaPorPrioridade = array_fill(0, count($prioridades), 0);
      while ($row = $res->fetch_assoc()) {
        $idx = array_search($row['prioridade'], $prioridades);
        if ($idx !== false && $row['total'] > 0) {
          $slaPorPrioridade[$idx] = round(($row['dentro_prazo'] / $row['total']) * 100, 2);
        }
      }
      $stmt->close();
      return $slaPorPrioridade;
    }
    
    // Função para calcular médias de horas e totais por prioridade para o Cross-Trend
    function calcularCrossTrend($conn, $prioridades, $ano) {
      $prioridadesLower = array_map('mb_strtolower', $prioridades);
      $prioridadesIn = "'" . implode("','", $prioridadesLower) . "'";
    
      $stmt = $conn->prepare("
        SELECT LOWER(prioridade) as prioridade,
               AVG(TIMESTAMPDIFF(DAY, data_abertura, data_encerramento)) AS media_d,
               COUNT(*) AS tot
        FROM manutencao
        WHERE status = 'Concluído'
          AND prioridade IS NOT NULL
          AND LOWER(prioridade) IN ($prioridadesIn)
          AND YEAR(data_encerramento) = ?
        GROUP BY LOWER(prioridade)
      ");
      $stmt->bind_param('i', $ano);
      $stmt->execute();
      $res = $stmt->get_result();
    
      $mediaDias = [];
      $totaisCross = [];
      foreach ($prioridades as $p) {
        $mediaDias[$p] = 0;
        $totaisCross[$p] = 0;
      }
      while ($row = $res->fetch_assoc()) {
        $p = $row['prioridade'];
        $idx = array_search(mb_strtolower($p), array_map('mb_strtolower', $prioridades));
        if ($idx !== false) {
          $mediaDias[$prioridades[$idx]] = round((float)$row['media_d'], 1);
          $totaisCross[$prioridades[$idx]] = (int)$row['tot'];
        }
      }
      $stmt->close();
      return [
        array_values(array_map(fn($p) => $mediaDias[$p], $prioridades)),
        array_values(array_map(fn($p) => $totaisCross[$p], $prioridades))
      ];
    }
    
    // Função para obter pedidos por mês para um responsável e ano
    function obterPedidosPorMesResponsavel($conn, $responsavel, $ano) {
          $stmt = $conn->prepare("
            SELECT MONTH(data_abertura) - 1 AS idx, COUNT(*) AS total
            FROM manutencao m
            JOIN docentes d ON m.responsavel = d.id
            WHERE d.nome = ? 
             AND YEAR(m.data_abertura) = ?
            GROUP BY idx
          ");
          $stmt->bind_param('si', $responsavel, $ano);
          $stmt->execute();
          $res = $stmt->get_result();

          // preenche zeros para todos os 12 meses
          $arr = array_fill(0, 12, 0);
          while ($r = $res->fetch_assoc()) {
            $arr[(int) $r['idx']] = (int) $r['total'];
          }
          $stmt->close();
          return $arr;
    }
    // Função para obter ranking de responsáveis por status e ano
    function obterRankingPorStatus($conn, $status, $ano) {
          $stmt = $conn->prepare("
            SELECT d.nome AS label, COUNT(*) AS value
            FROM manutencao m
            JOIN docentes d ON m.responsavel = d.id
            WHERE m.status = ? 
              AND YEAR(m.data_abertura) = ?
            GROUP BY d.nome
            ORDER BY value DESC
            LIMIT 5
          ");
          $stmt->bind_param('si', $status, $ano);
          $stmt->execute();
          $res = $stmt->get_result();

          $labels = [];
          $data   = [];
          while ($r = $res->fetch_assoc()) {
            $labels[] = $r['label'];
            $data[]   = (int) $r['value'];
          }
          $stmt->close();
          return ['labels' => $labels, 'data' => $data];
    }
    // Função para buscar detalhamento diário por status em um mês/ano
    function buscarDetalhePorDia($conn, $ano, $mes) {
      $stmt = $conn->prepare("
            SELECT DAY(data_abertura) AS period, status, COUNT(*) AS total
            FROM manutencao
            WHERE YEAR(data_abertura) = ?
             AND MONTH(data_abertura) = ?
            GROUP BY period, status
            ORDER BY period
      ");
          $stmt->bind_param('ii', $ano, $mes);
          $stmt->execute();
          $res = $stmt->get_result();

          $statusList  = ['Pendente','Em Andamento','Concluído','Rejeitado'];
          $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $mes, $ano);
          $labels      = range(1, $daysInMonth);
          $datasets    = [];

          // inicializa todos os status com zeros
          foreach ($statusList as $s) {
            $datasets[$s] = array_fill(0, $daysInMonth, 0);
          }

          while ($row = $res->fetch_assoc()) {
            $idx = (int) $row['period'] - 1;
            $datasets[$row['status']][$idx] = (int) $row['total'];
          }
          $stmt->close();

          return ['labels' => $labels, 'datasets' => $datasets];
    }

    function buscarDadosYoYDetalhado($conn, $anos, $statusList, $prioridades) {
      $placeholders = implode(',', array_fill(0, count($anos), '?'));
      $tipos = str_repeat('i', count($anos));
      $sql = "
          SELECT 
              YEAR(data_abertura) AS ano,
              MONTH(data_abertura) AS mes,
              status,
              prioridade,
              COUNT(*) AS total
          FROM manutencao
          WHERE YEAR(data_abertura) IN ($placeholders)
          GROUP BY ano, mes, status, prioridade
      ";
    
      $stmt = $conn->prepare($sql);
      $stmt->bind_param($tipos, ...$anos);
      $stmt->execute();
      $res = $stmt->get_result();
    
      // Inicializa estrutura corretamente
      $dados = [];
      foreach ($anos as $a) {
          foreach ($statusList as $s) {
              foreach ($prioridades as $p) {
                  $dados[$a][$s][$p] = array_fill(1, 12, 0);
              }
          }
      }
    
      while ($row = $res->fetch_assoc()) {
          $a = $row['ano'];
          $m = (int)$row['mes'];
          $s = $row['status'];
          $p = $row['prioridade'];
          if (isset($dados[$a][$s][$p][$m])) {
              $dados[$a][$s][$p][$m] = (int)$row['total'];
          }
      }
    
      return $dados;
    }
    

    function gerarFrequenciasPalavras(mysqli $conn): array {
      $frequencias = [];
      $sql = "SELECT descricao FROM manutencao WHERE descricao IS NOT NULL";
      $res = $conn->query($sql);
  
      while ($row = $res->fetch_assoc()) {
          $desc = strtolower($row['descricao']);
          $desc = preg_replace('/[^\p{L}\p{N}\s]/u', '', $desc); // remove pontuação
          $tokens = explode(" ", $desc);
  
          foreach ($tokens as $palavra) {
              $palavra = trim($palavra);
              if (strlen($palavra) > 3) {
                  $frequencias[$palavra] = ($frequencias[$palavra] ?? 0) + 1;
              }
          }
      }
  
      // Formata para ECharts
      $palavras = [];
      foreach ($frequencias as $texto => $peso) {
          $palavras[] = ['name' => $texto, 'value' => $peso];
      }
  
      return $palavras;
  }

    // =====================
    // ENDPOINTS AJAX PARA DRILL-DOWN/UP
    // =====================
    if (isset($_GET['action'])) {
      header('Content-Type: application/json');
      $action      = $_GET['action'];
      // define o ano a partir do parâmetro ou do ano corrente
      $yearSelected = isset($_GET['year'])
                     ? (int) $_GET['year']
                     : (int) date('Y');
    
      if ($action === 'ranking_por_status') {
         $status = $_GET['status'];
         $result = obterRankingPorStatus($conn, $status, $yearSelected);
         echo json_encode($result);
         exit;
      }
      if ($action === 'mes_por_responsavel') {
         $resp = $_GET['responsavel'];
         $data = obterPedidosPorMesResponsavel($conn, $resp, $yearSelected);
         echo json_encode(['data' => $data]);
         exit;
      }
      if ($action === 'drill_mes') {
        $ano  = isset($_GET['year'])  ? (int) $_GET['year']  : (int) date('Y');
        $mes = isset($_GET['month']) ? (int) $_GET['month'] : (int) date('n');
     
        // validação de faixa para mês
        if ($mes < 1 || $mes > 12) {
            echo json_encode(['error' => 'Mês inválido']);
            exit;
        }
     
        $resultado = buscarDetalhePorDia($conn, $ano, $mes);
        echo json_encode($resultado);
        exit;
     }
    
      echo json_encode(['error' => 'Ação inválida']);
      exit;
    }

    // Uso das funções agrupadas
    list($pendentes, $emAndamento, $finalizados, $rejeitados) = obterTotaisPorStatus($conn, $anoSelecionado);
    list($responsaveis, $totais) = obterRankingResponsaveis($conn, $anoSelecionado);
    $mediaDias = calcularMediaDiasConclusao($conn, $anoSelecionado);
    $labelsMeses = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];

    $dadosMesStatus = buscarDadosMesStatus($conn, $anoSelecionado);

    $totaisHoje = buscarTotaisHoje($conn);
    $totalHoje = array_sum($totaisHoje);

    $sla = calcularSLA($conn, $anoSelecionado);

    $pagina = isset($_GET['pagina_atendentes']) ? (int)$_GET['pagina_atendentes'] : 1;
    list($atendentes, $totalPaginas) = buscarAtendentes($conn, $anoSelecionado, $pagina);

    // --- Tendência mensal de aberturas vs. fechamentos (sem consultas no loop) ---
    $labelsTrend  = [];
    for ($m = 1; $m <= 12; $m++) {
      $labelsTrend[] = sprintf('%02d', $m);
    }
    $abertosMes   = buscarAberturasPorMes($conn, $anoSelecionado);

    // Busca fechamentos do ano selecionado agrupadas por mês
    $fechadosMes = buscarFechamentosPorMes($conn, $anoSelecionado);

    list($labelsAge, $dataAge) = calcularBacklogAge($conn, $anoSelecionado);

    // array de prioridades
    $prioridades = ['Alta','Média','Baixa'];
    // SLA por prioridade (% concluído em ≤3 dias)
    $slaPorPrioridade = calcularSlaPorPrioridade($conn, $prioridades, $anoSelecionado);

    // para o comparativo ano a ano (sem consultas dentro do loop)
    $meses = range(1,12);
    $anoAtual   = $anoSelecionado;
    $anoAnterior = $anoAtual - 1;
    // Busca aberturas do ano anterior agrupadas por mês
    $anterior = buscarAberturasPorMes($conn, $anoAnterior);
    $abertosMes   = buscarAberturasPorMes($conn, $anoSelecionado);
    $fechadosMes  = buscarAberturasPorMes($conn, $anoAnterior);

    // Uso da função
    list($mediaHoras, $totaisCross) = calcularCrossTrend($conn, $prioridades, $anoSelecionado);

    $anos = [$anoAnterior, $anoSelecionado];
    $statusList = ['Pendente', 'Em Andamento', 'Concluído'];
    $prioridades = ['Urgente', 'Média', 'Baixa'];
    $dadosYoYDetalhado = buscarDadosYoYDetalhado($conn, $anos, $statusList, $prioridades);

    $coresBase = [
      'Urgente' => '#dc3545',
      'Média'   => '#007bff',
      'Baixa'   => '#6c757d',
    ];
    $paleta = [
      '#872361', '#75ff85', '#8747ff', // ano 1
      '#e00b55', '#00e988', ' #feb43c', // ano 2
      '#f44336', '#2196f3', '#607d8b', // ano 3
      '#ff5722', '#00bcd4', '#8bc34a', // ano 4
      '#795548', '#009688', '#ffc107', // ano 5
  ];
  $estilos = [];
  $corIndex = 0;
  foreach ($anos as $ano) {
    foreach ($statusList as $status) {
      foreach ($prioridades as $prioridade) {
        $label = "$ano - $prioridade - $status";
        $dadosMes = $dadosYoYDetalhado[$ano][$status][$prioridade] ?? [];
        $data = array_map(fn($i) => $dadosMes[$i] ?? 0, range(1, 12));
        
        $cor = $paleta[$corIndex % count($paleta)];
        $estilos[] = [
          'label' => $label,
          'data' => $data,
          'fill' => true,
          'backgroundColor' => $cor . '33',
          'borderColor' => $cor,
          'borderWidth' => 1,
          'tension' => 0.3,
          'stack' => "$ano-$status"
        ];
        $corIndex++;
      }
    }
  }
  
  $cores = [];
foreach ($responsaveis as $i => $r) {
    $cores[] = sprintf("hsl(%d, 70%%, 60%%)", ($i * 60) % 360);
}
$dadosWordCloud = gerarFrequenciasPalavras($conn);
    ?>
<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <title>Dashboard de Desempenho de Manutenção</title>
        <link rel="stylesheet" href="../css/padrao.css">
        <link rel="stylesheet" href="../css/dashboard.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js" integrity="sha512-CQBWl4fJHWbryGE+Pc7UAxWMUMNMWzWxF4SQo9CgkJIN1kx6djDQZjh3Y8SZ1d+6I+1zze6Z7kHXO7q3UyZAWw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/echarts/5.6.0/echarts.min.js" integrity="sha512-XSmbX3mhrD2ix5fXPTRQb2FwK22sRMVQTpBP2ac8hX7Dh/605hA2QDegVWiAvZPiXIxOV0CbkmUjGionDpbCmw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
        <style>
              #anoForm{
                width: fit-content;
              }

              select {
                appearance: none;
                /* remove a aparência padrão do sistema */
                -webkit-appearance: none;
                -moz-appearance: none;
                width: 100%;
                padding: 10px 40px 10px 15px;
                font-size: 16px;
                font-weight: 500;
                color: #333;
                background-color: #fff;
                border: 1px solid #ccc;
                border-radius: 12px;
                box-shadow: inset -4px -4px 6px -1px white,
                    inset 2px 2px 8px -1px rgba(72, 79, 96, 0.3);
                background-image: url('data:image/svg+xml;utf8,<svg fill="%232a9d8f" height="20" viewBox="0 0 24 24" width="20" xmlns="http://www.w3.org/2000/svg"><path d="M7 10l5 5 5-5z"/></svg>');
                background-repeat: no-repeat;
                background-position: right 10px center;
                background-size: 20px;
                transition: all 0.3s ease-in-out;
                cursor: pointer;
                user-select: none;
            }

            select:focus {
                outline: none;
                border-color: #2a9d8f;
                box-shadow: 0 0 0 2px rgba(42, 157, 143, 0.3);
            }

            select:hover {
                border-color: #8bc34a;
            }
            #graficoArea {
        width: 100%;
        height: 500px;
      }
        </style>
    </head>
    <body class="d-flex flex-md-row w-100 h-100">
        <aside class="sidebar">
            <img src="../img/LogoPortalSenai.svg" alt="Portal Senai Manutenções" class="logo-senai">
            <div class="spacer"></div>
            <img src="../img/TechFix.svg" alt="Techfix Facilities" class="logo-techfix">
        </aside>
        <main class="main-content d-flex flex-md-column w-100 h-100">
            <header class="top-bar mt-2 animate__animated animate__fadeInDown">
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
            
            <div class="dashboard-container d-flex flex-wrap flex-md-column justify-content-evenly gap-4 mt-3 w-100">                
                <div class="dashboard-group d-flex flex-row justify-content-evenly align-items-start align-top gap-4 w-100" id="topo">
                <div class="d-flex justify-content-end px-4 w-100" id="ano-selector" class="d-flex animate_animated animate__fadeInDownBig">
                <form method="get" id="anoForm" class="form-inline d-flex animate_animated animate__fadeInDownBig" style="width: fit-content; min-width: 200px; flex-direction: row; align-items: center; gap:10px;">
                <a href="insights.php" title="Comparativo entre anos" class="btn btn-outline-secondary" style="border-radius: 50%; padding: 0.5rem; width: 2.5rem; height: 2.5rem; display: flex; align-items: center; justify-content: center;">
                    <span class="material-symbols-outlined">query_stats</span>
                  </a><label for="ano" class="me-2">Ano: 
                </label>
                  <select name="ano" id="ano" class="form-select w-auto" onchange="document.getElementById('anoForm').submit();">
                    <?php foreach ($anosDisponiveis as $ano): ?>
                      <option value="<?= $ano ?>" <?= $ano == $anoSelecionado ? 'selected' : '' ?>>
                        <?= $ano ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </form>
              </div>
                    <div class="card p-3 m-auto shadow-sm w-25 h-75 animate__animated animate__fadeInLeft" style="flex:1; min-width:300px"  id="status-hoje">
                        <h5 class="title"><i class="fa-solid fa-list-ul"></i> Status de Hoje</h5>
                        <div class="box ">
                            <ul class="list-group">
                                <li class="list-group-item "><b>Pendente:</b> <?= $totaisHoje['Pendente'] ?></li>
                                <li class="list-group-item"><b>Em Andamento:</b> <?= $totaisHoje['Em Andamento'] ?></li>
                                <li class="list-group-item"><b>Concluído:</b> <?= $totaisHoje['Concluído'] ?></li>
                                <li class="list-group-item fw-bold">Total: <?= $totalHoje ?></li>
                                <li class="list-group-item text-success">SLA (<= 3 dias): <?= $sla ?>%</li>
                            </ul>
                        </div>
                    </div>
                    <div class="card p-3 m-auto shadow-sm h-100 d-flex flex-column" id="tecnicos">
                        <h5 class="title"><i class="fa-solid fa-ticket"></i> Tickets por Atendente</h5>
                        <div class="table-wrapper" style="overflow-x:auto;">
                                <table class="" style="border-radius:15px 15px 0 0; ">
                                    <thead>
                                        <tr>
                                            <th>Nome</th>
                                            <th class="status-pendente">Pendente</th>
                                            <th class="status-andamento">Andamento</th>
                                            <th class="status-concluido">Concluído</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($atendentes as $a): ?>
                                        <tr>
                                        <td class="nomeAtendente">
                                                <?php if (isset($a['nif'])): ?>
                                                    <a href="tecnico_detalhes.php?nif=<?= urlencode($a['nif']) ?>">
                                                        <?= htmlspecialchars($a['nome']) ?>
                                                    </a>
                                                <?php else: ?>
                                                    <?= htmlspecialchars($a['nome']) ?>
                                                <?php endif; ?>
                                            </td>
                                            <td data-label="Pendentes" class="status-pendente"><b><?= $a['pendente'] ?></b></td>
                                            <td data-label="Em Andamento" class="status-andamento"><?= $a['andamento'] ?></td>
                                            <td data-label="Concluídos" class="status-concluido"><?= $a['concluido'] ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <nav class="mt-2">
                                    <ul class="pagination paginacao justify-content-center">
                                        <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                                        <li class="page-item <?= $i === $pagina ? 'active' : '' ?>">
                                            <a class="page-link" href="?pagina_atendentes=<?= $i ?>#tickets-atendentes"><?= $i ?></a>
                                        </li>
                                        <?php endfor; ?>
                                    </ul>
                                </nav>
                          </div>
                    </div>
                    <div class="dashboard-cards p-3 m-auto w-50 animate__animated animate__fadeInRight" id="cards-metricas" style="min-width: 200px; flex:1;">
                            <div class="info-box bg-pink hover-expand-effect">
                                <div class="icon">
                                    <span class="material-symbols-outlined">
                                    pending_actions
                                    </span>
                                </div>
                                <div class="content">
                                    <div class="text">Pendentes</div>
                                    <div class="number count-to" data-from="0" data-to="125" data-speed="15" data-fresh-interval="20"><?= $pendentes ?></div>
                                </div>
                            </div>
                            <div class="info-box bg-orange hover-expand-effect">
                                <div class="icon">
                                    <span class="material-symbols-outlined">
                                    content_paste
                                    </span>
                                </div>
                                <div class="content">
                                    <div class="text">Em Andamento</div>
                                    <div class="number count-to" data-from="0" data-to="1225" data-speed="1000" data-fresh-interval="20"><?= $emAndamento ?></div>
                                </div>
                            </div>
                            <div class="info-box bg-light-green hover-expand-effect">
                                <div class="icon">
                                    <span class="material-symbols-outlined">
                                    assignment_turned_in
                                    </span>
                                </div>
                                <div class="content">
                                    <div class="text">Finalizados</div>
                                    <div class="number count-to" data-from="0" data-to="243" data-speed="1000" data-fresh-interval="20"><?= $finalizados ?></div>
                                </div>
                            </div>
                            <div class="info-box bg-cyan hover-expand-effect">
                                <div class="icon">
                                    <span class="material-symbols-outlined">
                                    hourglass_bottom
                                    </span>
                                </div>
                                <div class="content">
                                    <div class="text">Tempo Médio de Conclusão</div>
                                    <div class="number count-to" data-from="0" data-to="257" data-speed="1000" data-fresh-interval="20"><?= $mediaDias ?> dias</div>
                                </div>
                            </div>

                      </div>
                </div>
                <div class="dashboard-group d-flex flex-row justify-content-evenly align-items-start gap-4 w-100 flex-wrap">
                    <!-- Gráfico de Pizza (à esquerda) -->
                    <div class="p-2 m-auto animate__animated animate__fadeInUp">
                        <div class="chart-container card p-3 shadow-sm" style="height: 420px;">
                            <h5 class="title text-center"><i class="fa-solid fa-chart-pie"></i> Distribuição por Status</h5>
                            <canvas id="statusChart"></canvas>
                        </div>
                    </div>
                    <div class="p-2 m-auto animate__animated animate__fadeInUp">
                        <div class="chart-container card p-3 shadow-sm" style="width: 700px;">

                            <!---<canvas id="multiLineChart" height="320px" ></canvas>-->
                            <div id="graficoArea"></div>
                        </div>
                    </div>

                </div>
                <div class="dashboard-group d-flex flex-row justify-content-evenly align-items-start gap-4 w-100 flex-wrap" >
                    <!-- Gráfico de Pizza (à esquerda) -->
                    <div class="p-2 m-auto animate__animated animate__fadeInUp">
                        <div class="chart-container card p-3 shadow-sm" style="height: 420px;">
                            <canvas id="graficoSetor"></canvas>
                        </div>
                    </div>
                    <div class="p-2 m-auto animate__animated animate__fadeInUp">
                        <div class="chart-container card p-3 shadow-sm" style="width: 700px;">
                            <div class="chart-wrapper">
                                <canvas id="backlogChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="dashboard-group d-flex flex-row justify-content-evenly align-items-start gap-4 w-100 flex-wrap">
                <div class="p-2 m-auto animate__animated animate__fadeInUp"  style="flex:1; min-width: 150px; min-height: 300px;">
                        <div class="chart-container card p-3 shadow-sm" style="width: fit-content;">
                            <div class="chart-wrapper"  style="width:300px; overflow-x: auto;">
                                <canvas id="rankingChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="p-2 m-auto animate__animated animate__fadeInUp" style="flex:1; min-width: 450px; min-height: 300px;">
                            <div class="chart-container card p-3 shadow-sm" style="width: fit-content;">
                                <div class="chart-wrapper w-100">
                                    <canvas id="chartSLA" style="width:100%; height: 300px;"></canvas>
                                </div>
                            </div>
                      </div>
                </div>
                <div class="dashboard-group d-flex flex-row justify-content-evenly align-items-start gap-4 w-100 flex-wrap"  style="flex:1; min-width: 300px;">
                    <div class="p-2  m-auto animate__animated animate__fadeInUp">
                        <div class="chart-container card p-3 shadow-sm">
                            <div class="chart-wrapper">
                            <div id="chartCross" style="width: 500px; height: 400px;"></div>
                            </div>
                        </div>
                    </div>
                    <div class="p-2  w-75 h-100 m-auto animate__animated animate__fadeInUp" style="flex:1; min-width: 450px; min-height: 300px;">
                        <div class="chart-container card p-3 shadow-sm">
                        <select id="prioridadeFiltro" class="form-select w-auto mb-2">
                          <option value="Todas">Todas as Prioridades</option>
                          <option value="Urgente">Urgente</option>
                          <option value="Média">Média</option>
                          <option value="Baixa">Baixa</option>
                        </select>
                            <div class="chart-wrapper" height="100%">
                                <canvas id="chartYoY" width="85%" ></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="dashboard-group d-fkex flex-row justify-content-evenly">
                  
                <div class="p-2 m-auto animate__animated animate__fadeInUp" style="flex:1; min-width: 450px; min-height: 300px;">
                        <div class="chart-container card p-3 shadow-sm" style="width: fit-content;">
                            <div class="chart-wrapper w-100">
                                <div id="wordCloud" style="min-width: 450px;"></div>
                            </div>
                        </div>
                    </div>


                  </div>

            </div>
            </div>
            </div>
            </div>
            </div>
            <?php if ($_SESSION['tipo'] == 'coordenador'): ?>
            <a href="listar_funcionarios.php" class="" title="Visualizar funcionários">
                <img src="../img/gerenciar-grupo.png" alt="add-user" class="add-user-img animate__animated animate__fadeInUp">
            </a>
            <a href="export_data.php" class="" title="Exportar dados">
            <img src="../img/exportar-arquivo.png" alt="add-user" class="add-dash-img animate__animated animate__fadeInUp">
            </a>
            <a href="solicita.php" class="" title="Abrir ordem de serviço">
        <img src="../img/documento.png" alt="Adicionar" class="add-button-img animate__animated animate__fadeInUp">
    </a>
    <?php else :?>
            <a href="solicita.php" class="">
        <img src="../img/documento.png" alt="Adicionar" class="add-button-img animate__animated animate__fadeInUp">
    </a>

  <?php endif; ?>            
  <script>
    // captura as cores diretamente das info-box
    const infoPink       = getComputedStyle(document.querySelector('.bg-pink')).backgroundColor;
    const infoOrange     = getComputedStyle(document.querySelector('.bg-orange')).backgroundColor;
    const infoLightGreen = getComputedStyle(document.querySelector('.bg-light-green')).backgroundColor;
    const infoCyan       = getComputedStyle(document.querySelector('.bg-cyan')).backgroundColor;
    const cores = [
      '#000000', // Preto
      '#e6b5aa', // Rosa claro
      '#004080', // Azul escuro
      '#007080', // Ciano escuro
      '#30a0a0', // Ciano médio
      '#38a850', // Verde claro
      '#70b080', // Verde médio
      '#a0b870', // Verde oliva
      '#f0e850', // Amarelo claro
      '#f8a800', // Laranja
      '#b01030', // Vermelho escuro
      '#8c003c'  // Magenta escuro
    ];
    // array de cores para statusChart
    const infoColors = [
      infoPink,       // Pendente
      infoOrange,     // Em andamento
      infoLightGreen, // Concluído
      infoCyan        // Rejeitado (você pode realocar se quiser outro mapeamento)
    ];
    // Ano atualmente selecionado (usado em todas as chamadas AJAX)
    const yearSelected = <?= json_encode($anoSelecionado) ?>;
    // -----------------------------
    // 1) Instância os charts em variáveis (exceto mesChart)
    // -----------------------------
    const statusCtx   = document.getElementById('statusChart').getContext('2d');
    const rankingCtx  = document.getElementById('rankingChart').getContext('2d');
    /*const multiLineChartCtx = document.getElementById('multiLineChart').getContext('2d');*/

    const statusChart = new Chart(statusCtx, {
      type: 'doughnut',
      data: {
        labels: ['Pendente', 'Em andamento', 'Concluído', 'Rejeitado'],
        datasets: [{
          data: [<?= $pendentes ?>, <?= $emAndamento ?>, <?= $finalizados ?>, <?= $rejeitados ?>],
          backgroundColor: infoColors
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,  
        plugins: {
          legend: { position: 'bottom' },
          datalabels: {
            formatter: (v, ctx) => {
              let sum = ctx.chart.data.datasets[0].data.reduce((a,b)=>a+b,0);
              return (v*100/sum).toFixed(1)+'%';
            },
            color: '#fff', font:{weight:'bold',size:14}
          }
        },
        onClick: (evt, elems) => {
          if (!elems.length) return;
          const status = statusChart.data.labels[elems[0].index];
          // atualiza rankingChart via AJAX
          fetch(`?action=ranking_por_status&status=${encodeURIComponent(status)}&year=${yearSelected}`)
            .then(r=>r.json()).then(json=>{
              rankingChart.data.labels = json.labels;
              rankingChart.data.datasets[0].data = json.data;
              rankingChart.update();
            });
        }
      },
      plugins: [ChartDataLabels]
    });

    const rankingChart = new Chart(rankingCtx, {
        type: 'polarArea',
        data: {
          labels: <?= json_encode($responsaveis) ?>,
          datasets: [{
            label: 'Chamados por Responsável',
            data: <?= json_encode($totais) ?>,
            backgroundColor: cores.slice(0, <?= count($responsaveis) ?>)
          }]
        },
        options: {
          responsive: true,
          plugins: {
            legend: {
              position: 'top',
            },
            title: {
              display: true,
              text: 'Chamados por Responsável'
            }
          }
        }
        });

        // 2) Gráfico de idade
  // Dados gerados pelo PHP
  const labelsAge = <?= json_encode($labelsAge) ?>;
  const dataAge = <?= json_encode($dataAge) ?>;

  // Instanciando o gráfico com ECharts
  const backlogOption = {
      title: {
        text: 'Tickets Pendentes por Idade',
        left: 'center',
        top: 'top',
        textStyle: {
          fontSize: 10
        }
      },
      tooltip: {
        trigger: 'axis',
        axisPointer: { type: 'shadow' }
      },
      grid: {
        left: '10%',
        right: '10%',
        bottom: '10%',
        top: '20%',
        containLabel: true
      },
      xAxis: {
        type: 'value',
        min: 0
      },
      yAxis: {
        type: 'category',
        data: <?= json_encode($labelsAge) ?>,
        axisLabel: {
          rotate: 0 // sem rotação agora
        }
      },
      series: [{
        name: 'Tickets Pendentes por Idade',
        type: 'bar',
        data: <?= json_encode($dataAge) ?>,
        itemStyle: {
          color: '#3f51b5'
        },
        barWidth: '50%'
      }]
    };

    const backlogChart = echarts.init(
      document.getElementById('backlogChart'),
      null,
      { devicePixelRatio: 2 } // aumenta nitidez em telas retina/4K
    );
    backlogChart.setOption(backlogOption);

    // 1) SLA Compliance por Prioridade
    const chartSLA = new Chart(
      document.getElementById('chartSLA').getContext('2d'),
      {
        type: 'bar',
        data: {
          labels: <?= json_encode($prioridades) ?>,
          datasets: [{
            label: '% Compliance SLA',
            data: <?= json_encode($slaPorPrioridade) ?>,
          }]
        },
        options: {
          plugins: {
            title: {
              display: true,
              text: 'SLA Compliance por Prioridade'
            }
          },
          scales: {
            y: {
              beginAtZero: true,
              stacked: false,
              title: {
                display: true,
                text: 'Total de Pedidos'
              }
            },
            x: {
              title: {
                display: true,
                text: 'Mês'
              }
            }
          }

        }
      }
    );

    let estadoAtual = {
  ano: new Date().getFullYear(),
  mes: null
};

function carregarGrafico(ano, mes = null) {
  const url = mes ? `grafico_area.php?ano=${ano}&mes=${mes}` : `grafico_area.php?ano=${ano}`;
  
  fetch(url)
    .then(res => res.json())
    .then(({ labels, dados }) => {
      const cores = {
        'Baixa - Pendente': '#e91e63',
        'Baixa - Em Andamento': '#ff9800',
        'Baixa - Concluído': '#8bc34a',
        'Média - Pendente': '#9c27b0',
        'Média - Em Andamento': '#ffc107',
        'Média - Concluído': '#4caf50',
        'Alta - Pendente': '#03a9f4',
        'Alta - Em Andamento': '#ff5722',
        'Alta - Concluído': '#009688',
        'Urgente - Pendente': '#f44336',
        'Urgente - Em Andamento': '#ffeb3b',
        'Urgente - Concluído': '#607d8b'
      };

      const series = [];
      for (const chave in dados) {
        series.push({
          name: chave,
          type: 'line',
          stack: 'total',
          areaStyle: {},
          emphasis: { focus: 'series' },
          data: labels.map(p => dados[chave][p] || 0),
          color: cores[chave] || undefined
        });
      }

      const chart = echarts.init(document.getElementById('graficoArea'));
      const titulo = mes 
        ? `Detalhes do mês ${estadoAtual.ano}-${String(mes).padStart(2, '0')}`
        : `Visão geral de ${estadoAtual.ano}`;

      chart.setOption({
        title: { text: titulo, left: 'center' ,  textStyle: {
    fontSize: 16
  }},
        tooltip: { trigger: 'axis' },
        legend: {
          orient: 'vertical',
          right: 10, // ou left: 10
          top: 'middle',
          type: 'scroll',
          textStyle: {
            fontSize: 12
          }
        },
        toolbox: {
          feature: {
            saveAsImage: {},
            myDrillUp: {
              show: !!mes,
              title: 'Voltar',
              icon: 'path://M512 64L96 320l416 256V64z',
              onclick: function () {
                estadoAtual.mes = null;
                carregarGrafico(estadoAtual.ano);
              }
            }
          }
        },
        grid: { left: '3%', right: '4%', bottom: '3%', containLabel: true },
        xAxis: { type: 'category', boundaryGap: false, data: labels },
        yAxis: { type: 'value' },
        series: series
      });

      chart.off('click');
      chart.on('click', function (params) {
        if (!estadoAtual.mes && params.name.includes('-')) {
          const partes = params.name.split('-');
          const mesClicado = parseInt(partes[1]);
          estadoAtual.mes = mesClicado;
          carregarGrafico(estadoAtual.ano, mesClicado);
        }
      });
    })
    .catch(error => {
      console.error('Erro ao carregar gráfico:', error);
    });
}

carregarGrafico(estadoAtual.ano);

 
    const chartYoY = new Chart(document.getElementById('chartYoY'), {
  type: 'line',
  data: {
    labels: <?= json_encode(['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez']) ?>,
    datasets: <?= json_encode($estilos) ?>
  },
  options: {
    responsive: true,
    plugins: {
      title: {
        display: true,
        text: 'Comparativo Ano a Ano por Prioridade e Status'
      },
      tooltip: {
        mode: 'index',
        intersect: false
      },
      legend: {
        position: 'bottom'
      }
    },
    interaction: {
      mode: 'index',
      intersect: false
    },
    scales: {
  y: { beginAtZero: true, stacked: true },
  x: { stacked: true }
}
  }
});

const prioridades = <?= json_encode($prioridades) ?>;
  const tempoMedio = <?= json_encode($mediaHoras) ?>;
  const totalChamados = <?= json_encode($totaisCross) ?>;
  const chartCross = echarts.init(
    document.getElementById('chartCross'),
    null,
    { devicePixelRatio: 2 }
);

const optionRadarCross = {
  title: {
    text: 'Resolução (dias) × Volume de Chamados',
    left: 'center',
    textStyle: { fontSize: 16 }
  },
  legend: {
    top: 'bottom',
    textStyle: { fontSize: 12 }
  },
  tooltip: {
    trigger: 'item',
    formatter: function (params) {
      const valores = params.value;
      const prioridadeLabels = <?= json_encode($prioridades) ?>;
      let detalhes = valores.map((v, i) => `${prioridadeLabels[i]}: ${v}`).join('<br>');
      return `<b>${params.seriesName} - ${params.name}</b><br>${detalhes}`;
    }
  },
  radar: {
    indicator: prioridades.map((p, i) => ({
      name: p,
      max: Math.max(tempoMedio[i], totalChamados[i]) + 10
    }))
  },
  series: [
    {
      name: 'Comparação',
      type: 'radar',
      data: [
        {
          value: tempoMedio,
          name: 'Tempo Médio (dias)',
          lineStyle: { color: '#007bff' },
          areaStyle: { color: 'rgba(0,123,255,0.2)' }
        },
        {
          value: totalChamados,
          name: 'Total de Chamados',
          lineStyle: { color: '#28a745' },
          areaStyle: { color: 'rgba(40,167,69,0.2)' }
        }
      ]
    }
  ]
};

chartCross.setOption(optionRadarCross);

  const fetchSetor = fetch(`dados_por_setor.php?ano=${yearSelected}`)
      .then(res => res.json())
      .then(data => {
        const labels = data.map(item => item.bloco);
        const valores = data.map(item => item.total);
        const total = valores.reduce((a, b) => a + b, 0);

        const graficoSetor = new Chart(document.getElementById('graficoSetor'), {
          type: 'doughnut',
          data: {
            labels: labels,
            datasets: [{
              label: 'Ordens de serviço por Bloco',
              data: valores,
              backgroundColor: cores.slice(0, valores.length),
              borderWidth: 0
            }]
          },
          options: {
            responsive: true,
            rotation: -90,
            circumference: 180,
            cutout: '55%',
            plugins: {
              legend: {
                display: false
              },
              tooltip: {
                enabled: false
              },
              title: {
                display: true,
                text: 'Ordens de serviço por Bloco',
              },
              datalabels: {
                color: '#fff',
                font: {
                  weight: 'bold',
                  size: 14
                },
                formatter: function(value, ctx) {
                  const label = ctx.chart.data.labels[ctx.dataIndex];
                  const percent = ((value / total) * 100).toFixed(1) + '%';
                  return `${label}\n${percent}`;
                }
              }
            }
          },
          plugins: [ChartDataLabels]
        });
      });

  </script>
<script>
  const wordCloudChart = echarts.init(document.getElementById('wordCloud'));

  const wordCloudOptions = {
    tooltip: { show: true },
    series: [{
      type: 'wordCloud',
      gridSize: 8,
      sizeRange: [14, 60],
      rotationRange: [-45, 45],
      shape: 'circle',
      width: '100%',
      height: '100%',
      drawOutOfBound: false,
      textStyle: {
        fontFamily: 'Arial',
        color: () => `rgb(${Math.round(Math.random()*200)}, ${Math.round(Math.random()*200)}, ${Math.round(Math.random()*200)})`,
        emphasis: {
          shadowBlur: 10,
          shadowColor: '#333'
        }
      },
      data: <?php echo json_encode($dadosWordCloud); ?>
    }]
  };

  wordCloudChart.setOption(wordCloudOptions);

  wordCloudChart.on('click', function (params) {
    alert('Você clicou na palavra: ' + params.name);
    // Aqui você pode redirecionar, filtrar ou abrir modal com a palavra
  });
</script>
<script>

</script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/js/all.min.js" integrity="sha512-b+nQTCdtTBIRIbraqNEwsjB6UvL3UEMkXnhzd8awtCYh0Kcsjl9uEgwVFVbhoj3uu1DO1ZMacNvLoyJJiNfcvg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        </main>

    </body>
</html>