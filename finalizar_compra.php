<?php
session_start();
include 'conexao.php';


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cupom_id'])) {
    $cupom_id = intval($_POST['cupom_id']);
    $hoje = date('Y-m-d');

    $stmt = $conn->prepare("SELECT codigo, validade FROM cupons WHERE id = ? AND validade >= ?");
    $stmt->bind_param("is", $cupom_id, $hoje);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 1) {
        $cupom = $res->fetch_assoc();
        $_SESSION['cupom_selecionado'] = [
            'codigo' => $cupom['codigo'],
            'validade' => $cupom['validade']
        ];
    }
    $stmt->close();

    header("Location: finalizar_compra.php");
    exit;
}

$subtotal = 0;
$produtos_carrinho = [];

foreach ($_SESSION['carrinho'] as $produto_id => $quantidade) {
  $stmt = $conn->prepare("SELECT nome, preco, variacao, imagem FROM produtos WHERE id = ?");

    $stmt->bind_param("i", $produto_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $produto = $res->fetch_assoc();
    if ($produto) {
        $totalItem = $produto['preco'] * $quantidade;
        $subtotal += $totalItem;
        $produtos_carrinho[] = [
            'nome' => $produto['nome'],
            'variacao' => $produto['variacao'],
            'preco' => $produto['preco'],
            'quantidade' => $quantidade,
            'totalItem' => $totalItem,
            'imagem' => $produto['imagem'],
        ];
    }
    $stmt->close();
}

$cupom_info = $_SESSION['cupom_selecionado'] ?? null;
$cupom_codigo = $cupom_info['codigo'] ?? '';
$cupom_validade = $cupom_info['validade'] ?? '';
$desconto = 0;

if ($cupom_codigo) {
    $hoje = date('Y-m-d');
    $stmt = $conn->prepare("SELECT valor, minimo, validade FROM cupons WHERE codigo = ? AND validade >= ?");
    $stmt->bind_param("ss", $cupom_codigo, $hoje);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 1) {
        $cupom = $res->fetch_assoc();
        if ($subtotal >= $cupom['minimo']) {
            $desconto = floatval($cupom['valor']);
        } else {
            unset($_SESSION['cupom_selecionado']);
            $cupom_codigo = '';
            $cupom_validade = '';
        }
    } else {
        unset($_SESSION['cupom_selecionado']);
        $cupom_codigo = '';
        $cupom_validade = '';
    }
    $stmt->close();
}

$subtotal_com_desconto = max(0, $subtotal - $desconto);

if ($subtotal_com_desconto >= 200) {
    $frete = 0;
    $freteTexto = "Frete grátis";
} elseif ($subtotal_com_desconto >= 52 && $subtotal_com_desconto <= 166.59) {
    $frete = 15;
    $freteTexto = "Frete: R$15,00";
} else {
    $frete = 20;
    $freteTexto = "Frete: R$20,00";
}

