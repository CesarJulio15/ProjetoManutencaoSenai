<?php
    session_start();
    require_once __DIR__ . '/conexao.php';

    if (!isset($_SESSION['id']) || !isset($_SESSION['tipo'])) {
        header("Location: index.php");
        exit();
    }

    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    $isTecnico   = ($_SESSION['tipo'] === 'técnico');
    $statusParam = $_GET['status'] ?? '';
    $busca       = isset($_GET['busca']) ? '%' . $_GET['busca'] . '%' : '%';

    $tipoUsuario = $_SESSION['tipo'];
    $usuarioId   = $_SESSION['id'];

    // Paginação
    $resultadosPorPagina = 8;
    $paginaAtual = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
    $offset = ($paginaAtual - 1) * $resultadosPorPagina;

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

    if ($isTecnico) {
        if (isset($_GET['meus'])) {
            $sql = $sqlBase . "WHERE m.executante = ? AND m.titulo LIKE ? LIMIT ? OFFSET ?";
            $stmt = prepararConsulta($conn, $sql, "isii", $usuarioId, $busca, $resultadosPorPagina, $offset);
        } elseif ($statusParam) {
            if ($statusParam === 'Pendente') {
                $sql = $sqlBase . "WHERE m.status = ? AND m.executante IS NULL AND m.titulo LIKE ? 
                ORDER BY
                    (m.prioridade = 'Urgente' AND DATE(m.data_abertura) = CURDATE()) DESC,
                    CASE m.prioridade
                        WHEN 'Urgente' THEN 1
                        WHEN 'Alta' THEN 2
                        WHEN 'Média' THEN 3
                        WHEN 'Baixa' THEN 4
                        ELSE 5
                    END,
                    m.data_abertura DESC,
                    m.id DESC
                LIMIT ? OFFSET ?";
                $stmt = prepararConsulta($conn, $sql, "ssii", $statusParam, $busca, $resultadosPorPagina, $offset);
            } else {
                $sql = $sqlBase . "WHERE m.status = ? AND m.executante = ? AND m.titulo LIKE ? 
                ORDER BY
                    (m.prioridade = 'Urgente' AND DATE(m.data_abertura) = CURDATE()) DESC,
                    CASE m.prioridade
                        WHEN 'Urgente' THEN 1
                        WHEN 'Alta' THEN 2
                        WHEN 'Média' THEN 3
                        WHEN 'Baixa' THEN 4
                        ELSE 5
                    END,
                    m.data_abertura DESC,
                    m.id DESC
                LIMIT ? OFFSET ?";
                $stmt = prepararConsulta($conn, $sql, "sisii", $statusParam, $usuarioId, $busca, $resultadosPorPagina, $offset);
            }
        } else {
            $sql = $sqlBase . "WHERE (m.executante IS NULL OR m.executante = ?) AND m.titulo LIKE ? 
            ORDER BY
                (m.prioridade = 'Urgente' AND DATE(m.data_abertura) = CURDATE()) DESC,
                CASE m.prioridade
                    WHEN 'Urgente' THEN 1
                    WHEN 'Alta' THEN 2
                    WHEN 'Média' THEN 3
                    WHEN 'Baixa' THEN 4
                    ELSE 5
                END,
                m.data_abertura DESC,
                m.id DESC
            LIMIT ? OFFSET ?";
            $stmt = prepararConsulta($conn, $sql, "isii", $usuarioId, $busca, $resultadosPorPagina, $offset);
        }
    } elseif ($tipoUsuario === 'coordenador') {
        if ($statusParam) {
            $sql = $sqlBase . "WHERE m.status = ? AND m.titulo LIKE ? 
            ORDER BY
                (m.prioridade = 'Urgente' AND DATE(m.data_abertura) = CURDATE()) DESC,
                CASE m.prioridade
                    WHEN 'Urgente' THEN 1
                    WHEN 'Alta' THEN 2
                    WHEN 'Média' THEN 3
                    WHEN 'Baixa' THEN 4
                    ELSE 5
                END,
                m.data_abertura DESC,
                m.id DESC
            LIMIT ? OFFSET ?";
                        
            $stmt = prepararConsulta($conn, $sql, "ssii", $statusParam, $busca, $resultadosPorPagina, $offset);
        } else {
            $sql = $sqlBase . "WHERE m.titulo LIKE ? LIMIT ? OFFSET ?";
            $stmt = prepararConsulta($conn, $sql, "sii", $busca, $resultadosPorPagina, $offset);
        }
    } else {
        if ($statusParam) {
            $sql = $sqlBase . "WHERE m.status = ? AND m.responsavel = ? AND m.titulo LIKE ? 
            ORDER BY
                (m.prioridade = 'Urgente' AND DATE(m.data_abertura) = CURDATE()) DESC,
                CASE m.prioridade
                    WHEN 'Urgente' THEN 1
                    WHEN 'Alta' THEN 2
                    WHEN 'Média' THEN 3
                    WHEN 'Baixa' THEN 4
                    ELSE 5
                END,
                m.data_abertura DESC,
                m.id DESC
                LIMIT ? OFFSET ?";
            
            $stmt = prepararConsulta($conn, $sql, "sisii", $statusParam, $usuarioId, $busca, $resultadosPorPagina, $offset);
        } else {
            $sql = $sqlBase . "WHERE m.responsavel = ? AND m.titulo LIKE ? 
            ORDER BY
                (m.prioridade = 'Urgente' AND DATE(m.data_abertura) = CURDATE()) DESC,
                CASE m.prioridade
                    WHEN 'Urgente' THEN 1
                    WHEN 'Alta' THEN 2
                    WHEN 'Média' THEN 3
                    WHEN 'Baixa' THEN 4
                    ELSE 5
                END,
                m.data_abertura DESC,
                m.id DESC
            LIMIT ? OFFSET ?";
            $stmt = prepararConsulta($conn, $sql, "isii", $usuarioId, $busca, $resultadosPorPagina, $offset);
        }
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $total = $conn->query("SELECT FOUND_ROWS()")->fetch_row()[0];
    $totalPaginas = ceil($total / $resultadosPorPagina);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Senai Manutenções</title>
    <link rel="stylesheet" href="../css/padrao.css">
    <link rel="stylesheet" href="../css/manutencao.css">
    <link rel="icon" type="image/png" href="../img/senai-logo 5.svg">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
    <style>
        .status-btn:nth-child(2) {
            width:170px;
        }
        .search-container {
  position: relative;
  display: inline-block;
  transition: all 0.3s ease-in-out;
}

.search-input {
  width: 130px;
  padding: 10px 40px 10px 20px;
  border-radius: 30px;
  border: none;
  outline: none;
  transition: all 0.3s ease;
  background: #f0f0f0;
  color: #333;
  text-align:left;
}

.search-input:focus,
.search-input:not(:placeholder-shown) {
  width: 280px;
  background: #fff;
  box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}

.search-icon {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  pointer-events: none;
}

.search-icon svg {
  width: 20px;
  height: 20px;
  fill: #6c7587;
}
    </style>
</head>
<body>
<aside class="sidebar" role="complementary" aria-label="Menu lateral">
    <img src="../img/LogoPortalSenai.svg" alt="Portal Senai Manutenções" class="logo-senai">
    <div class="spacer"></div>
    <img src="../img/TechFix.svg" alt="Techfix Facilities" class="logo-techfix">
</aside>

<main class="main-content d-flex flex-md-column w-100 h-100" role="main">
    <header class="top-bar">
            <div class="search-container">
        <input type="text" id="buscaInput" placeholder="Buscar..." class="search-input" />
        <span class="search-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 40">
            <path d="M18 3C26.3 3 33.1 9.7 33.1 18 33.1 21.5 31.9 24.7 29.9 27.3L37 34.4 34.4 37 27.3 29.9C24.7 31.9 21.5 33.1 18 33.1 9.7 33.1 3 26.3 3 18 3 9.7 9.7 3 18 3ZM18.1 6.9C11.9 6.9 6.9 11.9 6.9 18.1 6.9 24.2 11.9 29.2 18.1 29.2 24.2 29.2 29.2 24.2 29.2 18.1 29.2 11.9 24.2 6.9 18.1 6.9Z" />
            </svg>
        </span>
        </div>
        <div class="status-buttons">
            <?php if ($_SESSION['tipo'] == 'técnico'): ?>
                <a href="#"
                class="status-btn <?= isset($_GET['meus']) ? 'active' : '' ?>"
                data-meus="1">
                Seus Trabalhos
                </a>                                                                
            <?php endif; ?>
            <a href="#" class="status-btn <?php echo (isset($_GET['status']) && $_GET['status'] == 'Pendente') ? 'active' : ''; ?>" data-status="Pendente">Pendente</a>
            <a href="#" class="status-btn <?php echo (isset($_GET['status']) && $_GET['status'] == 'Em Andamento') ? 'active' : ''; ?>" data-status="Em Andamento">Em andamento</a>
            <a href="#" class="status-btn <?php echo (isset($_GET['status']) && $_GET['status'] == 'Concluído') ? 'active' : ''; ?>" data-status="Concluído">Concluído</a>
        </div>

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
    <section class="cards-container" id="resultado">
    <?php
        if ($result->num_rows == 0) {
            ?>
                <div class="no-results">
                    <img src="../img/no-results 1.svg" alt="Sem pedidos" style="width: 200px; height: 300px;">
                    <p>Nenhum pedido de manutenção foi encontrado.</p>
                </div>
            <?php
                } else {
                    while ($row = $result->fetch_assoc()) {
                        $flagColor = match ($row['status']) {
                            'Pendente' => '#a0352b',
                            'Em Andamento' => ' #e9963e',
                            'Concluído' => '#5b7f2e',
                            default => 'gray',
                        };

                        $link = 'detalhe.php?numero_os=' . urlencode($row['numero_os']);
            ?>
                <a href="<?php echo $link; ?>" class="card-link">
                    <div class="card">
                        <div class="card-header">
                            <div class="avatar"><?php echo strtoupper(substr($row['nome_responsavel'], 0, 1)); ?></div>
                            <span><?php echo htmlspecialchars($row['nome_responsavel']); ?></span>
                            <div class="flag" style="background-color: <?php echo $flagColor; ?>;"></div>
                        </div>
                        <div class="card-body card-image">
                            <?php
                                if (!empty($row['foto'])) {
                                    $fotoBase64 = base64_encode($row['foto']);
                                    echo "<img src='data:image/jpeg;base64,{$fotoBase64}' alt='Imagem da manutenção'>";
                                }
                            ?>
                        </div>
                                <div class="d-flex conjunto">
                                <p><strong>Nº OS:</strong> <?php echo htmlspecialchars($row['numero_os']); ?></p>
                                </div>                    
                                <div class="d-flex conjunto os-texto" id="">
                                                    <h4><?php echo htmlspecialchars($row['titulo']); ?></h4>
                                                    <p><strong>Localização:</strong> <?php echo htmlspecialchars($row['localizacao_ambiente']); ?></p>
                                    <?php if (!empty($row['nome_executante'])): ?>
                                        <p><strong>Executante:</strong> <?php echo htmlspecialchars($row['nome_executante']); ?></p>
                                    <?php endif; ?>       
                                    </div>
                                
                                <div class="d-flex w-100 h-100 card-footer">
                                    <span class="tag"><?php echo htmlspecialchars($row['bloco']); ?></span>
                                    <span class="status <?php echo strtolower(str_replace(' ', '-', $row['status'])); ?>">
                                        <?php echo htmlspecialchars($row['status']); ?>
                                    </span>
                                    <span class="badge prioridade-<?= strtolower($row['prioridade']) ?>">
                                    <?= htmlspecialchars($row['prioridade']) ?>
                                    </span>                 
                                </div>
                            </div>
                        </a>
                    <?php
                        }
                    }
                    ?>
                    </section>
                    <?php if ($totalPaginas > 1): ?>
                    <div class="paginacao mt-3">
                        <?php
                            $queryString = $_GET;

                            if ($paginaAtual > 1) {
                                $queryString['pagina'] = $paginaAtual - 1;
                                echo '<a href="?' . http_build_query($queryString) . '">
                                <span class="material-symbols-outlined">arrow_back_ios</span></a>';
                            }

                            for ($i = 1; $i <= $totalPaginas; $i++) {
                                $queryString['pagina'] = $i;
                                $classe = ($i == $paginaAtual) ? 'class="ativa"' : '';
                                echo '<a href="?' . http_build_query($queryString) . "\" $classe>$i</a>";
                            }

                            if ($paginaAtual < $totalPaginas) {
                                $queryString['pagina'] = $paginaAtual + 1;
                                echo '<a href="?' . http_build_query($queryString) . '">
                                <span class="material-symbols-outlined">arrow_forward_ios</span></a>';
                            }
                        ?>
                    </div>
                <?php endif; ?>
                        <?php if ($_SESSION['tipo'] == 'coordenador'): ?>
                    <a href="listar_funcionarios.php" title="Visualizar funcionários">
                        <img src="../img/gerenciar-grupo.png" alt="add-user" class="add-user-img">
                    </a>
                    <a href="dashboard.php" title="Visualizar Dashboard">
                        <img src="../img/painel-de-controle.png" alt="add-user" class="add-dash-img">
                    </a>
                    <a href="solicita.php" title="Abrir ordem de serviço">
                        <img src="../img/documento.png" alt="Adicionar" class="add-button-img">
                    </a>
                    <?php else :?>
                            <a href="solicita.php">
                        <img src="../img/documento.png" alt="Adicionar" class="add-button-img">
                    </a>
                <?php endif; ?>
</main>
<script>
window.onload = function () {
    <?php if (isset($_SESSION['sucesso'])): ?>
        Swal.fire({
            icon: 'success',
            title: <?= json_encode($_SESSION['sucesso']) ?>,
            showConfirmButton: false,
            timer: 2000
        });
        <?php unset($_SESSION['sucesso']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['erro'])): ?>
        Swal.fire({
            icon: 'error',
            title: 'Erro!',
            text: <?= json_encode($_SESSION['erro']) ?>
        });
        <?php unset($_SESSION['erro']); ?>
    <?php endif; ?>
}
</script>
<script src="../js/panelPedidos.js"></script>
</body>
</html>
