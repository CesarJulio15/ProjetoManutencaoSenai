<?php
include 'conexao.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = htmlspecialchars($_POST["nome"]);
    $tipo = $_POST["tipo"];
    $credencial = htmlspecialchars($_POST["credencial"]);
    $senha = password_hash($_POST["senha"], PASSWORD_DEFAULT);

   
    $sql = "INSERT INTO tb_usuario (nome, tipo, credencial, senha) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $nome, $tipo, $credencial, $senha);

    if ($stmt->execute()) {
        echo "<h2>Cadastro realizado com sucesso!</h2>";
        echo "<p><strong>Nome:</strong> $nome</p>";
        echo "<p><strong>Tipo:</strong> $tipo</p>";
        echo "<p><strong>Credencial:</strong> $credencial</p>";
        echo "<p><strong>Senha codificada:</strong> $senha</p>";
    } else {
        echo "<p>Erro ao cadastrar: " . $stmt->error . "</p>";
    }

    $stmt->close();
    $conn->close();
} else {
    echo "<p>Formulário não enviado corretamente.</p>";
}
