<?php
session_start();
include 'conexao.php';

function obterEstoqueAtual($conn, int $produto_id): int {
    $stmt = $conn->prepare("SELECT quantidade FROM estoque WHERE produto_id = ?");
    $stmt->bind_param("i", $produto_id);
    $stmt->execute();
    $stmt->bind_result($estoque);
    $stmt->fetch();
    $stmt->close();
    return $estoque ?? 0;
}

function atualizarEstoque($conn, int $produto_id, int $quantidade): bool {
    $stmt = $conn->prepare("UPDATE estoque SET quantidade = ? WHERE produto_id = ?");
    $stmt->bind_param("ii", $quantidade, $produto_id);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

function redirecionar(string $url): void {
    header("Location: $url");
    exit;
}

// Login via email apenas ilustrativo
if (!isset($_SESSION['user_email'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['user_email'])) {
        $email = filter_var(trim($_POST['user_email']), FILTER_VALIDATE_EMAIL);
        if ($email) {
            $_SESSION['user_email'] = $email;
            redirecionar($_SERVER['PHP_SELF']);
        } else {
            $error_email = "Por favor, insira um email válido.";
        }
    } else {
        ?>
        <!DOCTYPE html>
        <html lang="pt-br">
        <head>
            <meta charset="UTF-8" />
            <title>Bem vindo(a) ao Yummy Burguer!</title>
            
            <link rel="stylesheet" href="css/css.css">
            <link rel="shortcut icon" href="assets/favicon.ico" type="image/x-icon">
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
     
        </head>

        <body>
          <div class="login-container">
    <div class="container mt-5">
        <h2>Bem vindo(a) ao <br> <b>Yummy Burguer!</b></h2>
        <img src="assets/logo-fundo-vermelho.webp">
        <?php if (!empty($error_email)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error_email) ?></div>
        <?php endif; ?>
        <form method="POST" novalidate>
            <div class="mb-3">
                <br>
                <label for="user_email" class="form-label">Antes de acessar, informe seu email para facilitar o atendimento. <br> Bora lá!</label>
                <input type="email" name="user_email" id="user_email" class="form-control" required />
            </div>
            <button type="submit" class="btn btn-primary">Entrar</button>
        </form>
    </div>
</div>

       
        <?php
        exit;
    }
}

if (!isset($_SESSION['carrinho'])) {
    $_SESSION['carrinho'] = [];
}

$mensagem = $_SESSION['mensagem'] ?? '';
unset($_SESSION['mensagem']);

if ($_SERVER["REQUEST_METHOD"] === "POST" && !empty($_POST["acao"])) {
    $acao = $_POST["acao"];

    switch ($acao) {
        case "adicionar_ao_carrinho":
            $produto_id = filter_var($_POST["produto_id"] ?? 0, FILTER_VALIDATE_INT);
            $quantidade_solicitada = filter_var($_POST["quantidade"] ?? 0, FILTER_VALIDATE_INT);

            if ($produto_id === false || $quantidade_solicitada === false || $quantidade_solicitada < 1) {
                $_SESSION['mensagem'] = "Dados inválidos para adicionar ao carrinho.";
                break;
            }

            $estoque_atual = obterEstoqueAtual($conn, $produto_id);

            $_SESSION['carrinho'][$produto_id] = $_SESSION['carrinho'][$produto_id] ?? 0;
            $quantidade_total_carrinho = $_SESSION['carrinho'][$produto_id] + $quantidade_solicitada;

            if ($quantidade_total_carrinho > $estoque_atual) {
                $_SESSION['mensagem'] = "Quantidade do estoque atingida. Máximo disponível: $estoque_atual.";
            } else {
                $_SESSION['carrinho'][$produto_id] = $quantidade_total_carrinho;
                $_SESSION['mensagem'] = "Produto adicionado ao carrinho!";
                  $_SESSION['abrir_carrinho'] = true;
            }
            break;

        case "remover_do_carrinho":
            $produto_id_remover = filter_var($_POST['produto_id_remover'] ?? 0, FILTER_VALIDATE_INT);
            if ($produto_id_remover === false) {
                $_SESSION['mensagem'] = "Produto inválido para remoção.";
                break;
            }

            if (isset($_SESSION['carrinho'][$produto_id_remover])) {
                $estoque_atual = obterEstoqueAtual($conn, $produto_id_remover);
                $quantidade_no_carrinho = $_SESSION['carrinho'][$produto_id_remover];
                $novo_estoque = $estoque_atual + $quantidade_no_carrinho;

                if (atualizarEstoque($conn, $produto_id_remover, $novo_estoque)) {
                    unset($_SESSION['carrinho'][$produto_id_remover]);
                    $_SESSION['mensagem'] = "Produto removido do carrinho.";
                    $_SESSION['abrir_carrinho'] = true; 
                } else {
                    $_SESSION['mensagem'] = "Erro ao atualizar o estoque.";
                }
            } else {
                $_SESSION['mensagem'] = "Produto não encontrado no carrinho.";
            }
            break;

        case "aumentar_quantidade":
            $produto_id = filter_var($_POST["produto_id"] ?? 0, FILTER_VALIDATE_INT);
            if ($produto_id === false) {
                $_SESSION['mensagem'] = "Produto inválido.";
                break;
            }

            $estoque_atual = obterEstoqueAtual($conn, $produto_id);

            if (isset($_SESSION['carrinho'][$produto_id]) && $estoque_atual > 0) {
                $_SESSION['carrinho'][$produto_id]++;
                $novo_estoque = $estoque_atual - 1;

                if (atualizarEstoque($conn, $produto_id, $novo_estoque)) {
                    $_SESSION['mensagem'] = "Quantidade aumentada.";
                    $_SESSION['abrir_carrinho'] = true;

                } else {
                    $_SESSION['mensagem'] = "Erro ao atualizar o estoque.";
                }
            } else {
                $_SESSION['mensagem'] = "Estoque insuficiente para aumentar quantidade.";
            }
            break;

        case "diminuir_quantidade":
            $produto_id = filter_var($_POST["produto_id"] ?? 0, FILTER_VALIDATE_INT);
            if ($produto_id === false) {
                $_SESSION['mensagem'] = "Produto inválido.";
                break;
            }

            if (isset($_SESSION['carrinho'][$produto_id]) && $_SESSION['carrinho'][$produto_id] > 1) {
                $_SESSION['carrinho'][$produto_id]--;

                $estoque_atual = obterEstoqueAtual($conn, $produto_id);
                $novo_estoque = $estoque_atual + 1;

                if (atualizarEstoque($conn, $produto_id, $novo_estoque)) {
                    $_SESSION['mensagem'] = "Quantidade diminuída no carrinho.";
                $_SESSION['abrir_carrinho'] = true;
                } else {
                    $_SESSION['mensagem'] = "Erro ao atualizar o estoque.";
                }
            } else {
                $_SESSION['mensagem'] = "Quantidade mínima atingida no carrinho.";
            }
            break;

        default:
            $_SESSION['mensagem'] = "Ação inválida.";
    }
    redirecionar($_SERVER['PHP_SELF']);
}
$tipos = ['hamburguer', 'combo', 'bebida', 'sobremesa'];

$produtos_por_tipo = [];

foreach ($tipos as $tipo) {
    $stmt = $conn->prepare("
        SELECT p.id, p.nome, p.preco, p.variacao, e.quantidade 
        FROM produtos p
        LEFT JOIN estoque e ON p.id = e.produto_id
        WHERE p.tipo = ?
    ");
    $stmt->bind_param("s", $tipo);
    $stmt->execute();
    $produtos_por_tipo[$tipo] = $stmt->get_result();
}

$user_email = $_SESSION['user_email'] ?? '';
$stmt_pedidos = $conn->prepare("SELECT id, total, frete, cep, endereco, status, data FROM pedidos WHERE email_cliente = ? ORDER BY data DESC");
$stmt_pedidos->bind_param("s", $user_email);
$stmt_pedidos->execute();
$result_pedidos = $stmt_pedidos->get_result();
?>

<!-- O resto do seu HTML abaixo permanece igual -->

<!DOCTYPE html>a
<html lang="pt-br">
<head>
    <meta charset="UTF-8" />
    <title>Yummy Burguer</title>
    <link rel="stylesheet" href="css/css.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="shortcut icon" href="assets/favicon.ico" type="image/x-icon">
   
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<nav class="navbar">
    <div class="container">
       <img src="assets/logo-text.webp">
   
        <div class="align-nav" id="navbarSupportedContent">
            <span class="me-3">Bem-vindo, <?= htmlspecialchars($user_email) ?></span>

            <div class="align-nav">
                <a href="adm_index.php" class="btn btn-outline-primary ms-2">Admin <i class="bi bi-person-fill-gear"></i></a>

                <a href="logout.php" class="btn btn-danger">Sair <i class="bi bi-arrow-bar-right"></i></a>
                <a class="carrinho" onclick="toggleCarrinho()">
                    <i class="bi bi-bag"></i><span class="badge bg-danger"><?= count($_SESSION['carrinho']) ?></span>
                </a>
            </div>
</a>
        </div>
    </div>
</nav>
<div class="hero-div-container">
<img src="assets/yummy-background.webp" alt="Yummy Burguer" class="img-fluid" />
</div>

<div class="container mt-4">

 


    <div class="container-produtos">
<?php
$tipos1 = ['hamburguer', 'combo'];
foreach ($produtos_por_tipo as $tipo => $produtos):
    if (!in_array(strtolower($tipo), $tipos1)) continue;
?>
    <h2><?= htmlspecialchars(ucfirst($tipo)) ?></h2>

    <div id="carousel-<?= htmlspecialchars($tipo) ?>" class="carousel slide" data-bs-ride="carousel">
        <div class="carousel-inner">

            <?php
            $produtos_array = [];
            while ($p = $produtos->fetch_assoc()) {
                $produtos_array[] = $p;
            }
            $chunks = array_chunk($produtos_array, 3);
            ?>

            <?php foreach ($chunks as $index => $chunk): ?>
                <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                    <div class="row">
                        <?php foreach ($chunk as $p): ?>
                            <div class="col-md-4">
                                <div class="card h-100">
                                    <img src="imagem_produto.php?id=<?= $p['id'] ?>" class="card-img-top" alt="<?= htmlspecialchars($p['nome']) ?>">
                                    <div class="card-body d-flex flex-column">
                                        <h5 class="card-title"><?= htmlspecialchars($p['nome']) ?></h5>
                                        <p class="card-text"><?= htmlspecialchars($p['variacao']) ?></p>
                                        <p class="card-text"><strong>R$ <?= number_format($p['preco'], 2, ',', '.') ?></strong></p>

                                        <?php if ($p['quantidade'] > 0): ?>
                                            <button type="button" class="btn btn-primary mt-auto"
                                                onclick="abrirAdicionarCarrinho(
                                                    <?= (int)$p['id'] ?>,
                                                    '<?= addslashes(htmlspecialchars($p['nome'])) ?>',
                                                    <?= (int)$p['quantidade'] ?>,
                                                    'imagem_produto.php?id=<?= (int)$p['id'] ?>'
                                                )">
                                                Comprar
                                            </button>
                                        <?php else: ?>
                                            <span class="badge bg-danger mt-auto">Indisponível</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#carousel-<?= htmlspecialchars($tipo) ?>" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Anterior</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#carousel-<?= htmlspecialchars($tipo) ?>" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Próximo</span>
        </button>
    </div>
<?php endforeach; ?>

</div>
<div class="hero-div-container">
    <img src="assets/yummy-back (1).webp" />
</div>

<div class="container-produtos"><br><br>
<?php
$tipos2 = ['bebida', 'sobremesa'];
foreach ($produtos_por_tipo as $tipo => $produtos):
    if (!in_array(strtolower($tipo), $tipos2)) continue;
?>
    <h2><?= htmlspecialchars(ucfirst($tipo)) ?></h2>

    <div id="carousel-<?= htmlspecialchars($tipo) ?>" class="carousel slide" data-bs-ride="carousel">
        <div class="carousel-inner">

            <?php
            $produtos_array = [];
            while ($p = $produtos->fetch_assoc()) {
                $produtos_array[] = $p;
            }
            $chunks = array_chunk($produtos_array, 3);
            ?>

            <?php foreach ($chunks as $index => $chunk): ?>
                <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                    <div class="row">
                        <?php foreach ($chunk as $p): ?>
                            <div class="col-md-4">
                                <div class="card h-100">
                                    <img src="imagem_produto.php?id=<?= $p['id'] ?>" class="card-img-top" alt="<?= htmlspecialchars($p['nome']) ?>" >
                                    <div class="card-body d-flex flex-column">
                                        <h5 class="card-title"><?= htmlspecialchars($p['nome']) ?></h5>
                                        <p class="card-text">Variante: <?= htmlspecialchars($p['variacao']) ?></p>
                                        <p class="card-text"><strong>R$ <?= number_format($p['preco'], 2, ',', '.') ?></strong></p>

                                        <?php if ($p['quantidade'] > 0): ?>
                                            <button type="button" class="btn btn-primary mt-auto"
                                                onclick="abrirAdicionarCarrinho(
                                                    <?= (int)$p['id'] ?>,
                                                    '<?= addslashes(htmlspecialchars($p['nome'])) ?>',
                                                    <?= (int)$p['quantidade'] ?>,
                                                    'imagem_produto.php?id=<?= (int)$p['id'] ?>'
                                                )">
                                                Comprar
                                            </button>
                                        <?php else: ?>
                                            <span class="badge bg-danger mt-auto">Indisponível</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#carousel-<?= htmlspecialchars($tipo) ?>" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Anterior</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#carousel-<?= htmlspecialchars($tipo) ?>" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Próximo</span>
        </button>
    </div>

