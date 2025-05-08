<?php
session_start();
include 'conexao.php';

if (!isset($_SESSION['credencial']) || $_SESSION['tipo'] !== 'adm') {
    http_response_code(403);
    echo "Acesso não autorizado.";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = $_POST['titulo'] ?? '';
    $executante_id = $_POST['executante'] ?? '';

    if (empty($titulo) || empty($executante_id)) {
        echo "Dados incompletos.";
        exit;
    }

    // Buscar a credencial do usuário a partir do ID
    $sqlCred = "SELECT credencial FROM usuarios WHERE id = ?";
    $stmtCred = $conn->prepare($sqlCred);
    $stmtCred->bind_param("i", $executante_id);
    $stmtCred->execute();
    $resultCred = $stmtCred->get_result();

    if ($rowCred = $resultCred->fetch_assoc()) {
        $executante_credencial = $rowCred['credencial'];

        // Atualizar a manutenção com a credencial e status
        $sql = "UPDATE manutencao SET executante = ?, status = 'Em Andamento' WHERE titulo = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $executante_credencial, $titulo);

        if ($stmt->execute()) {
            echo "Executante designado com sucesso!";
        } else {
            echo "Erro ao designar executante.";
        }

        $stmt->close();
    } else {
        echo "Executante não encontrado.";
    }

    $stmtCred->close();
    $conn->close();
}
?>
