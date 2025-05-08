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
            $stmt->execute();
            $stmt->close();
        }
        // Caso deseje tratar o erro de forma diferente, você pode logar ou redirecionar para outra página
    }

    $conn->close();
    
    // Redirecionamento após o processamento
    header("Location: manutencao.php");
    exit();
} else {
    // Redireciona também se o formulário não foi enviado corretamente
    header("Location: manutencao.php");
    exit();
}
?>