<?php endforeach; ?>
</div>



<div class="modal fade" id="modalAdicionarCarrinho" tabindex="-1" aria-labelledby="modalAdicionarCarrinhoLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" class="modal-content" novalidate>
        <input type="hidden" name="acao" value="adicionar_ao_carrinho" />
        <input type="hidden" name="produto_id" id="produtoIdModal" />
        <div class="modal-header">
            <h5 class="modal-title" id="modalAdicionarCarrinhoLabel">
                <img src="assets/logo-fundo-vermelho.webp" >Adicionar ao Carrinho</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
        </div>
    <div class="modal-body">
    <div id="infoProdutoModal"></div>
    <label for="quantidadeModal" class="form-label">Quantidade:</label>
    <input type="number" min="1" name="quantidade" id="quantidadeModal" class="form-control" required />
</div>

        <div class="modal-footer">
            <button type="submit" class="btn btn-success">Adicionar</button>
        </div>
    </form>
  </div>
</div>

<div id="sidebarCarrinho" class="sidebar-carrinho"> 
    
<?php if ($mensagem): ?>
        <div class="alert alert-info"><?= htmlspecialchars($mensagem) ?></div>
    <?php endif; ?>
    <div class="sidebar-header">
         
        <h4>Meu Carrinho</h4>
        <button class="btn-close" onclick="toggleCarrinho()"></button>
    </div>
    <div class="sidebar-body">
        <?php include 'carrinho.php'; ?>
        <div class="align-carrinho">
        <h2 class="mt-5">Meus Pedidos</h2>
