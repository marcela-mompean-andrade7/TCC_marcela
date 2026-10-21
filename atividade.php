<?php
require_once __DIR__ . '/config.php';
login_required();
if (($_SESSION['role'] ?? '') !== 'aluno') redirect('dashboard.php');
$id = (int)($_GET['id'] ?? 0);
if (!student_can_access_activity($pdo, $id, (int)$_SESSION['user_id'])) {
    http_response_code(404);
    exit('Atividade não encontrada ou indisponível para sua turma.');
}
$q = $pdo->prepare('SELECT a.*,u.name professor FROM activities a JOIN users u ON u.id=a.professor_id WHERE a.id=?');
$q->execute([$id]);
$a = $q->fetch();
if (!$a) exit('Atividade não encontrada.');
$q = $pdo->prepare('SELECT id,question_text FROM questions WHERE activity_id=? ORDER BY id');
$q->execute([$id]);
$questions = $q->fetchAll();
$q = $pdo->prepare('SELECT * FROM submissions WHERE activity_id=? AND student_id=?');
$q->execute([$id, $_SESSION['user_id']]);
$done = $q->fetch();
$submittedAnswers = [];
if ($done) {
    $q = $pdo->prepare(
        'SELECT q.question_text, sa.answer, sa.is_correct
         FROM submission_answers sa
         JOIN questions q ON q.id = sa.question_id
         WHERE sa.submission_id = ?
         ORDER BY q.id'
    );
    $q->execute([$done['id']]);
    $submittedAnswers = $q->fetchAll();
}
$savedAnswers = [];
if (!$done) {
    $q = $pdo->prepare('SELECT question_id, answer FROM activity_progress WHERE activity_id=? AND student_id=?');
    $q->execute([$id, $_SESSION['user_id']]);
    $savedAnswers = array_column($q->fetchAll(), 'answer', 'question_id');
}
?>
<!doctype html>
<html lang="<?= e(current_language()) ?>" <?= accessibility_html_attributes() ?>>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>
        <?= e($a['title']) ?>
    </title>
    <link rel="stylesheet" href="<?= e(application_asset_url('css/style.css')) ?>">
    <script src="<?= e(application_asset_url('js/theme.js')) ?>"></script>
</head>

<body>
    <header class="nav">
        <a class="nav-brand" href="dashboard.php"><img src="<?= e(application_asset_url('imagens/logo.png')) ?>" alt="" aria-hidden="true">Lunaris Academy</a>
        <nav lang="<?= e(current_language()) ?>">
            <a href="dashboard.php"><?= e(t('Painel')) ?></a>
            <a href="logout.php"><?= e(t('Sair')) ?></a>
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
            <?php if ($submittedAnswers): ?>
                <ol class="activity-answer-review" aria-label="Resultado das questões">
                    <?php foreach ($submittedAnswers as $index => $answer): ?>
                        <li class="<?= $answer['is_correct'] ? 'answer-correct' : 'answer-incorrect' ?>">
                            <span>Questão <?= $index + 1 ?>: <?= e($answer['is_correct'] ? 'Correta' : 'Incorreta') ?></span>
                            <span><?= e($answer['question_text']) ?> Sua resposta: <?= e($answer['answer']) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>
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
                            <input class="answer-input" type="text" name="answers[<?= $x['id'] ?>]" value="<?= e((string)($savedAnswers[$x['id']] ?? '')) ?>" maxlength="120" required>
                        </label>
                    </div>
                <?php endforeach; ?>

                <p class="activity-save-status" aria-live="polite"></p>
                <button class="btn activity-submit" type="submit">Enviar atividade</button>
            </form>
        <?php endif; ?>
    </main>
    <?php if (!$done && $questions): ?>
        <script>
            const form = document.querySelector('.activity-form');
            const saveStatus = document.querySelector('.activity-save-status');
            let saveTimer;
            let saveQueue = Promise.resolve();

            function queueProgressSave() {
                const formData = new URLSearchParams(new FormData(form));
                saveStatus.textContent = 'Salvando progresso...';
                saveStatus.classList.remove('save-error');

                saveQueue = saveQueue.catch(() => {}).then(async () => {
                    const response = await fetch('salvar_progresso.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: formData
                    });
                    const result = await response.json();
                    if (!response.ok) {
                        throw new Error(result.message || 'Não foi possível salvar o progresso.');
                    }
                    saveStatus.textContent = 'Progresso salvo.';
                }).catch((error) => {
                    saveStatus.textContent = error.message;
                    saveStatus.classList.add('save-error');
                    throw error;
                });

                return saveQueue;
            }

            form.addEventListener('input', () => {
                clearTimeout(saveTimer);
                saveTimer = setTimeout(() => queueProgressSave().catch(() => {}), 500);
            });

            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                clearTimeout(saveTimer);
                const submitButton = form.querySelector('[type="submit"]');
                submitButton.disabled = true;
                try {
                    await queueProgressSave();
                    form.submit();
                } catch {
                    submitButton.disabled = false;
                }
            });
        </script>
    <?php endif; ?>
</body>

</html>