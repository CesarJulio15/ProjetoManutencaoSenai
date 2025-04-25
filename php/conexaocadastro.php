<?php
session_start();
include 'conexao.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = htmlspecialchars($_POST["nome"]);
    $tipo = $_POST["tipo"];
    $credencial = htmlspecialchars($_POST["credencial"]);
    $senha = password_hash($_POST["senha"], PASSWORD_DEFAULT);

    
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
     
        $fotoTmp = $_FILES['foto']['tmp_name'];
        $fotoData = file_get_contents($fotoTmp); 

        $sql = "INSERT INTO usuarios (nome, tipo, credencial, senha, foto) 
                VALUES (?, ?, ?, ?, ?)";
        
        if ($stmt = $conn->prepare($sql)) {
         
            $stmt->bind_param("sssss", $nome, $tipo, $credencial, $senha, $fotoData);

            
            if ($stmt->execute()) {
                echo "<p>Cadastro realizado com sucesso!</p>";
            } else {
                echo "<p>Erro ao cadastrar usuário: " . $stmt->error . "</p>";
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
