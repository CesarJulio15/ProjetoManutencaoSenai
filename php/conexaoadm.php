<?php
include 'conexao.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $credencial = htmlspecialchars($_POST["credencial"]);
    $senha = password_hash($_POST["senha"], PASSWORD_DEFAULT);

   
    $sql = "INSERT INTO usuarios (credencial, senha) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $credencial, $senha);

    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
    } else {
        echo "<p>Erro ao cadastrar: " . $stmt->error . "</p>";
    }

    
} else {
    echo "<p>Formulário não enviado corretamente.</p>";
}
