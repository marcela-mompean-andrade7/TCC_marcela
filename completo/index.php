<?php require 'config.php';
if (!empty($_SESSION['user_id'])) redirect('dashboard.php');
?>

<!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>MathPlay Solutions</title>
    <link rel="stylesheet" href="assets/style.css">
</head>

<body>
    <header class="nav">
      <b>🎮 MathPlay</b>
      <nav>
        <a href="login.php">Entrar</a>
        < a class="btn" href="cadastro.php">Criar conta</a>
      </nav>
    </header>
</body>