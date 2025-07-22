<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';

function enviarEmailAtualizacaoStatus($conn, $pedido_id, $novo_status) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'grazielimartins5@gmail.com'; // Seu e-mail SMTP
        $mail->Password   = 'SENHA';      // Sua senha ou app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        $mail->setFrom('grazielimartins5@gmail.com', 'Nome da Empresa');

     
        $stmt = $conn->prepare("SELECT email_cliente FROM pedidos WHERE id = ?");
        $stmt->bind_param("i", $pedido_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $cliente = $result->fetch_assoc();

        if ($cliente && $cliente['email_cliente']) {
            $mail->addAddress($cliente['email_cliente']);

            $mail->isHTML(true);
            $mail->Subject = 'Atualização de Status do Pedido';
            $mail->Body    = "O status do seu pedido foi atualizado para: <strong>$novo_status</strong>";

            $mail->send();
        } else {
            error_log("E-mail do cliente não encontrado para pedido ID $pedido_id");
        }

    } catch (Exception $e) {
        error_log("Erro ao enviar e-mail: {$mail->ErrorInfo}");
    }
}

