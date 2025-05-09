<?php
session_start();
require_once 'conexao.php';

if (!isset($_SESSION['nif']) || !isset($_POST['titulo'])) {
    header("Location: pagina_dos_profissionais_manutencao.php");
    exit;
}

$docente = $_SESSION['docente'];
$titulo = $_POST['titulo'];

// Remove rejeição anterior, se existir
$sqlDelete = "DELETE FROM rejeicoes WHERE docente = ? AND titulo = ?";
$stmt = $conn->prepare($sqlDelete);
$stmt->bind_param("ss", $docente, $titulo);
$stmt->execute();

// Remove o usuário como executante do pedido, se ele tiver aceitado
$sqlUpdate = "UPDATE manutencao SET executante = NULL, status = 'pendente' WHERE titulo = ? AND executante = ?";
$stmt = $conn->prepare($sqlUpdate);
$stmt->bind_param("ss", $titulo, $docente);
$stmt->execute();

// Registra nova rejeição
$sqlInsert = "INSERT INTO rejeicoes (docente, titulo) VALUES (?, ?)";
$stmt = $conn->prepare($sqlInsert);
$stmt->bind_param("ss", $docente, $titulo);
$stmt->execute();

header("Location: pagina_dos_profissionais_manutencao.php?status=pendente");
exit;
?>
