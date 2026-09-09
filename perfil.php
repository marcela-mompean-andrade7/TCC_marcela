<?php require 'config.php';
login_required();
$q = $pdo->prepare('SELECT * FROM users WHERE id=?');
$q->execute([$_SESSION['user_id']]);
$u = $q->fetch(); ?>
<!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Perfil</title>
    <link rel="stylesheet" href="assets/style.css">
</head>

<body>
    <header class="nav"><b>🎮 MathPlay</b>
        <nav><a href="dashboard.php">Painel</a><a href="logout.php">Sair</a></nav>
    </header>
    <main class="container narrow">
        <div class="card">
            <h1>👤 Meu perfil</h1>
            <p><b>Nome:</b> <?= e($u['name']) ?></p>
            <p><b>E-mail:</b> <?= e($u['email']) ?></p>
            <p><b>Tipo:</b> <?= e($u['role']) ?></p>
            <p><b>Série:</b> <?= e($u['serie'] ?? 'Não se aplica') ?></p>
            <p><b>Região:</b> <?= e($u['regiao']) ?></p>
            <p><b>Escola:</b> <?= e($u['escola']) ?></p>
            <p><b>Pontos:</b> ⭐ <?= e((string)$u['points']) ?></p>
        </div>
    </main>
</body>

</html>