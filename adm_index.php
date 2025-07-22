<?php
session_start();
include 'conexao.php';
require_once 'atualizar_status.php';

$mensagem = $_SESSION['mensagem'] ?? "";
unset($_SESSION['mensagem']);

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["acao"])) {
  
if ($_POST["acao"] == "cadastrar") {
    $nome = $_POST["nome"];
    $preco = floatval($_POST["preco"]);
    $variacao = $_POST["variacao"];
    $estoque = intval($_POST["estoque"]);
    $tipo = $_POST["tipo"];

    $imagem_blob = null;
    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
        $imagem_blob = file_get_contents($_FILES['imagem']['tmp_name']);
    }

  
    $stmt = $conn->prepare("INSERT INTO produtos (nome, preco, variacao, tipo, imagem) VALUES (?, ?, ?, ?, ?)");
    $null = NULL;
    $stmt->bind_param("sdssb", $nome, $preco, $variacao, $tipo, $null);
    $stmt->send_long_data(4, $imagem_blob);

    if ($stmt->execute()) {
        $produto_id = $stmt->insert_id;

      
        $stmt_estoque = $conn->prepare("INSERT INTO estoque (produto_id, quantidade) VALUES (?, ?)");
        $stmt_estoque->bind_param("ii", $produto_id, $estoque);
        $stmt_estoque->execute();

        $_SESSION['mensagem'] = "Produto cadastrado com sucesso!";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $_SESSION['mensagem'] = "Erro ao cadastrar produto.";
    }
}


