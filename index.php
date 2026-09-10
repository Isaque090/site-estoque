<?php
include_once('inc/config.php');
session_start();
if (!isset($_SESSION['login'])) {
    header('location:login.php');
} else if ($_SESSION['login'] == false) {
    header('location:login.php');
}


$smt = $conexao->prepare('select ds_cargo from Funcionarios where ds_email=? ');
$smt->bind_param('s', $_SESSION['email']);
$smt->execute();

$resultado = $smt->get_result();
$cargo = $resultado->fetch_assoc();
if ($cargo['ds_cargo'] != $_SESSION['cargo']) {
    $_SESSION['cargo'] = $cargo['ds_cargo'];
}
if (isset($_SESSION['cargo'])) {
    if ($_SESSION['cargo'] == 'admin') {

    }

}

$sql = "SELECT COALESCE(SUM(vl_total), 0) AS total FROM vendas";
$resultado = $conexao->query($sql);
$venda = $resultado->fetch_assoc();
$totalVendas = $venda['total'];


$sql = "SELECT COUNT(*) AS produto FROM produtos";
$resultado = $conexao->query($sql);
$produto = $resultado->fetch_assoc();
$quantidadeProdutos = $produto['produto'];


$sql = "SELECT COUNT(*) AS quantidade FROM compras";
$resultado = $conexao->query($sql);
$compra = $resultado->fetch_assoc();
$quantidadeCompras = $compra['quantidade'];

$sql = "SELECT COUNT(*) AS quantidade FROM funcionarios";
$resultado = $conexao->query($sql);
$funcionario = $resultado->fetch_assoc();
$quantidadeFuncionarios = $funcionario['quantidade'];
$sql = "SELECT 
            DATE(dt_venda) AS dia, 
            SUM(vl_total) AS valor
        FROM vendas
        GROUP BY DATE(dt_venda)
        ORDER BY dia ASC";

$resultado = $conexao->query($sql);

$vendasGrafico = [];

while ($linha = $resultado->fetch_assoc()) {
    $vendasGrafico[] = $linha;
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Estoque</title>
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
        }

        .card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            margin-left: 20px !important;
            margin-bottom: 10px;
        }

        .row .card {
            width: 290px !important;
        }

        .card h2 {
            margin-bottom: 10px;
            color: #f1f5f9;
        }

        .card p {
            color: #94a3b8;
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
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script type="text/javascript">
        google.charts.load('current', { 'packages': ['corechart'] });
        google.charts.setOnLoadCallback(drawChart);

        function drawChart() {
       var data = google.visualization.arrayToDataTable([
    ['Dia', 'Vendas'],

    <?php foreach ($vendasGrafico as $venda): ?>
        ['<?= date('d/m', strtotime($venda['dia'])) ?>', <?= (float)$venda['valor'] ?>],
    <?php endforeach; ?>

]);

            var options = {
                title: '',
                curveType: 'function',
                legend: {
                    position: 'bottom',
                    textStyle: { color: '#cbd5e1' }
                },
                backgroundColor: 'transparent',
                titleTextStyle:
                {
                    color: '#f1f5f9',
                    fontSize: 18
                },
                hAxis: {
                    textStyle:
                    {
                        color: '#94a3b8'
                    },
                    gridlines:
                    {
                        color: 'transparent'
                    }
                },
                vAxis:
                {
                    textStyle:
                    {
                        color: '#94a3b8'
                    },
                    gridlines:
                    {
                        color: '#334155'
                    }
                },
               
            };

            
            var chart = new google.visualization.LineChart(document.getElementById('curve_chart'));

            chart.draw(data, options);
        }
    </script>
</head>

<body>
    <nav class="navbar">
        <div class="logo">📦 Estoque</div>
        <div class="usuario">
            <div class="usuario-info">
                <div class="usuario-nome">
                    <?php echo $_SESSION['nome'] ?? 'Usuário'; ?>
                </div>
                <div class="usuario-cargo">
                    <?php echo $_SESSION['cargo'] ?? 'Funcionário'; ?>
                </div>
            </div>
            <a href="logout.php" class="btn-sair">Sair</a>
        </div>
    </nav>

    <aside class="sidebar">
        <div class="menu">
            <div class="menu-titulo">Menu
            </div>
            <a href="index.php" class="ativo">
                <span class="icone">🏠</span>
                <span>Dashboard</span>
            </a>
            <a href="produtos.php">
                <span class="icone">📦</span>
                <span>Produtos</span>
            </a>
            <?php if (
                isset($_SESSION['cargo']) && ($_SESSION['cargo'] == 'admin' || $_SESSION['cargo'] ==
                    'estoquista')
            ): ?>
                <a href="categorias.php">
                    <span class="icone">🏷️</span>
                    <span>Categorias</span>
                </a>
            <?php endif; ?>
            <a href="compras.php">
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
                <h1>Dashboard</h1>
                <p>Visão geral do sistema de estoque.</p>
            </div>
        </div>
        <div class="row">
            <div class="card ml-4 ">
                <p>Total em Vendas</p>
                <h4>R$
                    <?= number_format($totalVendas, 2, ',', '.') ?>
                </h4>
                <p>Valor Total Das Vendas</p>
            </div>
            <div class="card ml-4">
                <p>Produtos</p>
                <h4>
                    <?= htmlspecialchars($quantidadeProdutos) ?>
                </h4>
                <p>Produtos Cadastrados</p>
            </div>
            <div class="card ml-4">

                <p>Compras</p>
                <h4>
                    <?= htmlspecialchars($quantidadeCompras) ?>
                </h4>
                <p>Compras Registradas</p>
            </div>
            <div class="card ml-4">

                <p>Funcionarios</p>
                <h4>
                    <?= htmlspecialchars($quantidadeFuncionarios) ?>
                </h4>
                <p>Funcionarios Cadastrados</p>
            </div>
        </div>
        <div class="card ml-4">
            <div id="curve_chart" style="width: 100%; height: 500px;"></div>
        </div>

    </main>
</body>

</html>