<?php

session_start();
if (!isset($_SESSION['credencial']) || !in_array($_SESSION['tipo'], ['adm', 'manutencao', ])) {
    header("Location: index.php");
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
    <link rel="icon" type="image/png" href="../img/senai-logo 5.svg">
</head>
<body>

    <aside class="sidebar">
        <img src="../img/LogoPortalSenai.svg" alt="Portal Senai Manutenções" class="logo-senai">
        <div class="spacer"></div>
        <img src="../img/TechFix.svg" alt="Techfix Facilities" class="logo-techfix">
    </aside>

    <main class="main-content">
        <header class="top-bar">
        <form method="GET" class="search-box">
  <input type="text" name="busca" placeholder="Buscar...">
  <button type="submit"><img src="../img/IconSearch.svg" alt="Buscar"></button>
</form>
            <div class="status-buttons">
    <a href="?status=Pendente" class="status-btn <?php echo (isset($_GET['status']) && $_GET['status'] == 'Pendente') ? 'active' : ''; ?>">Pendente</a>
    <a href="?status=Em%20Andamento" class="status-btn <?php echo (isset($_GET['status']) && $_GET['status'] == 'Em Andamento') ? 'active' : ''; ?>">Em andamento</a>
    <a href="?status=Concluído" class="status-btn <?php echo (isset($_GET['status']) && $_GET['status'] == 'Concluído') ? 'active' : ''; ?>">Concluído</a>
</div>

            <div class="profile-icon">
                <?php
                require_once 'conexao.php';

                $credencial = $_SESSION['credencial'];
                $sql = "SELECT foto FROM usuarios WHERE credencial = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("s", $credencial);
                $stmt->execute();
                $stmt->bind_result($foto);
                $stmt->fetch();
                $stmt->close();

                if (!empty($foto)) {
                    $fotoBase64 = base64_encode($foto);
                    echo "<img src='data:image/jpeg;base64,{$fotoBase64}' alt='Foto do usuário' style='border-radius: 50%; width: 40px; height: 40px; object-fit: cover;'>";
                } else {
                    echo "<img src='../img/user-icon.png' alt='Foto padrão'>";
                }
                ?>
            </div>

            <div class="welcome-msg">
                <?php echo "Olá, " . htmlspecialchars($_SESSION['nome']) . "!"; ?>
            </div>
            <div class="logout-btn">
    <a href="logout.php" title="Sair">
        <img src="../img/logout.svg" alt="Logout" style="width: 24px; height: 24px;">
    </a>
</div>
        </header>

        <section class="cards-container">
        <?php
        require_once 'conexao.php';

        // Filtra os dados com base no status
        $status = isset($_GET['status']) ? $_GET['status'] : '';

        $status = isset($_GET['status']) ? $_GET['status'] : '';
        $busca = isset($_GET['busca']) ? '%' . $_GET['busca'] . '%' : '%';
        
        if ($status) {
            $sql = "SELECT * FROM manutencao WHERE status = ? AND titulo LIKE ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $status, $busca);
        } else {
            $sql = "SELECT * FROM manutencao WHERE titulo LIKE ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $busca);
        }
        

        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
        ?>
       <?php
$link = ($_SESSION['tipo'] === 'manutencao') 
    ? 'candidatar.php?titulo=' . urlencode($row['titulo']) 
    : 'detalhe.php?titulo=' . urlencode($row['titulo']);
?>
<a href="<?php echo $link; ?>" class="card-link">
            <div class="card">
                <div class="card-header">
                    <div class="avatar"><?php echo substr($row['responsavel'], 0, 1); ?></div>
                    <span><?php echo htmlspecialchars($row['responsavel']); ?></span>
                    <div class="flag"></div>
                </div>
                <div class="card-body card-image">
                    <?php
                        if (!empty($row['foto'])) {
                            $fotoBase64 = base64_encode($row['foto']);
                            echo "<img src='data:image/jpeg;base64,{$fotoBase64}' alt='Imagem da manutenção'>";
                        }
                    ?>
                </div>
                <div class="card-footer">
                    <h4><?php echo htmlspecialchars($row['titulo']); ?></h4>
                    <p><?php echo htmlspecialchars($row['descricao']); ?></p>
                    <button class="status <?php echo strtolower(str_replace(' ', '-', $row['status'])); ?>">
                        <?php echo htmlspecialchars($row['status']); ?>
                    </button>
                </div>
            </div>
        </a>

        <?php
        }
        ?>
        </section>
        <a href="solicita.php">
            <img src="../img/PlusCircle.svg" alt="Adicionar" class="add-button-img">
            </a>
    <a href="cadastro.php">
        <img src="../img/user.webp" alt="add-user" class="add-user-img">
    </a>     
    </main>

</body>
</html>
