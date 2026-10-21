<?php
require_once __DIR__ . '/../config.php';
teacher_required();

$errors = [];
$destinations = registered_student_destinations($pdo);
$title = '';
$description = '';
$difficulty = 'facil';
$dueDate = '';
$targetSchool = '';
$targetSerie = '';
$submittedQuestions = ['first' => [''], 'operation' => ['addition'], 'second' => [''], 'answer' => ['']];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();

    $title = is_string($_POST['title'] ?? null) ? trim($_POST['title']) : '';
    $description = is_string($_POST['description'] ?? null) ? trim($_POST['description']) : '';
    $difficulty = is_string($_POST['difficulty'] ?? null) ? $_POST['difficulty'] : '';
    $dueDate = is_string($_POST['due_date'] ?? null) ? trim($_POST['due_date']) : '';
    $targetSchool = is_string($_POST['target_school'] ?? null) ? trim($_POST['target_school']) : '';
    $targetSerie = is_string($_POST['target_serie'] ?? null) ? trim($_POST['target_serie']) : '';

    $postedQuestions = $_POST['questions'] ?? null;
    if (is_array($postedQuestions)) {
        foreach (['first', 'operation', 'second', 'answer'] as $field) {
            if (isset($postedQuestions[$field]) && is_array($postedQuestions[$field])) {
                $submittedQuestions[$field] = array_values($postedQuestions[$field]);
            }
        }
    }

    if ($title === '' || mb_strlen($title) > 160) {
        $errors[] = 'Informe um título de até 160 caracteres.';
    }
    if ($description === '') {
        $errors[] = 'Informe a descrição e as instruções da atividade.';
    }
    if (!in_array($difficulty, ['facil', 'medio', 'dificil'], true)) {
        $errors[] = 'Selecione uma dificuldade válida.';
    }
    if ($targetSchool === '' || $targetSerie === '') {
        $errors[] = 'Selecione a escola e a turma de destino.';
    } elseif (!in_array($targetSerie, $destinations[$targetSchool] ?? [], true)) {
        $errors[] = 'A turma selecionada não está disponível para essa escola.';
    }
    if ($dueDate !== '') {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $dueDate);
        if (!$date || $date->format('Y-m-d') !== $dueDate) {
            $errors[] = 'Informe um prazo válido.';
        } elseif ($date < new DateTimeImmutable('today')) {
            $errors[] = 'O prazo de entrega não pode estar no passado.';
        }
    }

    $questionCount = count($submittedQuestions['first']);
    if ($questionCount < 1) {
        $errors[] = 'Adicione pelo menos uma questão matemática.';
    } elseif ($questionCount > 50) {
        $errors[] = 'Uma atividade pode ter no máximo 50 questões.';
    }

    $validatedQuestions = [];
    foreach ($submittedQuestions['first'] as $index => $firstValue) {
        $questionNumber = $index + 1;
        $secondValue = $submittedQuestions['second'][$index] ?? null;
        $operation = $submittedQuestions['operation'][$index] ?? null;
        $answerValue = $submittedQuestions['answer'][$index] ?? null;
        $first = parse_activity_decimal($firstValue);
        $second = parse_activity_decimal($secondValue);
        $expected = parse_activity_decimal($answerValue);
        if ($first === null || $second === null || $expected === null) {
            $errors[] = "Questão $questionNumber: informe números válidos (até seis casas decimais).";
            continue;
        }
        if (!is_string($operation) || !in_array($operation, ['addition', 'subtraction', 'multiplication', 'division'], true)) {
            $errors[] = "Questão $questionNumber: selecione uma operação permitida.";
            continue;
        }
        if ($operation === 'division' && $second === 0.0) {
            $errors[] = "Questão $questionNumber: não é permitido dividir por zero.";
            continue;
        }
        $correctResult = calculate_activity_result($first, $operation, $second);
        if ($correctResult === null) {
            $errors[] = "Questão $questionNumber: o resultado está fora do limite aceito.";
            continue;
        }
        if (abs($correctResult - round($expected, 6, PHP_ROUND_HALF_UP)) >= 0.0000005) {
            $errors[] = "Questão $questionNumber: o resultado informado não corresponde à operação.";
            continue;
        }
        $validatedQuestions[] = [
            'first' => format_activity_decimal($first),
            'operation' => $operation,
            'second' => format_activity_decimal($second),
            'answer' => format_activity_decimal($correctResult),
        ];
    }

    if (!$errors && count($validatedQuestions) !== $questionCount) {
        $errors[] = 'Corrija as questões inválidas antes de publicar.';
    }

    if (!$errors) {
        $symbols = [
            'addition' => '+',
            'subtraction' => '−',
            'multiplication' => '×',
            'division' => '÷',
        ];
        try {
            $pdo->beginTransaction();
            $insertActivity = $pdo->prepare(
                'INSERT INTO activities(professor_id,target_school,target_serie,title,description,difficulty,due_date)
                 VALUES(?,?,?,?,?,?,?)'
            );
            $insertActivity->execute([
                $_SESSION['user_id'],
                $targetSchool,
                $targetSerie,
                $title,
                $description,
                $difficulty,
                $dueDate !== '' ? $dueDate : null,
            ]);
            $activityId = (int)$pdo->lastInsertId();
            $insertQuestion = $pdo->prepare(
                'INSERT INTO questions(activity_id,question_text,answer,first_number,operation,second_number)
                 VALUES(?,?,?,?,?,?)'
            );
            foreach ($validatedQuestions as $question) {
                $questionText = 'Quanto é ' . $question['first'] . ' ' . $symbols[$question['operation']] . ' ' . $question['second'] . '?';
                $insertQuestion->execute([
                    $activityId,
                    $questionText,
                    $question['answer'],
                    $question['first'],
                    $question['operation'],
                    $question['second'],
                ]);
            }
            $pdo->commit();
            redirect('index.php?publicada=1');
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Falha ao publicar atividade matemática: ' . $error->getMessage());
            $errors[] = 'Não foi possível publicar a atividade agora. Revise os dados e tente novamente.';
        }
    }
}
?>
<!doctype html>
<html lang="<?= e(current_language()) ?>" <?= accessibility_html_attributes() ?>>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Criar nova atividade — Lunaris Academy</title>
    <link rel="stylesheet" href="<?= e(application_asset_url('css/style.css')) ?>">
    <script src="<?= e(application_asset_url('js/theme.js')) ?>"></script>
    <script src="<?= e(application_asset_url('js/teacher-activity-form.js')) ?>" defer></script>
