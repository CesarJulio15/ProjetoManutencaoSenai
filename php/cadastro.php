<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Portal Senai Manutenções</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Seu CSS personalizado -->
    <link rel="stylesheet" href="../css/cadastro.css">
</head>
<body class="bg-light d-flex align-items-center justify-content-center min-vh-100">

<?php include 'conexao.php'; session_start(); ?>

<div class="container text-center">
    <!-- Logo principal -->
    <img src="../img/Mask group.svg" alt="Logo" class="logo img-fluid mb-3" style="max-width: 250px;">

    <h1 class="mb-4">
        <span class="portal">Portal Senai</span><br>
        <span class="manutencao">Manutenções</span>
    </h1>

    <!-- Formulário responsivo -->
    <div class="form-box bg-white shadow rounded p-4 mx-auto" style="max-width: 400px;">
        <h2 class="mb-2">Cadastrar usuário</h2>
        <p class="text-muted">Painel do administrador</p>

        <form action="conexaocadastro.php" method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <input type="text" name="nome" class="form-control" placeholder="Nome completo" required>
            </div>

            <div class="mb-3">
                <select name="tipo" class="form-select" required>
                    <option value="">Tipo</option>
                    <option value="funcionario">Funcionário</option>
                    <option value="manutencao">Equipe de manutenção</option>
                    <option value="adm">Administrador</option>
                </select>
            </div>

            <div class="mb-3">
                <input type="text" name="credencial" class="form-control" placeholder="Credencial" required>
            </div>

            <div class="mb-3">
                <input type="password" name="senha" class="form-control" placeholder="Senha" required>
            </div>

            <button type="submit" class="btn btn-dark w-100">Realizar Cadastro</button>

            <div class="volta mt-3">
                <a href="manutencao.php" class="btn btn-danger w-100">Voltar</a>
            </div>
        </form>
    </div>

    <!-- Logos adicionais -->
    <div class="logos d-flex justify-content-center align-items-center mt-4 flex-wrap gap-3">
        <img src="../img/Clip path group.svg" alt="Techfix" class="Techfix img-fluid" style="height: 70px;">
        <img src="../img/senai-logo 5.svg" alt="Senai" class="img-fluid" style="height: 50px;">
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Preview da imagem (caso use futuramente) -->
<script>
    document.getElementById('foto')?.addEventListener('change', function(event) {
        const file = event.target.files[0];
        const reader = new FileReader();

        reader.onload = function(e) {
            document.getElementById('foto-preview').src = e.target.result; 
        };

        if (file) {
            reader.readAsDataURL(file); 
        }
    });
</script>

</body>
</html>
