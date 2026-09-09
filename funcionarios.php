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


$smt = $conexao->prepare('select ds_cargo,status from Funcionarios where ds_email=? ');
$smt->bind_param('s', $_SESSION['email']);
$smt->execute();

$resultado = $smt->get_result();
$cargo = $resultado->fetch_assoc();
if ($cargo['status'] != "ativo") {
    header('location:logout.php');
    exit;
}

if ($cargo['ds_cargo'] != "admin") {
    header('location:index.php');
    exit;
}
if ($cargo['ds_cargo'] != $_SESSION['cargo']) {
    $_SESSION['cargo'] = $cargo['ds_cargo'];

}
//caso queserselecionar so os funcionarios que tem conta ativa so usar where status="ativo"

$sql = "SELECT * FROM Funcionarios";
$result = $conexao->query($sql);


if (isset($_POST['cadastrar'])) {
    $nome = $_POST['nm_funcionario'];
    $dt_nascimento = $_POST['dt_nascimento'];
    $senha = $_POST['ds_senha'];
    $email = $_POST['ds_email'];
    $cargo = $_POST['ds_cargo'];
    $telefone = $_POST['ds_telefone'];

    $enviar = $conexao->prepare('insert into funcionarios (nm_funcionario, dt_nascimento, ds_telefone, ds_email, ds_cargo, ds_senha) 
VALUES (?, ?, ?, ?, ?,?)');
    $enviar->bind_param('ssssss', $nome, $dt_nascimento, $telefone, $email, $cargo, $senha);
    if ($enviar->execute()) {

        $_SESSION['mensagemJs'] = "alert('Funcionario Cadastrado com Sucesso')";
    } else {

        $_SESSION['mensagemJs'] = "alert('ERRO ao cadstrar Funcionario')";
    }
    header('location:funcionarios.php');
    exit;
}

if (isset($_POST['editar'])) {
    $nome = $_POST['nm_funcionario'];
    $dt_nascimento = $_POST['dt_nascimento'];
    $senha = $_POST['ds_senha'];
    $email = $_POST['ds_email'];
    $cargo = $_POST['ds_cargo'];
    $telefone = $_POST['ds_telefone'];
    $status = $_POST['status'];
    $id = $_POST['cd_funcionario'];

    $editar = $conexao->prepare('UPDATE funcionarios SET nm_funcionario = ?, dt_nascimento = ?, ds_telefone = ?, ds_email = ?, ds_cargo = ?, ds_senha = ?, status = ? WHERE cd_funcionario = ?');
    $editar->bind_param('sssssssi', $nome, $dt_nascimento, $telefone, $email, $cargo, $senha, $status, $id);

    if ($editar->execute()) {


        $_SESSION['mensagemJs'] = "alert('Funcionario Editado com Sucesso')";
    } else {


        $_SESSION['mensagemJs'] = "alert('ERRO ao Editar Funcionario')";
    }
    header('location:funcionarios.php');
    exit;
}


if (isset($_POST['excluir'])) {
    $id = $_POST['id'];
    $status = "inativo";
    $excluir = $conexao->prepare('UPDATE funcionarios SET status = ? where cd_funcionario=?');
    $excluir->bind_param('si', $status, $id);
    if ($excluir->execute()) {
        $_SESSION['mensagemJs'] = "alert('Funcionario Excluido com Sucesso')";
    }else{
         $_SESSION['mensagemJs'] = "alert('ERRO ao Excluir Funcionario')";
    }
    header('location:funcionarios.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categorias - Estoque</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css"
        integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">

    <link rel="stylesheet" href="css/funcionario.css">
</head>

<body>
    <nav class="navbar">
        <div class="logo"> 📦 Estoque </div>
        <div class="usuario">
            <div class="usuario-info">
                <div class="usuario-nome"> <?php echo $_SESSION['nome'] ?? 'Usuário'; ?> </div>
                <div class="usuario-cargo"> <?php echo $_SESSION['cargo'] ?? 'Funcionário'; ?> </div>
            </div> <a href="logout.php" class="btn-sair"> Sair </a>
        </div>
    </nav>
    <aside class="sidebar">
        <div class="menu">
            <div class="menu-titulo"> Menu </div> <a href="index.php"> <span class="icone">🏠</span>
                <span>Dashboard</span> </a> <a href="produtos.php"> <span class="icone">📦</span> <span>Produtos</span>
            </a>
            <?php if (isset($_SESSION['cargo']) && $_SESSION['cargo'] == 'admin' || $_SESSION['cargo'] == 'estoquista'): ?>
                <a href="categorias.php"> <span class="icone">🏷️</span>
                    <span>Categorias</span> </a>
            <?php endif ?>
            <a href="compras.php"> <span class="icone">🛒</span> <span>Compras</span> </a> <a href="vendas.php"> <span
                    class="icone">💰</span> <span>Vendas</span>

            </a> <?php if (isset($_SESSION['cargo']) && $_SESSION['cargo'] == 'admin'): ?>
                <a href="funcionarios.php" class="ativo"> <span class="icone">👥</span>
                    <span>Funcionários</span>
                </a>
            <?php endif ?>
        </div>
    </aside>
    <main class="conteudo">
        <div class="cabecalho-pagina">
            <div>
                <h1>Dashboard</h1>
                <p> Gerencie as Dashboard dos produtos. </p>
            </div>
            <button type="button" class="btn btn-adicionar btn-sm" sty data-toggle="modal"
                data-target="#modalNovoFuncionario">
                + Novo Funcionario
            </button>
        </div>

        <table class="table table-striped table-hover">
            <thead class="thead-dark">
                <tr>
                    <th>Código</th>
                    <th>Nome</th>
                    <th>Data nascimento</th>
                    <th>Telefone</th>
                    <th>Email</th>
                    <th>Cargo</th>
                    <th>Status</th>
                    <th>ds_senha</th>


                    <th width="180">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($linha = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($linha['cd_funcionario']) ?></td>
                            <td><?= htmlspecialchars($linha['nm_funcionario']) ?></td>
                            <td><?= htmlspecialchars($linha['dt_nascimento']) ?></td>
                            <td><?= htmlspecialchars($linha['ds_telefone']) ?></td>
                            <td><?= htmlspecialchars($linha['ds_email']) ?></td>
                            <td><?= htmlspecialchars($linha['ds_cargo']) ?></td>
                            <td><?= htmlspecialchars($linha['status']) ?></td>
                            <td><?= htmlspecialchars($linha['ds_senha']) ?></td>


                            <td>
                                <div class="row">
                                    <form action="funcionarios.php" method="post">
                                        <input type="hidden" name="id" value="<?= $linha['cd_funcionario'] ?>">
                                        <button type="submit" name="excluir" class="btn btn-danger btn-sm"
                                            onclick="return confirm('Tem certeza que deseja excluir?')">Excluir</button>

                                    </form>

                                    <button type="button" class="btn btn-warning btn-editar btn-sm ml-3"
                                        data-id="<?= $linha['cd_funcionario'] ?>"
                                        data-nome="<?= htmlspecialchars($linha['nm_funcionario']) ?>"
                                        data-nascimento="<?= $linha['dt_nascimento'] ?>"
                                        data-telefone="<?= htmlspecialchars($linha['ds_telefone']) ?>"
                                        data-email="<?= htmlspecialchars($linha['ds_email']) ?>"
                                        data-cargo="<?= $linha['ds_cargo'] ?>" data-status="<?= $linha['status'] ?>"
                                        data-toggle="modal" data-target="#modalEditarFuncionario"> Editar </button>


                                </div>
                            </td>
                        </tr>


                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="text-center">Nenhum materia cadastrado.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </main>


    <div class="modal fade" id="modalNovoFuncionario" tabindex="-1" role="dialog"
        aria-labelledby="modalNovoFuncionarioLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content" style="color:#000;">
                <form method="post" action="funcionarios.php">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalNovoFuncionarioLabel">
                            Novo funcionário
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form action="funcionarios.php">
                            <div class="form-group">
                                <label for="nm_funcionario">Nome</label>
                                <input type="text" class="form-control" name="nm_funcionario" required>
                            </div>
                            <div class="form-group">
                                <label for="dt_nascimento">Data de nascimento</label>
                                <input type="date" class="form-control" name="dt_nascimento" required>
                            </div>
                            <div class="form-group">
                                <label for="ds_telefone">Telefone</label>
                                <input type="text" class="form-control" name="ds_telefone"
                                    placeholder="(11) 99999-9999">
                            </div>
                            <div class="form-group">
                                <label for="ds_email">E-mail</label>
                                <input type="email" class="form-control" name="ds_email" required>
                            </div>
                            <div class="form-group">
                                <label for="ds_cargo">Cargo</label>
                                <select class="form-control" name="ds_cargo" required>
                                    <option value="">Selecione um cargo</option>
                                    <option value="admin">Administrador </option>
                                    <option value="vendedor">Vendedor</option>
                                    <option value="estoquista">Estoquista</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="ds_senha">Senha</label>
                                <input type="password" class="form-control" name="ds_senha" required>
                            </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            Cancelar
                        </button>
                        <button type="submit" name="cadastrar" class="btn btn-primary">
                            Adicionar
                        </button>
                </form>
            </div>
            </form>
        </div>
    </div>
    </div>


    <div class="modal fade" id="modalEditarFuncionario" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content" style="color:#000;">
                <form method="post" action="funcionarios.php">
                    <div class="modal-header">
                        <h5 class="modal-title"> Editar funcionário </h5> <button type="button" class="close"
                            data-dismiss="modal"> <span>&times;</span> </button>
                    </div>
                    <div class="modal-body">
                        <form method="post">
                            <input type="hidden" name="cd_funcionario" id="editar_id">
                            <div class="form-group">
                                <label> Nome </label>
                                <input type="text" class="form-control" name="nm_funcionario" id="editar_nome" required>
                            </div>
                            <div class="form-group">
                                <label> Data de nascimento </label>
                                <input type="date" class="form-control" name="dt_nascimento" id="editar_nascimento"
                                    required>
                            </div>
                            <div class="form-group">
                                <label> Telefone </label>
                                <input type="text" class="form-control" name="ds_telefone" id="editar_telefone">
                            </div>
                            <div class="form-group">
                                <label> E-mail </label>
                                <input type="email" class="form-control" name="ds_email" id="editar_email" required>
                            </div>
                            <div class="form-group">
                                <label> Cargo </label>
                                <select class="form-control" name="ds_cargo" id="editar_cargo" required>
                                    <option value="admin"> Administrador </option>
                                    <option value="vendedor"> Vendedor </option>
                                    <option value="estoquista"> Estoquista </option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label> Status </label>
                                <select class="form-control" name="status" id="editar_status" required>
                                    <option value="ativo"> Ativo </option>
                                    <option value="inativo"> Inativo </option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label> Nova senha </label>
                                <input type="password" class="form-control" name="ds_senha"
                                    placeholder="Deixe vazio para manter a senha atual"> <small class="text-muted"> Só
                                    preencha se quiser alterar a senha. </small>
                            </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar </button>
                        <button type="submit" name="editar" class="btn btn-primary"> Salvar alterações </button>
                    </div>
                </form>
                </form>
            </div>
        </div>
    </div>


    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const botoesEditar = document.querySelectorAll(".btn-editar");
            botoesEditar.forEach(function (botao) {
                botao.addEventListener("click", function () {
                    const id = this.dataset.id;
                    const nome = this.dataset.nome;
                    const nascimento = this.dataset.nascimento;
                    const telefone = this.dataset.telefone;
                    const email = this.dataset.email;
                    const cargo = this.dataset.cargo;
                    const status = this.dataset.status;

                    document.getElementById("editar_id").value = id;
                    document.getElementById("editar_nome").value = nome;
                    document.getElementById("editar_nascimento").value = nascimento;
                    document.getElementById("editar_telefone").value = telefone;
                    document.getElementById("editar_email").value = email;
                    document.getElementById("editar_cargo").value = cargo;
                    document.getElementById("editar_status").value = status;


                });

            });

        });
        <?php if (isset($_SESSION['mensagemJs'])) {
            echo $_SESSION['mensagemJs'];
            unset($_SESSION['mensagemJs']);
        } ?>
    </script>


    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"
        integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo"
        crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.14.7/dist/umd/popper.min.js"
        integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1"
        crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/js/bootstrap.min.js"
        integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM"
        crossorigin="anonymous"></script>
</body>

</html>