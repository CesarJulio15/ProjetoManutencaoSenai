<?php
session_start();
if ($_SESSION['tipo'] !== 'coordenador') {
    http_response_code(403);
    exit;
}

require_once 'conexao.php';

$id_docente = filter_input(INPUT_POST, 'docente_id', FILTER_VALIDATE_INT);
$id_escola  = filter_input(INPUT_POST, 'escola_id', FILTER_VALIDATE_INT);
$acao       = $_POST['acao'] ?? '';

if (!$id_docente || !$id_escola || !in_array($acao, ['vincular', 'remover'])) {
    http_response_code(400);
    exit('Dados inválidos.');
}

if ($acao === 'vincular') {
    $stmt = $conn->prepare("INSERT IGNORE INTO docentes_escolas (id_docente, escola_id, status) VALUES (?, ?, 'Ativo')");
    $stmt->bind_param("ii", $id_docente, $id_escola);
    $stmt->execute();
    echo 'ok';
}

if ($acao === 'remover') {
    $stmt = $conn->prepare("DELETE FROM docentes_escolas WHERE id_docente = ? AND escola_id = ?");
    $stmt->bind_param("ii", $id_docente, $id_escola);
    $stmt->execute();
    echo 'ok';
}
