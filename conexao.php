<?php
$host = "localhost";
$user = "root";
$pass = "Senha";
$db = "mini_erp";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Erro de conexão: " . $conn->connect_error);
}
?>
