<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Portal Senai Manutenções</title>
  <link rel="stylesheet" href="../css/solicita.css">
</head>
<body>
  <header>
    <div class="top-bar">
      <span>Solicitar reparo</span>
    </div>
  </header>

  <div class="container">
    <aside class="menu-lateral">
      <img src="../img/senai-logo 5.svg" alt="Logo Portal Senai" class="logo">
    </aside>

    <main class="conteudo-principal">
      <div class="form-box">
        <div class="form-header">
          <div class="shapes">
            <div class="triangle"></div>
            <div class="square"></div>
            <div class="circle"></div>
          </div>
          <div class="upload-icon">⤴️</div>
        </div>
        <form>
          <label for="desc-breve">Descrição Breve</label>
          <input type="text" id="desc-breve" name="desc-breve">

          <label for="desc-detalhada">Descrição detalhada</label>
          <textarea id="desc-detalhada" name="desc-detalhada" rows="6"></textarea>

          <button type="submit">Criar</button>
        </form>
      </div>
    </main>

    <aside class="menu-lateral-direito">
      <div class="icone-usuario">👤</div>
    </aside>
  </div>

  <footer>
    <img src="logo-techfix.png" alt="Logo Techfix" class="logo-footer">
  </footer>
</body>
</html>