<?php if ($result_pedidos->num_rows > 0): ?>
<div class="table-responsive">
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Total</th>
                <th>Frete</th>
                <th>Data</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($pedido = $result_pedidos->fetch_assoc()): ?>
                <tr>
                    <td>R$ <?= number_format($pedido['total'], 2, ',', '.') ?></td>
                    <td>R$ <?= number_format($pedido['frete'], 2, ',', '.') ?></td>
                    <td><?= date('d/m H:i', strtotime($pedido['data'])) ?></td>
                </tr>
                <tr>
                    <td colspan="3"><strong>Status:</strong> <?= htmlspecialchars($pedido['status']) ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php else: ?>
    <p>Comece a comprar para ver seus pedidos aqui.</p>
<?php endif; ?>
</div>
    </div>
</div>
<div id="overlayCarrinho" class="overlay-carrinho" onclick="toggleCarrinho()"></div>
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


<script>
    function toggleCarrinho() {
    const sidebar = document.getElementById('sidebarCarrinho');
    const overlay = document.getElementById('overlayCarrinho');
    sidebar.classList.toggle('ativo');
    overlay.classList.toggle('ativo');
}

function abrirAdicionarCarrinho(id, nome, maxEstoque, imagemUrl) {
    const modal = new bootstrap.Modal(document.getElementById('modalAdicionarCarrinho'));
    document.getElementById('produtoIdModal').value = id;

  
    const container = document.getElementById('infoProdutoModal');
    container.innerHTML = `
      <div class="adicionar-carrinho">
        <img src="${imagemUrl}" alt="${nome}" />
      </div>   
      <h2 style="margin:0; font-weight:600;">${nome}</h2>
    `;

    const inputQtd = document.getElementById('quantidadeModal');
    inputQtd.max = maxEstoque;
    inputQtd.value = 1;
    modal.show();
}
</script>
<script src="script/script.js"></script>
<?php if (!empty($_SESSION['abrir_carrinho'])): ?>
<script>
    document.addEventListener("DOMContentLoaded", () => {
        toggleCarrinho();
    });
</script>
<?php unset($_SESSION['abrir_carrinho']); ?>
<?php endif; ?>

</body>
</html>
