<?php
session_start();
if (!isset($_SESSION['credencial'])) {
    header("Location: login.php");
    exit;
}
?>
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
        <div class="spacer"></div>
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
            <div class="welcome-msg">
                <?php echo "Olá, " . htmlspecialchars($_SESSION['nome']) . "!"; ?>
            </div>
        </header>

        <section class="cards-container">
        <?php
        require_once 'conexao.php';

        $sql = "SELECT * FROM manutencao";
        $result = $conn->query($sql);

        while ($row = $result->fetch_assoc()) {
        ?>
            <div class="card">
                <div class="card-header">
                    <div class="avatar"><?php echo substr($row['responsavel'], 0, 1); ?></div>
                    <span><?php echo $row['responsavel']; ?></span>
                    <div class="flag"></div>
                </div>
                <div class="card-body">
                    <div class="card-image">
                        <?php
                        if (!empty($row['foto'])) {
                            $fotoBase64 = base64_encode($row['foto']);
                            echo "<img src='data:image/jpeg;base64,{$fotoBase64}' alt='Imagem da manutenção'>";
                        }
                        ?>
                    </div>
                </div>
                <div class="card-footer">
                    <h4><?php echo $row['titulo']; ?></h4>
                    <p><?php echo $row['descricao']; ?></p>
                    <button class="status pending">Pendente</button>
                </div>
            </div>
        <?php
        }
        ?>
        </section>

        <a href="solicita.php">
            <img src="../img/PlusCircle.svg" alt="Adicionar" class="add-button-img">
        </a>
    </main>

</body>
</html>
