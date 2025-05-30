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
        .search-container {
            position: relative;
            margin: auto;
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
