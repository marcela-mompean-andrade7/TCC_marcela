<?php
require 'config.php';
login_required();
if (($_SESSION['role'] ?? '') !== 'aluno') redirect('dashboard.php');
$id = (int)($_GET['id'] ?? 0);
$q = $pdo->prepare('SELECT a.*,u.name professor FROM activities a JOIN users u ON u.id=a.professor_id WHERE a.id=?');
$q->execute([$id]);
$a = $q->fetch();
if (!$a) exit('Atividade não encontrada.');
$q = $pdo->prepare('SELECT * FROM questions WHERE activity_id=? ORDER BY id');
$q->execute([$id]);
$questions = $q->fetchAll();
$q = $pdo->prepare('SELECT * FROM submissions WHERE activity_id=? AND student_id=?');
$q->execute([$id, $_SESSION['user_id']]);
$done = $q->fetch();
?>
<!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>
        <?= e($a['title']) ?>
    </title>
    <link rel="stylesheet" href="assets/style.css">
</head>

<body>
    <header class="nav">
        <b>🎮 MathPlay</b>
        <nav><a href="dashboard.php">Painel</a></nav>
    </header>
    <main class="container narrow">
        <div class="card">
            <span class="tag">
                <?= e($a['difficulty']) ?>
            </span>
            <h1><?= e($a['title']) ?></h1>
            <p><?= nl2br(e($a['description'])) ?></p>
            <small>Professor: <?= e($a['professor']) ?></small>
        </div>
        <?php if ($done): ?>
            <div class="success">Você já respondeu esta atividade. Nota: <b>
                    <?= e((string)$done['score']) ?>/
                    <?= e((string)$done['total']) ?>
                </b>
                .</div><?php elseif (!$questions): ?>
            <div class="card">O professor ainda não adicionou questões.</div>
        <?php else: ?>
            <form method="post" action="responder.php">
                <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                <input type="hidden" name="activity_id" value="<?= $id ?>">
                <?php foreach ($questions as $i => $x): ?>
                    <div class="card">
                        <h3><?= ($i + 1) ?>.
                            <?= e($x['question_text']) ?>
                        </h3>
                        <input name="answers[<?= $x['id'] ?>]" required>
                    </div>
                <?php endforeach; ?>
                <button class="btn full">Enviar atividade</button>
            </form>
        <?php endif; ?>
    </main>
</body>

</html>