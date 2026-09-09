<?php
require 'config.php';
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  check_csrf();
  $name = trim($_POST['name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';
  $role = $_POST['role'] ?? '';
  $serie = $_POST['serie'] ?? null;
  $regiao = $_POST['regiao'] ?? '';
  $escola = trim($_POST['escola'] ?? '');
  if (mb_strlen($name) < 2) $errors[] = 'Informe um nome válido.';
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Informe um e-mail válido.';
  if (strlen($password) < 8) $errors[] = 'A senha precisa ter pelo menos 8 caracteres.';
  if (!in_array($role, ['aluno', 'professor'], true)) $errors[] = 'Escolha aluno ou professor.';
  if ($role === 'aluno' && !in_array($serie, ['6º ano', '7º ano', '8º ano', '9º ano'], true)) $errors[] = 'Escolha sua série.';
  if (!in_array($regiao, ['Norte', 'Nordeste', 'Centro-Oeste', 'Sudeste', 'Sul'], true)) $errors[] = 'Escolha sua região.';
  if ($escola === '') $errors[] = 'Informe sua escola.';
  if (!$errors) {
    $q = $pdo->prepare('SELECT id FROM users WHERE email=?');
    $q->execute([$email]);
    if ($q->fetch()) $errors[] = 'Este e-mail já está cadastrado.';
    else {
      $q = $pdo->prepare('INSERT INTO users(name,email,password_hash,role,serie,regiao,escola) VALUES(?,?,?,?,?,?,?)');
      $q->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $role, $role === 'aluno' ? $serie : null, $regiao, $escola]);
      redirect('login.php?ok=1');
    }
  }
}
?>
<!doctype html>
<html lang="pt-BR">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Cadastro</title>
  <link rel="stylesheet" href="assets/style.css">
</head>

<body>
  <main class="auth card"><a href="index.php">← Início</a>
    <h1>Criar conta</h1>
    <p>Preencha seus dados para entrar no MathPlay.</p>
    <?php foreach ($errors as $x): ?><div class="alert"><?= e($x) ?></div><?php endforeach; ?>
    <form method="post"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
      <label>Nome<input name="name" required maxlength="120" value="<?= e($_POST['name'] ?? '') ?>"></label>
      <label>E-mail<input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>"></label>
      <label>Senha<input type="password" name="password" minlength="8" required></label>
      <label>Tipo de conta<select name="role" id="role" required>
          <option value="">Selecione</option>
          <option value="aluno">Aluno</option>
          <option value="professor">Professor</option>
        </select></label>
      <label id="serieField">Série<select name="serie">
          <option value="">Selecione</option>
          <option>6º ano</option>
          <option>7º ano</option>
          <option>8º ano</option>
          <option>9º ano</option>
        </select></label>
      <label>Região<select name="regiao" required>
          <option value="">Selecione</option>
          <option>Norte</option>
          <option>Nordeste</option>
          <option>Centro-Oeste</option>
          <option>Sudeste</option>
          <option>Sul</option>
        </select></label>
      <label>Escola<input name="escola" required maxlength="180" value="<?= e($_POST['escola'] ?? '') ?>"></label>
      <button class="btn full">Cadastrar</button>
    </form>
    <p>Já possui conta? <a href="login.php">Entrar</a></p>
  </main>
  <script>
    const r = document.getElementById('role'),
      s = document.getElementById('serieField');

    function f() {
      s.style.display = r.value === 'professor' ? 'none' : 'grid'
    }
    r.addEventListener('change', f);
    f();
  </script>
</body>

</html>