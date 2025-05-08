<?php
session_start();
include 'conexao.php';

if (!isset($_SESSION['credencial'])) {
    echo "<p>Usuário não autenticado! Acesse <a href='index.php'>Login</a> para autenticar-se.</p>";
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $titulo = htmlspecialchars($_POST["titulo"]);
    $descricao = $_POST["descricao"];
    $responsavel = $_SESSION['credencial']; 

    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $fotoTmp = $_FILES['foto']['tmp_name'];

        if (!empty($fotoTmp)) {
            $fotoData = file_get_contents($fotoTmp);
            $fotoType = $_FILES['foto']['type'];
            $validExtensions = ["image/jpeg", "image/jpg", "image/png"];

            if (!in_array($fotoType, $validExtensions)) {
                echo "<p>Formato de imagem não permitido!</p>";
                exit();
            }

            // Verifica se o responsável existe na tabela de usuários
            $sqlVerificarResponsavel = "SELECT credencial FROM usuarios WHERE credencial = ?";
            $stmtVerificar = $conn->prepare($sqlVerificarResponsavel);
            $stmtVerificar->bind_param("s", $responsavel);
            $stmtVerificar->execute();
            $stmtVerificar->store_result();

            if ($stmtVerificar->num_rows === 1) {
                // Insere os dados no banco (sem status)
                $sql = "INSERT INTO manutencao (titulo, descricao, responsavel, foto) 
                        VALUES (?, ?, ?, ?)";

                if ($stmt = $conn->prepare($sql)) {
                    $stmt->bind_param("ssss", $titulo, $descricao, $responsavel, $fotoData);

                    if ($stmt->execute()) {
                        echo "<p>Cadastro de manutenção realizado com sucesso!</p>";
                    } else {
                        echo "<p>Erro ao cadastrar manutenção: " . $stmt->error . "</p>";
                    }

                    $stmt->close();
                } else {
                    echo "<p>Erro ao preparar a consulta: " . $conn->error . "</p>";
                }
            } else {
                echo "<p>Responsável não encontrado na tabela de usuários!</p>";
            }

            $stmtVerificar->close();
        } else {
            echo "<p>Erro no upload do arquivo, caminho temporário não encontrado!</p>";
        }
    } else {
        echo "<p>Erro no upload da foto ou foto não enviada!</p>";
    }

    $conn->close();
} else {
    echo "<p>Formulário não enviado corretamente.</p>";
}
?>
