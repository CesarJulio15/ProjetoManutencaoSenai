<?php
session_start();
require_once __DIR__ . '/conexao.php';

if (!isset($_SESSION['id']) || !isset($_SESSION['tipo'])) {
    header("Location: index.php");
    exit();
}

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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <style>

    </style>
</head>
<body>
<aside class="sidebar animate__animated animate__fadeInLeft" role="complementary" aria-label="Menu lateral">
    <img src="../img/LogoPortalSenai.svg" alt="Portal Senai Manutenções" class="logo-senai">
    <div class="spacer"></div>
    <img src="../img/TechFix.svg" alt="Techfix Facilities" class="logo-techfix">
</aside>

<main class="main-content d-flex flex-md-column w-100 h-100" role="main">
    <header class="top-bar  animate__animated animate__fadeInDown">
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
            <div class="logout-btn animate__animated animate__fadeInRight">
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
                <a href="<?php echo $link; ?>" class="card-link  animate__animated animate__fadeInUp">
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
                    <div class="paginacao mt-3  animate__animated animate__fadeInUp"></div>
                        <?php if ($_SESSION['tipo'] == 'coordenador'): ?>
                    <a href="listar_funcionarios.php" title="Visualizar funcionários" class="animate__animated animate__fadeInUp">
                        <img src="../img/gerenciar-grupo.png" alt="add-user" class="add-user-img">
                    </a>
                    <a href="dashboard.php" title="Visualizar Dashboard" class="animate__animated animate__fadeInUp">
                        <img src="../img/painel-de-controle.png" alt="add-user" class="add-dash-img">
                    </a>
                    <a href="solicita.php" title="Abrir ordem de serviço" class="animate__animated animate__fadeInUp">
                        <img src="../img/documento.png" alt="Adicionar" class="add-button-img">
                    </a>
                    <?php else :?>
                            <a href="solicita.php" class="animate__animated animate__fadeInUp">
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
