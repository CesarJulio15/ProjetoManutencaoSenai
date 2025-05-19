<?php
session_start();
require_once 'conexao.php';

if (!isset($_SESSION['nif']) || !isset($_POST['numero_os'])) {
    header("Location: panelTecnico.php");
    exit;
}

$docente = $_SESSION['docente'];
$numero_os = $_POST['numero_os'];
if (!preg_match('/^OS-\d{8}-\d{4}$/', $numero_os)) {
    header("Location: panelTecnico.php");
    exit;
}
$titulo = $_POST['titulo'];

// 1. Remove rejeição anterior
$stmt = $conn->prepare("DELETE FROM rejeicoes WHERE docente = ? AND numero_os = ?");
$stmt->bind_param("ss", $docente, $numero_os);
$stmt->execute();

// 2. Atualiza a manutenção
$stmt = $conn->prepare("UPDATE manutencao SET executante = NULL, status = 'Pendente' WHERE numero_os = ? AND executante = ?");
$stmt->bind_param("ss", $numero_os, $docente);
$stmt->execute();

// 3. Registra rejeição
$stmt = $conn->prepare("INSERT INTO rejeicoes (docente, numero_os) VALUES (?, ?)");
$stmt->bind_param("ss", $docente, $numero_os);
$stmt->execute();

header("Location: panelTecnico.php?status=pendente");
exit;
?>
