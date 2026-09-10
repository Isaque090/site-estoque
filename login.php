<?php
session_start();
include_once('inc/config.php');

$nExiste = '';

if (isset($_POST['entrar'])) {
    $smt = $conexao->prepare('select * from Funcionarios where ds_email=? and ds_senha=?');
    $smt->bind_param('ss', $_POST['email'], $_POST['senha']);
    $smt->execute();

    $resultado = $smt->get_result();
    if ($resultado->num_rows > 0) {
        $funcionario = $resultado->fetch_assoc();
        if ($funcionario['status'] == "ativo") {

            $_SESSION['email'] = $funcionario['ds_email'];
             $_SESSION['id'] = $funcionario['cd_funcionario'];
            $_SESSION['cargo'] = $funcionario['ds_cargo'];
            $_SESSION['nome']=$funcionario['nm_funcionario'];
            $_SESSION['login']=true;
            header('location:index.php');
        } else {
            $nExiste  ="Conta Deletada, fale com algum administrador";
        }


    } else {
    $nExiste = "Email ou senha incorretos";
    }

}

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #0f172a, #1e293b);
        }
        
        .login-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 1rem;
        }
        
        .form-control {
            background-color: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(148, 163, 184, 0.3);
            color: #f1f5f9;
        }
        
        .form-control:focus {
            background-color: rgba(15, 23, 42, 0.8);
            border-color: #0ea5e9;
            box-shadow: 0 0 0 0.25rem rgba(14, 165, 233, 0.25);
            color: #f1f5f9;
        }
        
        .form-control::placeholder {
            color: #64748b;
        }
        
        .form-label {
            color: #cbd5e1;
        }

        .alert{
            background-color: rgba(15, 23, 42, 0.8);
            border-color: #0ea5e9;
            box-shadow: 0 0 0 0.25rem rgba(0, 31, 46, 0.25);
            color: #dd3939;
           
        }
        .ButtonAlert{
            background-color: rgba(0, 32, 105, 0.8);
            border-color: #0ea5e9;
            box-shadow: 0 0 0 0.25rem rgba(3, 156, 227, 0);
            color: #ff0000;
           
        }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center">

  <div class="container">
    <div class="row justify-content-center">
        <div class="col-12 col-sm-10 col-md-6 col-lg-4">
            
            <div class="card login-card shadow-lg p-4">
                <div class="card-body">
                    <h2 class="text-center text-white mb-1">Login do Estoque</h2>
                    <p class="text-center text-secondary mb-4">Cadastro para vereficar se vc é estoquista, gerente ou admin</p>

                    <form action="" method="post">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control form-control-lg" id="email" name="email" placeholder="seu@email.com" required>
                        </div>

                        <div class="mb-4">
                            <label for="senha" class="form-label">Senha</label>
                            <input type="password" class="form-control form-control-lg" id="senha" name="senha" placeholder="Senha" required>
                        </div>

                        
                        <button type="submit" name="entrar" class="btn btn-primary btn-lg w-100">
                            Entrar
                        </button>

                     <?php if (!empty($nExiste)): ?>
                       <div class="alert alert-danger alert-dismissible fade show mt-3 mb-0" role="alert">
                         <?php echo $nExiste; ?>
                         <button type="button" class="btn-close ButtonAlert" data-bs-dismiss="alert" aria-label="Fechar"></button>
                        </div>
                     <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>