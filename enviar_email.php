<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';
session_start();

if (!isset($_SESSION['dados_email'])) {
    throw new Exception("Faltam dados para enviar o e-mail.");
}


$dados = $_SESSION['dados_email'];
$nome = $dados['nome'];
$email = $dados['email'];
$endereco = $dados['endereco'];
$cep = $dados['cep'];
$frete = $dados['frete'];
$desconto = $dados['desconto'];
$total = $dados['total'];
$subtotal = $dados['subtotal'];
$produtos = $dados['produtos'];


$mensagem = "<h2>Resumo da compra de $nome</h2>";
$mensagem .= "<ul>";
foreach ($produtos as $item) {
    $mensagem .= "<li>{$item['nome']} ({$item['variacao']}) - R$" . number_format($item['preco'],2,',','.') . 
                 " x {$item['quantidade']} = R$" . number_format($item['preco'] * $item['quantidade'],2,',','.') . "</li>";
}
$mensagem .= "</ul>";
$mensagem .= "<p><strong>Subtotal:</strong> R$" . number_format($subtotal,2,',','.') . "</p>";
if ($desconto > 0) {
    $mensagem .= "<p><strong>Desconto:</strong> -R$" . number_format($desconto,2,',','.') . "</p>";
}
$mensagem .= "<p><strong>Frete:</strong> R$" . number_format($frete,2,',','.') . "</p>";
$mensagem .= "<p><strong>Total:</strong> R$" . number_format($total,2,',','.') . "</p>";
$mensagem .= "<p><strong>Endereço:</strong> $endereco, CEP: $cep</p>";


try {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = 'sandbox.smtp.mailtrap.io'; 
    $mail->SMTPAuth   = true;
    $mail->Username   = 'grazielimartins5@gmail.com';
    $mail->Password   = 'SENHA';
    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;

    $mail->setFrom('SENHA', 'YUMMY BURGUER');
    $mail->addAddress($email, $nome);
    $mail->Subject = "Confirmação do pedido - YUMMY BURGUER";
    $mail->isHTML(true);
    $mail->Body = $mensagem;

    $mail->send();
    echo "E-mail enviado com sucesso!";
    
    // Limpa carrinho e sessão
    unset($_SESSION['carrinho']);
    unset($_SESSION['dados_email']);
} catch (Exception $e) {
    echo "Erro ao enviar e-mail: {$mail->ErrorInfo}";
}
