<?php
// Detalhes da conexão
$servername = "sh-pro66@hostgator.com.br";
$username = "devgom44_gomesfelipe";
$password = "Fgs@31447489";
$dbname = "db_plano";
$charset = 'utf8mb4';

// Cria a conexão
$conn = new mysqli($servername, $username, $password, $dbname, 3306);

// Verifica a conexão
if ($conn->connect_error) {
    error_log("Connection failed: " . $conn->connect_error); // Log the error
    die("Erro na conexão ao banco de dados. Por favor, tente novamente mais tarde."); // Display a user-friendly message
}

// Define o charset da conexão
if (!$conn->set_charset($charset)) {
    error_log("Error loading character set $charset: " . $conn->error); // Log charset error
    die("Erro de configuração no banco de dados.");
}

// Certifique-se de fechar a conexão após a execução das consultas
// $conn->close(); -> adicione isso no final do script ou em um ponto onde a conexão não é mais necessária

?>
