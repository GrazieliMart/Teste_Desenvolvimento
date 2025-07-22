<?php
session_start();
include 'conexao.php';

$pedido_id = intval($_GET['pedido_id'] ?? 0);




$stmt = $conn->prepare("SELECT nome_cliente, email_cliente, endereco, cep, desconto, frete, total, status, data FROM pedidos WHERE id = ?");
$stmt->bind_param("i", $pedido_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Pedido não encontrado.";
    exit;
}

$pedido = $result->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8" />
    <title>Yummy Burguer</title>
    <link rel="stylesheet" href="css/css.css">
    <link rel="shortcut icon" href="assets/favicon.ico" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<style>
    .obrigado-container {
        max-width: 600px;
        margin: 0 auto;
        text-align: center;
        padding: 50px;
        background-color: #f8f9fa;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }
    .obrigado-container h1 {
        color: #28a745;
        margin-bottom: 20px;
    }
    .obrigado-container ul{
        list-style: none;
        padding: 0;
        text-align: left;
    }
    .email{
        background-color: #e9ecef;
        border-radius: 50px;
        width: fit-content;
           
    }
</style>
</head>
<body>
<div class="container mt-5">
    <div class="obrigado-container">
    <h1>Obrigado pelo seu pedido!</h1>
    <p>Olá <strong><?= htmlspecialchars($pedido['nome_cliente']) ?></strong>, seu pedido <strong>#<?= $pedido_id ?></strong> foi recebido com sucesso.</p>
  <br>
    <div class="email">
    <p>Um e-mail de confirmação foi enviado para <strong><?= htmlspecialchars($pedido['email_cliente']) ?></strong>.</p>
</div>
<br>
    <h4>Resumo do Pedido</h4>
    <ul>
        <li><strong>Data do pedido:</strong> <?= date('d/m/Y H:i', strtotime($pedido['data'])) ?></li>
        <li><strong>Status:</strong> <?= htmlspecialchars($pedido['status']) ?></li>
        <li><strong>Endereço de entrega:</strong> <?= nl2br(htmlspecialchars($pedido['endereco'])) ?> - CEP: <?= htmlspecialchars($pedido['cep']) ?></li>
        <li><strong>Desconto:</strong> R$<?= number_format($pedido['desconto'], 2, ',', '.') ?></li>
        <li><strong>Frete:</strong> R$<?= number_format($pedido['frete'], 2, ',', '.') ?></li>
        <li><strong>Total:</strong> R$<?= number_format($pedido['total'], 2, ',', '.') ?></li>
    </ul>

    <a href="index.php" class="btn btn-primary">Voltar para a loja</a>
</div></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
