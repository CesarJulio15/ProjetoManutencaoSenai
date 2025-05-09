<?php
session_start();
if (!isset($_SESSION['nif']) || !in_array($_SESSION['tipo'], ['adm', 'manutencao'])) {
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
<style>
@media (max-width: 768px) {
    .sidebar {
        display: none !important;
    }

    .top-bar {
        display: flex;
        flex-direction: column;
        align-items: stretch;
        width: 100%;
        padding: 10px;
        box-sizing: border-box;
    }

    .search-box {
        width: 100%;
        display: flex;
        padding: 0;
        margin-bottom: 10px;
    }

    .search-box input {
        flex: 1;
        padding: 10px;
        font-size: 16px;
    }

    .search-box button {
        padding: 10px;
        background: none;
        border: none;
    }

    .status-buttons {
        display: flex;
        justify-content: space-between;
        gap: 5px;
        width: 100%;
    }

    .status-buttons .status-btn {
        flex: 1;
        font-size: 14px;
        padding: 10px 0;
        text-align: center;
        border-radius: 5px;
        background-color: #e0e0e0;
    }

    .status-buttons .status-btn.active {
        background-color: red;
        color: white;
    }
}
@media (max-width: 768px) {
    .welcome-msg,
    .logout-btn {
        display: none !important;
    }
}
@media (max-width: 768px) {
    .cards-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 0 10px;
    }

    .card-link {
        width: 100%;
        margin-bottom: 16px;
    }

    .card {
        width: 100%;
        box-sizing: border-box;
    }
}
</style>

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

            <div class="welcome-msg">
                <?php echo htmlspecialchars($_SESSION['nome']); ?>
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

        // Verifica se não há registros
        if ($result->num_rows == 0) {
        ?>
            <div class="no-results">
                <img src="../img/no-results 1.svg" alt="Sem pedidos" style="width: 200px; height: 300px;">
                <p>Nenhum pedido de manutenção foi encontrado.</p>
            </div>
        <?php
        } else {
            while ($row = $result->fetch_assoc()) {
                // Define a cor da flag com base no status
                $flagColor = '';
                switch ($row['status']) {
                    case 'Pendente':
                        $flagColor = 'red';
                        break;
                    case 'Em Andamento':
                        $flagColor = 'yellow';
                        break;
                    case 'Concluído':
                        $flagColor = 'green';
                        break;
                    default:
                        $flagColor = 'gray'; // Default caso não tenha um status definido
                }
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
        }
        ?>
        </section>
        <a href="solicita.php">
            <img src="../img/documento.png" alt="Adicionar" class="add-button-img">
        </a>
        <a href="cadastro.php">
            <img src="../img/adicionar-usuario.png" alt="add-user" class="add-user-img">
        </a>
        <a href="dashboard.php">
            <img src="../img/painel-de-controle.png" alt="add-user" class="add-dash-img">
        </a>
    </main>

</body>
</html>
