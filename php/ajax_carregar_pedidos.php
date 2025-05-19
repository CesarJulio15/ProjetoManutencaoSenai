<?php
session_start();
require_once __DIR__ . '/conexao.php';

if (!isset($_SESSION['id']) || !isset($_SESSION['tipo'])) {
    http_response_code(403);
    echo json_encode(['html' => '<p>Sem permissão</p>', 'total_paginas' => 0]);
    exit;
}

$pagina = max(1, intval($_POST['pagina'] ?? 1));
$busca = isset($_POST['busca']) ? '%' . $_POST['busca'] . '%' : '%';
$statusParam = $_POST['status'] ?? '';
$meus = isset($_POST['meus']);

$tipoUsuario = $_SESSION['tipo'];
$usuarioId = $_SESSION['id'];

$resultadosPorPagina = 8;
$offset = ($pagina - 1) * $resultadosPorPagina;

function prepararConsulta($conn, $sql, $tipos, ...$params) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($tipos, ...$params);
    return $stmt;
}

$sqlBase = "
    SELECT SQL_CALC_FOUND_ROWS m.*, d1.nome AS nome_responsavel, d2.nome AS nome_executante
    FROM manutencao m
    JOIN docentes d1 ON m.responsavel = d1.id
    LEFT JOIN docentes d2 ON m.executante = d2.id
";

if ($tipoUsuario === 'técnico') {
    if ($meus) {
        $sql = $sqlBase . "WHERE m.executante = ? AND m.titulo LIKE ? LIMIT ? OFFSET ?";
        $stmt = prepararConsulta($conn, $sql, "isii", $usuarioId, $busca, $resultadosPorPagina, $offset);
    } elseif ($statusParam) {
        if ($statusParam === 'Pendente') {
            $sql = $sqlBase . "WHERE m.status = ? AND m.executante IS NULL AND m.titulo LIKE ?
            ORDER BY (m.prioridade = 'Urgente' AND DATE(m.data_abertura) = CURDATE()) DESC,
                     CASE m.prioridade
                        WHEN 'Urgente' THEN 1
                        WHEN 'Alta' THEN 2
                        WHEN 'Média' THEN 3
                        WHEN 'Baixa' THEN 4
                        ELSE 5
                     END,
                     m.data_abertura DESC, m.id DESC
            LIMIT ? OFFSET ?";
            $stmt = prepararConsulta($conn, $sql, "ssii", $statusParam, $busca, $resultadosPorPagina, $offset);
        } else {
            $sql = $sqlBase . "WHERE m.status = ? AND m.executante = ? AND m.titulo LIKE ?
            ORDER BY (m.prioridade = 'Urgente' AND DATE(m.data_abertura) = CURDATE()) DESC,
                     CASE m.prioridade
                        WHEN 'Urgente' THEN 1
                        WHEN 'Alta' THEN 2
                        WHEN 'Média' THEN 3
                        WHEN 'Baixa' THEN 4
                        ELSE 5
                     END,
                     m.data_abertura DESC, m.id DESC
            LIMIT ? OFFSET ?";
            $stmt = prepararConsulta($conn, $sql, "sisii", $statusParam, $usuarioId, $busca, $resultadosPorPagina, $offset);
        }
    } else {
        $sql = $sqlBase . "WHERE (m.executante IS NULL OR m.executante = ?) AND m.titulo LIKE ?
        ORDER BY (m.prioridade = 'Urgente' AND DATE(m.data_abertura) = CURDATE()) DESC,
                 CASE m.prioridade
                    WHEN 'Urgente' THEN 1
                    WHEN 'Alta' THEN 2
                    WHEN 'Média' THEN 3
                    WHEN 'Baixa' THEN 4
                    ELSE 5
                 END,
                 m.data_abertura DESC, m.id DESC
        LIMIT ? OFFSET ?";
        $stmt = prepararConsulta($conn, $sql, "isii", $usuarioId, $busca, $resultadosPorPagina, $offset);
    }
} elseif ($tipoUsuario === 'coordenador') {
    if ($statusParam) {
        $sql = $sqlBase . "WHERE m.status = ? AND m.titulo LIKE ?
        ORDER BY (m.prioridade = 'Urgente' AND DATE(m.data_abertura) = CURDATE()) DESC,
                 CASE m.prioridade
                    WHEN 'Urgente' THEN 1
                    WHEN 'Alta' THEN 2
                    WHEN 'Média' THEN 3
                    WHEN 'Baixa' THEN 4
                    ELSE 5
                 END,
                 m.data_abertura DESC, m.id DESC
        LIMIT ? OFFSET ?";
        $stmt = prepararConsulta($conn, $sql, "ssii", $statusParam, $busca, $resultadosPorPagina, $offset);
    } else {
        $sql = $sqlBase . "WHERE m.titulo LIKE ? LIMIT ? OFFSET ?";
        $stmt = prepararConsulta($conn, $sql, "sii", $busca, $resultadosPorPagina, $offset);
    }
} else {
    if ($statusParam) {
        $sql = $sqlBase . "WHERE m.status = ? AND m.responsavel = ? AND m.titulo LIKE ?
        ORDER BY (m.prioridade = 'Urgente' AND DATE(m.data_abertura) = CURDATE()) DESC,
                 CASE m.prioridade
                    WHEN 'Urgente' THEN 1
                    WHEN 'Alta' THEN 2
                    WHEN 'Média' THEN 3
                    WHEN 'Baixa' THEN 4
                    ELSE 5
                 END,
                 m.data_abertura DESC, m.id DESC
        LIMIT ? OFFSET ?";
        $stmt = prepararConsulta($conn, $sql, "sisii", $statusParam, $usuarioId, $busca, $resultadosPorPagina, $offset);
    } else {
        $sql = $sqlBase . "WHERE m.responsavel = ? AND m.titulo LIKE ?
        ORDER BY (m.prioridade = 'Urgente' AND DATE(m.data_abertura) = CURDATE()) DESC,
                 CASE m.prioridade
                    WHEN 'Urgente' THEN 1
                    WHEN 'Alta' THEN 2
                    WHEN 'Média' THEN 3
                    WHEN 'Baixa' THEN 4
                    ELSE 5
                 END,
                 m.data_abertura DESC, m.id DESC
        LIMIT ? OFFSET ?";
        $stmt = prepararConsulta($conn, $sql, "isii", $usuarioId, $busca, $resultadosPorPagina, $offset);
    }
}

