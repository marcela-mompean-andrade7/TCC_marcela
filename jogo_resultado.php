<?php
require 'config.php';
login_required();
if (($_SESSION['role'] ?? '') !== 'aluno') redirect('dashboard.php');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('dashboard.php');
check_csrf();
$game = $_POST['game'] ?? 'operacoes';
$level = max(1, min(3, (int)($_POST['level'] ?? 1)));
$correct = trim($_POST['answer'] ?? '');
$given = trim($_POST['user_answer'] ?? '');
$ok = mb_strtolower(str_replace(' ', '', $given)) === mb_strtolower(str_replace(' ', '', $correct));
$score = $ok ? $level * 10 : 0;
$diff = ['', 'facil', 'medio', 'dificil'][$level];
if ($ok) {
    $q = $pdo->prepare('UPDATE users SET points=points+? WHERE id=?');
    $q->execute([$score, $_SESSION['user_id']]);
}
$q = $pdo->prepare('INSERT INTO game_results(student_id,game,difficulty,score,correct_answers,total_questions) VALUES(?,?,?,?,?,1)');
$q->execute([$_SESSION['user_id'], $game, $diff, $score, $ok ? 1 : 0]);
$next = $ok && $level < 3 ? $level + 1 : $level;
?>
<!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Resultado</title>
    <link rel="stylesheet" href="assets/style.css">
</head>

<body>
    <main class="container narrow">
        <div class="card result">
            <div class="big"><?= $ok ? '🎉' : '📚' ?></div>
            <h1><?= $ok ? 'Muito bem!' : 'Continue tentando!' ?></h1><?php if ($ok): ?><p>Você ganhou <b><?= $score ?> pontos</b>.</p><?php if ($level < 3): ?><p>🔓 Próximo nível desbloqueado!</p><?php endif; ?><?php else: ?><p>A resposta correta era <b><?= e($correct) ?></b>.</p><?php endif; ?><div class="actions"><a class="btn" href="jogo.php?game=<?= urlencode($game) ?>&level=<?= $next ?>">Próxima</a><a class="btn light" href="dashboard.php">Painel</a></div>
        </div>
    </main>
</body>

</html>