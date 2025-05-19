<?php
session_start();
require 'conexao.php';
require_once 'enviar_email.php';

if (!isset($_SESSION['id'])) {
    header("Location: index.php");
    exit();
}
// 2) Só processa se vier POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['erro'] = "Envie o formulário corretamente.";
    header("Location: solicita.php");
    exit();
}

function gerarNumeroOS($conn) {
    do {
        $numero_os = 'OS-' . date('Ymd') . '-' . str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        $check = $conn->prepare("SELECT id FROM manutencao WHERE numero_os = ?");
        $check->bind_param("s", $numero_os);
        $check->execute();
        $result = $check->get_result();
        $check->close();
    } while ($result->num_rows > 0);
    return $numero_os;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $numero_os = gerarNumeroOS($conn);  
    $titulo = trim(filter_input(INPUT_POST, "titulo", FILTER_SANITIZE_SPECIAL_CHARS));
    $descricao = trim(filter_input(INPUT_POST, "descricao", FILTER_SANITIZE_SPECIAL_CHARS));
    $bloco = trim(filter_input(INPUT_POST, "bloco", FILTER_SANITIZE_SPECIAL_CHARS));
    $localizacao = trim(filter_input(INPUT_POST, "localizacao", FILTER_SANITIZE_SPECIAL_CHARS));
    $data_abertura = date("Y-m-d");
    $tipoUsuario = $_SESSION['tipo'] ?? null;
    $origem = $_POST['origem'] ?? 'Não especificado';
    $tipo_manutencao = $_POST['tipo_manutencao'] ?? 'Corretiva';

        // Define o responsável: se for coordenador ou técnico, vem do POST; senão, do próprio login
    $responsavel = (in_array($tipoUsuario, ['coordenador', 'técnico']) && !empty($_POST['responsavel']))
    ? intval($_POST['responsavel'])
    : intval($_SESSION['id']);

    // Verifica uma única vez se o responsável existe
    $verificaResp = $conn->prepare("SELECT id FROM docentes WHERE id = ?");
    $verificaResp->bind_param("i", $responsavel);
    $verificaResp->execute();
    if ($verificaResp->get_result()->num_rows === 0) {
    $_SESSION['erro'] = "O responsável informado não existe.";
    header("Location: solicita.php");
    exit();
    }
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Token CSRF inválido.');
    }
        // força prioridade
    if (!in_array( $tipoUsuario, ['coordenador', 'técnico'])) {
        $prioridade = 'Média';
    } else {
        // validação extra
        $prioridade = filter_input(INPUT_POST, 'prioridade', FILTER_SANITIZE_SPECIAL_CHARS);
        $prioridade = in_array($prioridade, ['Baixa', 'Média', 'Alta', 'Urgente']) ? $prioridade : 'Média';
    }

    // 5) Define o executante: se for técnico, é o próprio usuário; senão, null
    $executante = null;
    if (in_array($tipoUsuario, ['coordenador', 'técnico']) && !empty($_POST['executante'])) {
        $executante = intval($_POST['executante']);
        $status = "Em Andamento";
    } elseif ($tipoUsuario === 'técnico') {
        $executante = intval($_SESSION['id']);
        $status = "Em Andamento";
    } else {
        $status = "Pendente";
    }
    // Tratamento da imagem (opcional)
    $foto = null;
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($_FILES['foto']['tmp_name']);
        $tiposPermitidos = ['image/jpeg', 'image/png'];
    
        if (in_array($mime, $tiposPermitidos)) {
            $foto = file_get_contents($_FILES['foto']['tmp_name']);
        } else {
            $_SESSION['erro'] = "Formato de imagem inválido. Apenas JPEG e PNG são permitidos.";
            header("Location: solicita.php");
            exit();
        }
    }
    
    $sql = "INSERT INTO manutencao (
        numero_os, titulo, descricao, responsavel, foto, status, data_abertura, prioridade, bloco, localizacao_ambiente, origem, tipo_manutencao
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        $_SESSION['erro'] = "Erro na preparação da query: " . $conn->error;
        header("Location: solicita.php");
        exit();
    }
    $stmt->bind_param("sssissssssss", $numero_os, $titulo, $descricao, $responsavel, $foto, $status, $data_abertura, $prioridade, $bloco, $localizacao, $origem, $tipo_manutencao);
    if ($stmt->execute()) {
            // Buscar e-mail e nome do responsável
            $query = $conn->prepare("SELECT nome, email FROM docentes WHERE id = ?");
            $query->bind_param("i", $responsavel);
            $query->execute();
            $result = $query->get_result();

            if ($row = $result->fetch_assoc()) {
                $nome = $row['nome'];
                $email = $row['email'];
                $linkOS = "https://devgomes.com/projetos/SENAImanutencao/php/detalhe.php?numero_os=" . urlencode($numero_os);
                $qrcodeURL = "https://chart.googleapis.com/chart?cht=qr&chs=180x180&chl=" . urlencode($linkOS);
                // ✉️ Enviar e-mail usando PHPMailer
                $assunto = "Confirmação de abertura de Ordem de Serviço";
                $mensagem = "
                    <p>Olá <strong>$nome</strong>,</p>
                    <p>Sua ordem de serviço com o título <strong>\"$titulo\"</strong> foi registrada com sucesso no sistema.</p>
                    <p><strong>Status atual:</strong> $status<br>
                    <strong>Data de abertura:</strong> $data_abertura</p>
                    <p>Acesse diretamente a OS pelo link abaixo:</p>
                    <p><a href='$linkOS'>$linkOS</a></p>
                    <p>Ou escaneie o QR Code:</p>
                    <img src='$qrcodeURL' alt='QR Code da OS' />
                    <p>Atenciosamente,<br>Sistema de Manutenção</p>
                ";

                $resultado = enviarEmailUsuario($email, $assunto, $mensagem);
                if ($resultado !== true) {
                    $_SESSION['erro'] = "Erro ao enviar e-mail: $resultado.";
                    header("Location: solicita.php");
                    exit();
                }
            }
            // ✉️ Se houver técnico designado, enviar também para ele
            if (!empty($executante)) {
                $qExec = $conn->prepare("SELECT nome, email FROM docentes WHERE id = ?");
                $qExec->bind_param("i", $executante);
                $qExec->execute();
                $resExec = $qExec->get_result();

                if ($rowExec = $resExec->fetch_assoc()) {
                    $nomeExec = $rowExec['nome'];
                    $emailExec = $rowExec['email'];

                    $assuntoExec = "Nova OS atribuída: $titulo";
                    $mensagemExec = "
                        <p>Olá <strong>$nomeExec</strong>,</p>
                        <p>Você foi designado como técnico responsável pela OS <strong>\"$titulo\"</strong>.</p>
                        <p>Status: <strong>Em Andamento</strong><br>Data: <strong>$data_abertura</strong></p>
                        <p>Atenciosamente,<br>Sistema de Manutenção</p>
                    ";

                    enviarEmailUsuario($emailExec, $assuntoExec, $mensagemExec);
                }
            }

            $_SESSION['sucesso'] = "Solicitação registrada com sucesso!";
            header("Location: solicita.php");
            exit();
    } else {
        $_SESSION['erro'] = "Erro ao registrar solicitação!.";
        header("Location: solicita.php");
        exit();
    }

    $stmt->close();
} 
?>