</head>

<body>
    <header class="nav teacher-create-nav">
        <a class="nav-brand teacher-nav-brand" href="../dashboard.php">
            <img src="<?= e(application_asset_url('imagens/logo.png')) ?>" alt="" aria-hidden="true">
            Lunaris Academy <span>• Professor</span>
        </a>
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
                <p>Escolha a turma e prepare questões matemáticas para seus alunos.</p>
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
                    <p>Defina as instruções, a turma e o prazo para entrega.</p>
                </div>
            </div>

            <?php foreach ($errors as $error): ?>
                <div class="alert" role="alert"><?= e($error) ?></div>
            <?php endforeach; ?>

            <?php if (!$destinations): ?>
                <div class="alert" role="alert">Ainda não há alunos cadastrados com escola e série. Cadastre alunos nessas turmas antes de publicar uma atividade.</div>
            <?php endif; ?>

            <form method="post" class="teacher-create-form" id="teacher-activity-form">
                <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                <label class="teacher-field teacher-field-wide">Título <span class="teacher-required-mark" aria-hidden="true">*</span>
                    <input name="title" required maxlength="160" autocomplete="off" value="<?= e($title) ?>">
                </label>
                <label class="teacher-field teacher-field-wide">Descrição e instruções <span class="teacher-required-mark" aria-hidden="true">*</span>
                    <textarea name="description" rows="4" required><?= e($description) ?></textarea>
                </label>
                <div class="teacher-field-grid">
                    <label class="teacher-field">Escola de destino <span class="teacher-required-mark" aria-hidden="true">*</span>
                        <select name="target_school" id="target-school" required <?= !$destinations ? 'disabled' : '' ?>
                            data-school-classes="<?= e(json_encode($destinations, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)) ?>">
                            <option value="">Selecione a escola</option>
                            <?php foreach ($destinations as $school => $series): ?>
                                <option value="<?= e($school) ?>"<?= $targetSchool === $school ? ' selected' : '' ?>><?= e($school) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="teacher-field">Turma / série <span class="teacher-required-mark" aria-hidden="true">*</span>
                        <select name="target_serie" id="target-serie" required <?= !$destinations ? 'disabled' : '' ?>>
                            <option value="">Selecione primeiro a escola</option>
                            <?php foreach ($destinations[$targetSchool] ?? [] as $series): ?>
                                <option value="<?= e($series) ?>"<?= $targetSerie === $series ? ' selected' : '' ?>><?= e($series) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="teacher-field">Dificuldade
                        <select name="difficulty">
                            <option value="facil"<?= $difficulty === 'facil' ? ' selected' : '' ?>>Fácil</option>
                            <option value="medio"<?= $difficulty === 'medio' ? ' selected' : '' ?>>Médio</option>
                            <option value="dificil"<?= $difficulty === 'dificil' ? ' selected' : '' ?>>Difícil</option>
                        </select>
                    </label>
                    <label class="teacher-field">Prazo de entrega
                        <input type="date" name="due_date" value="<?= e($dueDate) ?>">
                    </label>
                </div>

                <section class="teacher-math-builder" aria-labelledby="math-questions-title">
                    <div class="teacher-math-heading">
                        <div>
                            <span class="tag">02 · Questões matemáticas</span>
                            <h2 id="math-questions-title">Monte as operações</h2>
                            <p>Divisões podem ter casas decimais; informe o resultado arredondado para até seis casas.</p>
                        </div>
                        <button class="btn btn-secondary" type="button" id="add-math-question">+ Adicionar questão</button>
                    </div>
                    <div class="teacher-math-question-list" id="math-question-list">
                        <?php foreach ($submittedQuestions['first'] as $index => $firstValue): ?>
                            <fieldset class="teacher-math-question" data-math-question>
                                <legend class="teacher-math-question-title">Questão <?= $index + 1 ?></legend>
                                <button class="teacher-math-remove" type="button" data-remove-question aria-label="Remover questão <?= $index + 1 ?>">Remover</button>
                                <div class="teacher-math-fields">
                                    <label class="teacher-field">Primeiro número
                                        <input name="questions[first][]" type="text" inputmode="decimal" maxlength="17" required value="<?= e(is_string($firstValue) ? $firstValue : '') ?>">
                                    </label>
                                    <label class="teacher-field">Operação
                                        <select name="questions[operation][]" required>
                                            <?php foreach (['addition' => 'Adição (+)', 'subtraction' => 'Subtração (−)', 'multiplication' => 'Multiplicação (×)', 'division' => 'Divisão (÷)'] as $value => $label): ?>
                                                <option value="<?= e($value) ?>"<?= ($submittedQuestions['operation'][$index] ?? '') === $value ? ' selected' : '' ?>><?= e($label) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </label>
                                    <label class="teacher-field">Segundo número
                                        <input name="questions[second][]" type="text" inputmode="decimal" maxlength="17" required value="<?= e(is_string($submittedQuestions['second'][$index] ?? null) ? $submittedQuestions['second'][$index] : '') ?>">
                                    </label>
                                    <label class="teacher-field">Resultado correto
                                        <input name="questions[answer][]" type="text" inputmode="decimal" maxlength="17" required value="<?= e(is_string($submittedQuestions['answer'][$index] ?? null) ? $submittedQuestions['answer'][$index] : '') ?>">
                                    </label>
                                </div>
                                <p class="teacher-math-preview" data-question-preview aria-live="polite">Prévia da operação aparecerá aqui.</p>
                            </fieldset>
                        <?php endforeach; ?>
                    </div>
                    <template id="math-question-template">
                        <fieldset class="teacher-math-question" data-math-question>
                            <legend class="teacher-math-question-title">Questão</legend>
                            <button class="teacher-math-remove" type="button" data-remove-question>Remover</button>
                            <div class="teacher-math-fields">
                                <label class="teacher-field">Primeiro número
                                    <input name="questions[first][]" type="text" inputmode="decimal" maxlength="17" required>
                                </label>
                                <label class="teacher-field">Operação
                                    <select name="questions[operation][]" required>
                                        <option value="addition">Adição (+)</option>
                                        <option value="subtraction">Subtração (−)</option>
                                        <option value="multiplication">Multiplicação (×)</option>
                                        <option value="division">Divisão (÷)</option>
                                    </select>
                                </label>
                                <label class="teacher-field">Segundo número
                                    <input name="questions[second][]" type="text" inputmode="decimal" maxlength="17" required>
                                </label>
                                <label class="teacher-field">Resultado correto
                                    <input name="questions[answer][]" type="text" inputmode="decimal" maxlength="17" required>
                                </label>
                            </div>
                            <p class="teacher-math-preview" data-question-preview aria-live="polite">Prévia da operação aparecerá aqui.</p>
                        </fieldset>
                    </template>
                </section>

                <section class="teacher-activity-preview" aria-labelledby="activity-preview-title">
                    <div>
                        <span class="tag">03 · Publicação</span>
                        <h2 id="activity-preview-title">Prévia</h2>
                    </div>
                    <p data-activity-preview-title><?= e($title !== '' ? $title : 'Título da atividade') ?></p>
                    <p data-activity-preview-target>Selecione a escola e a turma de destino.</p>
                    <p data-activity-preview-count><?= count($submittedQuestions['first']) ?> questão(ões)</p>
                </section>

                <div class="teacher-create-actions">
                    <span>Todos os campos marcados são obrigatórios. A atividade será direcionada somente à escola e turma selecionadas.</span>
                    <button class="btn" type="submit" <?= !$destinations ? 'disabled' : '' ?>>Publicar atividade</button>
                </div>
            </form>
        </section>
    </main>
</body>

</html>
