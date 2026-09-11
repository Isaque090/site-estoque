<?php
include_once('inc/config.php');
session_start();
if (!isset($_SESSION['login'])) {
    header('location:login.php');
    exit;
} else if ($_SESSION['login'] == false) {
    header('location:login.php');
    exit;
}


$smt = $conexao->prepare('select ds_cargo,status from funcionarios where ds_email=? ');
$smt->bind_param('s', $_SESSION['email']);
$smt->execute();

$resultado = $smt->get_result();
$cargo = $resultado->fetch_assoc();
if ($cargo['status'] != "ativo") {
    header('location:logout.php');
    exit;
}

if ($cargo['ds_cargo'] == "vendedor" ) {
    header('location:index.php');
    exit;
}
if ($cargo['ds_cargo'] != $_SESSION['cargo']) {
    $_SESSION['cargo'] = $cargo['ds_cargo'];

}


$sql = "SELECT * FROM Categorias";
$result = $conexao->query($sql);


if (isset($_POST['cadastrar'])) {
    $nome = $_POST['nm_categoria'];
    $descricao = $_POST['ds_categoria'];
   

    $enviar = $conexao->prepare('insert into categorias (nm_categoria,  ds_categoria) 
VALUES (?, ?)');
    $enviar->bind_param('ss', $nome, $descricao);
    if ($enviar->execute()) {

        $_SESSION['mensagemJs'] = "alert('categoria Cadastrado com Sucesso')";
    } else {

        $_SESSION['mensagemJs'] = "alert('ERRO ao cadastrar categoria')";
    }
    header('location:categorias.php');
    exit;
}

if (isset($_POST['editar'])) {
    $nome = $_POST['nm_categoria'];
    $descricao = $_POST['ds_categoria'];
    $status = $_POST['status'];
    $id = $_POST['cd_categoria'];

    $editar = $conexao->prepare('UPDATE categorias SET nm_categoria = ?, ds_categoria= ?, status = ?  WHERE cd_categoria = ?');
    $editar->bind_param('sssi', $nome, $descricao,$status, $id);

    if ($editar->execute()) {


        $_SESSION['mensagemJs'] = "alert('categoria Editado com Sucesso')";
    } else {


        $_SESSION['mensagemJs'] = "alert('ERRO ao Editar categoria')";
    }
    header('location:categorias.php');
    exit;
}


if (isset($_POST['excluir'])) {
    $id = $_POST['id'];
    $status = "inativo";
    $excluir = $conexao->prepare('UPDATE categorias SET status = ? where cd_categoria=?');
    $excluir->bind_param('si', $status, $id);
    if ($excluir->execute()) {
        $_SESSION['mensagemJs'] = "alert('categoria Excluido com Sucesso')";
    } else {
        $_SESSION['mensagemJs'] = "alert('ERRO ao Excluir categoria')";
    }
    header('location:categorias.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categorias - Estoque</title><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/categorias.css">
  
</head>

<body>
    <nav class="navbar">
        <div class="logo">Estoque</div>
        <div class="usuario">
            <div class="usuario-info">
                <div class="usuario-nome"><?php echo $_SESSION['nome'] ?? 'Usuário'; ?></div>
                <div class="usuario-cargo"><?php echo $_SESSION['cargo'] ?? 'Funcionário'; ?></div>
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
                <a href="categorias.php" class="ativo">
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
                <a href="categorias.php">
                    <span class="icone">👥</span>
                    <span>Funcionários</span>
                </a>
            <?php endif; ?>
        </div>
    </aside>

    <main class="conteudo">
        <div class="cabecalho-pagina">
            <div>
                <h1>Categorias</h1>
                <p>Gerencie as categorias dos produtos.</p>
            </div>
            <button type="button" class="btn btn-adicionar btn-sm" data-toggle="modal"
                data-target="#modalNovacategoria">
                + Nova categoria
        </div>

       
        
        <table class="table table-striped table-hover mt-5">
            <thead class="thead-dark">
                <tr>
                    <th>Código</th>
                    <th>Nome</th>
                    <th>Descrição</th>
                    <th>Status</th>
                 
                    <th width="180">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($linha = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($linha['cd_categoria']) ?></td>
                            <td><?= htmlspecialchars($linha['nm_categoria']) ?></td>
                            <td><?= htmlspecialchars($linha['ds_categoria']) ?></td>
                                       <td><?= htmlspecialchars($linha['status']) ?></td>
                            
                            <td>
                                <div class="d-flex">
                                    <form action="categorias.php" method="post" class="mr-2">
                                        <input type="hidden" name="id" value="<?= $linha['cd_categoria'] ?>">
                                        <button type="submit" name="excluir" class="btn btn-danger btn-sm"
                                            onclick="return confirm('Tem certeza que deseja excluir?')">Excluir</button>
                                    </form>
                                    <button type="button" class="btn btn-warning btn-editar btn-sm"
                                        data-id="<?= $linha['cd_categoria'] ?>"
                                        data-nome="<?= htmlspecialchars($linha['nm_categoria']) ?>"
                                        data-descricao="<?= htmlspecialchars($linha['ds_categoria']) ?>"
                                        data-status="<?= $linha['status'] ?>"
                                        data-toggle="modal" data-target="#modalEditarcategoria">
                                        Editar
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" class="text-center">Nenhuma Categoria cadastrada.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </main>

    <div class="modal fade" id="modalNovacategoria" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form method="post" action="categorias.php">
                    <div class="modal-header">
                        <h5 class="modal-title">Nova Categoria</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Nome</label>
                            <input type="text" class="form-control" name="nm_categoria" required>
                        </div>
                        <div class="form-group">
                            <label>Descrição</label>
                            <input type="text" class="form-control" name="ds_categoria" required>
                        </div>
                       
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" name="cadastrar" class="btn btn-primary">Adicionar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <div class="modal fade" id="modalEditarcategoria" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form method="post" action="categorias.php">
                    <div class="modal-header">
                        <h5 class="modal-title">Editar Categoria</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="cd_categoria" id="editar_id">
                        <div class="form-group">
                            <label>Nome</label>
                            <input type="text" class="form-control" name="nm_categoria" id="editar_nome" required>
                        </div>
                        <div class="form-group">
                            <label>Descrição</label>
                            <input type="text" class="form-control" name="ds_categoria" id="editar_descricao"
                                required>
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                              <select class="form-control" name="status" id="editar_status" required>
                                <option value="ativo">Ativo</option>
                                <option value="inativo">Inativo</option>
                            </select>
                        </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" name="editar" class="btn btn-primary">Salvar alterações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const botoesEditar = document.querySelectorAll(".btn-editar");
            botoesEditar.forEach(function (botao) {
                botao.addEventListener("click", function () {
                    document.getElementById("editar_id").value = this.dataset.id;
                    document.getElementById("editar_nome").value = this.dataset.nome;
                    document.getElementById("editar_descricao").value = this.dataset.descricao;
                    document.getElementById("editar_status").value = this.dataset.status;
                });
            });
        });
        <?php if (isset($_SESSION['mensagemJs'])) {
            echo $_SESSION['mensagemJs'];
            unset($_SESSION['mensagemJs']);
        } ?>
    </script>

    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.14.7/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/js/bootstrap.min.js"></script>
</body>

</html>