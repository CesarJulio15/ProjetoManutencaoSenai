<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Senai Manutenções</title>
    <link rel="stylesheet" href="../css/manutencao.css">
</head>
<body>

    <aside class="sidebar">
        <img src="../img/LogoPortalSenai.svg" alt="Portal Senai Manutenções" class="logo-senai">
        <div class="spacer"></div> <!-- Espaço para afastar -->
        <img src="../img/TechFix.svg" alt="Techfix Facilities" class="logo-techfix">
    </aside>

    <main class="main-content">
        <header class="top-bar">
            <div class="search-box">
                <input type="text" placeholder="Buscar...">
                <button><img src="../img/IconSearch.svg" alt="Buscar"></button>
            </div>
            <div class="status-buttons">
                <button class="status-btn active">Pendente</button>
                <button class="status-btn">Em andamento</button>
                <button class="status-btn">Concluído</button>
            </div>
            <div class="profile-icon">
                <img src="../img/user-icon.png" alt="Perfil">
            </div>
        </header>

        <section class="cards-container">
            <?php
            for ($i = 0; $i < 8; $i++) {
            ?>
                <div class="card">
                    <div class="card-header">
                        <div class="avatar">A</div>
                        <span>Nome do Funcionário</span>
                        <div class="flag"></div>
                    </div>
                    <div class="card-body">
                        <div class="card-image"></div>
                    </div>
                    <div class="card-footer">
                        <h4>Descrição Breve</h4>
                        <p>Descrição</p>
                        <button class="status pending">Pendente</button>
                    </div>
                </div>
            <?php
            }
            ?>
        </section>

        <a href="solicita.php" class="add-button">
            <img src="../img/PlusCircle.svg" alt="Adicionar" class="add-button-img">
        </a>

    </main>

</body>
</html>
