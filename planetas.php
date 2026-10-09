<?php
require 'config.php';
login_required();

if (($_SESSION['role'] ?? '') !== 'aluno') {
    redirect('dashboard.php');
}

$q = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
$q->execute([$_SESSION['user_id']]);
$u = $q->fetch();

$planetArtwork = [
    'terra' => ['path' => 'assets/planets/terra.jpg', 'alt' => 'Terra vista do espaço', 'credit' => 'NASA / Apollo 17'],
    'marte' => ['path' => 'assets/planets/marte.jpg', 'alt' => 'Superfície e relevo de Marte vistos do espaço', 'credit' => 'NASA / JPL / USGS'],
    'jupiter' => ['path' => 'assets/planets/jupiter.jpg', 'alt' => 'Júpiter com suas faixas atmosféricas e luas', 'credit' => 'NASA / JPL'],
    'saturno' => ['path' => 'assets/planets/saturno.jpg', 'alt' => 'Saturno e seus anéis vistos pela Voyager 2', 'credit' => 'NASA / JPL'],
    'netuno' => ['path' => 'assets/planets/netuno.jpg', 'alt' => 'Netuno e suas nuvens vistos pela Voyager 2', 'credit' => 'NASA / JPL'],
    'urano' => ['path' => 'assets/planets/urano.jpg', 'alt' => 'Urano em cores naturais visto pela Voyager 2', 'credit' => 'NASA / JPL'],
    'sol' => ['path' => 'assets/planets/sol.jpg', 'alt' => 'Superfície e plasma solar observados pelo SDO', 'credit' => 'NASA / GSFC / SDO / AIA'],
];

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
<html lang="<?= e(current_language()) ?>" <?= accessibility_html_attributes() ?>>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="theme-color" content="#F5F7FF">
    <title>Planetas</title>
    <link rel="stylesheet" href="assets/style.css">
    <script src="assets/theme.js"></script>
</head>

<body>
    <header class="nav">
        <a class="nav-brand" href="dashboard.php"><img src="logo.png" alt="" aria-hidden="true">Lunaris Academy</a>
        <nav lang="<?= e(current_language()) ?>">
            <a href="dashboard.php"><?= e(t('Painel')) ?></a>
            <a href="universo.php">🌌 <?= e(t('Universo')) ?></a>
            <a href="missoes.php">🚀 <?= e(t('Missões')) ?></a>
            <a href="planetas.php" aria-current="page">🪐 <?= e(t('Planetas')) ?></a>
            <a href="avatares.php">🎭 <?= e(t('Avatares')) ?></a>
            <a href="perfil.php">🏅 <?= e(t('Perfil')) ?></a>
            <a href="logout.php"><?= e(t('Sair')) ?></a>
        </nav>
    </header>

    <main class="container narrow planet-page">
        <section class="planet-shell">
            <div class="page-header">
                <div>
                    <span class="tag">🪐 Planetas</span>
                    <h1>Loja de planetas</h1>
                    <p class="page-description">Amplie sua coleção e descubra novos mundos com os pontos conquistados.</p>
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
                    $artwork = $planetArtwork[$planet['slug']] ?? null;
                ?>
                    <article class="planet-card planet-card--<?= e($planet['slug']) ?> <?= $owned ? 'owned' : ($canAfford ? 'available' : 'locked') ?>">
                        <div class="planet-visual">
                            <?php if ($artwork): ?>
                                <img src="<?= e($artwork['path']) ?>" alt="<?= e($artwork['alt']) ?>" width="240" height="240" loading="lazy" decoding="async">
                                <span class="planet-image-credit"><?= e($artwork['credit']) ?></span>
                            <?php else: ?>
                                <span class="planet-artwork-pending" role="img" aria-label="Ilustração de <?= e($planet['name']) ?> em preparação">Ilustração em preparação</span>
                            <?php endif; ?>
                        </div>
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
