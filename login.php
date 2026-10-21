<?php
require_once __DIR__ . '/config.php';
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
    set_language($u['preferred_language'] ?? 'pt-BR');
    $_SESSION['lunaris_accessibility'] = normalize_accessibility_preferences($u['accessibility_needs'] ?? null);
    $_SESSION['lunaris_preferences_loaded'] = true;

    if (($u['role'] ?? '') === 'aluno') {
      ensure_student_starter_planet($pdo, (int)$u['id']);
    }

    redirect('dashboard.php');
  } else $error = 'E-mail ou senha incorretos.';
}
?>
<!doctype html>
<html lang="<?= e(current_language()) ?>" <?= accessibility_html_attributes() ?>>

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= e(t('Entrar')) ?> — Lunaris Academy</title>
  <link rel="stylesheet" href="<?= e(application_asset_url('css/style.css')) ?>">
  <script src="<?= e(application_asset_url('js/theme.js')) ?>"></script>
</head>

<body>
  <main class="auth card"><a href="index.php"><?= e(t('← Início')) ?></a>
    <h1><?= e(t('Entrar')) ?></h1><?php if ($error): ?><div class="alert" role="alert"><?= e(t($error)) ?></div><?php endif; ?><?php if (isset($_GET['ok'])): ?><div class="success"><?= e(t('Cadastro realizado! Faça seu login.')) ?></div><?php endif; ?>
    <form method="post"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><label><?= e(t('E-mail')) ?><input type="email" name="email" required></label><label><?= e(t('Senha')) ?><input type="password" name="password" required></label><button class="btn full"><?= e(t('Entrar')) ?></button></form>
    <p><?= e(t('Não tem conta?')) ?> <a href="cadastro.php"><?= e(t('Cadastrar')) ?></a></p>
  </main>
</body>

</html>