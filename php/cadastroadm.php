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
    
            <form action="conexaoadm.php" method="POST">
    
                <input type="text" name="credencial" placeholder="Credencial" required>
                <input type="password" name="senha" placeholder="Senha" required>

                <button type="submit">Entrar</button>
            </form>
        </div>

        <div class="logos">
            <img src="../img/Clip path group.svg" alt="Techfix" class="Techfix">
            <img src="../img/senai-logo 5.svg" alt="Senai">
        </div>
    </div>
</body>
</html>
