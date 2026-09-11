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

if (!$cargo || $cargo['status'] != 'ativo') {
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

    $id_fornecedor = (int) ($_POST['fornecedor'] ?? 0);
    $produtos = $_POST['produto'] ?? [];
    $quantidades = $_POST['quantidade'] ?? [];

    if ($id_fornecedor <= 0) {
        $_SESSION['mensagemJs'] = "alert('Selecione um fornecedor')";
        header('location:compras.php');
        exit;
    }

    $vl_total = 0;
    $itens_validos = [];

    foreach ($produtos as $i => $id_produto) {
        $qtd = (int) ($quantidades[$i] ?? 0);
        if ($qtd <= 0) continue;

        $stmt = $conexao->prepare('SELECT nm_produto, vl_produto FROM Produtos WHERE cd_produto = ?');
        $stmt->bind_param('i', $id_produto);
        $stmt->execute();
        $prod = $stmt->get_result()->fetch_assoc();

        if (!$prod) continue;

        $vl_total += $prod['vl_produto'] * $qtd;
        $itens_validos[] = [
            'id_produto' => $id_produto,
            'quantidade' => $qtd,
            'vl_unitario' => $prod['vl_produto']
        ];
    }

    if (empty($itens_validos)) {
        $_SESSION['mensagemJs'] = "alert('Selecione pelo menos um produto com quantidade')";
        header('location:compras.php');
        exit;
    }

    $stmt = $conexao->prepare('INSERT INTO compras (dt_compra, vl_total, id_funcionario, id_fornecedor) VALUES (NOW(), ?, ?, ?)');
    $stmt->bind_param('dii', $vl_total, $id_funcionario, $id_fornecedor);
    $stmt->execute();
    $id_compra = $conexao->insert_id;

    foreach ($itens_validos as $item) {
        $stmt = $conexao->prepare('INSERT INTO itens_compra (id_compra, id_produto, qt_produto, vl_unitario) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('iiid', $id_compra, $item['id_produto'], $item['quantidade'], $item['vl_unitario']);
        $stmt->execute();

        $stmt = $conexao->prepare('UPDATE Produtos SET qt_estoque = qt_estoque + ? WHERE cd_produto = ?');
        $stmt->bind_param('ii', $item['quantidade'], $item['id_produto']);
        $stmt->execute();
    }

    $_SESSION['mensagemJs'] = "alert('Compra cadastrada com sucesso!')";
    header('location:compras.php');
    exit;
}

if ($_SESSION['cargo'] != 'admin') {
    $sql = "SELECT c.cd_compra, c.dt_compra, c.vl_total, f.nm_funcionario, fo.nm_fornecedor
            FROM compras c
            INNER JOIN Funcionarios f ON c.id_funcionario = f.cd_funcionario
            INNER JOIN fornecedores fo ON c.id_fornecedor = fo.cd_fornecedor
            WHERE c.id_funcionario = $id_funcionario
            ORDER BY c.dt_compra DESC";
} else {
    $sql = "SELECT c.cd_compra, c.dt_compra, c.vl_total, f.nm_funcionario, fo.nm_fornecedor
            FROM compras c
            INNER JOIN Funcionarios f ON c.id_funcionario = f.cd_funcionario
            INNER JOIN fornecedores fo ON c.id_fornecedor = fo.cd_fornecedor
            ORDER BY c.dt_compra DESC";
}
$result = $conexao->query($sql);

$produtos = $conexao->query('SELECT cd_produto, nm_produto, vl_produto, qt_estoque FROM Produtos ORDER BY nm_produto');
$fornecedores = $conexao->query('SELECT cd_fornecedor, nm_fornecedor FROM fornecedores ORDER BY nm_fornecedor');

$itens_modal = [];
$compra_modal = null;

if (isset($_GET['ver_itens'])) {
    $id_compra = (int) $_GET['ver_itens'];

    $stmt = $conexao->prepare("SELECT c.cd_compra, c.dt_compra, c.vl_total, f.nm_funcionario, fo.nm_fornecedor
                               FROM compras c
                               INNER JOIN Funcionarios f ON c.id_funcionario = f.cd_funcionario
                               INNER JOIN fornecedores fo ON c.id_fornecedor = fo.cd_fornecedor
                               WHERE c.cd_compra = ?");
    $stmt->bind_param("i", $id_compra);
    $stmt->execute();
    $compra_modal = $stmt->get_result()->fetch_assoc();

    $stmt = $conexao->prepare("SELECT p.nm_produto, ic.qt_produto, ic.vl_unitario
                               FROM itens_compra ic
                               INNER JOIN Produtos p ON ic.id_produto = p.cd_produto
                               WHERE ic.id_compra = ?");
    $stmt->bind_param("i", $id_compra);
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
    <title>Compras - Estoque</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, Helvetica, sans-serif;
        }

        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #0f172a, #1e293b);
            color: #e2e8f0;
        }

        .navbar {
            height: 65px;
            width: 100%;
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(12px);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 25px;
            border-bottom: 1px solid rgba(148, 163, 184, 0.15);
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
        }

        .logo {
            font-size: 22px;
            font-weight: bold;
            color: #38bdf8;
        }

        .usuario {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .usuario-info {
            text-align: right;
        }

        .usuario-nome {
            font-weight: bold;
            color: #f1f5f9;
        }

        .usuario-cargo {
            font-size: 12px;
            color: #94a3b8;
        }

        .btn-sair {
            background: #ef4444;
            color: white;
            text-decoration: none;
            padding: 9px 15px;
            border-radius: 7px;
            transition: 0.2s;
        }

        .btn-sair:hover {
            background: #dc2626;
            color: white;
            text-decoration: none;
        }

        .sidebar {
            position: fixed;
            top: 65px;
            left: 0;
            width: 240px;
            height: calc(100vh - 65px);
            background: rgba(15, 23, 42, 0.95);
            border-right: 1px solid rgba(148, 163, 184, 0.1);
            padding: 20px 12px;
        }

        .menu-titulo {
            color: #64748b;
            font-size: 12px;
            text-transform: uppercase;
            padding: 10px 15px;
            margin-bottom: 5px;
        }

        .menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #94a3b8;
            text-decoration: none;
            padding: 13px 15px;
            margin-bottom: 5px;
            border-radius: 7px;
            transition: 0.2s;
        }

        .menu a:hover {
            background: rgba(56, 189, 248, 0.15);
            color: #e0f2fe;
        }

        .menu a.ativo {
            background: #0ea5e9;
            color: white;
        }

        .icone {
            width: 25px;
            text-align: center;
        }

        .conteudo {
            margin-left: 240px;
            padding: 95px 30px 30px;
        }

        .cabecalho-pagina {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .cabecalho-pagina h1 {
            font-size: 28px;
            color: #f1f5f9;
        }

        .cabecalho-pagina p {
            color: #94a3b8;
            margin-top: 5px;
        }

        .btn-adicionar {
            background: #0ea5e9;
            color: white;
            border: none;
            padding: 12px 18px;
            border-radius: 7px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.2s;
        }

        .btn-adicionar:hover {
            background: #0284c7;
        }

        .table {
            background: rgba(255, 255, 255, 0.05);
            color: #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
        }

        .table thead th {
            border-color: rgba(148, 163, 184, 0.2);
            color: #94a3b8;
            font-weight: 600;
            background: rgba(15, 23, 42, 0.6);
        }

        .table td,
        .table th {
            border-color: rgba(148, 163, 184, 0.15);
            vertical-align: middle;
            padding: 14px 16px;
        }

        .table-striped tbody tr:nth-of-type(odd) {
            background: rgba(255, 255, 255, 0.03);
        }

        .table-hover tbody tr:hover {
            background: rgba(56, 189, 248, 0.1);
        }

        .btn-info {
            background: #0ea5e9;
            border: none;
            color: white;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 13px;
        }

        .btn-info:hover {
            background: #0284c7;
            color: white;
        }

        .modal-content {
            background: #1e293b;
            color: #e2e8f0;
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 12px;
        }

        .modal-header {
            border-bottom: 1px solid rgba(148, 163, 184, 0.2);
        }

        .modal-footer {
            border-top: 1px solid rgba(148, 163, 184, 0.2);
        }

        .modal-title {
            color: #f1f5f9;
            font-weight: 600;
        }

        .close {
            color: #94a3b8;
            opacity: 1;
            text-shadow: none;
        }

        .close:hover {
            color: #e2e8f0;
        }

        .produto-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 14px 0;
            border-bottom: 1px solid rgba(148, 163, 184, 0.12);
        }

        .produto-item:last-child {
            border-bottom: none;
        }

        .produto-item input[type="number"] {
            width: 100px;
        }

        .form-control {
            background: #0f172a;
            border: 1px solid rgba(148, 163, 184, 0.3);
            color: #e2e8f0;
            border-radius: 7px;
        }

        .form-control:focus {
            background: #0f172a;
            color: #e2e8f0;
            border-color: #0ea5e9;
            box-shadow: 0 0 0 0.2rem rgba(14, 165, 233, 0.25);
        }

        .form-group label {
            color: #94a3b8;
            font-size: 14px;
            margin-bottom: 6px;
        }

        .btn-primary {
            background: #0ea5e9;
            border: none;
            border-radius: 7px;
            padding: 9px 18px;
            font-weight: 600;
        }

        .btn-primary:hover {
            background: #0284c7;
        }

        .btn-secondary {
            background: #475569;
            border: none;
            border-radius: 7px;
            padding: 9px 18px;
        }

        .btn-secondary:hover {
            background: #334155;
        }

        .text-success {
            color: #22c55e !important;
            font-weight: 600;
        }

        @media (max-width: 700px) {
            .sidebar {
                width: 70px;
            }

            .menu-titulo {
                display: none;
            }

            .menu a {
                justify-content: center;
            }

            .menu a span:not(.icone) {
                display: none;
            }

            .conteudo {
                margin-left: 70px;
            }

            .usuario-info {
                display: none;
            }

            .cabecalho-pagina {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <div class="logo">Estoque</div>
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
            <a href="index.php">
                <span class="icone">🏠</span>
                <span>Dashboard</span>
            </a>
            <a href="produtos.php">
                <span class="icone">📦</span>
                <span>Produtos</span>
            </a>
            <?php if (isset($_SESSION['cargo']) && ($_SESSION['cargo'] == 'admin' || $_SESSION['cargo'] == 'estoquista')): ?>
                <a href="categorias.php">
                    <span class="icone">🏷️</span>
                    <span>Categorias</span>
                </a>
            <?php endif; ?>
            <a href="compras.php" class="ativo">
                <span class="icone">🛒</span>
                <span>Compras</span>
            </a>
            <a href="vendas.php">
                <span class="icone">💰</span>
                <span>Vendas</span>
            </a>
            <?php if (isset($_SESSION['cargo']) && $_SESSION['cargo'] == 'admin'): ?>
                <a href="funcionarios.php">
                    <span class="icone">👥</span>
                    <span>Funcionários</span>
                </a>
            <?php endif; ?>
        </div>
    </aside>

    <main class="conteudo">
        <div class="cabecalho-pagina">
            <div>
                <h1>Compras</h1>
                <p>Gerencie as compras realizadas.</p>
            </div>
            <button type="button" class="btn-adicionar" data-toggle="modal" data-target="#modalNovaCompra">
                + Nova Compra
            </button>
        </div>

        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Funcionario</th>
                    <th>Fornecedor</th>
                    <th>Valor Total</th>
                    <th width="120">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($compra = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= date('d/m/Y H:i', strtotime($compra['dt_compra'])) ?></td>
                            <td><?= htmlspecialchars($compra['nm_funcionario']) ?></td>
                            <td><?= htmlspecialchars($compra['nm_fornecedor']) ?></td>
                            <td>R$ <?= number_format($compra['vl_total'], 2, ',', '.') ?></td>
                            <td>
                                <button type="button" class="btn btn-info btn-sm" onclick="verItens(<?= $compra['cd_compra'] ?>)">
                                    Ver itens
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center">Nenhuma compra registrada ainda.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </main>

    <div class="modal fade" id="modalNovaCompra" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="post">
                    <div class="modal-header">
                        <h5 class="modal-title">Nova Compra</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body" style="max-height: 500px; overflow-y: auto;">
                        <div class="form-group">
                            <label>Fornecedor</label>
                            <select name="fornecedor" class="form-control" required>
                                <option value="">Selecione o fornecedor</option>
                                <?php while ($f = $fornecedores->fetch_assoc()): ?>
                                    <option value="<?= $f['cd_fornecedor'] ?>"><?= htmlspecialchars($f['nm_fornecedor']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <p class="mb-3 mt-4" style="color:#94a3b8;">Selecione os produtos e a quantidade:</p>

                        <?php while ($p = $produtos->fetch_assoc()): ?>
                            <div class="produto-item">
                                <input type="hidden" name="produto[]" value="<?= $p['cd_produto'] ?>">
                                <div style="flex:1">
                                    <strong><?= htmlspecialchars($p['nm_produto']) ?></strong><br>
                                    <small style="color:#94a3b8;">R$ <?= number_format($p['vl_produto'], 2, ',', '.') ?> | Estoque atual: <?= $p['qt_estoque'] ?></small>
                                </div>
                                <input type="number" name="quantidade[]" class="form-control" min="0" value="0" placeholder="Qtd">
                            </div>
                        <?php endwhile; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" name="cadastrar" class="btn btn-primary">Finalizar Compra</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalVerItens" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Itens da Compra</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <?php if ($compra_modal): ?>
                        <h5 style="color:#f1f5f9;">Compra #<?= $compra_modal['cd_compra'] ?></h5>
                        <p style="color:#94a3b8;">Funcionário: <strong style="color:#e2e8f0;"><?= htmlspecialchars($compra_modal['nm_funcionario']) ?></strong></p>
                        <p style="color:#94a3b8;">Fornecedor: <strong style="color:#e2e8f0;"><?= htmlspecialchars($compra_modal['nm_fornecedor']) ?></strong></p>
                        <p style="color:#94a3b8;">Data: <?= date('d/m/Y H:i', strtotime($compra_modal['dt_compra'])) ?></p>

                        <table class="table table-striped mt-3">
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
                                    <?php $subtotal = $item['qt_produto'] * $item['vl_unitario']; ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['nm_produto']) ?></td>
                                        <td><?= $item['qt_produto'] ?></td>
                                        <td>R$ <?= number_format($item['vl_unitario'], 2, ',', '.') ?></td>
                                        <td>R$ <?= number_format($subtotal, 2, ',', '.') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <div class="text-right mt-3">
                            <strong>Total:</strong>
                            <span class="text-success">R$ <?= number_format($compra_modal['vl_total'], 2, ',', '.') ?></span>
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
            window.location.href = "compras.php?ver_itens=" + id;
        }

        <?php if (isset($_GET['ver_itens']) && $compra_modal): ?>
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