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
<html lang="<?= e(current_language()) ?>" <?= accessibility_html_attributes() ?>>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Questões da atividade — Lunaris Academy</title>
    <link rel="stylesheet" href="../assets/style.css">
    <script src="../assets/theme.js"></script>
</head>

<body>
    <header class="nav teacher-create-nav">
        <b>Lunaris Academy • Professor</b>
        <nav lang="<?= e(current_language()) ?>">
            <a href="index.php"><?= e(t('Minhas atividades')) ?></a>
            <a href="criar.php"><?= e(t('Nova atividade')) ?></a>
            <a href="../logout.php"><?= e(t('Sair')) ?></a>
        </nav>
    </header>
    <main class="container teacher-question-page">
        <header class="teacher-question-hero">
            <div>
                <span class="tag">Atividade · Questões</span>
                <h1>Monte o desafio da sua turma</h1>
                <p>Adicione enunciados e respostas corretas para preparar a atividade.</p>
            </div>
            <a class="btn btn-secondary" href="index.php">← Voltar ao painel</a>
        </header>

        <section class="teacher-activity-summary" aria-labelledby="activity-title">
            <span class="tag"><?= e($a['difficulty']) ?></span>
            <h2 id="activity-title"><?= e($a['title']) ?></h2>
            <p><?= nl2br(e($a['description'])) ?></p>
        </section>

        <section class="teacher-question-composer" aria-labelledby="question-form-title">
            <div class="teacher-create-section-heading">
                <span class="teacher-create-step" aria-hidden="true">+</span>
                <div>
                    <h2 id="question-form-title">Adicionar questão</h2>
                    <p>Escreva o enunciado e informe a resposta esperada.</p>
                </div>
            </div>
            <form method="post" class="teacher-create-form">
                <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                <input type="hidden" name="id" value="<?= $id ?>">
                <label class="teacher-field teacher-field-wide">Enunciado da questão
                    <textarea name="question_text" required rows="3"></textarea>
                </label>
                <label class="teacher-field teacher-field-wide">Resposta correta
                    <input name="answer" required>
                </label>
                <div class="teacher-create-actions">
                    <span>Confira o enunciado e a resposta antes de adicionar.</span>
                    <button class="btn" type="submit">Adicionar questão</button>
                </div>
            </form>
        </section>

        <section class="teacher-question-list" aria-labelledby="question-list-title">
            <div class="teacher-question-list-heading">
                <div>
                    <span class="tag">Conteúdo da atividade</span>
                    <h2 id="question-list-title">Questões adicionadas</h2>
                </div>
                <span class="teacher-question-count"><?= count($questions) ?> <?= count($questions) === 1 ? 'questão' : 'questões' ?></span>
            </div>
            <?php if (!$questions): ?>
                <p class="teacher-question-empty">Nenhuma questão adicionada ainda. Use o formulário acima para começar.</p>
            <?php else: ?>
                <ol class="teacher-question-items">
                    <?php foreach ($questions as $i => $x): ?>
                        <li class="teacher-question-item">
                            <span class="teacher-question-number" aria-label="Questão <?= $i + 1 ?>"><?= $i + 1 ?></span>
                            <div>
                                <p><?= nl2br(e($x['question_text'])) ?></p>
                                <small><strong>Resposta correta:</strong> <?= e($x['answer']) ?></small>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>
        </section>
    </main>
</body>

</html>