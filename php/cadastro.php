<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Portal Senai Manutenções</title>
    <link rel="stylesheet" href="../css/cadastro.css">
</head>
<body>
<?php include 'conexao.php'; ?>
    <div class="container">
        <img src="Mask group.svg" alt="Logo" class="logo">

        <h1><span class="portal">Portal Senai</span><br><span class="manutencao">Manutenções</span></h1>

        <div class="form-box">
            <h2>Cadastro</h2>
            <p paineltxt>Painel do administrador</p>
            <form action="conexaocadastro.php" method="POST">
                <input type="text" name="nome" placeholder="Nome completo" required>
                
                <select name="tipo" required>
                    <option value="">Tipo</option>
                    <option value="funcionario">Funcionário</option>
                    <option value="manutencao">Equipe de manutenção</option>
                </select>

                <input type="text" name="credencial" placeholder="Credencial" required>
                <input type="password" name="senha" placeholder="Senha" required>

                <button type="submit">Realizar Cadastro</button>
            </form>
        </div>

        <div class="logos">
            <img src="../img/Clip path group.svg" alt="Techfix" class="Techfix">
            <img src="../img/senai-logo 5.svg" alt="Senai">
        </div>
    </div>
</body>
</html>
