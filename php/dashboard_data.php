<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
  if (!isset($_SESSION['id']) && $_SESSION['tipo'] !== 'coordenador') {
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

    ?>