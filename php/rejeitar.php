<?php
session_start();
require_once 'conexao.php';

if (!isset($_SESSION['credencial']) || !isset($_POST['titulo'])) {
    header("Location: pagina_dos_profissionais_manutencao.php");
    exit;
}

$usuario = $_SESSION['credencial'];
$titulo = $_POST['titulo'];

// Remove rejeição anterior, se existir
$sqlDelete = "DELETE FROM rejeicoes WHERE usuario = ? AND titulo = ?";
$stmt = $conn->prepare($sqlDelete);
$stmt->bind_param("ss", $usuario, $titulo);
$stmt->execute();

// Remove o usuário como executante do pedido, se ele tiver aceitado
$sqlUpdate = "UPDATE manutencao SET executante = NULL, status = 'pendente' WHERE titulo = ? AND executante = ?";
$stmt = $conn->prepare($sqlUpdate);
$stmt->bind_param("ss", $titulo, $usuario);
$stmt->execute();

// Registra nova rejeição
$sqlInsert = "INSERT INTO rejeicoes (usuario, titulo) VALUES (?, ?)";
$stmt = $conn->prepare($sqlInsert);
$stmt->bind_param("ss", $usuario, $titulo);
$stmt->execute();

header("Location: pagina_dos_profissionais_manutencao.php?status=pendente");
exit;
?>
