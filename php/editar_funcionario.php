<?php
    session_start();
    require_once 'conexao.php';

    // Redireciona se não estiver logado
    if (!isset($_SESSION['id']) || !isset($_SESSION['tipo'])) {
        header("Location: index.php");
        exit;
    }

    $idUsuario = $_SESSION['id'];
    $tipoUsuario = $_SESSION['tipo'];

    // Sanitiza e valida o ID do docente
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if (!$id) {
        http_response_code(400);
        exit("ID inválido.");
    }

    // Busca docente no banco
    function buscarDocentePorId(mysqli $conn, int $id): ?array {
        $stmt = $conn->prepare("SELECT * FROM docentes WHERE id = ?");
        if (!$stmt) {
            error_log("Erro ao preparar: " . $conn->error);
            return null;
        }
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $docente = $resultado->fetch_assoc();
        $stmt->close();
        return $docente ?: null;
    }

    $docente = buscarDocentePorId($conn, $id);

    if (!$docente) {
        http_response_code(404);
        exit("Docente não encontrado.");
    }

    // Verifica permissão de acesso
    if ($tipoUsuario !== 'coordenador' && $idUsuario !== $id) {
        header("Location: erro403.php");
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
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/js/all.min.js" integrity="sha512-b+nQTCdtTBIRIbraqNEwsjB6UvL3UEMkXnhzd8awtCYh0Kcsjl9uEgwVFVbhoj3uu1DO1ZMacNvLoyJJiNfcvg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded" />
        <style>
            input{
                text-align:left;
            }
            .form-group .icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            user-select: none;
            padding: 10px;
            margin-right: 0;
            border-radius: 16px 0 0 16px;
            background: rgba(255, 255, 255, 0.2);
            color: var(--azul);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease-in-out;
            }
            .form-group:focus-within .icon {
            background: var(--azul); /* ou use um tom como #3498db */
            color: white;
            box-shadow: inset -4px -4px 6px -1px white,
                        inset 2px 2px 8px -1px rgba(72, 79, 96, 0.5);
            }
            fieldset input[type="text"], fieldset input[type="email"], fieldset input[type="password"], fieldset select{
                border-radius: 0px 14px 16px 0px;
                -webkit-border-radius: 0px 14px 16px 0px;
                -moz-border-radius: 0px 14px 16px 0px;
            }

            .status-indicador {
                width: 64px;
                height: 48px;
                position: absolute;
                top: 0;
                right: 0;
                display: inline-flex;
                padding: 6px 16px;
                font-size: 0.85rem;
                font-weight: bold;
                text-align: center;
                color: white;
                border-radius: 0px 0.5em 0px 0.5em;
                -webkit-border-radius: 0px 0.5em 0px 0.5em;
                -moz-border-radius: 0px 0.5em 0px 0.5em;
                background: red;
                user-select: none;
                transition: all 0.3s ease-in-out;
            }
            .status-indicador.ativo {
            background-color: #5b7f2e;
            }
            .status-indicador.inativo {
            background-color: #dc3545;
            }
            .borda-ativa {
            border: 2px solid #5b7f2e !important;
            }
            .borda-inativa {
            border: 2px solid #dc3545 !important;
            }
        </style>
    </head>
