<?php
require 'config.php';
login_required();

if (($_SESSION['role'] ?? '') !== 'aluno') {
    redirect('dashboard.php');
}

$q = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
$q->execute([$_SESSION['user_id']]);
$u = $q->fetch();

$notice = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();

    $planetId = (int)($_POST['planet_id'] ?? 0);

    if ($planetId <= 0) {
        $errors[] = 'Planeta inválido.';
    } else {
        $pdo->beginTransaction();
        try {
            $q = $pdo->prepare('SELECT u.points, p.id, p.name, p.slug, p.description, p.icon, p.price FROM users u JOIN planets p ON p.id = ? WHERE u.id = ? LIMIT 1');
            $q->execute([$planetId, $_SESSION['user_id']]);
            $planet = $q->fetch();

            if (!$planet) {
                throw new RuntimeException('Planeta inexistente.');
            }

            $q = $pdo->prepare('SELECT 1 FROM student_planets WHERE student_id = ? AND planet_id = ? LIMIT 1');
            $q->execute([$_SESSION['user_id'], $planetId]);
            if ($q->fetch()) {
                throw new RuntimeException('Você já possui este planeta.');
            }

            $userPoints = (int)$planet['points'];
            $planetPrice = (int)$planet['price'];

            if ($userPoints < $planetPrice) {
                throw new RuntimeException('Pontos insuficientes para comprar este planeta.');
            }

            $q = $pdo->prepare('UPDATE users SET points = points - ? WHERE id = ? AND points >= ?');
            $q->execute([$planetPrice, $_SESSION['user_id'], $planetPrice]);
            if ($q->rowCount() !== 1) {
                throw new RuntimeException('Não foi possível concluir a compra.');
            }

            $q = $pdo->prepare('INSERT INTO student_planets(student_id, planet_id) VALUES(?, ?)');
            $q->execute([$_SESSION['user_id'], $planetId]);
            if ($q->rowCount() !== 1) {
                throw new RuntimeException('Compra duplicada ou inválida.');
            }

            $pdo->commit();
            $notice = '✨ Parabéns! Você desbloqueou ' . $planet['name'] . '! Seu universo acaba de ficar maior.';

            $q = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
            $q->execute([$_SESSION['user_id']]);
            $u = $q->fetch();
        } catch (Throwable $e) {
            $pdo->rollBack();
            $errors[] = $e->getMessage();
        }
    }
}

$q = $pdo->prepare('SELECT p.*, EXISTS(SELECT 1 FROM student_planets sp WHERE sp.student_id = ? AND sp.planet_id = p.id) AS owned FROM planets p ORDER BY p.display_order ASC');
$q->execute([$_SESSION['user_id']]);
$planets = $q->fetchAll();
?>
<!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Planetas</title>
    <link rel="stylesheet" href="assets/style.css">
    <script src="assets/theme.js"></script>
</head>

<body>
    <header class="nav"><b>MathUp</b>
        <nav><a href="dashboard.php">Painel</a><a href="perfil.php">Perfil</a><a href="logout.php">Sair</a></nav>
    </header>

    <main class="container narrow planet-page">
        <section class="planet-shell">
            <div class="page-header">
                <div>
                    <span class="tag">🪐 Planetas</span>
                    <h1>Loja de planetas</h1>
                </div>
                <div class="points">⭐ <?= e((string)$u['points']) ?> pontos</div>
            </div>

            <?php if ($notice): ?>
                <div class="success-message"><?= e($notice) ?></div>
            <?php endif; ?>

            <?php foreach ($errors as $error): ?>
                <div class="alert"><?= e($error) ?></div>
            <?php endforeach; ?>

            <div class="planet-grid">
                <?php foreach ($planets as $planet):
                    $owned = (bool)$planet['owned'];
                    $planetPrice = (int)$planet['price'];
                    $canAfford = !$owned && $u['points'] >= $planetPrice;
                ?>
                    <article class="planet-card <?= $owned ? 'owned' : ($canAfford ? 'available' : 'locked') ?>">
                        <div class="planet-emoji"><?= e($planet['icon']) ?></div>
                        <h3><?= e($planet['name']) ?></h3>
                        <p><?= e($planet['description']) ?></p>
                        <div class="planet-price"><?= $planetPrice === 0 ? 'GRÁTIS' : e((string)$planetPrice) . ' pontos' ?></div>

                        <?php if ($owned): ?>
                            <span class="planet-status success">✓ Desbloqueado</span>
                            <button type="button" class="btn small disabled" disabled>Desbloqueado</button>
                        <?php elseif ($planetPrice === 0): ?>
                            <span class="planet-status neutral">✓ Desbloqueado</span>
                            <button type="button" class="btn small disabled" disabled>Grátis</button>
                        <?php elseif ($u['points'] < $planetPrice): ?>
                            <span class="planet-status warning">Pontos insuficientes</span>
                            <button type="button" class="btn small light" disabled>Comprar planeta</button>
                        <?php else: ?>
                            <span class="planet-status info">Disponível</span>
                            <form method="post">
                                <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                                <input type="hidden" name="planet_id" value="<?= (int)$planet['id'] ?>">
                                <button type="submit" class="btn small">Comprar planeta</button>
                            </form>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    </main>
</body>

</html>
