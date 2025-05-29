<?php
    session_start();
    if (!isset($_SESSION['id'])) {
        header("Location: index.php");
        exit();
    }
    require __DIR__ . '/conexao.php';
    
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
?>

<!DOCTYPE html>
<html lang="pt-br">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
        <title>Solicitação de ordem de serviço</title>
        <link rel="stylesheet" href="../css/padrao.css">
        <link rel="stylesheet" href="../css/solicita.css" />
        <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script> 
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="sweetalert2.all.min.js"></script>
        <link rel="icon" type="image/png" href="../img/senai-logo5.svg">
        <style>
            .btn-drag{
                background: rgb(112, 35, 35);
            }
        </style>
    </head>
    <body class="d-flex flex-column bg-body">
        <header class="top-bar mt-2 animate__animated animate__fadeInDown">
        <a href="panelTecnico.php" class="botao-voltar" title="Voltar"><i class="fa-solid fa-arrow-left"></i></a>
          <h3 class="title">Abertura de ordem de serviço</h3>
          <div class="welcome-msg me-3 text-dark fw-bold">
            <a href="editar_funcionario.php?id=<?= $_SESSION['id'] ?>" style="text-decoration: none; color: #212529;" title="Editar meus dados">
                <?= htmlspecialchars($_SESSION['nome']) ?>
            </a><br>
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
        <main class="main-content d-flex flex-md-row w-100 h-100">
        <!-- Conteúdo -->
        <form action="conexaosolicita.php" method="POST" enctype="multipart/form-data" class="d-flex flex-column flex-wrap justify-content-between align-items-center card w-75 animate__animated animate__fadeInUp" style="max-width: 1200px; overflow:auto;" >
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <div class="drag-area text-center p-4">
                        <div class="icon"><i class="fas fa-cloud-upload-alt"></i></div>
                        <button type="button" class="btn-round mb-2 mb-md-0 btn-drag">
                        Insira uma foto
						</button>
                        <input type="file" id="foto" name="foto" hidden>
                    </div>
                    <fieldset class="card-body d-flex flex-column flex-wrap w-100 h-100">
                        <legend><i class="fa-solid fa-pen-nib"></i> Dados do Pedido</legend>
                        <div class="esquerda d-flex flex-column w-100 justify-content-between mt-3 gap-4">
                        <div class="d-flex flex-md-row justify-content-evenly w-100 mb-3 gap-2">
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
                                    <div class="d-flex flex-row mb-3 gap-2">
                                        <label for="responsavel" class="form-label">
                                        <span class="material-symbols-outlined">assignment_ind</span>
                                        Solicitado por: </label>
                                        <select name="responsavel" id="responsavel" class="form-select" required>
                                            <option value="" disabled selected>Selecione o responsável</option>
                                            <?php while ($r = $resps->fetch_assoc()): ?>
                                            <option value="<?= $r['id'] ?>">
                                                <?= htmlspecialchars($r['nome'], ENT_QUOTES) ?>
                                            </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <br>
                                    <?php
                                        $tecnicos = $conn->query("SELECT id, nome FROM docentes WHERE tipo = 'Técnico'");
                                        echo '<div class="d-flex flex-row mb-3 gap-2">';
                                        echo '<label for="executante">Técnico responsável:</label>';
                                        echo '<select name="executante" id="executante" class="form-select">';
                                        echo '<option value="" selected>Selecione</option>';
                                        while ($tec = $tecnicos->fetch_assoc()) {
                                            echo "<option value='{$tec['id']}'>{$tec['nome']}</option>";
                                        }
                                        echo '</select>';
                                        echo '</div>';
                                    ?>
                                            <div class="d-flex mb-3">
                                                <label for="prioridade">
                                                <span class="material-symbols-outlined">priority_high</span> 
                                                Prioridade: </label>
                                                <select name="prioridade" id="prioridade" required>
                                                    <option value="Baixa">Baixa</option>
                                                    <option value="Média" selected>Média</option>
                                                    <option value="Alta">Alta</option>
                                                    <option value="Urgente">Urgente</option>
                                                </select>
                                            </div>
                                            <br>
                                <?php else: ?>                  
                                        </div>
                                        </div>
                                        <!-- usuário comum vira automaticamente o responsável -->
                                        <input type="hidden" name="responsavel" value="<?= intval($_SESSION['id']) ?>">
                                        <!-- usuários “normais” não veem select -->
                                        <input type="hidden" name="prioridade" value="Média">
                              <?php endif; ?>
                                </div>
                        <div class="conjunto d-flex flex-column gap-4">
                        <div class="d-flex mb-3 gap-2">
                        <input type="text" id="titulo" name="titulo" placeholder="Descrição breve" required />
                        </div>                  
                        <div class="text-center mb-3 gap-2">
                        <textarea id="descDetalhada" name="descricao" rows="5" placeholder="Detalhe o problema" required></textarea>
                        </div>
                        <div class="d-flex mb-3 gap-4">
                            <div class="d-flex flex-md-row gap-2">
                                    <label for="origem">Origem:</label>
                                    <select name="origem" id="origem" class="form-control" required>
                                        <option value="" disabled selected>Selecione</option>
                                        <option value="Elétrica">Elétrica</option>
                                        <option value="Hidráulica">Hidráulica</option>
                                        <option value="Mecânica">Mecânica</option>
                                        <option value="Infraestrutura">Infraestrutura</option>
                                        <option value="Outros">Outros</option>
                                    </select>
                            </div> 
                            <?php if (in_array($_SESSION['tipo'], ['coordenador', 'técnico'])): ?>
                                <div class="d-flex mb-3 gap-2">
                                    <label for="tipo_manutencao">Tipo de Manutenção:</label>
                                    <select name="tipo_manutencao" id="tipo_manutencao" class="form-control" required>
                                        <option value="" disabled selected>Selecione</option>
                                        <option value="Corretiva">Corretiva</option>
                                        <option value="Preventiva">Preventiva</option>
                                        <option value="Preditiva">Preditiva</option>
                                    </select>
                                </div>
                            <?php endif; ?>           
                        </div>
                        <div class="direita d-flex flex-column mt-3 w-100 justify-content-between align-items-center ">
                        <div class="d-flex flex-column mb-3">
                        <input type="hidden" id="bloco" name="bloco" required>
                        <div class="map-wrapper mb-3" style="width: 100%; margin: auto;">
                        <img src="../img/mapa_bloco.png" usemap="#blocosmapa" class="img-fluid" style="max-width: 100%; height: auto;   user-select: none; z-index:0;">                  
                        </div>
                        <div class="d-flex flex-md-row">
                            <label><strong><i class="fa-solid fa-location-dot"></i> Selecione o setor/bloco:</strong></label>
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
                        <br>
                        <div class="form-group d-flex flex-md-row">
                          <label for="localizacao"><b>Localização do Ambiente: </b></label>
                          <input type="text" class="form-control" name="localizacao" id="localizacao" placeholder="Insira o ambiente com problema (Ex: Sala 32)" required>
                        </div>
                        </div>
                        </div>
                    </fieldset>

                    <div class="form-footer">
                    <button type="submit" class="btn-confirma" title="Salvar"><i class="fa-solid fa-check"></i></button>
                    <a href="panelTecnico.php" class="btn-cancela" title="Cancelar"><i class="fa-solid fa-xmark"></i></a>  
                    </div>
                    </form>
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

            window.onload = function () {
    <?php if (isset($_SESSION['sucesso'])): ?>
        Swal.fire({
            icon: 'success',
            title: <?= json_encode($_SESSION['sucesso']) ?>,
            showConfirmButton: false,
            timer: 2000
        });
        <?php unset($_SESSION['sucesso']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['erro'])): ?>
        Swal.fire({
            icon: 'error',
            title: 'Erro!',
            text: <?= json_encode($_SESSION['erro']) ?>
        });
        <?php unset($_SESSION['erro']); ?>
    <?php endif; ?>
}
        </script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/js/all.min.js" integrity="sha512-b+nQTCdtTBIRIbraqNEwsjB6UvL3UEMkXnhzd8awtCYh0Kcsjl9uEgwVFVbhoj3uu1DO1ZMacNvLoyJJiNfcvg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    </body>

</html>
