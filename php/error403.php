<?php
http_response_code(403); // Define cabeçalho HTTP
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="robots" content="noindex, nofollow">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 Acesso Negado - Sistema</title>
    <link rel="icon" type="image/png" href="../img/senai-logo5.svg"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css" integrity="sha512-MV7K8+y+gLIBoVD59lQIYicR65iaqukzvf/nwasF0nqhPay5w/9lJmVM2hMDcnK1OnMGCdVK+iQrJ7lzPJQd1w==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
    <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"
  />
    <link rel="stylesheet" href="../css/error403.css">
</head>
<body>
    <main>
    <div class="container animate__animated animate__bounce">
        <h1>403</h1>
        <h2>Acesso Negado</h2>
        <div class="conjunto">
        <p>Oops! Parece que você tentou acessar uma área restrita.</p>
        <small style="color:#777;">Se você acredita que isso é um erro, entre em contato com o administrador do sistema.</small>
        </div>
        <a href="index.php" class="botao-voltar" role="button">Voltar para a página inicial</a>
    </div>
    </main>
    
</body>
<script>
    setTimeout(() => {
        window.location.href = "index.php";
    }, 10000); // Redireciona em 10 segundos
</script>
</html>
