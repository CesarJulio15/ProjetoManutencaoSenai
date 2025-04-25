<?php
session_start();
include 'conexao.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $credencial = htmlspecialchars($_POST["credencial"]);
    $senha = password_hash($_POST["senha"], PASSWORD_DEFAULT);


        $sql = "INSERT INTO administrador (credencial, senha) 
                VALUES (?, ?)";
        
       
        if ($stmt = $conn->prepare($sql)) {
         
            $stmt->bind_param("ss",$credencial, $senha);

            
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
?>
