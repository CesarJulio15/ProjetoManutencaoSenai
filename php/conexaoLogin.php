<?php
session_start();
include 'conexao.php';

// Verifica se o usuário está autenticado
if (!isset($_SESSION['credencial'])) {
    echo "<p>Você não está logado. Por favor, faça o login.</p>";
    exit;
}

// Obtém o nome do usuário a partir da sessão
$usuario = $_SESSION['nome'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $titulo = htmlspecialchars($_POST["titulo"]);
    $descricao = $_POST["descricao"];
    $responsavel = $_POST["responsavel"];

    // Verifica se uma foto foi enviada
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $fotoTmp = $_FILES['foto']['tmp_name'];
        $fotoData = file_get_contents($fotoTmp);

        // Prepara e executa a consulta para inserir o novo registro
        $sql = "INSERT INTO manutencao (titulo, descricao, responsavel, foto, usuario) 
                VALUES (?, ?, ?, ?, ?)";

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("sssss", $titulo, $descricao, $responsavel, $fotoData, $usuario); // Agora passa o nome do usuário

            if ($stmt->execute()) {
                echo "<p>Cadastro realizado com sucesso!</p>";
            } else {
                echo "<p>Erro ao cadastrar solicitação: " . $stmt->error . "</p>";
            }

            $stmt->close();
        } else {
            echo "<p>Erro ao preparar a consulta: " . $conn->error . "</p>";
        }
    } else {
        echo "<p>Erro no upload da foto ou foto não enviada!</p>";
    }

    $conn->close();
} else {
    echo "<p>Formulário não enviado corretamente.</p>";
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Solicitação de Reparo</title>
    <link rel="stylesheet" href="../css/solicita.css" />
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
</head>
<body>
  <!-- Formulário de Solicitação de Reparo -->
  <div class="container">
    <form action="conexaosolicita.php" method="POST" enctype="multipart/form-data" class="card">
      <div class="drag-area">
        <div class="icon"><i class="fas fa-cloud-upload-alt"></i></div>
        <button>Insira uma foto</button>
        <input type="file" hidden>
      </div>
      <div class="card-body">
        <label for="titulo">Descrição Breve</label>
        <input type="text" id="titulo" name="titulo" placeholder="Digite aqui..." required />
        <label for="descDetalhada">Descrição detalhada</label>
        <textarea id="descDetalhada" name="descricao" rows="5" placeholder="Detalhe o problema..." required></textarea>
        <button type="submit">Criar</button>
      </div>
    </form>
  </div>

  <script>
    // Funções para upload de arquivos e arrastar e soltar
    const dropArea = document.querySelector(".drag-area");
    const input = dropArea.querySelector("input");

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
    });

    // Função para mostrar o arquivo escolhido
    input.addEventListener("change", function() {
      const file = this.files[0];
      if (file && file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = function(e) {
          const imgTag = `<img src="${e.target.result}" alt="Imagem">`;
          dropArea.innerHTML = imgTag;
        }
        reader.readAsDataURL(file);
      } else {
        alert("Este arquivo não é uma imagem!");
      }
    });
  </script>
</body>
</html>
