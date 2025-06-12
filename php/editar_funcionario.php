<?php
session_start();
include 'conexao.php';

if (!isset($_SESSION['id']) || !isset($_SESSION['tipo'])) {
    header("Location: index.php");
    exit;
}

$idUsuario = $_SESSION['id'];
$tipoUsuario = $_SESSION['tipo'];

if (!isset($_GET['id'])) {
    echo "ID inválido.";
    exit;
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    echo "ID inválido.";
    exit;
}

if ($tipoUsuario !== 'coordenador' && $idUsuario != $id) {
    echo "Acesso negado.";
    exit;
}

function buscarDocentePorId($conn, $id) {
    $stmt = $conn->prepare("SELECT * FROM docentes WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $docente = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $docente;
}

$docente = buscarDocentePorId($conn, $id);
if (!$docente) {
    echo "Docente não encontrado.";
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <title>Editar Docente</title>
        <link rel="stylesheet" href="../css/padrao.css">
        <link rel="stylesheet" href="../css/manutencao.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css" integrity="sha512-MV7K8+y+gLIBoVD59lQIYicR65iaqukzvf/nwasF0nqhPay5w/9lJmVM2hMDcnK1OnMGCdVK+iQrJ7lzPJQd1w==" crossorigin="anonymous" referrerpolicy="no-referrer" />
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
        <style>
            .form-group .icon {
                display: inline-flex;
                background-color: var(--light-gray-color);
                color: var(--blue-color);
                padding: 9px 4px;
                margin-right: 0;
                border-radius: 4px 0 0 4px;
                justify-content: center;
                align-items: center;
                align-content: center;
                user-select: none;
            }
            .form-group:focus-within .icon {
                background-color: var(--blue-color);
                color: var(--white-color);
                box-shadow: var(--box-shadow);
            }
        </style>
    </head>
<body class="d-flex flex-md-row flex-wrap w-100 h-100">
<aside class="sidebar">
    <img src="../img/LogoPortalSenai.svg" class="logo-senai" alt="Logo Senai">
    <div class="spacer"></div>
    <img src="../img/TechFix.svg" class="logo-techfix" alt="Logo TechFix">
</aside>

<main class="main-content d-flex flex-md-column flex-wrap w-100 h-100">
    <div class="top-bar">
        <a href="panelTecnico.php" class="botao-voltar" title="Voltar"><i class="fa-solid fa-arrow-left"></i></a>
        <div class="title">
        <h3>Editar Funcionário</h3>
        </div>
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
    <form action="atualizar_docente.php" method="post" class="form-box d-flex flex-md-column flex-wrap justify-content-between align-items-center card w-75 h-100 mt-3">
        <input type="hidden" name="id" value="<?= $docente['id'] ?>">
        <fieldset class="d-flex flex-wrap w-100 h-100">
            <legend>Dados pessoais</legend>

            <div class="d-flex flex-wrap w100" style="gap:10px;">
        <div class="d-flex flex-wrap form-group" style="gap:10px;">        
            <label><b>Nome:</b></label>
            <span class="icon material-symbols-rounded">person</span>
            <input type="text" name="nome" value="<?= htmlspecialchars($docente['nome'], ENT_QUOTES) ?>" required>
        </div>
        <div class="d-flex flex-wrap form-group" style="gap:10px;">        
            <label><b>NIF:</b></label>
            <input type="text" name="nif" value="<?= htmlspecialchars($docente['nif']) ?>" required>
        </div>
        <div class="d-flex flex-wrap" style="gap:10px;">        
            <label><b>Status:</b></label>
            <select name="status" <?= $tipoUsuario !== 'coordenador' ? 'disabled' : '' ?> required>
            <option value="Ativo" <?= $docente['docente_status'] === 'Ativo' ? 'selected' : '' ?>>Ativo</option>
            <option value="Inativo" <?= $docente['docente_status'] === 'Inativo' ? 'selected' : '' ?>>Inativo</option>
        </select>
        </div>
        </div>
        <div class="d-flex flex-row flex-wrap w-100" style="gap:10px;">
            <div class="d-flex flex-wrap" style="gap:10px;">        
                <label><b>Email:</b></label>
                    <input type="email" name="email" value="<?= htmlspecialchars($docente['email']) ?>" placeholder="E-mail" required>
            </div>
            <div class="d-flex flex-wrap" style="gap:10px;">
                    <label><b>Senha </b>(deixe em branco para não alterar):</label>
                    <input type="password" name="senha" placeholder="Senha (deixe em branco para não alterar)"></div>
            </div>
        </div>
        <div class="d-flex flex-wrap"></div>
        <label><b>Tipo:</b></label>
        <select name="tipo" <?= $tipoUsuario !== 'coordenador' ? 'disabled' : '' ?>>
            <option value="Coordenador" <?= $docente['tipo'] === 'Coordenador' ? 'selected' : '' ?>>Coordenador</option>
            <option value="Docente" <?= $docente['tipo'] === 'Docente' ? 'selected' : '' ?>>Docente</option>
            <option value="Técnico" <?= $docente['tipo'] === 'Técnico' ? 'selected' : '' ?>>Técnico</option>
        </select>
        <?php if ($tipoUsuario !== 'coordenador'): ?>
            <input type="hidden" name="tipo" value="<?= $docente['tipo'] ?>">
        <?php endif; ?>

        </fieldset>

        <div class="form-footer">
        <button type="submit" class="btn-confirma">Salvar</button>
        </div>

    </form>
</main>
</body>
</html>
