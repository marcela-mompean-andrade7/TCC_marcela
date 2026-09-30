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
        <b>MathUp</b>
        <nav>
            <a href="dashboard.php">Painel</a>
            <a href="logout.php">Sair</a>
        </nav>
    </header>

    <main class="container narrow activity-page">
        <section class="activity-header card">
            <div class="activity-meta">
                <span class="tag"><?= e($a['difficulty']) ?></span>
            </div>

            <div class="activity-info">
                <h1><?= e($a['title']) ?></h1>
                <p><?= nl2br(e($a['description'])) ?></p>
            </div>

            <div class="activity-instructor">Professor: <?= e($a['professor']) ?></div>
        </section>

        <?php if ($done): ?>
            <div class="activity-success">Você já respondeu esta atividade. Nota: <b>
                    <?= e((string)$done['score']) ?>/<?= e((string)$done['total']) ?>
                </b>.</div>
        <?php elseif (!$questions): ?>
            <div class="activity-empty">O professor ainda não adicionou questões.</div>
        <?php else: ?>
            <form class="activity-form" method="post" action="responder.php">
                <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                <input type="hidden" name="activity_id" value="<?= $id ?>">

                <?php foreach ($questions as $i => $x): ?>
                    <div class="question-card card">
                        <div class="question-head">
                            <span class="question-number"><?= ($i + 1) ?></span>
                            <h3><?= e($x['question_text']) ?></h3>
                        </div>

                        <label class="answer-field">
                            <span>Sua resposta</span>
                            <input class="answer-input" type="text" name="answers[<?= $x['id'] ?>]" required>
                        </label>
                    </div>
                <?php endforeach; ?>

                <button class="btn activity-submit" type="submit">Enviar atividade</button>
            </form>
        <?php endif; ?>
    </main>
</body>

</html>