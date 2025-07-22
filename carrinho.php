<?php
include 'conexao.php';

$mensagem = $_SESSION['mensagem'] ?? '';
unset($_SESSION['mensagem']);
?>

<style>
  .carrinho-lista {
    display: flex;
    flex-wrap: wrap; 
    gap: 15px; 
    padding-left: 0;
    list-style: none;
  }
  .carrinho-item {
    flex: 1 1 200px; 
    max-width: 100%$_COOKIE;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 10px;
    box-sizing: border-box;
    background: #fff;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
  }
  .carrinho-item img {
    width: 100%;
    height: 120px;
    object-fit: cover;
    border-radius: 5px;
    margin-bottom: 10px;
  }
  .btn-group {
    display: flex;
    gap: 5px;
    flex-wrap: wrap;
    margin-top: 8px;
  }
  .btn-group form,
  .btn-group button {
    margin: 0 !important;
  }
</style>

<?php
if ($mensagem) {
    echo "<div class='alert alert-warning alert-dismissible fade show' role='alert'>";
    echo htmlspecialchars($mensagem);
    echo "<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>";
    echo "</div>";
}

if (!isset($_SESSION['carrinho']) || count($_SESSION['carrinho']) === 0) {
    echo "<p>Sua sacola está vazia.</p>";
    return;
}

$subtotal = 0;


foreach ($_SESSION['carrinho'] as $produto_id => $quantidade) {
    $stmt = $conn->prepare("SELECT nome, preco, variacao, imagem FROM produtos WHERE id = ?");
    $stmt->bind_param("i", $produto_id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $produto = $resultado->fetch_assoc();

    if (!$produto) continue;

    $totalItem = $produto['preco'] * $quantidade;
    $subtotal += $totalItem;

    echo '<li class="carrinho-item">';

    echo '<img src="imagem_produto.php?id=' . $produto_id . '" alt="' . htmlspecialchars($produto['nome']) . '">';
    
    echo '<div>';
    echo "<strong>" . htmlspecialchars($produto['nome']) . "</strong><br>";
    echo htmlspecialchars($produto['variacao']) . "<br><br>";
    echo "R$" . number_format($produto['preco'], 2, ',', '.') . " x $quantidade = <strong>R$" . number_format($totalItem, 2, ',', '.') . "</strong>";
    echo '</div>';

    echo '<div class="btn-group">';
    // Botão remover
    echo '<form method="POST" style="display:inline-block;">';
    echo '<input type="hidden" name="acao" value="remover_do_carrinho">';
    echo '<input type="hidden" name="produto_id_remover" value="' . $produto_id . '">';
    echo '<button type="submit" class="btn btn-danger btn-sm"><i class="bi bi-x-circle"></i> Remover</button>';
    echo '</form>';

    // Botão diminuir
    echo '<form method="POST" style="display:inline-block;">';
    echo '<input type="hidden" name="acao" value="diminuir_quantidade">';
    echo '<input type="hidden" name="produto_id" value="' . $produto_id . '">';
    echo '<button type="submit" class="btn btn-outline-secondary btn-sm">-</button>';
    echo '</form>';

    // Quantidade visual
    echo '<button class="btn btn-light btn-sm" disabled>' . $quantidade . '</button>';

    // Botão aumentar
    echo '<form method="POST" style="display:inline-block;">';
    echo '<input type="hidden" name="acao" value="aumentar_quantidade">';
    echo '<input type="hidden" name="produto_id" value="' . $produto_id . '">';
    echo '<button type="submit" class="btn btn-outline-secondary btn-sm">+</button>';
    echo '</form>';
    echo '</div>';

    echo '</li>';
}

echo '</ul>';


if ($subtotal >= 200) {
    $frete = 0;
    $freteTexto = "Frete grátis";
} elseif ($subtotal >= 52 && $subtotal <= 166.59) {
    $frete = 15;
    $freteTexto = "Frete: R$15,00";
} else {
    $frete = 20;
    $freteTexto = "Frete: R$20,00";
}

$total = $subtotal + $frete;

echo "<hr>";
echo "<p><strong>Subtotal:</strong> R$" . number_format($subtotal, 2, ',', '.') . "</p>";
echo "<p><strong>$freteTexto</strong></p>";
echo "<p><strong>Total com frete:</strong> R$" . number_format($total, 2, ',', '.') . "</p>";
echo "<hr>";


echo "<form method='POST' action='finalizar_compra.php'>";
echo "<input type='hidden' name='acao' value='finalizar'>";

echo "<h5>Cupons disponíveis</h5>";
$hoje = date('Y-m-d');
$sqlCupons = "SELECT id, codigo, nome_cupom, valor, validade, minimo FROM cupons WHERE validade >= ? ORDER BY nome_cupom";
$stmtCupons = $conn->prepare($sqlCupons);
$stmtCupons->bind_param("s", $hoje);
$stmtCupons->execute();
$resultCupons = $stmtCupons->get_result();

$algumDisponivel = false;
echo '<div class="mb-3">';
while ($cupom = $resultCupons->fetch_assoc()) {
    $disponivel = ($subtotal >= $cupom['minimo']);
    if ($disponivel) {
        $algumDisponivel = true;
    }

    echo '<div class="form-check">';
    echo '<input class="form-check-input" type="radio" name="cupom_id" id="cupom_' . $cupom['id'] . '" value="' . $cupom['id'] . '" ' . ($disponivel ? '' : 'disabled') . '>';
    echo '<label class="form-check-label" for="cupom_' . $cupom['id'] . '">';
    echo '<strong>' . htmlspecialchars($cupom['nome_cupom']) . '</strong><br>';
    echo "Desconto: R$" . number_format($cupom['valor'], 2, ',', '.') . "<br>";
    echo "Válido até: " . date('d/m/Y', strtotime($cupom['validade'])) . "<br>";
    echo "Mínimo: R$" . number_format($cupom['minimo'], 2, ',', '.');
    if (!$disponivel) {
        echo " <small class='text-danger'>(Subtotal insuficiente)</small>";
    }
    echo '</label>';
    echo '</div>';
}
echo '</div>';

if (!$algumDisponivel) {
    echo "<small>Nenhum cupom disponível para seu subtotal atual.</small><br>";
}

echo "<div class='align-button'><button type='submit' class='btn btn-primary'>Finalizar Compra</button></div>";
echo "</form>";
?>
