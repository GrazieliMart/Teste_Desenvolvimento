<?php
include 'conexao.php';

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) exit;

$stmt = $conn->prepare("SELECT imagem FROM produtos WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($imagem_blob);
$stmt->fetch();
$stmt->close();

if ($imagem_blob) {
    header("Content-Type: image/jpeg"); 
    echo $imagem_blob;
} else {
    header("Content-Type: image/png");
    readfile("img/sem-imagem.png"); 
}
