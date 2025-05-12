<?php
session_start();
include 'conexao.php';
// 0) Se não estiver logado, redireciona
if (!isset($_SESSION['id'])) {
    header("Location: index.php");
    exit();
}
// 1) Carrega SweetAlert2
echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';

// 2) Só processa se vier POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "<script>
        Swal.fire({
            icon: 'info',
            title: 'Acesso inválido',
            text: 'Envie o formulário corretamente.'
        }).then(() => {
            window.location.href = 'solicita.php';
        });
    </script>";
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $titulo = $_POST["titulo"];
    $descricao = $_POST["descricao"];
    $data_abertura = date("Y-m-d");
    $bloco = $_POST['bloco'];

        // força prioridade
    if (!in_array($_SESSION['tipo'], ['coordenador', 'técnico'])) {
        $prioridade = 'Média';
    } else {
        // validação extra
        $prioridade = in_array($_POST['prioridade'], ['Baixa','Média','Alta','Urgente'])
                    ? $_POST['prioridade']
                    : 'Média';
    }

    // 2) Define o responsável:
    //    - se for coordenador ou técnico, usa o valor vindo de POST
    //    - caso contrário, é o próprio usuário logado
    if (in_array($_SESSION['tipo'], ['coordenador', 'técnico'])) {
        $responsavel = intval($_POST['responsavel']);
    } else {
        $responsavel = intval($_SESSION['id']);
    }
    // 5) Define o executante: se for técnico, é o próprio usuário; senão, null
    if ($_SESSION['tipo'] === 'técnico') {
        $executante = intval($_SESSION['id']);
            $status = "Em Andamento";
    } else {
        $executante = null;
        $status = "Pendente";
    }
    // Tratamento da imagem (opcional)
    $foto = null;
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $foto = file_get_contents($_FILES['foto']['tmp_name']);
    }
        

    $sql = "INSERT INTO manutencao (titulo, descricao, responsavel, foto, status, data_abertura, bloco) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Erro na preparação da query!',
                text: '" . addslashes($conn->error) . "',
                confirmButtonText: 'Fechar'
            });
        </script>";
        exit();
    }

    $stmt->bind_param("ssissss", $titulo, $descricao, $responsavel, $foto, $status, $data_abertura, $bloco);

    if ($stmt->execute()) {
        echo "<script>
            Swal.fire({
                icon: 'success',
                title: 'Solicitação registrada com sucesso!',
                showConfirmButton: false,
                timer: 2000
            }).then(() => {
                window.location.href = 'dashboard.php';
            });
        </script>";
    } else {
        echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Erro ao registrar solicitação!',
                text: '" . addslashes($stmt->error) . "',
                confirmButtonText: 'Voltar'
            });
        </script>";
    }

    $stmt->close();
} 
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Confirmação</title>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<?php if (isset($executou) && $executou): ?>
<script>
  Swal.fire({
    icon: 'success',
    title: 'Solicitação registrada!',
    showConfirmButton: false,
    timer: 2000
  }).then(() => {
    window.location.href = 'panelTecnico.php';
  });
</script>
<?php elseif (isset($executou)): ?>
<script>
  Swal.fire({
    icon: 'error',
    title: 'Erro ao registrar',
    text: '<?= addslashes($stmt->error ?? '') ?>'
  });
</script>
<?php else: ?>
<script>
  Swal.fire({
    icon: 'info',
    title: 'Acesso Inválido',
    text: 'Envie o formulário corretamente.'
  }).then(() => {
    window.location.href = 'panelTecnico.php';
  });
</script>
<?php endif; ?>
</body>
</html>