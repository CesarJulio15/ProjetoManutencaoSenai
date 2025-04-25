<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Portal Senai Manutenções</title>
    <link rel="stylesheet" href="../css/cadastro.css">
</head>
<body>
    
<?php include 'conexao.php'; 
session_start();
?>
    <div class="container">
        <img src="../img/Mask group.svg" alt="Logo" class="logo">

        <h1><span class="portal">Portal Senai</span><br><span class="manutencao">Manutenções</span></h1>

        <div class="form-box">
            <h2>Cadastro</h2>
            <p paineltxt>Painel do administrador</p>
            <form action="conexaocadastro.php" method="POST" enctype="multipart/form-data">
                <input type="text" name="nome" placeholder="Nome completo" required>
                
                <select name="tipo" required>
                    <option value="">Tipo</option>
                    <option value="funcionario">Funcionário</option>
                    <option value="manutencao">Equipe de manutenção</option>
                </select>

                <input type="text" name="credencial" placeholder="Credencial" required>
                <input type="password" name="senha" placeholder="Senha" required>

               
                <label for="foto" class="foto-label">
                    <div class="foto-container">
                        <img src="../img/user.webp" alt="Foto de perfil" id="foto-preview" class="foto-preview">
                    </div>
                    <input type="file" name="foto" id="foto" accept="image/*" class="foto-input" required>
                    insira uma foto
                </label>

                <button type="submit">Realizar Cadastro</button>
            </form>
        </div>

        <div class="logos">
            <img src="../img/Clip path group.svg" alt="Techfix" class="Techfix">
            <img src="../img/senai-logo 5.svg" alt="Senai">
        </div>
    </div>

    <script>
     
        document.getElementById('foto').addEventListener('change', function(event) {
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