<body class="d-flex flex-row flex-wrap w-100 h-100">
    <aside class="sidebar vh-100">
        <img src="../img/LogoPortalSenai.svg" class="logo-senai" alt="Logo Senai">
        <div class="spacer"></div>
        <img src="../img/TechFix.svg" class="logo-techfix" alt="Logo TechFix">
    </aside>
    <main class="main-content d-flex flex-md-column flex-wrap w-100 h-100">
        <div class="top-bar">
            <a href="panelTecnico.php" class="botao-voltar" title="Voltar"><i class="fa-solid fa-chevron-left"></i></a>
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
        <form action="atualizar_docente.php" method="post" 
        class="form-box d-flex flex-md-column flex-wrap justify-content-between align-items-center card w-75 h-100 mt-3 
        <?= $docente['docente_status'] === 'Ativo' ? 'borda-ativa' : 'borda-inativa' ?>" style="max-width:fit-content;">
            <div class="status-indicador-container d-flex justify-content-end w-100 pe-4">
                <div class="badge status-indicador <?= $docente['docente_status'] === 'Ativo' ? 'ativo' : 'inativo' ?>">
                </div>
            </div>        
            <input type="hidden" name="id" value="<?= $docente['id'] ?>">        
            <?php if ($tipoUsuario !== 'coordenador'): ?>
                <input type="hidden" name="tipo" value="<?= $docente['tipo'] ?>">
            <?php endif; ?>
            <fieldset class="d-md-inline-flex flex-lg-column flex-wrap justify-content-lg-center align-items-center w-100 h-100 mt-3" id="dados">
                <legend>Dados pessoais</legend>

                <div class="d-flex flex-wrap w-100" style="gap:10px;">
                    <div class="d-flex flex-nowrap form-group" style="height:48px;">        
                        <span class="icon material-symbols-rounded">person</span>
                        <input type="text" name="nome" value="<?= htmlspecialchars($docente['nome'], ENT_QUOTES) ?>" placeholder="Insira o nome do funcionário." required>
                    </div>
                    <div class="d-flex flex-nowrap form-group" style="height:48px;">        
                        <span class="icon material-symbols-rounded">badge</span>
                        <input type="text" name="nif" value="<?= htmlspecialchars($docente['nif']) ?>" placeholder="Insira o nif do funcionário." required <?= $tipoUsuario !== 'coordenador' ? 'disabled' : '' ?>>
                    </div>
                </div>
                <div class="d-flex flex-wrap w-100" style="gap:10px;">
                    <div class="d-flex flex-nowrap form-group" style="height:48px;">        
                        <span class="icon material-symbols-rounded">email</span>
                        <input type="email" name="email" value="<?= htmlspecialchars($docente['email']) ?>" placeholder="E-mail" required <?= $tipoUsuario !== 'coordenador' ? 'disabled' : '' ?>>
                    </div>
                    <div class="d-flex flex-nowrap form-group" style="height:48px;">
                        <span class="icon material-symbols-rounded">password</span>
                        <input type="password" name="senha" placeholder="Senha (deixe em branco para não alterar)"></div>
                    </div>
                </div>
                <div class="d-flex flex-wrap w-100" style="gap:10px;">
                    <div class="d-flex flex-nowrap form-group" style="height:48px;">        
                        <span class="icon material-symbols-rounded">check_circle</span>
                        <select name="status" class="custom-select" <?= $tipoUsuario !== 'coordenador' ? 'disabled' : '' ?> style="min-width:100px;" required>
                            <option value="Ativo" <?= $docente['docente_status'] === 'Ativo' ? 'selected' : '' ?>>Ativo</option>
                            <option value="Inativo" <?= $docente['docente_status'] === 'Inativo' ? 'selected' : '' ?>>Inativo</option>
                        </select>
                    </div>            
                    <div  class="d-flex flex-nowrap form-group" style="height:48px;">
                        <span class="icon material-symbols-rounded">manage_accounts</span>
                        <select name="tipo" class="custom-select" <?= $tipoUsuario !== 'coordenador' ? 'disabled' : '' ?> style="min-width:150px;" required>
                            <option value="Coordenador" <?= $docente['tipo'] === 'Coordenador' ? 'selected' : '' ?>>Coordenador</option>
                            <option value="Docente" <?= $docente['tipo'] === 'Docente' ? 'selected' : '' ?>>Docente</option>
                            <option value="Técnico" <?= $docente['tipo'] === 'Técnico' ? 'selected' : '' ?>>Técnico</option>
                            <option value="Outros" <?= $docente['tipo'] === 'Outros' ? 'selected' : '' ?>>Outros</option>
                        </select>            
                    </div>            
                </div>
            </fieldset>
            <div class="form-footer">
            <button type="submit" class="btn-confirma" title="Salvar"><i class="fa-solid fa-check"></i></button>
            </div>

        </form>
    </main>
</body>
</html>
