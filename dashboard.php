<?php
require 'config.php';
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
                        ORDER BY a.created_at DESC LIMIT 12");
    $q->execute([$u['id'], $u['id']]);
    $acts = $q->fetchAll();
} else {
    $acts = $pdo->query("SELECT a.*,u.name professor FROM activities a JOIN users u ON u.id=a.professor_id ORDER BY a.created_at DESC LIMIT 12")->fetchAll();
}
$res = [];
$totalPoints = (int)$u['points'];
if ($u['role'] === 'aluno') {
    $q = $pdo->prepare("SELECT * FROM game_results WHERE student_id=? ORDER BY played_at DESC LIMIT 10");
    $q->execute([$u['id']]);
    $res = $q->fetchAll();
}
?>
<!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Painel</title>
    <link rel="stylesheet" href="assets/style.css">
    <script src="assets/theme.js"></script>
</head>

<body>
    <header class="nav"><b>MathUp</b>
        <nav><span><?= e($u['name']) ?></span><?php if ($u['role'] === 'professor'): ?><a href="professor/">Professor</a><?php endif; ?><a href="perfil.php">Perfil</a><a href="logout.php">Sair</a></nav>
    </header>
    <main class="container">
        <div class="welcome">
            <div><span class="tag"><?= e(strtoupper($u['role'])) ?></span>
                <h1>Olá, <?= e($u['name']) ?>!</h1>
                <p><?= e($u['escola']) ?> • <?= e($u['regiao']) ?><?php if ($u['serie']): ?> • <?= e($u['serie']) ?><?php endif; ?></p>
            </div>
            <div class="points">⭐ <?= $totalPoints ?> pontos</div>
        </div>
        <?php if ($u['role'] === 'aluno'): ?>
            <h2>🎮 Jogos</h2>
            <div class="grid">
                <a class="game card" href="jogo.php?game=operacoes"><span>➕</span>
                    <h3>Operações</h3>
                    <p>Adição, subtração e multiplicação</p>
                </a>
                <a class="game card" href="jogo.php?game=fracoes"><span>🍕</span>
                    <h3>Frações</h3>
                    <p>Frações e proporções</p>
                </a>
                <a class="game card" href="jogo.php?game=equacoes"><span>⚖️</span>
                    <h3>Equações</h3>
                    <p>Descubra o X</p>
                </a>
                <a class="game card" href="jogo.php?game=geometria"><span>📐</span>
                    <h3>Geometria</h3>
                    <p>Área e perímetro</p>
                </a>
            </div>
            <h2>📚 Tarefas</h2>
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
                            <span class="tag"><?= e($a['difficulty']) ?></span>
                            <span class="status-badge <?= $statusClass ?>">
                                <span class="status-dot"></span>
                                <?= $statusLabel ?>
                            </span>
                        </div>
                        <h3><?= e($a['title']) ?></h3>
                        <p><?= nl2br(e($a['description'])) ?></p><small>Professor: <?= e($a['professor']) ?><?php if ($a['due_date']): ?> • Entrega: <?= e($a['due_date']) ?><?php endif; ?></small>
                        <div class="actions"><a class="btn" href="atividade.php?id=<?= $a['id'] ?>">Abrir tarefa</a></div>
                    </article>
                <?php endforeach; ?>
                <?php if (!$acts): ?>
                    <div class="card">Nenhuma tarefa publicada.</div>
                <?php endif; ?>
            </div>
            <h2>📈 Histórico</h2>
            <div class="card table-wrap">
                <table>
                    <tr>
                        <th>Jogo</th>
                        <th>Nível</th>
                        <th>Pontos</th>
                        <th>Acertos</th>
                    </tr><?php foreach ($res as $r): ?><tr>
                            <td><?= e($r['game']) ?></td>
                            <td><?= e($r['difficulty']) ?></td>
                            <td><?= e((string)$r['score']) ?></td>
                            <td><?= e((string)$r['correct_answers']) ?>/<?= e((string)$r['total_questions']) ?></td>
                        </tr><?php endforeach; ?>
                </table>
            </div>
        <?php else: ?><div class="card">
                <h2>👩‍🏫 Área do professor</h2>
                <p>Publique atividades e acompanhe os resultados dos alunos.</p><a class="btn" href="professor/">Abrir painel</a>
            </div><?php endif; ?>
    </main>
</body>

</html>