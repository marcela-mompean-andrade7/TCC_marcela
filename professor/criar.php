<?php
require '../config.php';
teacher_required();
$err = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $t = trim($_POST['title'] ?? '');
    $d = trim($_POST['description'] ?? '');
    $dif = $_POST['difficulty'] ?? '';
    $date = $_POST['due_date'] ?: null;
    if ($t === '' || $d === '') $err[] = 'Preencha título e descrição.';
    if (!in_array($dif, ['facil', 'medio', 'dificil'], true)) $err[] = 'Dificuldade inválida.';
    if (!$err) {
        $q = $pdo->prepare('INSERT INTO activities(professor_id,title,description,difficulty,due_date) VALUES(?,?,?,?,?)');
        $q->execute([$_SESSION['user_id'], $t, $d, $dif, $date]);
        $id = (int)$pdo->lastInsertId();
        redirect("editar.php?id=$id");
    }
}
?>
<!doctype html>
<html lang="<?= e(current_language()) ?>" <?= accessibility_html_attributes() ?>>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Criar nova atividade — Lunaris Academy</title>
    <link rel="stylesheet" href="../assets/style.css">
    <script src="../assets/theme.js"></script>
</head>

<body>
    <header class="nav teacher-create-nav">
        <b>Lunaris Academy • Professor</b>
        <nav lang="<?= e(current_language()) ?>">
            <a href="index.php"><?= e(t('Minhas atividades')) ?></a>
            <a href="criar.php" aria-current="page"><?= e(t('Nova atividade')) ?></a>
            <a href="../logout.php"><?= e(t('Sair')) ?></a>
        </nav>
    </header>
    <main class="container teacher-create-page">
        <header class="teacher-create-hero">
            <div class="teacher-create-copy">
                <span class="tag">Área do professor · Nova publicação</span>
                <h1>Criar nova atividade</h1>
                <p>Prepare desafios matemáticos e acompanhe a evolução dos seus alunos.</p>
            </div>
            <div class="teacher-create-hero-side">
                <span class="teacher-create-icon" aria-hidden="true">∑</span>
                <a class="btn btn-secondary" href="index.php">← Voltar ao painel</a>
            </div>
        </header>

        <section class="teacher-create-card" aria-labelledby="activity-details-title">
            <div class="teacher-create-section-heading">
                <span class="teacher-create-step" aria-hidden="true">01</span>
                <div>
                    <h2 id="activity-details-title">Informações da atividade</h2>
                    <p>Defina as instruções e as configurações principais para a turma.</p>
                </div>
            </div>

            <?php foreach ($err as $x): ?>
                <div class="alert" role="alert"><?= e($x) ?></div>
            <?php endforeach; ?>

            <form method="post" class="teacher-create-form">
                <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                <label class="teacher-field teacher-field-wide">Título <span class="teacher-required-mark" aria-hidden="true">*</span>
                    <input name="title" required maxlength="160" autocomplete="off">
                </label>
                <label class="teacher-field teacher-field-wide">Descrição e instruções <span class="teacher-required-mark" aria-hidden="true">*</span>
                    <textarea name="description" rows="5" required></textarea>
                </label>
                <div class="teacher-field-grid">
                    <label class="teacher-field">Dificuldade
                        <select name="difficulty">
                            <option value="facil">Fácil</option>
                            <option value="medio">Médio</option>
                            <option value="dificil">Difícil</option>
                        </select>
                    </label>
                    <label class="teacher-field">Data de entrega
                        <input type="date" name="due_date">
                    </label>
                </div>
                <div class="teacher-create-actions">
                    <span>Título e descrição são obrigatórios.</span>
                    <button class="btn" type="submit">Criar e adicionar questões</button>
                </div>
            </form>
        </section>
    </main>
</body>

</html>