if ($_POST["acao"] == "editar") {
    $id = intval($_POST["id"]);
    $nome = $_POST["nome"];
    $preco = floatval($_POST["preco"]);
    $variacao = $_POST["variacao"];
    $estoque = intval($_POST["estoque"]);
    $tipo = $_POST["tipo"];


    $stmt = $conn->prepare("UPDATE produtos SET nome=?, preco=?, variacao=?, tipo=? WHERE id=?");
    $stmt->bind_param("sdssi", $nome, $preco, $variacao, $tipo, $id);
    $stmt->execute();

   
    $stmt_estoque = $conn->prepare("UPDATE estoque SET quantidade=? WHERE produto_id=?");
    $stmt_estoque->bind_param("ii", $estoque, $id);
    $stmt_estoque->execute();

    $_SESSION['mensagem'] = "Produto atualizado com sucesso!";
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

if ($_POST["acao"] == "excluir") {
    $id = intval($_POST["id"]);

    $conn->query("DELETE FROM estoque WHERE produto_id = $id");
    $conn->query("DELETE FROM produtos WHERE id = $id");

    $_SESSION['mensagem'] = "Produto excluído com sucesso!";
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}


if ($_POST["acao"] == "cadastrar_cupom") {
    $codigo = strtoupper(trim($_POST["codigo"]));
    $nome_cupom = trim($_POST["nome_cupom"]);
    $valor = floatval($_POST["valor"]);
    $validade = $_POST["validade"];
    $minimo = floatval($_POST["minimo"]);

    $stmt = $conn->prepare("INSERT INTO cupons (codigo, nome_cupom, valor, validade, minimo) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssdss", $codigo, $nome_cupom, $valor, $validade, $minimo);

    if ($stmt->execute()) {
        $_SESSION['mensagem'] = "Cupom cadastrado com sucesso!";
    } else {
        $_SESSION['mensagem'] = "Erro ao cadastrar cupom: " . $stmt->error;
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}


if ($_POST["acao"] == "atualizar_cupom") {
    $id = intval($_POST["id"]);
    $codigo = strtoupper(trim($_POST["codigo"]));
    $nome_cupom = trim($_POST["nome_cupom"]);
    $valor = floatval($_POST["valor"]);
    $validade = $_POST["validade"];
    $minimo = floatval($_POST["minimo"]);

    $stmt = $conn->prepare("UPDATE cupons SET codigo = ?, nome_cupom = ?, valor = ?, validade = ?, minimo = ? WHERE id = ?");
    $stmt->bind_param("ssdssi", $codigo, $nome_cupom, $valor, $validade, $minimo, $id);

    if ($stmt->execute()) {
        $_SESSION['mensagem'] = "Cupom atualizado com sucesso!";
    } else {
        $_SESSION['mensagem'] = "Erro ao atualizar cupom: " . $stmt->error;
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}


if ($_POST["acao"] == "excluir_cupom") {
    $id_cupom = intval($_POST["id_cupom"]);
    $stmt = $conn->prepare("DELETE FROM cupons WHERE id = ?");
    $stmt->bind_param("i", $id_cupom);
    $stmt->execute();

    $_SESSION['mensagem'] = "Cupom excluído!";
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
 if ($_POST["acao"] == "editar_estoque") {
        $produto_id = intval($_POST["produto_id"]);
        $quantidade = intval($_POST["quantidade"]);

       
        $stmt = $conn->prepare("INSERT INTO estoque (produto_id, quantidade) VALUES (?, ?)
                                ON DUPLICATE KEY UPDATE quantidade = VALUES(quantidade)");
        $stmt->bind_param("ii", $produto_id, $quantidade);

        if ($stmt->execute()) {
            $_SESSION['mensagem'] = "Estoque atualizado com sucesso!";
        } else {
            $_SESSION['mensagem'] = "Erro ao atualizar estoque.";
        }

        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }


if ($_POST["acao"] == "atualizar_estoque_em_massa") {
    $estoques = $_POST["estoques"] ?? [];

    foreach ($estoques as $produto_id => $quantidade) {
        $stmt = $conn->prepare("UPDATE estoque SET quantidade=? WHERE produto_id=?");
        $stmt->bind_param("ii", $quantidade, $produto_id);
        $stmt->execute();
    }

    $_SESSION['mensagem'] = "Estoques atualizados com sucesso!";
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

     if ($_POST["acao"] == "atualizar_estoque_em_massa") {
        $estoques = $_POST["estoques"] ?? [];

        foreach ($estoques as $produto_id => $quantidade) {
            $stmt = $conn->prepare("UPDATE estoque SET quantidade=? WHERE produto_id=?");
            $stmt->bind_param("ii", $quantidade, $produto_id);
            $stmt->execute();
        }

        $_SESSION['mensagem'] = "Estoques atualizados com sucesso!";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
   

if ($_POST["acao"] == "atualizar_status_pedido") {
    $pedido_id = intval($_POST["pedido_id"]);
    $novo_status = $_POST["novo_status"];


    $stmt = $conn->prepare("UPDATE pedidos SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $novo_status, $pedido_id);
    $stmt->execute();


    enviarEmailAtualizacaoStatus($conn, $pedido_id, $novo_status);

    $_SESSION['mensagem'] = "Status do pedido #$pedido_id atualizado e e-mail enviado.";
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}




}
$produtos = $conn->query("SELECT p.id, p.nome, p.preco, p.variacao, p.tipo, e.quantidade FROM produtos p LEFT JOIN estoque e ON p.id = e.produto_id");
$pedidos = $conn->query("SELECT * FROM pedidos ORDER BY data DESC");

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8" />
    <title>Yummy Burguer</title>
    <link rel="stylesheet" href="css/css.css">
     <link href="https://fonts.googleapis.com/css2?family=Montserrat&display=swap" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="shortcut icon" href="assets/favicon.ico" type="image/x-icon">
   
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css"> <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<style>
    .pedido-bloco {
    border: 2px solid #007bff; 
    border-radius: 8px;
    margin-bottom: 20px;
    padding: 12px 15px;
    background: #f9faff;
}

.pedido-header tr {
    background-color: #d0e2ff; 
}

.itens-pedido {
    margin-top: 10px;
    padding-left: 20px;
}

.itens-pedido ul {
  
    list-style-type: none;
    padding-left: 0px;
}

</style>
</head>
<body>

<nav class="navbar">
    <div class="container">
       <img src="assets/logo-text.webp">
   
        <div class="align-nav" id="navbarSupportedContent">
            <span class="me-3">Bem-vindo, Administrador</span>

            <div class="align-nav">
                <a href="index.php" class="btn btn-outline-primary ms-2"><i class="bi bi-fork-knife"></i> Área do Usuário   </a>

                <a href="logout.php" class="btn btn-danger">Sair <i class="bi bi-arrow-bar-right"></i></a>
               
            </div>
</a>
        </div>
    </div>
</nav>
<div class="hero-div-container">
<img src="assets/yummy-back (2).webp" alt="Yummy Burguer" class="img-fluid" />
</div>

<div class="container mt-4">
    <div class="align-adm">



    <?php if ($mensagem): ?>
        <div class="alert alert-success"><?= $mensagem ?></div>
    <?php endif; ?>

<h2>Pedidos</h2>

<?php while ($pedido = $pedidos->fetch_assoc()): ?>
    <div class="pedido-bloco">
        <table class="table table-bordered pedido-header">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Data</th>
                    <th>Total</th>
                    <th>Frete   </th>
                    <th>CEP</th>
                    <th>Endereço</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?= $pedido['id'] ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($pedido['data'])) ?></td>
                    <td><?= number_format($pedido['total'], 2, ',', '.') ?></td>
                    <td><?= number_format($pedido['frete'], 2, ',', '.') ?></td>
                    <td><?= htmlspecialchars($pedido['cep']) ?></td>
                    <td><?= htmlspecialchars($pedido['endereco']) ?></td>
                    <td>
                        <form method="POST" style="display:inline-block;" id="form-status">
                            <input type="hidden" name="acao" value="atualizar_status_pedido">
                            <input type="hidden" name="pedido_id" value="<?= $pedido['id'] ?>">
                            <select name="novo_status" class="form-select form-select-sm" onchange="this.form.submit()">
                                <?php
                                $status_options = [
                                    'pendente' => 'Pendente (Aguardando confirmação)',
                                    'em preparo' => 'Em preparo (Loja está preparando seu pedido)',
                                    'em rota' => 'Em rota (Pedido em rota para seu endereço)',
                                    'entregue' => 'Entregue (Pedido entregue)',
                                    'cancelado' => 'Cancelado'
                                ];
                                foreach ($status_options as $valor => $label):
                                ?>
                                    <option value="<?= $valor ?>" <?= ($pedido['status'] === $valor) ? 'selected' : '' ?>>
                                        <?= $label ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="itens-pedido">
            <strong>Itens do pedido:</strong>
            <ul>
                <?php
                $stmt_itens = $conn->prepare("
                    SELECT p.nome, ip.quantidade, p.variacao 
                    FROM itens_pedido ip 
                    JOIN produtos p ON ip.produto_id = p.id 
                    WHERE ip.pedido_id = ?
                ");
                $stmt_itens->bind_param("i", $pedido['id']);
                $stmt_itens->execute();
                $result_itens = $stmt_itens->get_result();

                while ($item = $result_itens->fetch_assoc()):
                ?>
                    <li>
                        <?= htmlspecialchars($item['nome']) ?>
                        (<?= htmlspecialchars($item['variacao']) ?>) -
                        Quantidade: <?= intval($item['quantidade']) ?>
                    </li>
                <?php endwhile; ?>

                <?php $stmt_itens->close(); ?>
            </ul>
        </div>
    </div>
<?php endwhile; ?>


<div class="align-adm-container">

   

    <form method="POST" enctype="multipart/form-data"> 
        <h2><i class="bi bi-egg-fried"></i> <br><br>Cadastro de Produto</h2>
        <input type="hidden" name="acao" value="cadastrar" >
        <div class="mb-2">
            <label>Nome:</label>
            <input type="text" name="nome" class="form-control" required>
        </div>
        <div class="mb-2">
            <label>Preço:</label>
            <input type="number" step="0.01" name="preco" class="form-control" required>
        </div>
        <div class="mb-2">
            <label>Variação:</label>
            <input type="text" name="variacao" class="form-control">
        </div>
        <div class="form-group">
  <label for="tipo">Tipo do Produto:</label>
  <input type="text" class="form-control" id="tipo" name="tipo" value="<?= $produto['tipo'] ?? '' ?>" required>
</div>

        <div class="mb-2">
            <label>Estoque:</label>
            <input type="number" name="estoque" class="form-control" required>
        </div>
         <div class="mb-2">
        <label>Imagem do Produto:</label>
      <input type="file" name="imagem" accept="image/*" class="form-control">
    </div>
        <button type="submit" class="btn btn-success">Cadastrar</button>
    </form>

    


<form method="POST" class="mb-4">    
    <h2><i class="bi bi-cash-coin"></i> <br><br>Cadastro de Cupons</h2>
    <input type="hidden" name="acao" value="cadastrar_cupom">
    <div class="mb-2">
        <label>Código do Cupom:</label>
        <input type="text" name="codigo" class="form-control" required>
    </div>
    <div class="mb-2">
        <label>Nome do Cupom:</label>
        <input type="text" name="nome_cupom" class="form-control" required>
    </div>
    <div class="mb-2">
        <label>Valor de Desconto (R$):</label>
        <input type="number" step="0.01" name="valor" class="form-control" required>
    </div>
    <div class="mb-2">
        <label>Validade:</label>
        <input type="date" name="validade" class="form-control" required>
    </div>
    <div class="mb-2">
        <label>Valor mínimo do subtotal para usar o cupom (R$):</label>
        <input type="number" step="0.01" name="minimo" class="form-control" value="0" required>
    </div>
    <button type="submit" class="btn btn-success">Cadastrar Cupom</button>
</form>



</div>
<br>
<h3>Cupons Cadastrados</h3>
 <div class="table-responsive">
<table class="table">
    <thead>
        <tr>
            <th>Código</th>
            <th>Nome</th>
            <th>Desconto (R$)</th>
            <th>Validade</th>
            <th>Min. Subtotal</th>
            <th>Ações</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        $cupons = $conn->query("SELECT * FROM cupons ORDER BY validade DESC");
        while($c = $cupons->fetch_assoc()):
        ?>
        <tr>
            <td><?= htmlspecialchars($c['codigo']) ?></td>
            <td><?= htmlspecialchars($c['nome_cupom']) ?></td>
            <td>R$ <?= number_format($c['valor'], 2, ',', '.') ?></td>
            <td><?= $c['validade'] ?></td>
            <td>R$ <?= number_format($c['minimo'], 2, ',', '.') ?></td>
           <td>
    <button type="button" class="btn btn-sm btn-warning"
        onclick="editarCupom(
            '<?= $c['id'] ?>',
            '<?= htmlspecialchars($c['codigo'], ENT_QUOTES) ?>',
            '<?= htmlspecialchars($c['nome_cupom'], ENT_QUOTES) ?>',
            '<?= $c['valor'] ?>',
            '<?= $c['validade'] ?>',
            '<?= $c['minimo'] ?>'
        )">Editar</button>

    <button type="button" class="btn btn-sm btn-danger" onclick="confirmarExclusaoCupom(<?= $c['id'] ?>)">Excluir</button>
</td>

        </tr>
        <?php endwhile; ?>
    </tbody>
</table>
        </div>

<!-- Filtro por tipo -->
 <br>
 <h3>Produtos</h3>
<div class="mb-3">
    <label for="filtro-tipo" class="form-label"><b>Produtos/Estoque</b> <br>Filtrar por tipo:</label>
    <select id="filtro-tipo" class="form-select" onchange="filtrarTipo()">
        <option value="">Todos</option>
        <?php
        $tipos = [];
        $produtos->data_seek(0);
        while ($p = $produtos->fetch_assoc()) {
            $tipos[] = htmlspecialchars($p['tipo']);
        }
        $tipos = array_unique($tipos);
        sort($tipos);
        foreach ($tipos as $tipo) {
            echo "<option value=\"$tipo\">$tipo</option>";
        }
        $produtos->data_seek(0);
        ?>
    </select>
</div>

<!-- Cards -->
<div class="row" id="lista-produtos">
    <?php while ($p = $produtos->fetch_assoc()): ?>
        <div class="col-6 col-md-2 mb-4 card-produto-adm" data-tipo="<?= htmlspecialchars($p['tipo']) ?>">
            <div class="p-3 rounded h-100">
                <div class="d-inline align-items-center mb-2 gap-2">
                    <img src="imagem_produto.php?id=<?= $p['id'] ?>" alt="Imagem do produto"
                        style="width:100%; height:150px; object-fit:cover; border:1px solid #ddd; border-radius:4px;">
                    <div>
                        <br>
                        <strong><?= htmlspecialchars($p['nome']) ?></strong><br>
                        <small>[<?= htmlspecialchars($p['variacao']) ?>] - R$<?= number_format($p['preco'], 2, ',', '.') ?></small><br>
                        <small>Estoque: <?= $p['quantidade'] ?> | Tipo: <?= htmlspecialchars($p['tipo']) ?></small>
                    </div>
                </div>
                <br>
                <div class="d-inline justify-content-between mt-2">
                 <button class="btn btn-sm btn-primary rounded-pill" onclick="editarProduto(
    '<?= $p['id'] ?>',
    '<?= addslashes(htmlspecialchars($p['nome'])) ?>',
    '<?= $p['preco'] ?>',
    '<?= addslashes(htmlspecialchars($p['variacao'])) ?>',
    '<?= $p['quantidade'] ?>',
    '<?= addslashes(htmlspecialchars($p['tipo'])) ?>',
    'imagem_produto.php?id=<?= $p['id'] ?>'
)">
    <i class="bi bi-pencil-square"></i>
</button>

                    <button class="btn btn-sm btn-danger rounded-pill" onclick="confirmarExclusao(<?= $p['id'] ?>)"><i class="bi bi-trash"></i></button>
                </div>
            </div>
        </div>
    <?php endwhile; ?>
</div>
<script>
function filtrarTipo() {
    const tipoSelecionado = document.getElementById("filtro-tipo").value.toLowerCase();
    const produtos = document.querySelectorAll(".card-produto-adm");

    produtos.forEach(produto => {
        const tipo = produto.getAttribute("data-tipo").toLowerCase();
        produto.style.display = (!tipoSelecionado || tipo === tipoSelecionado) ? 'block' : 'none';
    });
}
</script>


 




</div>


<div class="modal fade" id="modalEditarProduto" tabindex="-1" aria-labelledby="modalEditarProdutoLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="acao" value="editar">
        <input type="hidden" name="id" id="editar_id">
        <div class="modal-header">
          <h5 class="modal-title" id="modalEditarProdutoLabel">Editar Produto</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
        </div>
        <div class="modal-body">
          <div class="mb-2">
            <div class="img-modal">
 
  <img id="editar_imagem_preview" src="" alt="Imagem do Produto" style="max-width: 100%; max-height: 200px; border: 1px solid #ddd; border-radius: 4px;">
</div>

            <label for="editar_nome">Nome:</label>
            <input type="text" name="nome" id="editar_nome" class="form-control" required>
          </div>
          <div class="mb-2">
            <label for="editar_preco">Preço:</label>
            <input type="number" step="0.01" name="preco" id="editar_preco" class="form-control" required>
          </div>
          <div class="mb-2">
            <label for="editar_variacao">Variação:</label>
            <input type="text" name="variacao" id="editar_variacao" class="form-control">
          </div>
          <div class="mb-2">
            <label for="editar_tipo">Tipo:</label>
            <input type="text" name="tipo" id="editar_tipo" class="form-control" required>
          </div>
          <div class="mb-2">
            <label for="editar_estoque">Estoque:</label>
            <input type="number" name="estoque" id="editar_estoque" class="form-control" required>
          </div>
          <div class="mb-2">
            <label for="editar_imagem">Imagem do Produto (opcional):</label>
            <input type="file" name="imagem" id="editar_imagem" accept="image/*" class="form-control">
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Salvar Alterações</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        </div>
      </form>
    </div>
  </div>
</div>


        

<div class="modal fade" id="modalExcluirProduto" tabindex="-1" aria-labelledby="modalExcluirProdutoLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <input type="hidden" name="acao" value="excluir">
        <input type="hidden" name="id" id="excluir_id">
        <div class="modal-header">
          <h5 class="modal-title" id="modalExcluirProdutoLabel">Confirmar Exclusão</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
        </div>
        <div class="modal-body">
          Tem certeza que deseja excluir este produto?
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-danger">Sim, Excluir</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modalEdicaoCupom" tabindex="-1" aria-labelledby="modalEdicaoCupomLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEdicaoCupomLabel">Editar Cupom</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="acao" value="atualizar_cupom">
                    <input type="hidden" name="id" id="idModalCupom">

                    <div class="mb-3">
                        <label for="codigoModal" class="form-label">Código do Cupom:</label>
                        <input type="text" class="form-control" name="codigo" id="codigoModal" required>
                    </div>
                    <div class="mb-3">
                        <label for="nomeCupomModal" class="form-label">Nome do Cupom:</label>
                        <input type="text" class="form-control" name="nome_cupom" id="nomeCupomModal" required>
                    </div>
                    <div class="mb-3">
                        <label for="valorModal" class="form-label">Valor de Desconto (R$):</label>
                        <input type="number" step="0.01" class="form-control" name="valor" id="valorModal" required>
                    </div>
                    <div class="mb-3">
                        <label for="validadeModal" class="form-label">Validade:</label>
                        <input type="date" class="form-control" name="validade" id="validadeModal" required>
                    </div>
                    <div class="mb-3">
                        <label for="minimoModal" class="form-label">Valor mínimo do subtotal:</label>
                        <input type="number" step="0.01" class="form-control" name="minimo" id="minimoModal" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Atualizar Cupom</button>
                </div>
            </form>
        </div>
    </div>
</div>


<div class="modal fade" id="modalExcluirCupom" tabindex="-1" aria-labelledby="modalExcluirCupomLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <input type="hidden" name="acao" value="excluir_cupom">
        <input type="hidden" name="id_cupom" id="excluir_id_cupom">
        <div class="modal-header">
          <h5 class="modal-title" id="modalExcluirCupomLabel">Confirmar Exclusão de Cupom</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
        </div>
        <div class="modal-body">
          Tem certeza que deseja excluir este cupom?
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-danger">Sim, Excluir</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modalEstoque" tabindex="-1" aria-labelledby="modalEstoqueLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEstoqueLabel">Editar Estoque dos Produtos</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="acao" value="atualizar_estoque_em_massa">
                    <div class="row">
                        <?php
                        $produtos->data_seek(0);
                        while ($p = $produtos->fetch_assoc()):
                        ?>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?= htmlspecialchars($p['nome']) ?> (<?= htmlspecialchars($p['variacao']) ?>)</label>
                                <input type="number" class="form-control" name="estoques[<?= $p['id'] ?>]" value="<?= $p['quantidade'] ?>">
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Salvar Estoques</button>
                </div>
            </form>
        </div>
    </div>
</div>


<div class="modal fade" id="modalEstoque" tabindex="-1" aria-labelledby="modalEstoqueLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEstoqueLabel">Editar Estoque dos Produtos</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="acao" value="atualizar_estoque_em_massa">
                    <div class="row">
                        <?php
                        $produtos->data_seek(0);
                        while ($p = $produtos->fetch_assoc()):
                        ?>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?= htmlspecialchars($p['nome']) ?> (<?= htmlspecialchars($p['variacao']) ?>)</label>
                                <input type="number" class="form-control" name="estoques[<?= $p['id'] ?>]" value="<?= $p['quantidade'] ?>">
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Salvar Estoques</button>
                </div>
            </form>
        </div>
    </div>
</div>
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
<script>
    function editarCupom(id, codigo, nome_cupom, valor, validade, minimo) {
    document.getElementById('idModalCupom').value = id;
    document.getElementById('codigoModal').value = codigo;
    document.getElementById('nomeCupomModal').value = nome_cupom;
    document.getElementById('valorModal').value = valor;
    document.getElementById('validadeModal').value = validade;
    document.getElementById('minimoModal').value = minimo;

    var modal = new bootstrap.Modal(document.getElementById('modalEdicaoCupom'));
    modal.show();
}

function confirmarExclusaoCupom(id) {
    document.getElementById('excluir_id_cupom').value = id;
    var modal = new bootstrap.Modal(document.getElementById('modalExcluirCupom'));
    modal.show();
}
function editarProduto(id, nome, preco, variacao, quantidade, tipo, imagemUrl) {
    document.getElementById('editar_id').value = id;
    document.getElementById('editar_nome').value = nome;
    document.getElementById('editar_preco').value = preco;
    document.getElementById('editar_variacao').value = variacao;
    document.getElementById('editar_estoque').value = quantidade;
    document.getElementById('editar_tipo').value = tipo;


    const imgPreview = document.getElementById('editar_imagem_preview');
    imgPreview.src = imagemUrl;
    imgPreview.style.display = 'block';

    new bootstrap.Modal(document.getElementById('modalEditarProduto')).show();
}


function confirmarExclusao(id) {
    document.getElementById('excluir_id').value = id;
    var modal = new bootstrap.Modal(document.getElementById('modalExcluirProduto'));
    modal.show();
}
</script>


</body>
</html>
