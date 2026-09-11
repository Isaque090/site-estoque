<?php
include_once('inc/config.php');
session_start();

if (!isset($_SESSION['login']) || $_SESSION['login'] == false) {
    header('location:login.php');
    exit;
}

$smt = $conexao->prepare('SELECT cd_funcionario, nm_funcionario, ds_cargo FROM Funcionarios WHERE ds_email = ?');
$smt->bind_param('s', $_SESSION['email']);
$smt->execute();

$resultado = $smt->get_result();
$funcionario = $resultado->fetch_assoc();

if (!$funcionario) {
    session_destroy();
    header('location:login.php');
    exit;
}

$_SESSION['id'] = $funcionario['cd_funcionario'];
$_SESSION['nome'] = $funcionario['nm_funcionario'];
$_SESSION['cargo'] = $funcionario['ds_cargo'];

$sql = "SELECT cd_produto, nm_produto, vl_produto, dt_validade_produto, ds_produto, qt_estoque
        FROM produtos
        ORDER BY nm_produto ASC";

$result = $conexao->query($sql);

if (!$result) {
    die('Erro ao buscar produtos: ' . $conexao->error);
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produtos - Estoque</title>

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
            text-decoration: none;
            padding: 12px 18px;
            border-radius: 7px;
            font-weight: bold;
            transition: 0.2s;
        }

        .btn-adicionar:hover {
            background: #0284c7;
            color: white;
            text-decoration: none;
        }

        .card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }

        .card h2 {
            margin-bottom: 20px;
            color: #f1f5f9;
        }

        .tabela-container {
            width: 100%;
            overflow-x: auto;
        }

        .tabela {
            width: 100%;
            border-collapse: collapse;
        }

        .tabela th {
            color: #38bdf8;
            text-align: left;
            padding: 15px 12px;
            border-bottom: 1px solid rgba(148, 163, 184, 0.2);
            white-space: nowrap;
        }

        .tabela td {
            padding: 15px 12px;
            color: #e2e8f0;
            border-bottom: 1px solid rgba(148, 163, 184, 0.1);
            white-space: nowrap;
        }

        .tabela tr:hover {
            background: rgba(255, 255, 255, 0.05);
        }

        .descricao {
            max-width: 250px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .estoque {
            font-weight: bold;
        }

        .estoque-baixo {
            color: #f87171 !important;
        }

        .estoque-normal {
            color: #4ade80 !important;
        }

        .validade-vencida {
            color: #f87171 !important;
            font-weight: bold;
        }

        .validade-proxima {
            color: #facc15 !important;
            font-weight: bold;
        }

        .btn-editar {
            background: #f59e0b;
            color: white;
            text-decoration: none;
            padding: 7px 12px;
            border-radius: 6px;
            font-size: 13px;
        }

        .btn-editar:hover {
            background: #d97706;
            color: white;
            text-decoration: none;
        }

        .btn-excluir {
            background: #ef4444;
            color: white;
            text-decoration: none;
            padding: 7px 12px;
            border-radius: 6px;
            font-size: 13px;
            border: none;
            cursor: pointer;
        }

        .btn-excluir:hover {
            background: #dc2626;
        }

        .sem-produtos {
            text-align: center;
            padding: 40px;
            color: #94a3b8;
        }

        .quantidade {
            color: #94a3b8;
            margin-bottom: 15px;
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
                padding: 90px 15px 20px;
            }

            .usuario-info {
                display: none;
            }

            .cabecalho-pagina {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .cabecalho-pagina h1 {
                font-size: 24px;
            }

            .card {
                padding: 15px;
            }
        }
    </style>
</head>

<body>

    <nav class="navbar">

        <div class="logo">
            Estoque
        </div>

        <div class="usuario">

            <div class="usuario-info">

                <div class="usuario-nome">
                    <?php echo htmlspecialchars($_SESSION['nome'] ?? 'Usuário'); ?>
                </div>

                <div class="usuario-cargo">
                    <?php echo htmlspecialchars($_SESSION['cargo'] ?? 'Funcionário'); ?>
                </div>

            </div>

            <a href="logout.php" class="btn-sair">
                Sair
            </a>

        </div>

    </nav>

    <aside class="sidebar">

        <div class="menu">

            <div class="menu-titulo">
                Menu
            </div>

            <a href="index.php">
                <span class="icone">🏠</span>
                <span>Dashboard</span>
            </a>

            <a href="produtos.php" class="ativo">
                <span class="icone">📦</span>
                <span>Produtos</span>
            </a>

            <?php if (isset($_SESSION['cargo']) && ($_SESSION['cargo'] == 'admin' || $_SESSION['cargo'] == 'estoquista')): ?>

                <a href="categorias.php">
                    <span class="icone">🏷️</span>
                    <span>Categorias</span>
                </a>

            <?php endif; ?>

           <?php if (
                isset($_SESSION['cargo']) && ($_SESSION['cargo'] == 'admin' || $_SESSION['cargo'] ==
                    'estoquista')
            ): ?>
            <a href="compras.php">
                <span class="icone">🛒</span>
                <span>Compras</span>
            </a>
             <?php endif; ?>

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
                <h1>Produtos</h1>
                <p>Gerencie os produtos do estoque.</p>
            </div>

            <a href="produto_cadastrar.php" class="btn-adicionar">
                + Novo Produto
            </a>

        </div>

        <div class="card">

            <h2>Produtos cadastrados</h2>

            <div class="quantidade">
                <?php echo $result->num_rows; ?> produto(s) cadastrado(s)
            </div>

            <?php if ($result->num_rows > 0): ?>

                <div class="tabela-container">

                    <table class="tabela">

                        <thead>

                            <tr>
                                <th>Código</th>
                                <th>Produto</th>
                                <th>Preço</th>
                                <th>Validade</th>
                                <th>Descrição</th>
                                <th>Estoque</th>
                                
           <?php if (
                isset($_SESSION['cargo']) && ($_SESSION['cargo'] == 'admin' || $_SESSION['cargo'] ==
                    'estoquista')
            ): ?>
                                <th>Ações</th>
                                 <?php endif; ?>
                            </tr>

                        </thead>

                        <tbody>

                            <?php while ($produto = $result->fetch_assoc()): ?>

                                <?php
                                $dataValidade = strtotime($produto['dt_validade_produto']);
                                $hoje = strtotime(date('Y-m-d'));
                                $trintaDias = strtotime('+30 days');

                                $classeValidade = '';

                                if ($dataValidade < $hoje) {
                                    $classeValidade = 'validade-vencida';
                                } elseif ($dataValidade <= $trintaDias) {
                                    $classeValidade = 'validade-proxima';
                                }

                                $classeEstoque = $produto['qt_estoque'] <= 10
                                    ? 'estoque-baixo'
                                    : 'estoque-normal';
                                ?>

                                <tr>

                                    <td>
                                        <?php echo htmlspecialchars($produto['cd_produto']); ?>
                                    </td>

                                    <td>
                                        <?php echo htmlspecialchars($produto['nm_produto']); ?>
                                    </td>

                                    <td>
                                        R$
                                        <?php echo number_format($produto['vl_produto'], 2, ',', '.'); ?>
                                    </td>

                                    <td class="<?php echo $classeValidade; ?>">
                                        <?php echo date('d/m/Y', $dataValidade); ?>
                                    </td>

                                    <td class="descricao" title="<?php echo htmlspecialchars($produto['ds_produto']); ?>">
                                        <?php echo htmlspecialchars($produto['ds_produto']); ?>
                                    </td>

                                    <td class="estoque <?php echo $classeEstoque; ?>">
                                        <?php echo htmlspecialchars($produto['qt_estoque']); ?>
                                    </td>
                         
           <?php if (
                isset($_SESSION['cargo']) && ($_SESSION['cargo'] == 'admin' || $_SESSION['cargo'] ==
                    'estoquista')
            ): ?>
                                    <td>

                                        <a
                                            href="produto_editar.php?id=<?php echo $produto['cd_produto']; ?>"
                                            class="btn-editar">
                                            Editar
                                        </a>

                                        <a
                                            href="produto_excluir.php?id=<?php echo $produto['cd_produto']; ?>"
                                            class="btn-excluir"
                                            onclick="return confirm('Tem certeza que deseja excluir este produto?');">
                                            Excluir
                                        </a>

                                    </td>
  <?php endif; ?>
                                </tr>

                            <?php endwhile; ?>

                        </tbody>

                    </table>

                </div>

            <?php else: ?>

                <div class="sem-produtos">
                    Nenhum produto cadastrado.
                </div>

            <?php endif; ?>

        </div>

    </main>

</body>

</html>