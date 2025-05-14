<?php
require_once 'conexao.php';

if (!isset($_GET['token'])) {
    exit("Token inválido.");
}

$token = $_GET['token'];

$stmt = $conn->prepare("SELECT id FROM docentes WHERE reset_token = ? AND reset_token_expiry >= NOW()");
$stmt->bind_param("s", $token);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows === 0) {
    exit("Token expirado ou inválido.");
}
?>

<form method="POST" action="salvar_nova_senha.php">
    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
    <label>Nova Senha:</label>
    <input type="password" name="nova_senha" required>
    <button type="submit">Salvar Nova Senha</button>
</form>
