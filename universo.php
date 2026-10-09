<?php
require 'config.php';
login_required();

if (($_SESSION['role'] ?? '') !== 'aluno') {
    redirect('dashboard.php');
}

$studentId = (int)$_SESSION['user_id'];
$q = $pdo->prepare('SELECT a.name AS avatar_name, a.icon, a.image_path, u.name AS student_name FROM student_avatar_selection s JOIN avatars a ON a.id = s.avatar_id JOIN users u ON u.id = s.student_id WHERE s.student_id = ?');
$q->execute([$studentId]);
$avatar = $q->fetch();

$q = $pdo->prepare('SELECT COUNT(*) FROM student_planets WHERE student_id = ?');
$q->execute([$studentId]);
$planetCount = (int)$q->fetchColumn();

$q = $pdo->prepare('SELECT COUNT(*) FROM student_avatars WHERE student_id = ?');
$q->execute([$studentId]);
$avatarCount = (int)$q->fetchColumn();

$q = $pdo->prepare('SELECT COUNT(*) FROM mission_rewards WHERE student_id = ?');
$q->execute([$studentId]);
$completedMissionCount = (int)$q->fetchColumn();
$q = $pdo->query('SELECT COUNT(*) FROM missions');
$missionCount = (int)$q->fetchColumn();
?>
<!doctype html>
<html lang="<?= e(current_language()) ?>" <?= accessibility_html_attributes() ?>>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="theme-color" content="#F5F7FF">
    <title>Universo Lunaris</title>
    <link rel="stylesheet" href="assets/style.css">
    <script src="assets/theme.js"></script>
</head>

<body>
    <header class="nav">
        <a class="nav-brand" href="dashboard.php"><img src="logo.png" alt="" aria-hidden="true">Lunaris Academy</a>
        <nav lang="<?= e(current_language()) ?>">
            <a href="dashboard.php"><?= e(t('Painel')) ?></a>
            <a href="universo.php" aria-current="page">🌌 <?= e(t('Universo')) ?></a>
            <a href="missoes.php">🚀 <?= e(t('Missões')) ?></a>
            <a href="planetas.php">🪐 <?= e(t('Planetas')) ?></a>
            <a href="avatares.php">🎭 <?= e(t('Avatares')) ?></a>
            <a href="perfil.php">🏅 <?= e(t('Perfil')) ?></a>
            <a href="logout.php"><?= e(t('Sair')) ?></a>
        </nav>
    </header>

    <main class="container narrow universe-page">
        <section class="universe-shell">
            <div class="universe-hero">
                <img class="universe-hero-icon universe-hero-portrait" src="<?= e(($avatar['image_path'] ?? '') ?: 'assets/avatars/luna.svg') ?>" alt="Avatar atual: <?= e($avatar['avatar_name'] ?? 'Luna — Guardiã Lunar') ?>" width="112" height="130">
                <div>
                    <span class="tag">🌌 Universo Lunaris</span>
                    <h1>Sua jornada continua, <?= e($avatar['student_name'] ?? 'explorador') ?>!</h1>
                    <p>Explore novos mundos, personalize seu avatar e acompanhe tudo o que conquistou.</p>
                </div>
            </div>

            <div class="universe-stats">
                <span>🪐 <strong><?= $planetCount ?></strong> planetas</span>
                <span>🎭 <strong><?= $avatarCount ?></strong> avatares</span>
            </div>

            <div class="universe-grid">
                <a class="universe-card" href="planetas.php">
                    <span role="img" aria-label="Planeta">🪐</span>
                    <h2>Planetas</h2>
                    <p>Desbloqueie novos mundos usando seus pontos.</p>
                    <strong>Visitar loja <span aria-hidden="true">→</span></strong>
                </a>
                <a class="universe-card" href="missoes.php">
                    <span role="img" aria-label="Missões">🚀</span>
                    <h2>Missões espaciais</h2>
                    <p>Resolva desafios matemáticos e conquiste 25 estrelas por missão.</p>
                    <strong><?= $completedMissionCount ?> de <?= $missionCount ?> concluídas <span aria-hidden="true">→</span></strong>
                </a>
                <a class="universe-card" href="avatares.php">
                    <span role="img" aria-label="Avatar">🎭</span>
                    <h2>Avatares</h2>
                    <p>Escolha a identidade que vai acompanhar suas aventuras.</p>
                    <strong>Personalizar avatar <span aria-hidden="true">→</span></strong>
                </a>
                <a class="universe-card" href="perfil.php">
                    <span role="img" aria-label="Coleção">🏅</span>
                    <h2>Minha coleção</h2>
                    <p>Veja seus planetas, avatares e seu progresso.</p>
                    <strong>Abrir perfil <span aria-hidden="true">→</span></strong>
                </a>
                <article class="universe-card universe-coming-soon" aria-label="Constelações, em breve">
                    <span role="img" aria-label="Constelação">✨</span>
                    <h2>Constelações</h2>
                    <p>Novos desafios e descobertas cósmicas chegarão em breve.</p>
                    <strong>Em breve</strong>
                </article>
            </div>
        </section>
    </main>
</body>

</html>
