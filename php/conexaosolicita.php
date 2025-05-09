<?php
session_start();
include 'conexao.php';

if (!isset($_SESSION['nif'])) {
    echo "<script>alert('Usuário não autenticado! Faça login.'); window.location.href='index.php';</script>";
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $titulo = htmlspecialchars($_POST["titulo"]);
    $descricao = $_POST["descricao"];
    $responsavel = $_SESSION['nif'];

    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $fotoTmp = $_FILES['foto']['tmp_name'];

        if (!empty($fotoTmp)) {
            $fotoData = file_get_contents($fotoTmp);
            $fotoType = $_FILES['foto']['type'];
            $validExtensions = ["image/jpeg", "image/jpg", "image/png"];

            if (!in_array($fotoType, $validExtensions)) {
                echo "<script>alert('Formato de imagem não permitido!'); window.history.back();</script>";
                exit();
            }

            // Verifica se o responsável existe na tabela de usuários
            $sqlVerificarResponsavel = "SELECT nif FROM docentes WHERE nif = ?";
            $stmtVerificar = $conn->prepare($sqlVerificarResponsavel);
            $stmtVerificar->bind_param("s", $responsavel);
            $stmtVerificar->execute();
            $stmtVerificar->store_result();

            if ($stmtVerificar->num_rows === 1) {
                $tituloOriginal = $titulo;
                $sufixo = 1;
                $sqlCheckTitulo = "SELECT 1 FROM manutencao WHERE titulo = ?";
                $stmtCheckTitulo = $conn->prepare($sqlCheckTitulo);
                $stmtCheckTitulo->bind_param("s", $titulo);

                while (true) {
                    $stmtCheckTitulo->execute();
                    $stmtCheckTitulo->store_result();

                    if ($stmtCheckTitulo->num_rows == 0) {
                        break;
                    }

                    $titulo = $tituloOriginal . "(" . $sufixo . ")";
                    $stmtCheckTitulo->bind_param("s", $titulo);
                    $sufixo++;
                }
                $stmtCheckTitulo->close();

                $sql = "INSERT INTO manutencao (titulo, descricao, responsavel, foto) 
                        VALUES (?, ?, ?, ?)";

                if ($stmt = $conn->prepare($sql)) {
                    $stmt->bind_param("ssss", $titulo, $descricao, $responsavel, $fotoData);

                    if ($stmt->execute()) {
                        echo "<script>alert('Cadastro realizado com sucesso! Título usado: $titulo'); window.location.href='solicita.php';</script>";
                    } else {
                        echo "<script>alert('Erro ao cadastrar manutenção: " . $stmt->error . "'); window.history.back();</script>";
                    }

                    $stmt->close();
                } else {
                    echo "<script>alert('Erro ao preparar a consulta: " . $conn->error . "'); window.history.back();</script>";
                }
            } else {
                echo "<script>alert('Responsável não encontrado na tabela de usuários!'); window.history.back();</script>";
            }

            $stmtVerificar->close();
        } else {
            echo "<script>alert('Erro no upload do arquivo, caminho temporário não encontrado!'); window.history.back();</script>";
        }
    } else {
        echo "<script>alert('Erro no upload da foto ou foto não enviada!'); window.history.back();</script>";
    }

    $conn->close();
} else {
    echo "<script>alert('Formulário não enviado corretamente.'); window.history.back();</script>";
}
?>
