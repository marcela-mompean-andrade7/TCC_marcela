<?php
require_once __DIR__ . '/config.php';
login_required();
$q = $pdo->prepare('SELECT * FROM users WHERE id=?');
$q->execute([$_SESSION['user_id']]);
$u = $q->fetch();
$acts = [];
if ($u['role'] === 'aluno') {
    $q = $pdo->prepare("SELECT a.*, u.name professor, s.id as submission_id,
                               (SELECT COUNT(*) FROM activity_progress p WHERE p.activity_id=a.id AND p.student_id=?) AS progress_count
                        FROM activities a
                        JOIN users u ON u.id=a.professor_id
                        LEFT JOIN submissions s ON s.activity_id=a.id AND s.student_id=?
                        WHERE (a.target_school IS NULL OR (a.target_school = ? AND a.target_serie = ?))
                          AND (a.due_date IS NULL OR a.due_date >= CURRENT_DATE)
                        ORDER BY a.created_at DESC LIMIT 12");
    $q->execute([$u['id'], $u['id'], $u['escola'], $u['serie']]);
    $acts = $q->fetchAll();
} else {
    $acts = $pdo->query("SELECT a.*,u.name professor FROM activities a JOIN users u ON u.id=a.professor_id ORDER BY a.created_at DESC LIMIT 12")->fetchAll();
}
$res = [];
$totalPoints = (int)$u['points'];
$currentAvatar = null;
$missionSummary = null;
$recommendedMission = null;
if ($u['role'] === 'aluno') {
    $q = $pdo->prepare("SELECT * FROM game_results WHERE student_id=? ORDER BY played_at DESC LIMIT 10");
    $q->execute([$u['id']]);
    $res = $q->fetchAll();

    $q = $pdo->prepare('SELECT a.slug, a.name, a.icon, a.image_path FROM student_avatar_selection s JOIN avatars a ON a.id = s.avatar_id WHERE s.student_id = ?');
    $q->execute([$u['id']]);
    $currentAvatar = $q->fetch();
    if ($currentAvatar) {
        $currentAvatar['profile_image_path'] = avatar_profile_image($currentAvatar);
    }

    $q = $pdo->prepare(
        'SELECT COUNT(*) AS total_missions,
                (SELECT COUNT(*) FROM mission_rewards WHERE student_id = ?) AS completed_missions,
                (SELECT COALESCE(SUM(stars), 0) FROM mission_rewards WHERE student_id = ?) AS mission_stars
         FROM missions'
    );
    $q->execute([$u['id'], $u['id']]);
    $missionSummary = $q->fetch();

    $q = $pdo->prepare(
        'SELECT m.slug, m.title,
                (SELECT a.current_question FROM mission_attempts a
                 WHERE a.student_id = ? AND a.mission_id = m.id
                 ORDER BY a.id DESC LIMIT 1) AS current_question,
                m.question_count
         FROM missions m
         WHERE NOT EXISTS (
             SELECT 1 FROM mission_rewards r WHERE r.student_id = ? AND r.mission_id = m.id
         )
         ORDER BY m.display_order, m.id
         LIMIT 1'
    );
    $q->execute([$u['id'], $u['id']]);
    $recommendedMission = $q->fetch();
}
?>
<!doctype html>
<html lang="<?= e(current_language()) ?>" <?= accessibility_html_attributes() ?>>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="theme-color" content="#F5F7FF">
    <title><?= e(t('Painel')) ?> — Lunaris Academy</title>
    <link rel="stylesheet" href="<?= e(application_asset_url('css/style.css')) ?>">
    <script src="<?= e(application_asset_url('js/theme.js')) ?>"></script>
</head>

<body>
    <header class="nav">
        <a class="nav-brand" href="dashboard.php"><img src="<?= e(application_asset_url('imagens/logo.png')) ?>" alt="" aria-hidden="true">Lunaris Academy</a>
        <nav lang="<?= e(current_language()) ?>">
            <span><?= e($u['name']) ?></span>
            <a href="dashboard.php" aria-current="page"><?= e(t('Painel')) ?></a>
            <?php if ($u['role'] === 'professor'): ?><a href="professor/"><?= e(t('Professor')) ?></a><?php endif; ?>
            <?php if ($u['role'] === 'aluno'): ?>
                <a href="universo.php"><?= e(t('Universo')) ?></a>
                <a href="missoes.php"><?= e(t('Missões')) ?></a>
                <a href="planetas.php"><?= e(t('Planetas')) ?></a>
                <a href="avatares.php"><?= e(t('Avatares')) ?></a>
            <?php endif; ?>
            <a href="perfil.php"><?= e(t('Perfil')) ?></a>
            <a href="logout.php"><?= e(t('Sair')) ?></a>
        </nav>
    </header>
    <main class="container">
        <div class="welcome">
            <div class="welcome-copy"><span class="tag"><?= e(t($u['role'])) ?></span>
                <div class="welcome-title">
                    <?php if ($currentAvatar): ?><img class="welcome-avatar-image" src="<?= e($currentAvatar['profile_image_path']) ?>" alt="Avatar atual: <?= e($currentAvatar['name']) ?>" width="54" height="54"><?php endif; ?>
                    <h1><?= e(t('Olá,')) ?> <?= e($u['name']) ?>!</h1>
                </div>
                <p><?= e($u['escola']) ?> • <?= e(t($u['regiao'])) ?><?php if ($u['serie']): ?> • <?= e(t($u['serie'])) ?><?php endif; ?></p>
                <?php if ($currentAvatar): ?><p class="dashboard-avatar-name">🎭 <?= e($currentAvatar['name']) ?></p><?php endif; ?>
            </div>
            <?php if ($u['role'] === 'aluno'): ?>
                <div class="points">🌟<?= $totalPoints ?> <?= e(t('pontos')) ?></div>
            <?php endif; ?>
        </div>
        <?php if ($u['role'] === 'aluno'): ?>
            <section class="dashboard-mission">
                <div class="dashboard-mission-copy">
                    <span class="tag"><?= e(t('Missões Espaciais')) ?></span>
                    <h2><?= e(t('Sua próxima jornada matemática')) ?></h2>
                    <p><?= e(t('Conclua desafios e receba 25 estrelas extras por missão.')) ?></p>
                    <?php if ($recommendedMission): ?>
                        <a class="btn small" href="missoes.php?mission=<?= e($recommendedMission['slug']) ?>">
                            <?= e(t($recommendedMission['current_question'] ? 'Continuar' : 'Iniciar')) ?> <?= e($recommendedMission['title']) ?>
                        </a>
                    <?php else: ?>
                        <a class="btn small" href="missoes.php"><?= e(t('Revisitar missões')) ?></a>
                    <?php endif; ?>
                </div>
                <div class="dashboard-mission-stats">
                    <span><strong><?= e((string)$missionSummary['completed_missions']) ?>/<?= e((string)$missionSummary['total_missions']) ?></strong> <?= e(t('missões concluídas')) ?></span>
                    <span><strong><?= e((string)$missionSummary['total_missions'] - (int)$missionSummary['completed_missions']) ?></strong> <?= e(t('disponíveis')) ?></span>
                    <span><strong><?= e((string)$missionSummary['mission_stars']) ?> ⭐</strong> <?= e(t('conquistadas')) ?></span>
                </div>
            </section>
            <h2><?= e(t('Jogos')) ?></h2>
            <div class="grid">
                <a class="game card" href="jogo.php?game=operacoes"><span>➕</span>
                    <h3><?= e(t('Operações')) ?></h3>
                    <p><?= e(t('Adição, subtração e multiplicação')) ?></p>
                </a>
                <a class="game card" href="jogo.php?game=fracoes"><span>🍕</span>
                    <h3><?= e(t('Frações')) ?></h3>
                    <p><?= e(t('Frações e proporções')) ?></p>
                </a>
                <a class="game card" href="jogo.php?game=equacoes"><span>⚖️</span>
                    <h3><?= e(t('Equações')) ?></h3>
                    <p><?= e(t('Descubra o X')) ?></p>
                </a>
                <a class="game card" href="jogo.php?game=geometria"><span>📐</span>
                    <h3><?= e(t('Geometria')) ?></h3>
                    <p><?= e(t('Área e perímetro')) ?></p>
                </a>
            </div>
            <h2><?= e(t('Tarefas')) ?></h2>
            <div class="activity-list">
                <?php foreach ($acts as $a):
                    $statusClass = 'status-red';
                    $statusLabel = 'Não iniciado';

                    if (!empty($a['submission_id'])) {
                        $statusClass = 'status-green';
                        $statusLabel = 'Concluído';
                    } elseif ((int)$a['progress_count'] > 0) {
                        $statusClass = 'status-orange';
                        $statusLabel = 'Parcialmente concluído';
                    }
                ?>
                    <article class="card task-card">
                        <div class="task-head">
                            <span class="tag"><?= e(t($a['difficulty'])) ?></span>
                            <span class="status-badge <?= $statusClass ?>">
                                <span class="status-dot"></span>
                                <?= e(t($statusLabel)) ?>
                            </span>
                        </div>
                        <h3><?= e($a['title']) ?></h3>
                        <p lang="pt-BR"><?= nl2br(e($a['description'])) ?></p><small><?= e(t('Professor:')) ?> <?= e($a['professor']) ?><?php if ($a['due_date']): ?> • <?= e(t('Entrega:')) ?> <?= e($a['due_date']) ?><?php endif; ?></small>
                        <div class="actions"><a class="btn" href="atividade.php?id=<?= $a['id'] ?>"><?= e(t('Abrir tarefa')) ?></a></div>
                    </article>
                <?php endforeach; ?>
                <?php if (!$acts): ?>
                    <div class="card"><?= e(t('Nenhuma tarefa publicada.')) ?></div>
                <?php endif; ?>
            </div>
            <h2><?= e(t('Histórico')) ?></h2>
            <div class="card table-wrap">
                <table>
                    <tr>
                        <th><?= e(t('Jogo')) ?></th>
                        <th><?= e(t('Nível')) ?></th>
                        <th><?= e(t('Pontos')) ?></th>
                        <th><?= e(t('Acertos')) ?></th>
                    </tr><?php foreach ($res as $r): ?><tr>
                            <td><?= e($r['game']) ?></td>
                            <td><?= e($r['difficulty']) ?></td>
                            <td><?= e((string)$r['score']) ?></td>
                            <td><?= e((string)$r['correct_answers']) ?>/<?= e((string)$r['total_questions']) ?></td>
                        </tr><?php endforeach; ?>
                </table>
            </div>
        <?php else: ?><div class="card teacher-dashboard-card">
                <h2><?= e(t('Área do professor')) ?></h2>
                <p><?= e(t('Publique atividades e acompanhe os resultados dos alunos.')) ?></p><a class="btn" href="professor/"><?= e(t('Abrir painel')) ?></a>
            </div><?php endif; ?>
    </main>
</body>

</html>