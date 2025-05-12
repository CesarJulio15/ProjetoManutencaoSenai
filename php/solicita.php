<?php
session_start();
if (!isset($_SESSION['id'])) {
    header("Location: index.php");
    exit();
}

// Inclui conexão (sem saída de texto)
require __DIR__ . '/conexao.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Solicitação de Reparo</title>
  <link rel="stylesheet" href="../css/solicita.css" />
  <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script> 
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css" integrity="sha512-MV7K8+y+gLIBoVD59lQIYicR65iaqukzvf/nwasF0nqhPay5w/9lJmVM2hMDcnK1OnMGCdVK+iQrJ7lzPJQd1w==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="sweetalert2.all.min.js"></script>
  <link rel="icon" type="image/png" href="../img/senai-logo 5.svg">
  <style>

</style>
</head>
<body class="bg-body">
<header class="top-bar">
                <div class="welcome-msg me-3 text-dark fw-bold">
                    <?php echo htmlspecialchars($_SESSION['nome']); ?><br>
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
  <main class="container-fluid p-0">
    <div class="row g-0 min-vh-100 flex-column flex-md-row">
      <!-- Sidebar -->
      <div class="col-12 col-md-1 sidebar d-flex flex-md-column justify-content-between align-items-center py-3">
        <div class="logo-superior">
          <img src="../img/LogoPortalSenai.svg" alt="Portal Senai Manutenções" class="img-fluid" />
        </div>
        <div class="logo-inferior">
          <img src="../img/TechFix.svg" alt="TechFix Facilities" class="img-fluid" />
        </div>
      </div>

      <!-- Conteúdo -->
      <div class="col container d-flex justify-content-center align-items-center py-5">
        <form action="conexaosolicita.php" method="POST" enctype="multipart/form-data" class="d-flex flex-wrap justify-content-between align-items-center card w-100" style="max-width: 1200px;">
              <div class="drag-area text-center p-4">
                <div class="icon"><i class="fas fa-cloud-upload-alt"></i></div>
                <button type="button">Insira uma foto</button>
                <input type="file" id="foto" name="foto" hidden>
              </div>
              <fieldset class="card-body d-flex flex-wrap flex-row w-100">
              <legend><i class="fa-solid fa-pen-nib"></i> Dados do Pedido</legend>

              <div class="esquerda d-flex flex-column w-100 justify-content-between mt-3 ">
              <?php
                    // Se for coordenador ou técnico, exibe dropdown de "Responsável"
                    // caso contrário, define automaticamente o próprio usuário como responsável
                    if (in_array($_SESSION['tipo'], ['coordenador', 'técnico'])):
                        // Busca apenas os docentes (professores) para atribuição de responsável
                        $resps = $conn->query("
                            SELECT id, nome
                            FROM docentes
                            WHERE docente_status = 'Ativo'
                            ORDER BY nome
                        ");
              ?>
              <div class="d-flex mb-3">
                <label for="responsavel" class="form-label">
                  <span class="material-symbols-outlined">assignment_ind</span>
                  Responsável: </label>
                    <br>
                <select name="responsavel" id="responsavel" class="form-select" required>
                  <option value="" disabled selected>Selecione o responsável</option>
                  <?php while ($r = $resps->fetch_assoc()): ?>
                    <option value="<?= $r['id'] ?>">
                      <?= htmlspecialchars($r['nome'], ENT_QUOTES) ?>
                    </option>
                  <?php endwhile; ?>
                </select>
              </div>
              <div class="d-flex mb-3">
                  <label for="prioridade">
                    <span class="material-symbols-outlined">priority_high</span> 
                    Prioridade: </label>
                    <br>
                  <select name="prioridade" id="prioridade" required>
                    <option value="Baixa">Baixa</option>
                    <option value="Média" selected>Média</option>
                    <option value="Alta">Alta</option>
                    <option value="Urgente">Urgente</option>
                  </select>
                    </div>
                <?php else: ?>
                  <!-- usuário comum vira automaticamente o responsável -->
                  <input type="hidden" name="responsavel" value="<?= intval($_SESSION['id']) ?>">
                    <!-- usuários “normais” não veem select -->
                  <input type="hidden" name="prioridade" value="Média">
                <?php endif; ?>
                    
              <br>
              <div class="d-flex-mb-3">
              <input type="text" id="titulo" name="titulo" placeholder="Descrição breve" required />
              </div>
                <br>                      
              <div class="text-center mb-3">
                <textarea id="descDetalhada" name="descricao" rows="5" placeholder="Detalhe o problema" required></textarea>
              </div>
              </div>
              <br>
              <div class="direita d-flex flex-column mt-3 w-100 justify-content-between align-items-center ">
                  <div class="d-flex flex-column mb-3">
                    <input type="hidden" id="bloco" name="bloco" required>
                    <div class="map-wrapper mb-3" style="width: 100%; margin: auto;">
                      <img src="../img/mapa_bloco.png" usemap="#blocosmapa" class="img-fluid" style="max-width: 100%; height: auto;">
                        <map name="blocosmapa">
                            <!-- E0 -->
                            <area shape="rect" coords="150,15,370,55" href="#" alt="E0" onclick="selecionarBloco('E0'); return false;">

                            <!-- C0 -->
                            <area shape="rect" coords="150,55,400,100" href="#" alt="C0" onclick="selecionarBloco('C0'); return false;">

                            <!-- B0 (salas) -->
                            <area shape="rect" coords="150,100,400,150" href="#" alt="B0" onclick="selecionarBloco('B0'); return false;">

                            <!-- B0 - Quadra -->
                            <area shape="rect" coords="40,100,150,150" href="#" alt="B0 - Quadra" onclick="selecionarBloco('B0 - Quadra'); return false;">

                            <!-- A0 -->
                            <area shape="rect" coords="150,150,400,200" href="#" alt="A0" onclick="selecionarBloco('A0'); return false;">

                            <!-- D0 (bloco vertical) -->
                            <area shape="rect" coords="400,150,425,240" href="#" alt="D0" onclick="selecionarBloco('D0'); return false;">

                            <!-- Secretaria / Auditório -->
                            <area shape="rect" coords="425,210,505,265" href="#" alt="Secretaria / Auditório" onclick="selecionarBloco('Secretaria / Auditório'); return false;">

                            <!-- F0 -->
                            <area shape="rect" coords="10,140,50,230" href="#" alt="F0" onclick="selecionarBloco('F0'); return false;">

                            <!-- G0 -->
                            <area shape="rect" coords="100,230,190,280" href="#" alt="G0" onclick="selecionarBloco('G0'); return false;">
                          </map>                    
                    </div>
                    <label><strong><i class="fa-solid fa-location-dot"></i> Selecione o setor/bloco:</strong></label><br>
                    <select name="bloco" id="bloco" class="form-select" required>
                          <option value="" disabled selected>Escolha um setor</option>
                          <option value="G0">G0 – Portaria</option>
                          <optgroup label="D0">
                            <option value="D0 - Secretaria">Secretaria</option>
                            <option value="D0 - Auditório">Auditório</option>
                          </optgroup>
                          <optgroup label="A0/A1">                      
                            <option value="A0">A0</option>
                          <option value="A1">A1</option> 
                        </optgroup>
                        <optgroup label="B0/B1">
                        <option value="B0 - Quadra">B0 – Quadra</option>
                          <option value="B0">B0</option>
                          <option value="B1">B1</option>
                        </optgroup>
                        <optgroup label="C0/C1">
                            <option value="C0">C0</option>
                            <option value="C1">C1</option>
                        </optgroup>
                          <option value="E0">E0 – Zeladoria / Empilhadeira</option>
                          <option value="F0">F0 – Cabine Primária</option>
                    </select>
                    </div>
              </div>
              <br>
                    </fieldset>
                                <div class="form-footer">
                                  <button type="submit" class="btn-confirma" title="Salvar"><i class="fa-solid fa-check"></i></button>
                                  <a href="manutencao.php" class="botao-voltar" title="Voltar"><i class="fa-solid fa-xmark"></i></a>  
                      </div>
          </form>
            </div>
        
          </div>
  </main>
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
      file = event.dataTransfer.files[0];
      input.files = event.dataTransfer.files; 
      showFile();
    });

    function showFile() {
      let fileType = file.type;
      let validExtensions = ["image/jpeg", "image/jpg", "image/png"];
      if (validExtensions.includes(fileType)) {
        let fileReader = new FileReader();
        fileReader.onload = () => {
          icon.style.display = "none";
          button.style.display = "none";

          // Remove imagem antiga e botão se houver
          const oldImg = dropArea.querySelector("img");
          const oldRemoveBtn = dropArea.querySelector(".remove-btn");
          if (oldImg) oldImg.remove();
          if (oldRemoveBtn) oldRemoveBtn.remove();

          const previewImg = document.createElement("img");
          previewImg.src = fileReader.result;
          previewImg.alt = "Prévia da imagem";
          previewImg.style.maxHeight = "200px";
          previewImg.style.borderRadius = "5px";
          previewImg.style.marginTop = "10px";

          const removeBtn = document.createElement("button");
          removeBtn.innerText = "✖ Remover imagem";
          removeBtn.className = "remove-btn";
          removeBtn.style.marginTop = "15px";
          removeBtn.style.padding = "10px 20px";
          removeBtn.style.fontSize = "16px";
          removeBtn.style.border = "none";
          removeBtn.style.borderRadius = "25px";
          removeBtn.style.cursor = "pointer";
          removeBtn.style.backgroundColor = "#b81313";
          removeBtn.style.color = "#fff";
          removeBtn.style.transition = "background-color 0.3s ease";
          removeBtn.style.boxShadow = "0 4px 6px rgba(0, 0, 0, 0.1)";
          removeBtn.style.fontWeight = "bold";

          removeBtn.onmouseover = () => {
            removeBtn.style.backgroundColor = "#991010";
          };
          removeBtn.onmouseleave = () => {
            removeBtn.style.backgroundColor = "#b81313";
          };
          removeBtn.onclick = (e) => {
            e.preventDefault();
            previewImg.remove();
            removeBtn.remove();
            input.value = ""; // limpa input
            icon.style.display = "block";
            button.style.display = "inline-block";
            dropArea.classList.remove("active");
          };

          dropArea.appendChild(previewImg);
          dropArea.appendChild(removeBtn);
        };
        fileReader.readAsDataURL(file);
      } else {
        alert("Este não é um arquivo de imagem válido!");
        dropArea.classList.remove("active");
      }
    }
    function selecionarBloco(cod) {
  document.getElementById("bloco").value = cod;
  document.getElementById("blocoSelecionado").innerText = "Bloco selecionado: " + cod;
}
</script>
</body>
</html>
