<?php
  session_start();
  $tipo = $_SESSION['tipo'];
    if (!isset($_SESSION['id'])) {
    header("Location: index.php");
    exit();
  }
  else if ($tipo !== 'coordenador') {
    header("Location: error403.php");
    exit();
  }
require_once 'conexao.php';
require_once 'enviar_email.php';

    function atualizarExecutante($conn, $executante, $numero_os) {
        $sql = "UPDATE manutencao SET executante = ? WHERE numero_os = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $executante, $numero_os);
        return $stmt->execute();
    }

    function buscarDadosOS($conn, $numero_os) {
        $busca = $conn->prepare("
            SELECT 
                m.titulo, m.numero_os,
                d_exec.nome AS nome_exec, d_exec.email AS email_exec,
                d_resp.nome AS nome_resp, d_resp.email AS email_resp
            FROM manutencao m
            LEFT JOIN docentes d_exec ON m.executante = d_exec.id
            LEFT JOIN docentes d_resp ON m.responsavel = d_resp.id
            WHERE m.numero_os = ?
        ");
        $busca->bind_param("s", $numero_os);
        $busca->execute();
        return $busca->get_result()->fetch_assoc();
    }

    function enviarEmailsDesignacao($dados) {
        $assunto = "Designação de Ordem de Serviço: {$dados['titulo']}";
        // E-mail para o técnico designado
        if (!empty($dados['email_exec'])) {
            $mensagemExec = "
                <p>Olá <strong>{$dados['nome_exec']}</strong>,</p>
                <p>Você foi designado como técnico responsável pela ordem de serviço <strong>\"{$dados['titulo']}\"</strong>.</p>
                <p>Por favor, acesse o sistema para iniciá-la.</p>
                <p>Atenciosamente,<br>Sistema de Manutenção</p>
            ";
            enviarEmailUsuario($dados['email_exec'], $assunto, $mensagemExec);
        }
        // E-mail para o responsável pela OS
        if (!empty($dados['email_resp'])) {
            $mensagemResp = "
                <p>Olá <strong>{$dados['nome_resp']}</strong>,</p>
                <p>Sua ordem de serviço <strong>\"{$dados['titulo']}\"</strong> foi atribuída ao técnico <strong>{$dados['nome_exec']}</strong>.</p>
                <p>Atenciosamente,<br>Sistema de Manutenção</p>
            ";
            enviarEmailUsuario($dados['email_resp'], $assunto, $mensagemResp);
        }
    }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $numero_os = $_POST['numero_os'] ?? '';
    $executante = $_POST['executante'] ?? '';
    if (atualizarExecutante($conn, $executante, $numero_os)) {
        $_SESSION['executante_sucesso'] = true;
        $dados = buscarDadosOS($conn, $numero_os);
        if ($dados) {
            enviarEmailsDesignacao($dados);
        }
    } else {
        $_SESSION['executante_erro'] = true;
    }
    header("Location: detalhe.php?numero_os=" . urlencode($numero_os));
    exit();
}
?>
