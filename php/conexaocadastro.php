<?php
session_start();
if (!isset($_SESSION['id']) && $_SESSION['tipo'] !== 'coordenador') {
    header("Location: index.php");
    exit();
}
include 'conexao.php'; 
require_once 'enviar_email.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = htmlspecialchars($_POST["nome"]);
    $tipo = $_POST["tipo"];
    $nif = str_replace('sn', '',htmlspecialchars($_POST["nif"])); // Remove 'sn' se existir
    $email = $_POST['email'];
    $senha = password_hash($_POST["senha"], PASSWORD_DEFAULT);

    $sql =  "INSERT INTO docentes (nome, nif, tipo, email, senha) VALUES (?, ? ,?, ?, ?)";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("sssss", $nome, $nif, $tipo, $email, $senha);
        // Após cadastrar no banco com sucesso:
        if ($stmt->execute()) {
            // Dados do novo usuário
            $nomeUsuario = $_POST['nome'];
            $emailUsuario = $_POST['email'];
            $senhaUsuario = $_POST['senha']; // Cuidado: nunca envie a senha real em produção!
            $paginaLogin = "https://devgomes.com/projetos/SENAImanutencao/php/";
            // Envia e-mail com os dados
            enviarEmailCadastro($emailUsuario, $nomeUsuario, $senhaUsuario, $paginaLogin);
            // Redireciona ou exibe mensagem de sucesso
            header("Location: panelTecnico.php");
            exit();
        }
        $stmt->close();
    }

    $conn->close();

    header("Location: panelTecnico.php");
    exit();
} else {
    header("Location: panelTecnico.php");
    exit();
}
?>
