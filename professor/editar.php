<?php
require '../config.php';
teacher_required();
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$q = $pdo->prepare('SELECT * FROM activities WHERE id=? AND professor_id=?');
$q->execute([$id, $_SESSION['user_id']]);
$a = $q->fetch();
if (!$a) exit('Atividade não encontrada.');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $text = trim($_POST['question_text'] ?? '');
    $answer = trim($_POST['answer'] ?? '');
    if ($text !== '' && $answer !== '') {
        $q = $pdo->prepare('INSERT INTO questions(activity_id,question_text,answer) VALUES(?,?,?)');
        $q->execute([$id, $text, $answer]);
    }
    redirect("editar.php?id=$id");
}
$q = $pdo->prepare('SELECT * FROM questions WHERE activity_id=? ORDER BY id');
$q->execute([$id]);
$questions = $q->fetchAll();
?>
<!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Questões</title>
    <link rel="stylesheet" href="../assets/style.css">
    <script src="../assets/theme.js"></script>
</head>

<body>
    <main class="container narrow"><a href="index.php">← Painel</a>
        <div class="card"><span class="tag"><?= e($a['difficulty']) ?></span>
            <h1><?= e($a['title']) ?></h1>
            <p><?= nl2br(e($a['description'])) ?></p>
        </div>
        <div class="card">
            <h2>Adicionar questão</h2>
            <form method="post"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="id" value="<?= $id ?>"><label>Questão<textarea name="question_text" required rows="3"></textarea></label><label>Resposta correta<input name="answer" required></label><button class="btn">Adicionar</button></form>
        </div>
        <h2>Questões (<?= count($questions) ?>)</h2><?php foreach ($questions as $i => $x): ?><div class="card"><b><?= ($i + 1) ?>.</b> <?= e($x['question_text']) ?><br><small>Resposta: <?= e($x['answer']) ?></small></div><?php endforeach; ?>
    </main>
</body>

</html>