$stmt->execute();
$result = $stmt->get_result();

$total = $conn->query("SELECT FOUND_ROWS()")->fetch_row()[0];
$totalPaginas = ceil($total / $resultadosPorPagina);

// Renderizar HTML
ob_start();

if ($result->num_rows === 0) {
    echo '<div class="no-results"><img src="../img/no-results 1.svg" alt="Sem pedidos" style="width:200px;height:300px;"><p>Nenhum pedido de manutenção foi encontrado.</p></div>';
} else {
    while ($row = $result->fetch_assoc()) {
        $flagColor = match ($row['status']) {
            'Pendente' => '#a0352b',
            'Em Andamento' => '#e9963e',
            'Concluído' => '#5b7f2e',
            default => 'gray',
        };
        $link = 'detalhe.php?numero_os=' . urlencode($row['numero_os']);
        $foto = !empty($row['foto']) ? "<img src='data:image/jpeg;base64," . base64_encode($row['foto']) . "' alt='Imagem da manutenção'>" : '';
        $statusClass = strtolower(str_replace(' ', '-', $row['status']));
        $statusLabel = htmlspecialchars($row['status']);
        $prioridadeClass = strtolower(strtr($row['prioridade'], [
            'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u',
            'â'=>'a','ê'=>'e','ô'=>'o','ã'=>'a','õ'=>'o',
            'Á'=>'a','É'=>'e','Í'=>'i','Ó'=>'o','Ú'=>'u',
            'Â'=>'a','Ê'=>'e','Ô'=>'o','Ã'=>'a','Õ'=>'o'
        ]));
        $prioridadeClass = str_replace(' ', '-', $prioridadeClass);
        $prioridadeLabel = htmlspecialchars($row['prioridade']);
        echo <<<HTML
<a href="{$link}" class="card-link">
    <div class="card">
        <div class="card-header">
            <div class="avatar">{$row['nome_responsavel'][0]}</div>
            <span>{$row['nome_responsavel']}</span>
            <div class="flag" style="background-color: {$flagColor};"></div>
        </div>
        <div class="card-body card-image">{$foto}</div>
        <div class="d-flex conjunto">
            <p><strong>Nº OS:</strong> {$row['numero_os']}</p>
        </div>
        <div class="d-flex conjunto os-texto">
            <h4>{$row['titulo']}</h4>
            <p><strong>Localização:</strong> {$row['localizacao_ambiente']}</p>
HTML;
        if (!empty($row['nome_executante'])) {
            echo "<p><strong>Executante:</strong> {$row['nome_executante']}</p>";
        }
        echo <<<HTML
        </div>
        <div class="d-flex w-100 h-100 card-footer">
            <span class="tag">{$row['bloco']}</span>
            <span class="status {$statusClass}">{$statusLabel}</span>
            <span class="badge prioridade-{$prioridadeClass}">{$prioridadeLabel}</span>
        </div>
    </div>
</a>
HTML;
    }
}

$html = ob_get_clean();
echo json_encode(['html' => $html, 'total_paginas' => $totalPaginas]);
