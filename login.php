<?php
require 'config.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  check_csrf();
  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';
  $q = $pdo->prepare('SELECT * FROM users WHERE email=? LIMIT 1');
  $q->execute([$email]);
  $u = $q->fetch();
  if ($u && password_verify($password, $u['password_hash'])) {
    session_regenerate_id(true);
    $_SESSION['user_id'] = $u['id'];
    $_SESSION['role'] = $u['role'];
    $_SESSION['name'] = $u['name'];
    redirect('dashboard.php');
  } else $error = 'E-mail ou senha incorretos.';
}
?>
<!doctype html>
<html lang="pt-BR">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Entrar</title>
  <link rel="stylesheet" href="assets/style.css">
</head>

<body>
  <main class="auth card"><a href="index.php">← Início</a>
    <h1>Entrar</h1><?php if ($error): ?><div class="alert"><?= e($error) ?></div><?php endif; ?><?php if (isset($_GET['ok'])): ?><div class="success">Cadastro realizado! Faça seu login.</div><?php endif; ?>
    <form method="post"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><label>E-mail<input type="email" name="email" required></label><label>Senha<input type="password" name="password" required></label><button class="btn full">Entrar</button></form>
    <p>Não tem conta? <a href="cadastro.php">Cadastrar</a></p>
  </main>
</body>

</html>