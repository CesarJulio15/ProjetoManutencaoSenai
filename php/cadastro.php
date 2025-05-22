<?php
session_start();
// Se não estiver logado OU não for coordenador, redireciona
if (!isset($_SESSION['id']) || $_SESSION['tipo'] !== 'coordenador') {
    header("Location: index.php");
    exit();
}
require __DIR__ . '/conexao.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Portal Senai Manutenções</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css" integrity="sha512-MV7K8+y+gLIBoVD59lQIYicR65iaqukzvf/nwasF0nqhPay5w/9lJmVM2hMDcnK1OnMGCdVK+iQrJ7lzPJQd1w==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/cadastro.css">
    <link rel="icon" type="image/png" href="../img/senai-logo 5.svg">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>

    </style>
</head>
<body class="">
<?php if (isset($_GET['erro']) && $_GET['erro'] === 'duplicado') : ?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            Swal.fire({
                icon: 'warning',
                title: 'Funcionário já cadastrado',
                text: 'Este e-mail ou NIF já está em uso.',
                confirmButtonColor: '#3085d6'
            });
        });
    </script>
<?php endif; ?>          
    <header class="top-bar">
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
        </header>
    <main class="d-flex flex-md-column w-100 h-100">

        <div class="form-header">
        <!-- Logo principal -->
        <img src="../img/Mask group.svg" alt="Logo" class="logo img-fluid mb-3" style="max-width: 250px;">

        <h1 class="mb-4">
            <span class="portal">Portal Senai</span><br>
            <span class="manutencao">Manutenções</span>
        </h1>
        </div>
    <div class="container text-center">
        <!-- Formulário responsivo -->
        <form action="conexaocadastro.php" method="POST" enctype="multipart/form-data" class="form-box bg-white shadow rounded p-4 mx-auto w-100" autocomplete="off">
                <div class="topo-cadastro mt-3">
                <a href="listar_funcionarios.php" class="btn btn-danger btn-volta volta"><span class="material-symbols-outlined">
                                arrow_back_ios
                                </span></a>
                        <div class="titulo">
                            <h1>Cadastrar funcionário</h1>
                            <p>Painel do administrador</p>
                        </div>
                </div>
                <div class="mb-3">
                    <input type="text" name="nome" class="form-control" placeholder="Nome completo" required>
                </div>

                <div class="mb-3">
                    <select name="tipo" class="form-select" required>
                        <option value="" disabled>Tipo</option>
                        <option value="Docente" selected="selected">Funcionário</option>
                        <option value="Técnico">Equipe de manutenção</option>
                        <option value="Coordenador">Administrador</option>
                    </select>
                </div>

                <div class="mb-3">
                    <input type="text" name="nif" class="form-control" placeholder="NIF" required>
                </div>
                <div class="mb-3">
                    <input type="email" name="email" id="email" class="form-control" placeholder="email">
                </div>
                <div class="mb-3">
                    <input type="password" name="senha" class="form-control" placeholder="Senha" required>
                </div>
                <div class="form-footer">
                <button type="submit" class="btn btn-dark w-100 btn-confirma" title="Realizar Cadastro"><i class="fa-solid fa-check"></i></button>                
                </div>
        </form>
        <div >

    </div>

    <!-- Logos adicionais -->
    <div class="logos d-flex justify-content-center align-items-center mt-4 flex-wrap gap-3">
        <img src="../img/Clip path group.svg" alt="Techfix" class="Techfix img-fluid" style="height: 70px;">
        <img src="../img/senai-logo5.svg" alt="Senai" class="img-fluid" style="height: 50px;">
    </div>
</div>    
</main>


<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
