<?php
   session_start();
   include 'conexao.php';
if (!isset($_SESSION['id']) && !in_array($_SESSION['tipo'], ['coordenador', 'técnico'])) {
    header("Location: index.php");
    exit();
}

   $tecnico_id = $_SESSION['id'] ?? null;

   // Candidatar
   if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['titulo']) && isset($_POST['acao'])) {
      $titulo = $_POST['titulo'];
      if ($_POST['acao'] === 'candidatar') {
         $executante = $tecnico_id;

         $sql = "UPDATE manutencao SET status = 'Em Andamento', executante = ? WHERE titulo = ? AND executante IS NULL";
         $stmt = $conn->prepare($sql);
         $stmt->bind_param("ss", $executante, $titulo);
         $stmt->execute();
         $stmt->close();
      }
      // Concluir
      if ($_POST['acao'] === 'concluir') {
         $sql = "
            UPDATE manutencao
               SET status = 'Concluído',           /* cuidado com o acento, deve bater com o ENUM */
                     data_encerramento = CURDATE()  /* atribui a data atual (YYYY-MM-DD) */
               WHERE titulo = ? 
               AND executante = ?";
         $stmt = $conn->prepare($sql);
         $stmt->bind_param("ss", $titulo, $tecnico_id);
         $stmt->execute();
         $stmt->close();
      }
      header("Location: panelTecnico.php");
      exit();
   }

   if (isset($_GET['titulo'])) {
       $titulo = urldecode($_GET['titulo']);
   
       // JOIN para trazer o nome do responsável
       $sql = "SELECT m.*, d1.nome AS nome_responsavel, d2.nome AS nome_executante
               FROM manutencao m
               JOIN docentes d1 ON m.responsavel = d1.id
               LEFT JOIN docentes d2 ON m.executante = d2.id
               WHERE m.titulo = ?";
       $stmt = $conn->prepare($sql);
       $stmt->bind_param("s", $titulo);
       $stmt->execute();
       $result = $stmt->get_result();
   
       if ($row = $result->fetch_assoc()) {
          $jaExecutando = ($row['executante'] === $tecnico_id);
          $status = $row['status'];
          switch ($status) {
              case 'Pendente':     $statusClass = 'status-pendente';     break;
              case 'Em Andamento': $statusClass = 'status-em-andamento'; break;
              case 'Concluído':    $statusClass = 'status-concluido';    break;
              case 'Rejeitado':    $statusClass = 'status-rejeitado';    break;
              default:             $statusClass = '';
          }
   ?>
<!DOCTYPE html>
<html lang="pt-BR">
   <head>
      <meta charset="UTF-8">
      <title>Detalhe da Manutenção</title>
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <link rel="stylesheet" href="../css/padrao.css">
      <link rel="stylesheet" href="../css/detalhes.css">
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css" integrity="sha512-MV7K8+y+gLIBoVD59lQIYicR65iaqukzvf/nwasF0nqhPay5w/9lJmVM2hMDcnK1OnMGCdVK+iQrJ7lzPJQd1w==" crossorigin="anonymous" referrerpolicy="no-referrer" />
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
      <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
      <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
      <link rel="icon" type="image/png" href="../img/senai-logo 5.svg">
      <style>

      </style>
   </head>
   <body class="">
   <?php
         $alerta = '';
         if (isset($_SESSION['executante_sucesso'])) {
             $alerta = "<script>
                 Swal.fire({
                     icon: 'success',
                     title: 'Executante atualizado com sucesso!',
                     confirmButtonText: 'OK'
                 });
             </script>";
             unset($_SESSION['executante_sucesso']);
         } elseif (isset($_SESSION['executante_erro'])) {
             $alerta = "<script>
                 Swal.fire({
                     icon: 'error',
                     title: 'Erro ao atualizar executante.',
                     text: 'Tente novamente.',
                     confirmButtonText: 'OK'
                 });
             </script>";
             unset($_SESSION['executante_erro']);
         }
         ?>
         <?php if (!empty($alerta)) echo $alerta; ?>

      <!-- Sidebar -->
      <aside class="sidebar d-flex flex-column justify-content-between align-items-center flex-md-column flex-sm-row p-3 gap-3">
         <img src="../img/LogoPortalSenai.svg" alt="Portal Senai Manutenções" class="logo-senai">
         <img src="../img/TechFix.svg" alt="Techfix Facilities" class="logo-techfix">
      </aside>
  <main class="">
      <header class="top-bar">

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
      <div class="detalhe-container <?= $statusClass ?> d-flex flex-column mb-5">
      <input type="hidden" id="titulo" value="<?php echo htmlspecialchars($row['titulo']); ?>">
            <!-- badge colorido -->
            <?php if ($statusClass): ?>
               <div class="status-indicator <?= $statusClass ?>" title="Status do pedido"></div>
            <?php endif; ?>
         <!-- Voltar -->
         <div class="card-header d-lg-flex text-start mb-4 d-flex align-start justify-content-evenly w-100 ">
            <a href="panelTecnico.php" class="botao-voltar" title="Voltar">
               <span class="material-symbols-outlined">arrow_back_ios</span>
            </a>
            <h1 class="numero_os" id="numero_os"><?php echo htmlspecialchars($row['numero_os']); ?></h1>
         </div>
         <div class="info-detalhe mb-4 mt-4 w-100 h-100 text-start">
            <h2 class="title titulo-manutencao"><?php echo htmlspecialchars($row['titulo']); ?></h2>
            <div class="info d-flex flex-column align-items-start align-center w-100">
               <div class="d-flex flex-md-row gap-4 align-items-start align-center  w-100">               
                  <div class="info-item mt-3">
                     <label class="info-label me-1"><b>Aberto por</b>:</label>
                     <p class="info-text mb-0"><?php echo htmlspecialchars($row['nome_responsavel']); ?></p>
                  </div>
                  <div class="info-item mt-3">
                     <label class="info-label me-1"><b>Prioridade</b>:</label>
                     <p class="info-text mb-0"><?= htmlspecialchars($row['prioridade']) ?></p>
                  </div>
                  <?php if (!empty($row['executante'])): ?>
                  <div class="info-item mt-3">
                     <label class="info-label me-1"><b>Técnico</b>:</label>
                     <p class="info-text mb-0"><?php echo htmlspecialchars($row['nome_executante']); ?></p>
                  </div>
                  <?php endif; ?>
               </div>
               <div class="d-flex flex-md-row gap-4 align-center w-100">
                        <div class="info-item mt-3">
                           <label class="info-label"><b>Bloco</b>:</label>
                           <p class="info-text"><?php echo nl2br(htmlspecialchars($row['bloco'])); ?></p>
                        </div>
                        <div class="info-item mt-3">
                           <label class="info-label"><b>Localização (ambiente)</b>:</label>
                           <p class="info-text"><?php echo nl2br(htmlspecialchars($row['localizacao_ambiente'])); ?></p>
                        </div>
               </div>
               <div class="d-flex flex-md-row gap-4 align-center w-100">
                     <div class="info-item mt-3">
                        <label class="info-label"><b>Descrição</b>:</label>
                        <p class="info-text"><?php echo nl2br(htmlspecialchars($row['descricao'])); ?></p>
                     </div>
               </div>

               
            </div>

            <div class="container-img mt-2">
               <?php
                  if (!empty($row['foto'])) {
                     $fotoBase64 = base64_encode($row['foto']);
                     echo "<img src='data:image/jpeg;base64,{$fotoBase64}' alt='Imagem da manutenção' class='img-fluid rounded mb-3 d-block mx-auto'>";
                  }else{
                     echo "<class='no-results'><img src='../img/no-results 1.svg' alt='Imagem da manutenção' class='img-fluid rounded mb-3 d-block mx-auto'></div>";
                  }
                  ?>
            </div>
            <!-- Rodapé com botões -->
            <div class="card-footer mt-2 mb-2 form-footer">
               <?php if ($_SESSION['tipo'] == 'coordenador') : ?>
               <div class="d-flex gap-2 flex-wrap justify-content-center">
                  <button class="status-btn btn btn-danger" data-status="Pendente" title="Pendente" style="background: #e91e63;">
                  <span class="material-symbols-outlined">pending_actions</span></button>
                  <button class="status-btn btn btn-warning text-white" data-status="Em Andamento" title="Em Andamento" style="background: #ff9800;">
                  <span class="material-symbols-outlined">assignment</span></button>
                  <button class="status-btn btn btn-success" data-status="Concluído" title="Concluído" style="background: #5b7f2e;"><span class="material-symbols-outlined">
                        assignment_turned_in
                        </span></button>
                  <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#designarModal">
                  <span class="material-symbols-outlined">engineering</span> Designar
               </button>
                                    <!-- Excluir -->
                     <div class="flex-shrink-0">
                        <form method="POST" action="excluir_manutencao.php" onsubmit="return confirm('Tem certeza que deseja excluir esta manutenção?');">
                           <input type="hidden" name="titulo" value="<?php echo htmlspecialchars($row['titulo']); ?>">
                           <button type="submit" class="btn p-0 btn-excluir">
                           <img src="../img/trash.png" alt="Excluir" width="24" height="24">
                           </button>
                        </form>
                     </div>
               </div>
               <?php elseif ($row['executante'] === null && $_SESSION['tipo'] == 'técnico') : ?>
                     <!-- Exibir botão "Candidatar" se não há executante -->
                  <form method="post">
                        <input type="hidden" name="titulo" value="<?= htmlspecialchars($row['titulo']) ?>">
                        <input type="hidden" name="acao" value="candidatar">
                        <button type="submit" class="btn-candidatar" title="Candidatar-se">
                           <span class="material-symbols-outlined">how_to_reg</span></button>
                  </form>
                                    <form action="rejeitar.php" method="post">
                  <input type="hidden" name="titulo" value="<?= htmlspecialchars($row['titulo']) ?>">
                  <button type="submit" class="btn-rejeitar" title="Rejeitar">
                     <span class="material-symbols-outlined">person_cancel</span></button>
               </form>
               <?php elseif ($jaExecutando && $row['status'] == 'Em Andamento' && $_SESSION['tipo'] == 'técnico'): ?>
                  <!-- Se o usuário atual for o executante, exibir "Concluir" -->
                  <form method="post">
                        <input type="hidden" name="titulo" value="<?= htmlspecialchars($row['titulo']) ?>">
                        <input type="hidden" name="acao" value="concluir">
                        <button type="submit" class="btn-confirma" title="Concluir"><span class="material-symbols-outlined">check</span></button>
                  </form>
                     <form action="rejeitar.php" method="post">
                  <input type="hidden" name="titulo" value="<?= htmlspecialchars($row['titulo']) ?>">
                  <button type="submit" class="btn-rejeitar" title="Rejeitar">
                     <span class="material-symbols-outlined">person_cancel</span></button>
               </form>
               <?php endif; ?>
            </div>
         </div>
      </main>

      <!-- Modal Designar -->
      <div class="modal fade" id="designarModal" tabindex="-1" aria-labelledby="designarModalLabel" aria-hidden="true">
         <div class="modal-dialog">
            <div class="modal-content">
               <form method="POST" action="designar_executante.php">
                  <div class="modal-header">
                     <h5 class="modal-title" id="designarModalLabel">Designar Técnico</h5>
                     <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                  </div>
                  <div class="modal-body">
                     <input type="hidden" name="titulo" value="<?php echo htmlspecialchars($row['titulo']); ?>">
                     <?php
                        $tecnicos = $conn->query("SELECT id, nome FROM docentes WHERE tipo = 'Técnico'");
                        echo '<div class="form-group mb-3">';
                        echo '<label for="executante">Selecionar usuário:</label>';
                        echo '<select name="executante" id="executante" class="form-select" required>';
                        echo '<option value="" disabled selected>Selecione</option>';
                                while ($tec = $tecnicos->fetch_assoc()) {
                                    $selected = ($row['executante'] == $tec['id']) ? 'selected' : '';
                                    echo "<option value='{$tec['id']}' $selected>{$tec['nome']}</option>";
                                }
                        echo '</select>';
                        echo '</div>';
                        ?>
                     <?php if (!empty($row['executante'])): ?>
                     <div class="alert alert-warning mt-3">
                        Já existe um técnico designado. Você pode substituí-lo ou removê-lo abaixo.
                     </div>
                     <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="remover_executante" value="1" id="remover_executante">
                        <label class="form-check-label" for="remover_executante">Remover executante atual</label>
                     </div>
                     <?php endif; ?>
                  </div>
                  <div class="modal-footer">
                     <button type="submit" class="btn btn-sm btn-success ms-2">Salvar</button>
                  </div>
               </form>
            </div>
         </div>
      </div>
      <script>
         document.querySelectorAll('.status-btn').forEach(btn => {
             btn.addEventListener('click', () => {
                 const status = btn.dataset.status;
                 const titulo = document.getElementById('titulo').value;
         
                 fetch('atualizar_status.php', {
                     method: 'POST',
                     headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                     body: 'titulo=' + encodeURIComponent(titulo) + '&status=' + encodeURIComponent(status)
                 })
                 .then(response => response.text())
                 .then(data => {
                     Swal.fire({
                         icon: 'success',
                         title: 'Status atualizado!',
                         text: 'O status foi alterado para "' + status + '".',
                         confirmButtonText: 'OK'
                     }).then(() => {
                         location.reload(); // recarrega a página para refletir alterações
                     });
                 })
                 .catch(error => {
                     Swal.fire({
                         icon: 'error',
                         title: 'Erro ao atualizar',
                         text: 'Não foi possível alterar o status.',
                         confirmButtonText: 'OK'
                     });
                 });
             });
         });
      </script>
      <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
   
</main>
</body>
</html>
<?php
   } else {
       echo "<p class='text-center mt-5 text-danger'>Manutenção não encontrada.</p>";
   }
   $stmt->close();
   } else {
   echo "<p class='text-center mt-5 text-warning'>Título não fornecido.</p>";
   }
   $conn->close();
   ?>