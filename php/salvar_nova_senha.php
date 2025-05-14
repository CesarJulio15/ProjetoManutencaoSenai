<?php
require_once 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'];
    $senha = password_hash($_POST['nova_senha'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("UPDATE docentes SET senha = ?, reset_token = NULL, reset_token_expiry = NULL WHERE reset_token = ? AND reset_token_expiry >= NOW()");
    $stmt->bind_param("ss", $senha, $token);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo "Senha atualizada com sucesso. <a href='index.php'>Acessar sistema</a>";
    } else {
        echo "Token inválido ou expirado.";
    }
}
