<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Solicitação de Reparo</title>
  <link rel="stylesheet" href="../css/solicita.css" />
  <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css" integrity="sha512-MV7K8+y+gLIBoVD59lQIYicR65iaqukzvf/nwasF0nqhPay5w/9lJmVM2hMDcnK1OnMGCdVK+iQrJ7lzPJQd1w==" crossorigin="anonymous" referrerpolicy="no-referrer" />
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
    <form action="conexaosolicita.php" method="POST" enctype="multipart/form-data" class="card">
      <div class="drag-area">
        <div class="icon"><i class="fas fa-cloud-upload-alt"></i></div>
        <button type="button">Insira uma foto</button>
        <input type="file" id="foto"  name="foto" hidden>
      </div>
      <div class="card-body">
  <label for="titulo">Descrição Breve</label>
  <input type="text" id="titulo" name="titulo" placeholder="Digite aqui..." required />

  <label for="descDetalhada">Descrição detalhada</label>
  <textarea id="descDetalhada" name="descricao" rows="5" placeholder="Detalhe o problema..." required></textarea>

  <button type="submit">Criar</button>

  <a href="manutencao.php" class="botao-voltar">Voltar</a>
</div>

    </form>
  </div>

  <style>
    .drag-area {
      border: 2px dashed #fff;
      height: 100%;
      width: 100%;
      border-radius: 5px;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-direction: column;
    }

    .drag-area.active {
      border: 2px solid #fff;
    }

    .drag-area .icon {
      margin-top: 10px;
      font-size: 70px;
      color:rgb(112, 35, 35);
    }

    .drag-area header {
      font-size: 30px;
      font-weight: 500;
      background-color: #f9f9f9;
    }

    .drag-area span {
      font-size: 25px;
      font-weight: bolder;
      color: #f9f9f9;
      margin: 10px 0 15px 0;
    }

    .drag-area button {
      padding: 10px 25px;
      font-size: 20px;
      font-weight: 500;
      border: none;
      outline: none;
      background-color: #f9f9f9;
      color:rgb(184, 15, 15);
      border-radius: 5px;
      cursor: pointer;
    }

    .drag-area img {
      height: 100%;
      width: 100%;
      object-fit: cover;
      border-radius: 5px;
    }
  </style>
<script>
  const dropArea = document.querySelector(".drag-area"),
        button = dropArea.querySelector("button"),
        input = dropArea.querySelector("input"),
        icon = dropArea.querySelector(".icon");

  let file;

  button.onclick = () => {
    input.click();
  }

  input.addEventListener("change", function () {
    file = this.files[0];
    dropArea.classList.add("active");
    showFile();
  });

  dropArea.addEventListener("dragover", (event) => {
    event.preventDefault();
    dropArea.classList.add("active");
  });

  dropArea.addEventListener("dragleave", () => {
    dropArea.classList.remove("active");
  });

  dropArea.addEventListener("drop", (event) => {
    event.preventDefault();
    input.files = event.dataTransfer.files;
    file = input.files[0];
    showFile();
  });

  function showFile() {
    let fileType = file.type;
    let validExtensions = ["image/jpeg", "image/jpg", "image/png"];
    if (validExtensions.includes(fileType)) {
      let fileReader = new FileReader();
      fileReader.onload = () => {
        // Esconde os elementos padrão
        icon.style.display = "none";
        button.style.display = "none";

        // Mostra a imagem sem remover o input
        const previewImg = document.createElement("img");
        previewImg.src = fileReader.result;
        previewImg.alt = "Prévia da imagem";
        previewImg.style.maxHeight = "200px";
        previewImg.style.borderRadius = "5px";
        previewImg.style.marginTop = "10px";

        // Remove imagem antiga se houver
        const oldImg = dropArea.querySelector("img");
        if (oldImg) oldImg.remove();

        dropArea.appendChild(previewImg);
      };
      fileReader.readAsDataURL(file);
    } else {
      alert("Este não é um arquivo de imagem válido!");
      dropArea.classList.remove("active");
    }
  }
</script>

</body>
</html>
