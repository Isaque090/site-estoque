<?php
session_start();
if (!isset($_SESSION['login'])) {
    header('location:login.php');
} else if ($_SESSION['login'] == false) {
    header('location:login.php');
}


if (isset($_SESSION['cargo'])){
    if($_SESSION['cargo'] == 'admin') {
        
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

    <div>
        <h1>Categorias</h1>
    </div>
</body>

</html>