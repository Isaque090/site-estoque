<?php
session_start();
include_once('inc/config.php');

if (isset($_POST['entar'])) {
    $smt = $conexao->prepare('select * from Funcionarios where ds_email=? and ds_senha=?');
    $smt->bind_param('ss', $_POST['email'], $_POST['senha']);
    $smt->execute();

    $resultado = $smt->get_result();
    if ($resultado->num_rows > 0) {
        $funcionario = $resultado->fetch_assoc();
        if ($funcionario['status'] == "ativo") {

            $_SESSION['email'] = $funcionario['ds_email'];
            $_SESSION['cargo'] = $funcionario['ds_cargo'];
            $_SESSION['nome']=$funcionario['nm_funcionario'];
            $_SESSION['login']=true;
            header('location:index.php');
        } else {
            echo "Conta Deletada fale com algum administrador";
        }


    } else {
        echo "nao existe";
    }

}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>

<body>
    <form action="" method="post">
        <label for="email">Email</label>
        <input name="email" type="email" required>
        <label for="senha">Senha</label>
        <input type="password" name="senha" required>
        <input type="submit" name="entar">
    </form>
</body>

</html>