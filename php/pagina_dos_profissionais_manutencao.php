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
    <link rel="stylesheet" href="../css/profissionais_manutencao.css">
    <link rel="icon" type="image/png" href="../img/senai-logo 5.svg">
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

            .welcome-msg,
            .logout-btn {
                display: none !important;
            }

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

        .no-results {
            text-align: center;
            margin-top: 60px;
            color: #818181;
            font-size: 26px;
        }

        .no-results img {
            width: 60px;
            height: 60px;
            margin-bottom: 10px;
            opacity: 0.7;
        }
    </style>
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
            <a href="?status=pendente" class="status-btn <?php echo (isset($_GET['status']) && $_GET['status'] == 'pendente') ? 'active' : ''; ?>">Pendente</a>
            <a href="?status=em%20andamento" class="status-btn <?php echo (isset($_GET['status']) && $_GET['status'] == 'em andamento') ? 'active' : ''; ?>">Seus Trabalhos</a>
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

        $status = isset($_GET['status']) ? $_GET['status'] : '';
        $busca = isset($_GET['busca']) ? '%' . $_GET['busca'] . '%' : '%';
        $nif = $_SESSION['nif'];

        if ($status == 'em andamento') {
            $status = 'em andamento';
            $sql = "SELECT * FROM manutencao WHERE executante = ? AND status = ? AND titulo LIKE ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sss", $nif, $status, $busca);
        } elseif ($status == 'pendente') {
            $status = 'pendente';
            $sql = "SELECT * FROM manutencao 
                    WHERE status = ? 
                    AND titulo LIKE ?
                    AND titulo NOT IN (SELECT titulo FROM rejeicoes WHERE docente = ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sss", $status, $busca, $nif);
        } else {
            $sql = "SELECT * FROM manutencao 
                    WHERE titulo LIKE ? 
                    AND titulo NOT IN (SELECT titulo FROM rejeicoes WHERE docente = ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $busca, $nif);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 0) {
            echo '
            <div class="no-results">
                   <img src="../img/no-results 1.svg" alt="Sem pedidos" style="width: 200px; height: 300px;">
                <p>Nenhum pedido de manutenção foi encontrado.</p>
            </div>';
        } else {
            while ($row = $result->fetch_assoc()) {
                ?>
                <a href="candidatar.php?titulo=<?php echo urlencode($row['titulo']); ?>" class="card-link">
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
        }

        $stmt->close();
        $conn->close();
        ?>
    </section>
</main>
</body>
</html>
