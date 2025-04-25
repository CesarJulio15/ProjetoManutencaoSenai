<?php
include 'conexao.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  
    $credencial = $_POST['credencial'];
    $senha = $_POST['senha'];

 
    $sql = "SELECT senha FROM usuarios WHERE credencial = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $credencial);
    $stmt->execute();
    $stmt->store_result();

    
    if ($stmt->num_rows === 1) {
        $stmt->bind_result($senha_hash); 
        $stmt->fetch();

     
        if (password_verify($senha, $senha_hash)) {
            echo "<p>Acesso permitido.</p>";
        } else {
            echo "<p>Senha incorreta.</p>";
        }
    } else {
        echo "<p>Credencial inválida ou administrador não encontrado.</p>";
    }

   
    $stmt->close();
    $conn->close();
} else {
    echo "<p>Requisição inválida.</p>";
}
?>
