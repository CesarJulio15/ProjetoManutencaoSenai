<?php
session_start();
if (!isset($_SESSION['id']) && !in_array($_SESSION['tipo'], ['coordenador', 'técnico'])) {
    header("Location: index.php");
    exit();
}

require_once __DIR__ . '/conexao.php';

if (!isset($conn) || !($conn instanceof mysqli)) {
    die('Erro na conexão com o banco de dados');
    exit();
}

$isTecnico   = ($_SESSION['tipo'] === 'técnico');
$statusParam = $_GET['status'] ?? '';
$busca       = isset($_GET['busca']) ? '%' . $_GET['busca'] . '%' : '%';

function prepararConsulta($conn, $sql, $tipos, ...$params) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($tipos, ...$params);
    return $stmt;
}

$tipoUsuario = $_SESSION['tipo'];
$usuarioId = $_SESSION['id'];

$sqlBase = "
    SELECT m.*, d1.nome AS nome_responsavel, d2.nome AS nome_executante
    FROM manutencao m
    JOIN docentes d1 ON m.responsavel = d1.id
    LEFT JOIN docentes d2 ON m.executante = d2.id
";

if ($isTecnico) {
    if (isset($_GET['meus'])) {
        $sql = $sqlBase . "WHERE m.executante = ? AND m.titulo LIKE ?";
        $stmt = prepararConsulta($conn, $sql, "is", $usuarioId, $busca);
    } elseif ($statusParam) {
        if ($statusParam === 'Pendente') {
            $sql = $sqlBase . "WHERE m.status = ? AND m.executante IS NULL AND m.titulo LIKE ?";
            $stmt = prepararConsulta($conn, $sql, "ss", $statusParam, $busca);
        } else {
            $sql = $sqlBase . "WHERE m.status = ? AND m.executante = ? AND m.titulo LIKE ?";
            $stmt = prepararConsulta($conn, $sql, "sis", $statusParam, $usuarioId, $busca);
        }
    } else {
        $sql = $sqlBase . "WHERE (m.executante IS NULL OR m.executante = ?) AND m.titulo LIKE ?";
        $stmt = prepararConsulta($conn, $sql, "is", $usuarioId, $busca);
        }
    } elseif ($tipoUsuario === 'coordenador') {
        if ($statusParam) {
            $sql = $sqlBase . "WHERE m.status = ? AND m.titulo LIKE ?";
             $stmt = prepararConsulta($conn, $sql, "ss", $statusParam, $busca);
        } else {
            $sql = $sqlBase . "WHERE m.titulo LIKE ?";
            $stmt = prepararConsulta($conn, $sql, "s", $busca);
        }
    } else {
        if ($statusParam) {
            $sql = $sqlBase . "WHERE m.status = ? AND m.responsavel = ? AND m.titulo LIKE ?";
            $stmt = prepararConsulta($conn, $sql, "sis", $statusParam, $usuarioId, $busca);
        } else {
            $sql = $sqlBase . "WHERE m.responsavel = ? AND m.titulo LIKE ?";
            $stmt = prepararConsulta($conn, $sql, "is", $usuarioId, $busca);
        }
    }

    $stmt->execute();
    $result = $stmt->get_result();
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
</head>
<body>
<aside class="sidebar" role="complementary" aria-label="Menu lateral">
    <img src="../img/LogoPortalSenai.svg" alt="Portal Senai Manutenções" class="logo-senai">
    <div class="spacer"></div>
    <img src="../img/TechFix.svg" alt="Techfix Facilities" class="logo-techfix">
</aside>

<main class="main-content d-flex flex-md-column w-100 h-100" role="main">
    <header class="top-bar">
        <form method="GET" class="search-box" autocomplete="off">
            <input type="text" name="busca" placeholder="Buscar...">
            <button type="submit"><img src="../img/IconSearch.svg" alt="Buscar"></button>
        </form>

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
            <a href="editar_funcionario.php?id=<?= $_SESSION['id'] ?>" style="text-decoration: none; color: #212529;" title="Editar meus dados">
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

    <section class="cards-container">
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
                                <div class="d-flex conjunto">
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

<script>
document.querySelectorAll('.status-btn').forEach(btn => {
  btn.addEventListener('click', e => {
    e.preventDefault();
    const urlParams = new URLSearchParams(window.location.search);

    // Se for “Seus Trabalhos”
    if (btn.dataset.meus !== undefined) {
      if (urlParams.has('meus')) {
        urlParams.delete('meus');
      } else {
        urlParams.delete('status');
        urlParams.set('meus', '1');
      }
    } else {
      // um botão de status normal
      urlParams.delete('meus');
      const status = btn.dataset.status;
      if (urlParams.get('status') === status) {
        urlParams.delete('status');
      } else {
        urlParams.set('status', status);
      }
    }
    // mantém o termo de busca
    window.location.search = urlParams.toString();
  });
});
document.querySelectorAll('.avatar').forEach(function(avatar) {
    const letra = avatar.textContent.trim().charAt(0).toLowerCase();
    if (letra.match(/[a-z]/i)) {
      avatar.classList.add(letra);
    }
  });
</script>
</main>

</body>
</html>
