<?php
session_start();
require 'conexao.php';
require_once 'enviar_email.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

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
    function validarResponsavel($conn, $responsavel) {
        $verificaResp = $conn->prepare("SELECT id FROM docentes WHERE id = ?");
        $verificaResp->bind_param("i", $responsavel);
        $verificaResp->execute();
        $result = $verificaResp->get_result();
        $verificaResp->close();
        return $result->num_rows > 0;
    }

    function tratarImagem($file) {
        if (isset($file) && $file['error'] === UPLOAD_ERR_OK) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($file['tmp_name']);
            $tiposPermitidos = ['image/jpeg', 'image/png'];
            if (in_array($mime, $tiposPermitidos)) {
                return file_get_contents($file['tmp_name']);
            } else {
                $_SESSION['erro'] = "Formato de imagem inválido. Apenas JPEG e PNG são permitidos.";
                header("Location: solicita.php");
                exit();
            }
        }
        return null;
    }

    function enviarEmailResponsavel($conn, $responsavel, $numero_os, $titulo, $status, $data_abertura) {
        $query = $conn->prepare("SELECT nome, email FROM docentes WHERE id = ?");
        $query->bind_param("i", $responsavel);
        $query->execute();
        $result = $query->get_result();

        if ($row = $result->fetch_assoc()) {
            $nome = $row['nome'];
            $email = $row['email'];
            $linkOS = "https://devgomes.com/projetos/SENAImanutencao/php/detalhe.php?numero_os=" . urlencode($numero_os);
            $qrcodeTempDir = __DIR__ . '/../qrcodes/';
            if (!file_exists($qrcodeTempDir)) {
                mkdir($qrcodeTempDir, 0755, true);
            }
            $qrcodeFile = $qrcodeTempDir . $numero_os . '.png';
            $options = new QROptions([
                'outputType' => QRCode::OUTPUT_IMAGE_PNG,
                'eccLevel' => QRCode::ECC_L,
                'scale' => 5,
            ]);
            (new QRCode($options))->render($linkOS, $qrcodeFile);
            $qrcodeURL = 'https://devgomes.com/projetos/SENAImanutencao/qrcodes/' . $numero_os . '.png';
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
            return enviarEmailUsuario($email, $assunto, $mensagem);
        }
        return false;
    }

    function enviarEmailExecutante($conn, $executante, $titulo, $data_abertura) {
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
            return enviarEmailUsuario($emailExec, $assuntoExec, $mensagemExec);
        }
        return false;
    }

    // --- Início do processamento principal ---
    $numero_os = gerarNumeroOS($conn);  
    $titulo = trim(filter_input(INPUT_POST, "titulo", FILTER_SANITIZE_SPECIAL_CHARS));
    $descricao = trim($_POST['descricao']);
    $bloco = trim(filter_input(INPUT_POST, "bloco", FILTER_SANITIZE_SPECIAL_CHARS));
    $localizacao = trim(filter_input(INPUT_POST, "localizacao", FILTER_SANITIZE_SPECIAL_CHARS));
    $data_abertura = date("Y-m-d");
    $tipoUsuario = $_SESSION['tipo'] ?? null;
    $origem = $_POST['origem'] ?? 'Não especificado';
    $tipo_manutencao = $_POST['tipo_manutencao'] ?? 'Corretiva';

    $responsavel = (in_array($tipoUsuario, ['coordenador', 'técnico']) && !empty($_POST['responsavel']))
        ? intval($_POST['responsavel'])
        : intval($_SESSION['id']);

    if (!validarResponsavel($conn, $responsavel)) {
        $_SESSION['erro'] = "O responsável informado não existe.";
        header("Location: solicita.php");
        exit();
    }

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Token CSRF inválido.');
    }

    if (!in_array($tipoUsuario, ['coordenador', 'técnico'])) {
        $prioridade = 'Média';
    } else {
        $prioridade = filter_input(INPUT_POST, 'prioridade', FILTER_SANITIZE_SPECIAL_CHARS);
        $prioridade = in_array($prioridade, ['Baixa', 'Média', 'Alta', 'Urgente']) ? $prioridade : 'Média';
    }

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

    $foto = tratarImagem($_FILES['foto'] ?? null);

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
        $resultado = enviarEmailResponsavel($conn, $responsavel, $numero_os, $titulo, $status, $data_abertura);
        $email_resultado = null;
        if (!empty($executante)) {
            $email_resultado = enviarEmailExecutante($conn, $executante, $titulo, $data_abertura);
        }
        if ($email_resultado === true || $email_resultado === null) {
            $_SESSION['sucesso'] = "Solicitação registrada com sucesso!";
        } else {
            $_SESSION['erro'] = "Erro ao enviar e-mail: " . $resultado;
        }
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