<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Solicitação de Reparo</title>
  <link rel="stylesheet" href="../css/solicita.css" />
</head>
<body>
  <div class="sidebar">
    <div class="logo-superior">
      <img src="../img/LogoPortalSenai.svg" alt="Portal Senai Manutenções" />
    </div>
    <div class="logo-inferior">
      <img src="../img/TechFix.svg" alt="TechFix Facilities" />
    </div>
  </div>

  <div class="container">
    <div class="card">
      <div class="card-top">
        <div class="icons">
          <div class="triangle"></div>
          <div class="square-circle">
            <div class="square"></div>
            <div class="circle"></div>
          </div>
        </div>
        <div class="upload-icon">⬆️</div>
      </div>
      <div class="card-body">
        <label for="descBreve">Descrição Breve</label>
        <input type="text" id="descBreve" placeholder="Digite aqui..." />

        <label for="descDetalhada">Descrição detalhada</label>
        <textarea id="descDetalhada" rows="5" placeholder="Detalhe o problema..."></textarea>

        <button type="submit">Criar</button>
      </div>
    </div>
  </div>
  <a href="manutencao.php">
             <img src="../img/MinusCircle.svg" alt="Adicionar" class="add-button-img">
        </a>
</body>
</html>