$total = $subtotal_com_desconto + $frete;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['finalizar'])) {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $endereco = trim($_POST['endereco'] ?? '');
    $cep = trim($_POST['cep'] ?? '');

    if (!$nome || !$email || !$endereco || !$cep) {
        $erro = "Por favor, preencha todos os dados do endereço.";
    } else {
        $status = 'pendente';
        $data_pedido = date('Y-m-d H:i:s');

        $stmt = $conn->prepare("INSERT INTO pedidos (nome_cliente, email_cliente, endereco, cep, desconto, frete, total, status, data) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssdddss", $nome, $email, $endereco, $cep, $desconto, $frete, $total, $status, $data_pedido);
        $stmt->execute();
        $pedido_id = $stmt->insert_id;
        $stmt->close();

        // Inserir os itens do pedido e atualizar estoque
foreach ($_SESSION['carrinho'] as $produto_id => $quantidade) {
    // Inserir item no pedido
    $stmt = $conn->prepare("INSERT INTO itens_pedido (pedido_id, produto_id, quantidade) VALUES (?, ?, ?)");
    $stmt->bind_param("iii", $pedido_id, $produto_id, $quantidade);
    $stmt->execute();
    $stmt->close();

    // Atualizar estoque: diminuir quantidade
    $stmt = $conn->prepare("UPDATE estoque SET quantidade = quantidade - ? WHERE produto_id = ? AND quantidade >= ?");
    $stmt->bind_param("iii", $quantidade, $produto_id, $quantidade);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        // Se não atualizou (estoque insuficiente), você pode tratar o erro (rollback ou aviso)
        // Aqui, só um exemplo simples:
        die("Erro: Estoque insuficiente para o produto ID $produto_id.");
    }
    $stmt->close();
}

        try {
            $mail = new PHPMailer(true);

            //Configurações do servidor SMTP
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'grazielimartins5@gmail.com'; // Seu e-mail SMTP
    $mail->Password   = 'qwtyrhbvrpcktupq';           // Sua senha ou app password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // ssl
    $mail->Port       = 465;

    // Remetente e destinatário
    $mail->setFrom($email, $nome);
    $mail->addAddress('grazielimartins5@gmail.com'); // Destino da mensagem
    $mail->addReplyTo($email, $nome);

            $mail->isHTML(true);
            $mail->Subject = "Pedido Finalizado - Cliente: $nome";

            $corpoEmail = "<h2>Novo pedido de $nome</h2>";
            $corpoEmail .= "<p><strong>Nome:</strong> $nome</p>";
            $corpoEmail .= "<p><strong>Email:</strong> $email</p>";
            $corpoEmail .= "<p><strong>CEP:</strong> $cep</p>";
            $corpoEmail .= "<p><strong>Endereço:</strong> $endereco</p>";
            $corpoEmail .= "<p><strong>Desconto:</strong> R$" . number_format($desconto, 2, ',', '.') . "</p>";
            $corpoEmail .= "<p><strong>Frete:</strong> R$" . number_format($frete, 2, ',', '.') . "</p>";
            $corpoEmail .= "<p><strong>Total:</strong> R$" . number_format($total, 2, ',', '.') . "</p>";
            $corpoEmail .= "<h4>Produtos:</h4><ul>";

            foreach ($produtos_carrinho as $item) {
                $corpoEmail .= "<li>" . htmlspecialchars($item['nome']) . " (" . htmlspecialchars($item['variacao']) . ") - "
                    . $item['quantidade'] . "x R$" . number_format($item['preco'], 2, ',', '.') . "</li>";
            }
            $corpoEmail .= "</ul>";

            $mail->Body = $corpoEmail;

            $mail->send();
        } catch (Exception $e) {
          
        }

        unset($_SESSION['carrinho']);
        unset($_SESSION['cupom_selecionado']);

        header("Location: obrigado.php?pedido_id=$pedido_id");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8" />
    <title>Yummy Burguer</title>
     <link href="https://fonts.googleapis.com/css2?family=Montserrat&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/css.css">
    <link rel="shortcut icon" href="assets/favicon.ico" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
<div class="container mt-4">
    <div class="finalizar-compra">

        <div class="img-finalizar-compra">
            <img src="assets/logo-text.webp" alt="">
        </div>
        <div class="container-finalizar-compra">
    <h2>Finalizar Compra</h2>

    <?php if (!empty($erro)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>

    <h4>Produtos no Carrinho</h4>
    <div class="produtos-carrinho">
 <ul>
    <?php foreach ($produtos_carrinho as $item): ?>
        <li>
            <img src="data:image/jpeg;base64,<?= base64_encode($item['imagem']) ?>" alt="<?= htmlspecialchars($item['nome']) ?>">

           <br><b><?= htmlspecialchars($item['nome']) ?></b>
            <br>
            (<?= htmlspecialchars($item['variacao']) ?>) - R$<?= number_format($item['preco'], 2, ',', '.') ?> 
            x <?= $item['quantidade'] ?> = R$<?= number_format($item['totalItem'], 2, ',', '.') ?>
        </li>
    <?php endforeach; ?>
</ul></div>


    <p><strong>Subtotal:</strong> R$<?= number_format($subtotal, 2, ',', '.') ?></p>

    <?php if ($desconto > 0): ?>
        <p><strong>Desconto (Cupom <?= htmlspecialchars($cupom_codigo) ?>):</strong> -R$<?= number_format($desconto, 2, ',', '.') ?></p>
        <p><strong>Validade do cupom:</strong> <?= date('d/m/Y', strtotime($cupom_validade)) ?></p>
    <?php endif; ?>

    <p><strong><?= $freteTexto ?></strong></p>
    <p id="total"><strong>Total a pagar:</strong> <b>R$<?= number_format($total, 2, ',', '.') ?></b></p>

    <hr>

   <form method="POST" class="mt-3">

        <h4>Informações de Entrega</h4>

        <div class="mb-3">
            <label for="nome" class="form-label">Nome Completo</label>
            <input type="text" name="nome" id="nome" class="form-control" required value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>">
        </div>

        <div class="mb-3">
            <label for="email" class="form-label">E-mail</label>
          <input type="email" name="email" id="email" class="form-control" required value="<?= htmlspecialchars($_SESSION['user_email'] ?? '') ?>">

        </div>

        <div class="mb-3">
            <label for="cep" class="form-label">CEP</label>
            <div class="input-group">
                <input type="text" name="cep" id="cep" class="form-control" required maxlength="9" placeholder="00000-000" value="<?= htmlspecialchars($_POST['cep'] ?? '') ?>">
                <button type="button" id="btnBuscarCep" class="btn btn-outline-primary">Buscar</button>
            </div>
        </div>

        <div class="mb-3">
            <label for="endereco" class="form-label">Endereço</label>
            <textarea name="endereco" id="endereco" class="form-control" required><?= htmlspecialchars($_POST['endereco'] ?? '') ?></textarea>
        </div>

      <button type="submit" name="finalizar" class="btn btn-primary">Finalizar Compra</button>

    </form>

    <a href="index.php" class="btn btn-secondary mt-3">Voltar ao Carrinho</a>
</div></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('btnBuscarCep').addEventListener('click', function() {
    const cepInput = document.getElementById('cep');
    const cep = cepInput.value.replace(/\D/g, '');

    if (cep.length !== 8) {
        alert('CEP inválido. Digite um CEP com 8 dígitos.');
        return;
    }

    fetch(`https://viacep.com.br/ws/${cep}/json/`)
        .then(response => response.json())
        .then(data => {
            if (data.erro) {
                alert('CEP não encontrado.');
                document.getElementById('endereco').value = '';
                return;
            }
            const enderecoCompleto = `${data.logradouro}, ${data.bairro}, ${data.localidade} - ${data.uf}`;
            document.getElementById('endereco').value = enderecoCompleto;
        })
        .catch(() => {
            alert('Erro ao consultar CEP. Tente novamente.');
            document.getElementById('endereco').value = '';
        });
});
</script>
<div class="hero-div-container">
    <img src="assets/yummy-back (2).webp" />
</div>

<footer class="bg-dark text-white py-4">
    <div class="container text-center">
        <p class="mb-2">Desenvolvido por <strong>Grazieli de Freitas Martins</strong></p>
       <div style="display:flex; justify-content:center; gap:10px;">
        <a href="https://www.linkedin.com/in/grazieli-freitas-martins-61329b258/" target="_blank"><i class="bi bi-linkedin"></i></a>
        <a href="https://www.behance.net/grazieli_martins" target="_blank"><i class="bi bi-behance"></i></a>
        <a href="https://github.com/GrazieliMart" target="_blank"><i class="bi bi-github"></i></a>
         <a href="https://grazieli-martins.netlify.app/" target="_blank"><i class="bi bi-link"></i></a>

    </div></div>
</footer>
</body>
</html>
