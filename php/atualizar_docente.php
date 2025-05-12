<?php
include 'conexao.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id     = $_POST['id'];
    $nome   = $_POST['nome'];
    $nif    = $_POST['nif'];
    $email  = $_POST['email'];
    $tipo   = $_POST['tipo'];
    $status = $_POST['status'];
    $senha  = $_POST['senha'];

    if (!empty($senha)) {
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
        $sql = "UPDATE docentes SET nome = ?, nif = ?, email = ?, senha = ?, tipo = ?, docente_status = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssi", $nome, $nif, $email, $senha_hash, $tipo, $status, $id);
    } else {
        $sql = "UPDATE docentes SET nome = ?, nif = ?, email = ?, tipo = ?, docente_status = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssi", $nome, $nif, $email, $tipo, $status, $id);
    }

    if ($stmt->execute()) {
        header("Location: listar_funcionarios.php?msg=sucesso");
        exit();
    } else {
        echo "Erro ao atualizar: " . $stmt->error;
    }
} else {
    echo "Requisição inválida.";
}
