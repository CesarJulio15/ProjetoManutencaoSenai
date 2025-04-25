<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

include 'conexao.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (isset($_SESSION['credencial'])) {
        $titulo = htmlspecialchars($_POST["titulo"]);
        $descricao = $_POST["descricao"];
        $responsavel = $_SESSION['credencial']; 

        if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
            $fotoTmp = $_FILES['foto']['tmp_name'];
            $fotoData = file_get_contents($fotoTmp); 

            $sql = "INSERT INTO manutencao (titulo, descricao, responsavel, foto) 
                    VALUES (?, ?, ?, ?)";
            
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("ssss", $titulo, $descricao, $responsavel, $fotoData);

                if ($stmt->execute()) {
                    echo "<p>Cadastro realizado com sucesso!</p>";
                } else {
                    echo "<p>Erro ao cadastrar: " . $stmt->error . "</p>";
                }

                $stmt->close();
            } else {
                echo "<p>Erro ao preparar a consulta: " . $conn->error . "</p>";
            }
        } else {
            echo "<p>Erro no upload da foto ou foto não enviada!</p>";
        }
    } else {
        echo "<p>Usuário não autenticado!</p>";
    }

    $conn->close();
} else {
    echo "<p>Formulário não enviado corretamente.</p>";
}
?>
