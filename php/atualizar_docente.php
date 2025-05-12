<?php
include 'conexao.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id     = $_POST['id'];
    $nome   = $_POST['nome'];
    $nif    = $_POST['nif'];
    $email  = $_POST['email'];
    $tipo   = $_POST['tipo'];
    $senha  = $_POST['senha'];

    if (!empty($senha)) {
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
        $sql = "UPDATE docentes SET nome = ?, nif = ?, email = ?, senha = ?, tipo = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssi", $nome, $nif, $email, $senha_hash, $tipo, $id);
    } else {
        $sql = "UPDATE docentes SET nome = ?, nif = ?, email = ?, tipo = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssi", $nome, $nif, $email, $tipo, $id);
    }

    if ($stmt->execute()) {
        header("Location: listar_pessoal.php?msg=sucesso");
        exit();
    } else {
        echo "Erro ao atualizar: " . $stmt->error;
    }
} else {
    echo "Requisição inválida.";
}
