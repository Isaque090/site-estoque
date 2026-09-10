<?php
include_once('inc/config.php');
session_start();

if (!isset($_SESSION['login']) || $_SESSION['login'] == false) {
    header('location:login.php');
    exit;
}


$smt = $conexao->prepare('SELECT ds_cargo, status FROM Funcionarios WHERE ds_email = ?');
$smt->bind_param('s', $_SESSION['email']);
$smt->execute();
$cargo = $smt->get_result()->fetch_assoc();

if ($cargo['status'] != 'ativo') {
    header('location:logout.php');
    exit;
}
if ($cargo['ds_cargo'] != $_SESSION['cargo']) {
    $_SESSION['cargo'] = $cargo['ds_cargo'];
}


$smt = $conexao->prepare('SELECT cd_funcionario FROM Funcionarios WHERE ds_email = ?');
$smt->bind_param('s', $_SESSION['email']);
$smt->execute();
$func = $smt->get_result()->fetch_assoc();
$id_funcionario = $func['cd_funcionario'];


if (isset($_POST['cadastrar'])) {
    $produtos = $_POST['produto'] ?? [];
    $quantidades = $_POST['quantidade'] ?? [];

    $vl_total = 0;
    $itens_validos = [];


    foreach ($produtos as $i => $id_produto) {
        $qtd = (int) ($quantidades[$i] ?? 0);
        if ($qtd <= 0)
            continue;

        $stmt = $conexao->prepare('SELECT nm_produto, vl_produto, qt_estoque FROM Produtos WHERE cd_produto = ?');
        $stmt->bind_param('i', $id_produto);
        $stmt->execute();
        $prod = $stmt->get_result()->fetch_assoc();

        if (!$prod || $prod['qt_estoque'] < $qtd) {
            $_SESSION['mensagemJs'] = "alert('Estoque insuficiente do produto: " . ($prod['nm_produto'] ?? '') . "')";
            header('location:vendas.php');
            exit;
        }

        $vl_total += $prod['vl_produto'] * $qtd;
        $itens_validos[] = [
            'id_produto' => $id_produto,
            'quantidade' => $qtd,
            'vl_unitario' => $prod['vl_produto']
        ];
    }

    if (empty($itens_validos)) {
        $_SESSION['mensagemJs'] = "alert('Selecione pelo menos um produto com quantidade')";
        header('location:vendas.php');
        exit;
    }


    $stmt = $conexao->prepare('INSERT INTO vendas (dt_venda, vl_total, id_funcionario) VALUES (NOW(), ?, ?)');
    $stmt->bind_param('di', $vl_total, $id_funcionario);
    $stmt->execute();
    $id_venda = $conexao->insert_id;

    foreach ($itens_validos as $item) {
        $stmt = $conexao->prepare('INSERT INTO itens_venda (id_venda, id_produto, quantidade, vl_unitario) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('iiid', $id_venda, $item['id_produto'], $item['quantidade'], $item['vl_unitario']);
        $stmt->execute();

        $stmt = $conexao->prepare('UPDATE Produtos SET qt_estoque = qt_estoque - ? WHERE cd_produto = ?');
        $stmt->bind_param('ii', $item['quantidade'], $item['id_produto']);
        $stmt->execute();
    }

    $_SESSION['mensagemJs'] = "alert('Venda cadastrada com sucesso!')";
    header('location:vendas.php');
    exit;
}


$sql = "SELECT v.cd_venda, v.dt_venda, v.vl_total, f.nm_funcionario
        FROM vendas v
        INNER JOIN Funcionarios f ON v.id_funcionario = f.cd_funcionario
        ORDER BY v.dt_venda DESC";
$result = $conexao->query($sql);


$produtos = $conexao->query('SELECT cd_produto, nm_produto, vl_produto, qt_estoque FROM Produtos WHERE qt_estoque > 0 ORDER BY nm_produto');

$itens_modal = [];
$venda_modal = null;

if (isset($_GET['ver_itens'])) {

    $id_venda = (int) $_GET['ver_itens'];

    $stmt = $conexao->prepare("SELECT  v.cd_venda, v.dt_venda,v.vl_total,f.nm_funcionario FROM vendas v INNER JOIN Funcionarios f ON v.id_funcionario = f.cd_funcionario WHERE v.cd_venda = ? ");
    $stmt->bind_param("i", $id_venda);
    $stmt->execute();
    $venda_modal = $stmt->get_result()->fetch_assoc();

    $stmt = $conexao->prepare(" SELECT p.nm_produto,iv.quantidade,iv.vl_unitario FROM itens_venda iv INNER JOIN Produtos p ON iv.id_produto = p.cd_produto WHERE iv.id_venda = ?");

    $stmt->bind_param("i", $id_venda);
    $stmt->execute();

    $resultado_itens = $stmt->get_result();

    while ($item = $resultado_itens->fetch_assoc()) {
        $itens_modal[] = $item;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendas - Estoque</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/vendas.css">

</head>

<body>
    <nav class="navbar">
        <div class="logo">📦 Estoque</div>
        <div class="usuario">
            <div class="usuario-info">
                <div class="usuario-nome"><?= $_SESSION['nome'] ?? 'Usuário' ?></div>
                <div class="usuario-cargo"><?= $_SESSION['cargo'] ?? 'Funcionário' ?></div>
            </div>
            <a href="logout.php" class="btn-sair">Sair</a>
        </div>
    </nav>

    <aside class="sidebar">
        <div class="menu">
            <div class="menu-titulo">Menu</div>
            <a href="index.php"><span class="icone">🏠</span><span>Dashboard</span></a>
            <a href="produtos.php"><span class="icone">📦</span><span>Produtos</span></a>
            <?php if (isset($_SESSION['cargo']) && ($_SESSION['cargo'] == 'admin' || $_SESSION['cargo'] == 'estoquista')): ?>
                <a href="categorias.php"><span class="icone">🏷️</span><span>Categorias</span></a>
            <?php endif; ?>
            <a href="compras.php"><span class="icone">🛒</span><span>Compras</span></a>
            <a href="vendas.php" class="ativo"><span class="icone">💰</span><span>Vendas</span></a>
            <?php if (isset($_SESSION['cargo']) && $_SESSION['cargo'] == 'admin'): ?>
                <a href="funcionarios.php"><span class="icone">👥</span><span>Funcionários</span></a>
            <?php endif; ?>
        </div>
    </aside>

    <main class="conteudo">
        <div class="cabecalho-pagina">
            <div>
                <h1>Vendas</h1>
                <p>Gerencie as vendas realizadas.</p>
            </div>
            <button type="button" class="btn btn-adicionar" data-toggle="modal" data-target="#modalNovaVenda">
                + Nova Venda
            </button>
        </div>

        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Data</th>
                    <th>Funcionário</th>
                    <th>Valor Total</th>
                    <th width="120">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($venda = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $venda['cd_venda'] ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($venda['dt_venda'])) ?></td>
                            <td><?= htmlspecialchars($venda['nm_funcionario']) ?></td>
                            <td>R$ <?= number_format($venda['vl_total'], 2, ',', '.') ?></td>
                            <td>
                                <button type="button" class="btn btn-info btn-sm" onclick="verItens(<?= $venda['cd_venda'] ?>)">
                                    Ver itens
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center">Nenhuma venda registrada ainda.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </main>

    <div class="modal fade" id="modalNovaVenda" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="post">
                    <div class="modal-header">
                        <h5 class="modal-title">Nova Venda</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body" style="max-height: 450px; overflow-y: auto;">
                        <p class="mb-3">Selecione os produtos e informe a quantidade:</p>

                        <?php while ($p = $produtos->fetch_assoc()): ?>
                            <div class="produto-item">
                                <input type="hidden" name="produto[]" value="<?= $p['cd_produto'] ?>">
                                <div style="flex:1">
                                    <strong><?= htmlspecialchars($p['nm_produto']) ?></strong><br>
                                    <small>R$ <?= number_format($p['vl_produto'], 2, ',', '.') ?> | Estoque:
                                        <?= $p['qt_estoque'] ?></small>
                                </div>
                                <input type="number" name="quantidade[]" class="form-control" min="0"
                                    max="<?= $p['qt_estoque'] ?>" value="0" placeholder="Qtd">
                            </div>
                        <?php endwhile; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" name="cadastrar" class="btn btn-primary">Finalizar Venda</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalVerItens" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"> Itens da Venda</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php if ($venda_modal): ?>
                        <h5> Venda #<?= $venda_modal['cd_venda'] ?> </h5>
                        <p>Funcionário: <strong> <?= htmlspecialchars($venda_modal['nm_funcionario']) ?> </strong> </p>
                        <p>Data: <?= date('d/m/Y H:i',strtotime($venda_modal['dt_venda']) ) ?></p>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Produto</th>
                                    <th>Quantidade</th>
                                    <th>Valor unitário</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($itens_modal as $item): ?>
                                    <?php $subtotal =$item['quantidade'] *$item['vl_unitario'];?>
                                    <tr>
                                        <td>
                                            <?= htmlspecialchars($item['nm_produto'])?>
                                        </td>
                                        <td>
                                            <?= $item['quantidade'] ?>
                                        </td>
                                        <td>
                                            R$ <?= number_format( $item['vl_unitario'],2,',','.') ?>
                                        </td>

                                        <td>
                                            R$ <?= number_format( $subtotal,2,',','.') ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <div class="text-right">
                            <strong>Total:</strong>
                            <span class="text-success">
                                R$<?= number_format( $venda_modal['vl_total'], 2,',','.') ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>


    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.14.7/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/js/bootstrap.min.js"></script>
    <script>


        function verItens(id) {
            window.location.href = "vendas.php?ver_itens=" + id;
        }

        <?php if (isset($_GET['ver_itens']) && $venda_modal): ?>

            $(document).ready(function () {
                $('#modalVerItens').modal('show');
            });

        <?php endif; ?>


        <?php
        if (isset($_SESSION['mensagemJs'])) {
            echo $_SESSION['mensagemJs'];
            unset($_SESSION['mensagemJs']);
        }
        ?>

    </script>
</body>

</html>