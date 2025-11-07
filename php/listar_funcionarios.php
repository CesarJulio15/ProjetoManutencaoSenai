<?php
    session_start();
    include 'conexao.php';

    if (!isset($_SESSION['id'])) {
        header("Location: index.php");
        exit();
    }
    else if ($_SESSION['tipo'] !== 'coordenador') {
        header("Location: error403.php");
        exit();
    }

    function buscarEscolas($conn) {
        $sql = "SELECT id, nome, cfp FROM escolas ORDER BY cfp";
        return $conn->query($sql);
    }

    function buscarFuncionarios($conn, $filtro_tipo, $pagina, $limite, $escola_id = 0) {
        $offset = ($pagina - 1) * $limite;

        $query = "
            SELECT d.*
            FROM docentes d
            LEFT JOIN docentes_escolas de ON d.id = de.docente_id
            WHERE d.docente_status = 'Ativo'
        ";

        $params = [];
        $types = '';

        if (!empty($filtro_tipo)) {
            $query .= " AND d.tipo = ?";
            $types .= 's';
            $params[] = $filtro_tipo;
        }

        if ($escola_id > 0) {
            $query .= " AND de.escola_id = ?";
            $types .= 'i';
            $params[] = $escola_id;
        }

        $query .= " LIMIT ? OFFSET ?";
        $types .= 'ii';
        $params[] = $limite;
        $params[] = $offset;

        $stmt = $conn->prepare($query);

        if (count($params) === 2) {
            $stmt->bind_param($types, $params[0], $params[1]);
        } elseif (count($params) === 3) {
            $stmt->bind_param($types, $params[0], $params[1], $params[2]);
        } elseif (count($params) === 4) {
            $stmt->bind_param($types, $params[0], $params[1], $params[2], $params[3]);
        }

        $stmt->execute();
        return $stmt->get_result();
    }


    function contarFuncionarios($conn, $filtro_tipo, $escola_id = 0) {
        $query = "
            SELECT COUNT(DISTINCT d.id) as total
            FROM docentes d
            LEFT JOIN docentes_escolas de ON d.id = de.docente_id
            WHERE d.docente_status = 'Ativo'
        ";

        $params = [];
        $types = '';

        if (!empty($filtro_tipo)) {
            $query .= " AND d.tipo = ?";
            $types .= 's';
            $params[] = $filtro_tipo;
        }

        if ($escola_id > 0) {
            $query .= " AND de.escola_id = ?";
            $types .= 'i';
            $params[] = $escola_id;
        }

        $stmt = $conn->prepare($query);

        if (!empty($params)) {
            if (count($params) === 1) {
                $stmt->bind_param($types, $params[0]);
            } elseif (count($params) === 2) {
                $stmt->bind_param($types, $params[0], $params[1]);
            }
        }

        $stmt->execute();
        $res = $stmt->get_result();
        return $res->fetch_assoc()['total'];
    }
    // Verifica se o usuário está logado e é coordenador
    $escolas_result = buscarEscolas($conn);
    $escola_id = isset($_GET['escola_id']) ? intval($_GET['escola_id']) : 0;

    // Filtro e paginação
    $filtro_tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';
    $pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
    $limite = 6;

    $result = buscarFuncionarios($conn, $filtro_tipo, $pagina, $limite, $escola_id);
    $total_registros = contarFuncionarios($conn, $filtro_tipo, $escola_id);
    $total_paginas = ceil($total_registros / $limite);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Lista de Funcionários</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
    <link rel="stylesheet" href="../css/padrao.css">
    <link rel="stylesheet" href="../css/manutencao.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-9012455024725100"
     crossorigin="anonymous"></script>
<style>
    *{
        user-select:none;
    }
    table thead th:first-child {
        border-top: 1px solid transparent;
        border-left: 1px solid transparent;
        border-radius: 25px 0px 0px 0px;
    }
    #escola_id{
        border-radius:20px;
    }
</style>
</head>
<body >
<aside class="sidebar animate__animated animate__fadeInLeft">
    <img src="../img/LogoPortalSenai.svg" alt="Portal Senai Manutenções" class="logo-senai">
    <div class="spacer"></div>
    <img src="../img/TechFix.svg" alt="Techfix Facilities" class="logo-techfix">
</aside>

