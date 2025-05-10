<?php
session_start();
include 'conexao.php';

echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";

if (!isset($_SESSION['id'])) {
    echo "<script>
        Swal.fire({
            icon: 'warning',
            title: 'Usuário não autenticado!',
            text: 'Faça login para continuar.',
            confirmButtonText: 'OK'
        }).then(() => {
            window.location.href = 'index.php';
        });
    </script>";
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $titulo = $_POST["titulo"];
    $descricao = $_POST["descricao"];
    $responsavel = $_SESSION["id"];
    $data_abertura = date("Y-m-d");
    $status = "Pendente";

    // Tratamento da imagem (opcional)
    $foto = null;
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $foto = file_get_contents($_FILES['foto']['tmp_name']);
    }

    $sql = "INSERT INTO manutencao (titulo, descricao, responsavel, foto, status, data_abertura) 
            VALUES (?, ?, ?, ?, ?, ?)";

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

    $stmt->bind_param("ssisss", $titulo, $descricao, $responsavel, $foto, $status, $data_abertura);

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
} else {
    echo "<script>
        Swal.fire({
            icon: 'info',
            title: 'Acesso inválido!',
            text: 'Envie o formulário corretamente.',
            confirmButtonText: 'OK'
        });
    </script>";
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
<?php
echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
?>
</body>
</html>