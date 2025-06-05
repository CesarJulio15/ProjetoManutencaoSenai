<?php
session_start();
include 'conexao.php';

if (!isset($_SESSION['id']) || $_SESSION['tipo'] !== 'coordenador') {
    header("Location: index.php");
    exit();
}


// Filtro e paginação
$filtro_tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$limite = 6;
$offset = ($pagina - 1) * $limite;

// Query principal com filtro e paginação
if (!empty($filtro_tipo)) {
    $stmt = $conn->prepare("SELECT * FROM docentes WHERE tipo = ? AND docente_status = 'Ativo' LIMIT ? OFFSET ?");
    $stmt->bind_param("sii", $filtro_tipo, $limite, $offset);
} else {
    $stmt = $conn->prepare("SELECT * FROM docentes WHERE docente_status = 'Ativo' LIMIT ? OFFSET ?");
    $stmt->bind_param("ii", $limite, $offset);
}
$stmt->execute();
$result = $stmt->get_result();

// Contagem total para paginação
if (!empty($filtro_tipo)) {
    $count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM docentes WHERE tipo = ?  AND docente_status = 'Ativo'");
    $count_stmt->bind_param("s", $filtro_tipo);
} else {
    $count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM docentes WHERE docente_status = 'Ativo'");
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_registros = $count_result->fetch_assoc()['total'];
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
<style>

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
  <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/js/all.min.js" integrity="sha512-b+nQTCdtTBIRIbraqNEwsjB6UvL3UEMkXnhzd8awtCYh0Kcsjl9uEgwVFVbhoj3uu1DO1ZMacNvLoyJJiNfcvg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>
</body>
</html>