<main class="main-content">
    <div class="top-bar animate__animated animate__fadeInDown">
    <a href="panelTecnico.php" class="botao-voltar" title="Voltar"><i class="fa-solid fa-arrow-left"></i></a>
    <h2 class="title text-center mb-4">Lista de Funcionários</h2>
        <form method="get" class="search-box ">
            <select name="escola_id">
                <option value="0">Todas as escolas</option>
                <?php while ($escola = $escolas_result->fetch_assoc()): ?>
                    <option value="<?= $escola['id'] ?>" <?= $escola_id == $escola['id'] ? 'selected' : '' ?>>
                        <?= '' . htmlspecialchars($escola['cfp']) . ' - ' . htmlspecialchars($escola['nome']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <select name="tipo">
                <option value="">Todos os tipos</option>
                <option value="Coordenador" <?= $filtro_tipo == 'Coordenador' ? 'selected' : '' ?>>Coordenador</option>
                <option value="Docente" <?= $filtro_tipo == 'Docente' ? 'selected' : '' ?>>Docente</option>
                <option value="Técnico" <?= $filtro_tipo == 'Técnico' ? 'selected' : '' ?>>Técnico</option>
            </select>
            <button type="submit" title="Filtrar">
                <span class="material-symbols-outlined">filter_alt</span>
            </button>
        </form>
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
    </div>
    <div class="animate__animated animate__fadeInUp" style="overflow-x:auto;  padding:15px;">
    <?php if ($result->num_rows > 0): ?>
        <table class="mt-3" style="border-radius:15px 15px 0 0;">
            <thead>
                <tr>
                    <th style="width:10%">NIF</th>
                    <th>Nome</th>
                    <th>Email</th>
                    <th>Tipo</th>
                    <th style="width:15%">Ação</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['nif']) ?></td>
                    <td><?= htmlspecialchars($row['nome']) ?></td>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                    <td><?= htmlspecialchars($row['tipo']) ?></td>
                    <td>
                        <a class="btn-confirma" href="editar_funcionario.php?id=<?= $row['id'] ?>" class="btn-confirma" title="Editar">
                            <span class="material-symbols-outlined">edit_square</span>
                        </a>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>

        <div class="mt-3 paginacao">
            <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                <a href="?pagina=<?= $i ?>&tipo=<?= urlencode($filtro_tipo) ?>" class="<?= $pagina == $i ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php else: ?>
        <div class="no-results animate__animated animate__fadeInUp">
            <img src="../img/no-results 1.svg" alt="Não foram encontrados registros" style="width: 200px; height: 300px;">
            <p>Nenhum registro de funcionário foi encontrado.</p>
        </div>
    <?php endif; ?>
</div>
</div>
</main>
<?php if ($_SESSION['tipo'] == 'coordenador'): ?>
    <a href="cadastro.php" title="Cadastrar funcionário">
        <img src="../img/adicionar-usuario.png" alt="add-user" class="add-user-img">
    </a>
    <a href="dashboard.php" title="Visualizar Dashboard">
        <img src="../img/painel-de-controle.png" alt="add-user" class="add-dash-img">
    </a>
    <a href="solicita.php" title="Abrir ordem de serviço">
        <img src="../img/documento.png" alt="Adicionar" class="add-button-img">
    </a>
    <?php else :?>
            <a href="solicita.php" title="Abrir ordem de serviço">
        <img src="../img/documento.png" alt="Adicionar" class="add-button-img">
    </a>
  <?php endif; ?>
  <script>
document.getElementById('btnVincular').addEventListener('click', function () {
    const escolaId = document.getElementById('nova_escola').value;
    const docenteId = <?= $id ?>;
    if (!escolaId) return;

    fetch('vincular_docente_escola.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
            docente_id: docenteId,
            escola_id: escolaId,
            acao: 'vincular'
        })
    })
    .then(resp => resp.text())
    .then(resp => location.reload());
});

document.querySelectorAll('.remover-vinculo').forEach(btn => {
    btn.addEventListener('click', function () {
        const escolaId = this.dataset.escola;
        const docenteId = <?= $id ?>;

        fetch('vincular_docente_escola.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({
                docente_id: docenteId,
                escola_id: escolaId,
                acao: 'remover'
            })
        })
        .then(resp => resp.text())
        .then(resp => location.reload());
    });
});
</script>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/js/all.min.js" integrity="sha512-b+nQTCdtTBIRIbraqNEwsjB6UvL3UEMkXnhzd8awtCYh0Kcsjl9uEgwVFVbhoj3uu1DO1ZMacNvLoyJJiNfcvg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>
</body>
</html>
