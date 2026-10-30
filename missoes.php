<?php
require_once __DIR__ . '/config.php';
login_required();

if (($_SESSION['role'] ?? '') !== 'aluno') {
    redirect('dashboard.php');
}

const MISSION_REWARD_STARS = 25;

function mission_choice(string $prompt, string $answer, array $choices, string $explanation): array
{
    return [
        'prompt' => $prompt,
        'answer' => $answer,
        'choices' => $choices,
        'explanation' => $explanation,
    ];
}

function mission_input(string $prompt, string $answer, string $explanation): array
{
    return [
        'prompt' => $prompt,
        'answer' => $answer,
        'choices' => [],
        'explanation' => $explanation,
    ];
}

function normalize_mission_answer(string $answer): string
{
    return preg_replace('/\s+/', '', str_replace(',', '.', mb_strtolower(trim($answer)))) ?? '';
}

function redirect_to_mission(string $slug): never
{
    redirect('missoes.php?mission=' . rawurlencode($slug));
}

function mission_destination_visual(array $mission): string
{
    static $planetArtwork = [
        'enigma-de-marte' => 'assets/planets/marte-planet.png',
        'fortaleza-de-jupiter' => 'assets/planets/jupiter.jpg',
        'portal-de-saturno' => 'assets/planets/saturno.jpg',
    ];
    static $destinationIcons = [
        'despertar-da-lua' => '🌙',
        'nebulosa-dos-enigmas' => '🌌',
        'ultima-fronteira' => '✨',
    ];

    $slug = (string)($mission['slug'] ?? '');
    if (isset($planetArtwork[$slug])) {
        return '<img class="mission-destination-art" src="' . e(application_asset_url($planetArtwork[$slug])) . '" alt="" aria-hidden="true">';
    }

    $icon = $destinationIcons[$slug] ?? trim((string)($mission['destination_icon'] ?? ''));
    if ($icon === '' || preg_match('/^\?+$/u', $icon) === 1 || $icon === '�') {
        $icon = '🪐';
    }

    return e($icon);
}

$questionBank = [
    'despertar-da-lua' => [
        mission_choice('A bateria auxiliar tinha 18 unidades de energia e recebeu mais 7. Quanto há agora?', '25', ['23', '25', '26', '27'], 'Somamos as energias: 18 + 7 = 25.'),
        mission_input('O reservatório tinha 42 litros e a equipe usou 19. Quantos litros restaram?', '23', 'Subtraímos o que foi usado: 42 - 19 = 23.'),
        mission_choice('Dois painéis forneceram 35 e 28 unidades. Qual é a energia total?', '63', ['53', '62', '63', '73'], 'Somamos as duas quantidades: 35 + 28 = 63.'),
        mission_input('Havia 70 sinais no painel e 36 foram desligados. Quantos continuam acesos?', '34', 'Retiramos os sinais desligados: 70 - 36 = 34.'),
        mission_choice('A estação recebeu 24 unidades, depois 18, e consumiu 9. Qual é o saldo?', '33', ['31', '32', '33', '51'], 'Primeiro somamos 24 + 18 = 42 e então subtraímos 9: 42 - 9 = 33.'),
    ],
    'enigma-de-marte' => [
        mission_choice('O mapa mostra 7 grupos com 8 marcas em cada um. Quantas marcas há?', '56', ['48', '54', '56', '64'], 'Multiplicamos os grupos: 7 × 8 = 56.'),
        mission_input('O código 81 precisa ser dividido igualmente entre 9 antenas. Quanto cabe a cada uma?', '9', 'Dividimos 81 por 9: 81 ÷ 9 = 9.'),
        mission_choice('Cada veículo percorre 12 quilômetros por rota. Qual a distância de 6 rotas?', '72', ['62', '70', '72', '82'], 'São 6 grupos de 12: 12 × 6 = 72 quilômetros.'),
        mission_input('96 amostras serão distribuídas por 8 laboratórios. Quantas ficam em cada um?', '12', 'Dividimos as amostras igualmente: 96 ÷ 8 = 12.'),
        mission_choice('A equipe encontrou 9 caixas com 7 cristais cada e usou 8 cristais. Quantos sobraram?', '55', ['47', '54', '55', '63'], 'Primeiro: 9 × 7 = 63. Depois: 63 - 8 = 55.'),
    ],
    'fortaleza-de-jupiter' => [
        mission_choice('Resolva respeitando a prioridade da multiplicação: 8 + 3 × 4.', '20', ['44', '20', '32', '24'], 'A multiplicação vem primeiro: 3 × 4 = 12; depois 8 + 12 = 20.'),
        mission_input('Resolva: (18 - 6) ÷ 3.', '4', 'Os parênteses primeiro: 18 - 6 = 12; então 12 ÷ 3 = 4.'),
        mission_choice('Qual é o resultado de 5 × (7 + 2) - 11?', '34', ['24', '34', '45', '56'], 'Parênteses: 7 + 2 = 9. Multiplicação: 5 × 9 = 45. Subtração: 45 - 11 = 34.'),
        mission_input('Resolva: 48 ÷ 6 + 7 × 3.', '29', 'Divisão e multiplicação primeiro: 8 + 21 = 29.'),
        mission_choice('Resolva: 36 - (4 + 2) × 5.', '6', ['6', '30', '66', '150'], 'Parênteses: 4 + 2 = 6. Multiplicação: 6 × 5 = 30. Por fim: 36 - 30 = 6.'),
        mission_input('Resolva da esquerda para a direita: (24 + 12) ÷ 4 × 2.', '18', 'Parênteses: 36. Depois, da esquerda para a direita: 36 ÷ 4 = 9 e 9 × 2 = 18.'),
    ],
    'portal-de-saturno' => [
        mission_choice('Uma peça mede 1/2 metro e outra 1/4 metro. Qual é o comprimento total?', '3/4', ['1/4', '2/6', '3/4', '1'], 'Com denominador 4: 1/2 = 2/4; então 2/4 + 1/4 = 3/4.'),
        mission_input('O sensor marcou 0,60 e depois 0,25. Qual é a soma decimal?', '0.85', 'Alinhando as casas decimais: 0,60 + 0,25 = 0,85.'),
        mission_choice('Três quartos de 20 parafusos serão usados. Quantos são?', '15', ['5', '12', '15', '18'], 'Um quarto de 20 é 5. Três quartos são 3 × 5 = 15.'),
        mission_input('Some os comprimentos: 1,5 m + 2,35 m.', '3.85', 'Escrevendo 1,5 como 1,50: 1,50 + 2,35 = 3,85 metros.'),
        mission_choice('Qual número decimal representa a fração 2/5?', '0.4', ['0.25', '0.4', '0.5', '2.5'], 'Dividimos o numerador pelo denominador: 2 ÷ 5 = 0,4.'),
        mission_input('O fluxo de 2,4 litros foi dividido em porções de 0,6 litro. Quantas porções foram feitas?', '4', 'Calculamos 2,4 ÷ 0,6. Multiplicando ambos por 10: 24 ÷ 6 = 4.'),
    ],
    'nebulosa-dos-enigmas' => [
        mission_choice('Resolva 3x + 5 = 20. Qual é o valor de x?', '5', ['3', '5', '8', '15'], 'Subtraia 5 dos dois lados: 3x = 15. Divida por 3: x = 5.'),
        mission_input('Resolva 2(x + 4) = 18.', '5', 'Divida por 2: x + 4 = 9. Subtraia 4: x = 5.'),
        mission_choice('Resolva x ÷ 3 + 4 = 9.', '15', ['5', '12', '15', '39'], 'Subtraia 4: x ÷ 3 = 5. Multiplique por 3: x = 15.'),
        mission_input('A sequência é 2, 5, 11, 23... Cada termo é o anterior vezes 2 mais 1. Qual é o próximo?', '47', 'Aplicamos a regra ao último termo: 23 × 2 + 1 = 47.'),
        mission_choice('O irmão tem 21 anos, que é 3 anos a mais que o dobro da idade de Lia. Quantos anos Lia tem?', '9', ['6', '8', '9', '12'], 'Se x é a idade de Lia, 2x + 3 = 21. Então 2x = 18 e x = 9.'),
        mission_input('Dois números somam 30 e a diferença entre eles é 8. Qual é o maior?', '19', 'Somando as duas informações: o maior é (30 + 8) ÷ 2 = 19.'),
        mission_choice('Resolva 5x - 7 = 2x + 8.', '5', ['3', '5', '8', '15'], 'Subtraia 2x: 3x - 7 = 8. Some 7: 3x = 15. Logo x = 5.'),
    ],
    'ultima-fronteira' => [
        mission_choice('O núcleo recebeu 12,5 unidades e mais 7,5. Qual é o total?', '20', ['19', '20', '20.5', '21'], 'Somamos as partes inteiras e decimais: 12,5 + 7,5 = 20.'),
        mission_input('Divida 144 cristais igualmente entre 12 módulos.', '12', '144 ÷ 12 = 12 cristais por módulo.'),
        mission_choice('Calcule (9 - 3)².', '36', ['12', '18', '36', '72'], 'Primeiro 9 - 3 = 6. Depois 6² = 6 × 6 = 36.'),
        mission_input('Dois terços de 27 sondas foram ativados. Quantas sondas?', '18', 'Um terço de 27 é 9; dois terços são 2 × 9 = 18.'),
        mission_choice('Qual fração simplificada equivale a 0,75?', '3/4', ['1/4', '2/3', '3/4', '7/5'], '0,75 = 75/100. Simplificando por 25, obtemos 3/4.'),
        mission_input('Resolva 4x + 6 = 30.', '6', 'Subtraia 6: 4x = 24. Divida por 4: x = 6.'),
        mission_choice('Um painel triangular tem base de 10 m e altura de 8 m. Qual é sua área?', '40', ['18', '40', '80', '90'], 'A área do triângulo é base × altura ÷ 2: 10 × 8 ÷ 2 = 40 m².'),
        mission_input('Complete a sequência: 3, 6, 12, 24...', '48', 'Cada termo é o dobro do anterior: 24 × 2 = 48.'),
        mission_choice('Cinco sondas transmitem 20 dados cada. Quantos dados chegam ao centro?', '100', ['25', '80', '100', '120'], 'Multiplicamos a quantidade de sondas pelos dados de cada uma: 5 × 20 = 100.'),
        mission_input('O núcleo tinha 240 unidades e recuperou 25% dessa energia. Quantas unidades recuperou?', '60', '25% é um quarto. Um quarto de 240 é 240 ÷ 4 = 60.'),
    ],
];

$studentId = (int)$_SESSION['user_id'];
$studentQuery = $pdo->prepare('SELECT id, name, points, serie FROM users WHERE id = ? AND role = ?');
$studentQuery->execute([$studentId, 'aluno']);
$student = $studentQuery->fetch();
if (!$student) {
    redirect('dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $slug = (string)($_POST['mission'] ?? '');
    $action = (string)($_POST['action'] ?? '');
    if (!isset($questionBank[$slug]) || !in_array($action, ['start', 'answer'], true)) {
        http_response_code(400);
        exit('Missão ou ação inválida.');
    }

    try {
        $pdo->beginTransaction();
        $userLock = $pdo->prepare('SELECT points, role FROM users WHERE id = ? FOR UPDATE');
        $userLock->execute([$studentId]);
        $lockedStudent = $userLock->fetch();
        if (!$lockedStudent || $lockedStudent['role'] !== 'aluno') {
            throw new RuntimeException('Apenas alunos podem participar das missões.');
        }

        $missionQuery = $pdo->prepare('SELECT * FROM missions WHERE slug = ?');
        $missionQuery->execute([$slug]);
        $mission = $missionQuery->fetch();
        if (!$mission || (int)$mission['question_count'] !== count($questionBank[$slug])) {
            throw new RuntimeException('Esta missão está indisponível no momento.');
        }

        if ($action === 'start') {
            $activeAttemptQuery = $pdo->prepare(
                'SELECT id FROM mission_attempts
                 WHERE student_id = ? AND mission_id = ? AND completed_at IS NULL
                 ORDER BY id DESC LIMIT 1 FOR UPDATE'
            );
            $activeAttemptQuery->execute([$studentId, $mission['id']]);
            if (!$activeAttemptQuery->fetch()) {
                $createAttempt = $pdo->prepare('INSERT INTO mission_attempts(mission_id, student_id) VALUES(?, ?)');
                $createAttempt->execute([$mission['id'], $studentId]);
            }
            $pdo->commit();
            redirect_to_mission($slug);
        }

        $attemptId = filter_var($_POST['attempt_id'] ?? null, FILTER_VALIDATE_INT);
        $questionNumber = filter_var($_POST['question_number'] ?? null, FILTER_VALIDATE_INT);
        $answer = $_POST['answer'] ?? null;
        if (!$attemptId || !$questionNumber || !is_string($answer) || mb_strlen($answer) > 255) {
            throw new RuntimeException('Resposta inválida. Confira os dados e tente novamente.');
        }

        $attemptQuery = $pdo->prepare(
            'SELECT * FROM mission_attempts
             WHERE id = ? AND student_id = ? AND mission_id = ? FOR UPDATE'
        );
        $attemptQuery->execute([$attemptId, $studentId, $mission['id']]);
        $attempt = $attemptQuery->fetch();
        if (!$attempt || $attempt['completed_at'] !== null || (int)$attempt['current_question'] !== $questionNumber) {
            throw new RuntimeException('Esta questão já foi respondida ou a tentativa não está ativa. Atualize a página.');
        }

        $question = $questionBank[$slug][$questionNumber - 1] ?? null;
        if (!$question || ($question['choices'] && !in_array($answer, $question['choices'], true))) {
            throw new RuntimeException('A resposta enviada não corresponde a esta questão.');
        }

        $isCorrect = normalize_mission_answer($answer) === normalize_mission_answer($question['answer']);
        $saveAnswer = $pdo->prepare('INSERT INTO mission_answers(attempt_id, question_number, answer, is_correct) VALUES(?, ?, ?, ?)');
        $saveAnswer->execute([$attemptId, $questionNumber, trim($answer), $isCorrect ? 1 : 0]);

        $newCorrectAnswers = (int)$attempt['correct_answers'] + ($isCorrect ? 1 : 0);
        $nextQuestion = (int)$attempt['current_question'] + ($isCorrect ? 1 : 0);
        $missionComplete = $isCorrect && $nextQuestion > (int)$mission['question_count'];

        if ($isCorrect) {
            $updateAttempt = $pdo->prepare(
                'UPDATE mission_attempts
                 SET current_question = ?, correct_answers = ?, completed_at = ?
                 WHERE id = ? AND student_id = ? AND completed_at IS NULL'
            );
            $updateAttempt->execute([
                $nextQuestion,
                $newCorrectAnswers,
                $missionComplete ? date('Y-m-d H:i:s') : null,
                $attemptId,
                $studentId,
            ]);
        }

        $rewardAwarded = false;
        $rewardPreviouslyReceived = false;
        if ($missionComplete) {
            $rewardQuery = $pdo->prepare('SELECT id FROM mission_rewards WHERE student_id = ? AND mission_id = ? FOR UPDATE');
            $rewardQuery->execute([$studentId, $mission['id']]);
            $existingReward = $rewardQuery->fetch();
            if ($existingReward) {
                $rewardPreviouslyReceived = true;
            } else {
                $saveReward = $pdo->prepare('INSERT INTO mission_rewards(student_id, mission_id, attempt_id, stars) VALUES(?, ?, ?, ?)');
                $saveReward->execute([$studentId, $mission['id'], $attemptId, MISSION_REWARD_STARS]);
                $creditStars = $pdo->prepare('UPDATE users SET points = points + ? WHERE id = ?');
                $creditStars->execute([MISSION_REWARD_STARS, $studentId]);
                if ($creditStars->rowCount() !== 1) {
                    throw new RuntimeException('Não foi possível creditar a recompensa.');
                }
                $rewardAwarded = true;
            }
        }

        $pdo->commit();
        if ($isCorrect) {
            unset($_SESSION['mission_flash']);
        } else {
            $_SESSION['mission_flash'] = [
                'slug' => $slug,
                'attempt_id' => (int)$attemptId,
                'question_number' => (int)$questionNumber,
            ];
        }
    } catch (RuntimeException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['mission_error'] = $e->getMessage();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Erro ao processar missão ' . ($slug ?? '') . ' para aluno ' . $studentId . ': ' . $e->getMessage());
        $_SESSION['mission_error'] = 'Não foi possível processar sua resposta. Tente novamente.';
    }

    redirect_to_mission($slug);
}

$missionListQuery = $pdo->prepare(
    'SELECT m.*,
            (SELECT a.current_question FROM mission_attempts a
             WHERE a.mission_id = m.id AND a.student_id = ?
             ORDER BY a.id DESC LIMIT 1) AS latest_question,
            (SELECT a.completed_at FROM mission_attempts a
             WHERE a.mission_id = m.id AND a.student_id = ?
             ORDER BY a.id DESC LIMIT 1) AS latest_completed_at,
            EXISTS(SELECT 1 FROM mission_rewards r WHERE r.mission_id = m.id AND r.student_id = ?) AS reward_received
     FROM missions m
     ORDER BY m.display_order, m.id'
);
$missionListQuery->execute([$studentId, $studentId, $studentId]);
$missions = $missionListQuery->fetchAll();
$missionBySlug = [];
foreach ($missions as $missionRow) {
    $missionBySlug[$missionRow['slug']] = $missionRow;
}

$selectedSlug = (string)($_GET['mission'] ?? '');
$selectedMission = $missionBySlug[$selectedSlug] ?? null;
if ($selectedSlug !== '' && !$selectedMission) {
    http_response_code(404);
}

$attempt = null;
$currentQuestion = null;
$reward = null;
if ($selectedMission) {
    $attemptQuery = $pdo->prepare(
        'SELECT * FROM mission_attempts
         WHERE student_id = ? AND mission_id = ?
         ORDER BY id DESC LIMIT 1'
    );
    $attemptQuery->execute([$studentId, $selectedMission['id']]);
    $attempt = $attemptQuery->fetch() ?: null;

    $rewardQuery = $pdo->prepare('SELECT * FROM mission_rewards WHERE student_id = ? AND mission_id = ?');
    $rewardQuery->execute([$studentId, $selectedMission['id']]);
    $reward = $rewardQuery->fetch() ?: null;

    if ($attempt && $attempt['completed_at'] === null) {
        $currentQuestion = $questionBank[$selectedSlug][(int)$attempt['current_question'] - 1] ?? null;
    }
}

$flash = $_SESSION['mission_flash'] ?? null;
if (
    is_array($flash)
    && ($flash['slug'] ?? '') === $selectedSlug
    && (int)($flash['attempt_id'] ?? 0) === (int)($attempt['id'] ?? 0)
    && (int)($flash['question_number'] ?? 0) === (int)($attempt['current_question'] ?? 0)
) {
    unset($_SESSION['mission_flash']);
} else {
    $flash = null;
}
$pageError = $_SESSION['mission_error'] ?? '';
unset($_SESSION['mission_error']);

$studentQuery->execute([$studentId, 'aluno']);
$student = $studentQuery->fetch();
$completedCount = 0;
$totalMissionStars = 0;
foreach ($missions as $missionRow) {
    if ((bool)$missionRow['reward_received']) {
        $completedCount++;
        $totalMissionStars += MISSION_REWARD_STARS;
    }
}
$nextMission = null;
foreach ($missions as $missionRow) {
    if (!(bool)$missionRow['reward_received']) {
        $nextMission = $missionRow;
        break;
    }
}
?>
<!doctype html>
<html lang="<?= e(current_language()) ?>" <?= accessibility_html_attributes() ?>>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="theme-color" content="#F5F7FF">
    <title>Missões Espaciais — Lunaris Academy</title>
    <link rel="stylesheet" href="<?= e(application_asset_url('css/style.css')) ?>">
    <script src="<?= e(application_asset_url('js/theme.js')) ?>"></script>
</head>

<body>
    <header class="nav">
        <a class="nav-brand" href="dashboard.php"><img src="<?= e(application_asset_url('imagens/logo.png')) ?>" alt="" aria-hidden="true">Lunaris Academy</a>
        <nav lang="<?= e(current_language()) ?>">
            <a href="dashboard.php"><?= e(t('Painel')) ?></a>
            <a href="universo.php"><?= e(t('Universo')) ?></a>
            <a href="missoes.php" aria-current="page"><?= e(t('Missões')) ?></a>
            <a href="planetas.php"><?= e(t('Planetas')) ?></a>
            <a href="avatares.php"><?= e(t('Avatares')) ?></a>
            <a href="perfil.php"><?= e(t('Perfil')) ?></a>
            <a href="logout.php"><?= e(t('Sair')) ?></a>
        </nav>
    </header>

    <main class="container mission-page">
        <section class="mission-shell">
            <?php if ($selectedMission): ?>
                <div class="page-header mission-page-heading">
                    <div>
                        <a class="mission-back-link" href="missoes.php">← Voltar às missões</a>
                        <span class="tag"><?= mission_destination_visual($selectedMission) ?> <?= e($selectedMission['destination']) ?></span>
                        <h1><?= e($selectedMission['title']) ?></h1>
                        <p><?= e($selectedMission['story']) ?></p>
                    </div>
                    <div class="points">⭐ <?= e((string)$student['points']) ?> pontos</div>
                </div>

                <div class="mission-facts">
                    <span><strong>Conteúdo:</strong> <?= e($selectedMission['topic']) ?></span>
                    <span><strong>Dificuldade:</strong> <?= e($selectedMission['difficulty']) ?></span>
                    <span><strong>Objetivo:</strong> <?= e((string)$selectedMission['question_count']) ?> respostas corretas</span>
                    <span class="mission-reward-label">Recompensa: +25 ⭐</span>
                </div>

                <?php if ($pageError): ?>
                    <div class="alert" role="alert"><?= e($pageError) ?></div>
                <?php endif; ?>

                <?php if (!$attempt || ($attempt['completed_at'] !== null && ($_GET['practice'] ?? '') === '1')): ?>
                    <div class="mission-ready">
                        <span class="mission-ready-icon" aria-hidden="true"><?= mission_destination_visual($selectedMission) ?></span>
                        <div>
                            <h2><?= $reward ? 'Pratique novamente quando quiser' : 'Pronto para decolar?' ?></h2>
                            <p><?= $reward ? 'Você já recebeu a recompensa desta missão. Uma nova tentativa serve para praticar, sem estrelas adicionais.' : 'Responda corretamente a todas as questões para concluir a missão e receber 25 estrelas.' ?></p>
                            <form method="post">
                                <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                                <input type="hidden" name="mission" value="<?= e($selectedSlug) ?>">
                                <input type="hidden" name="action" value="start">
                                <button class="btn" type="submit"><?= $reward ? 'Praticar novamente' : 'Iniciar missão' ?></button>
                            </form>
                        </div>
                    </div>
                <?php elseif ($attempt['completed_at'] !== null): ?>
                    <?php
                    $rewardAwardedNow = $reward && (int)$reward['attempt_id'] === (int)$attempt['id'];
                    $rewardAlreadyReceived = $reward && !$rewardAwardedNow;
                    $nextIndex = array_search($selectedMission, $missions, true);
                    $nextMissionItem = $nextIndex !== false ? ($missions[$nextIndex + 1] ?? null) : null;
                    ?>
                    <section class="mission-complete" role="status" aria-live="polite">
                        <span class="mission-complete-stars" aria-hidden="true">✦　⭐　✦</span>
                        <span class="tag">🌟 Missão concluída</span>
                        <h2>🌟 MISSÃO CONCLUÍDA!</h2>
                        <p>Parabéns, explorador! Você superou os desafios matemáticos e conquistou mais estrelas para sua jornada na Lunaris Academy.</p>
                        <strong class="mission-earned"><?= $rewardAwardedNow ? '+25 ⭐ ESTRELAS' : ($rewardAlreadyReceived ? 'RECOMPENSA JÁ RECEBIDA' : 'MISSÃO CONCLUÍDA') ?></strong>
                        <div class="mission-result-stats">
                            <span><small>Missão</small><strong><?= e($selectedMission['title']) ?></strong></span>
                            <span><small>Questões respondidas</small><strong><?= e((string)$selectedMission['question_count']) ?></strong></span>
                            <span><small>Total de acertos</small><strong><?= e((string)$attempt['correct_answers']) ?></strong></span>
                            <span><small>Saldo atualizado</small><strong><?= e((string)$student['points']) ?> pontos</strong></span>
                        </div>
                        <p><?= $rewardAwardedNow ? 'O servidor confirmou o crédito de 25 estrelas ao seu saldo.' : 'Esta missão já havia concedido suas 25 estrelas; praticar novamente não gera outro crédito.' ?></p>
                        <div class="actions mission-result-actions">
                            <a class="btn btn-secondary" href="missoes.php">Voltar às Missões</a>
                            <?php if ($nextMissionItem): ?><a class="btn" href="missoes.php?mission=<?= e($nextMissionItem['slug']) ?>">Próxima Missão</a><?php endif; ?>
                            <a class="btn light" href="missoes.php?mission=<?= e($selectedSlug) ?>&amp;practice=1">Praticar novamente</a>
                        </div>
                    </section>
                <?php elseif ($currentQuestion): ?>
                    <?php
                    $questionTotal = (int)$selectedMission['question_count'];
                    $questionNumber = (int)$attempt['current_question'];
                    $progressPercent = (int)floor((int)$attempt['correct_answers'] / $questionTotal * 100);
                    ?>
                    <section class="mission-question-panel">
                        <div class="mission-progress-heading">
                            <span>Questão <?= e((string)$questionNumber) ?> de <?= e((string)$questionTotal) ?></span>
                            <strong><?= e((string)$attempt['correct_answers']) ?> acertos</strong>
                        </div>
                        <progress class="mission-progress" value="<?= e((string)$attempt['correct_answers']) ?>" max="<?= e((string)$questionTotal) ?>" aria-label="Progresso da missão"><?= e((string)$progressPercent) ?>%</progress>
                        <div class="mission-question">
                            <span class="tag">Desafio matemático</span>
                            <h2><?= e($currentQuestion['prompt']) ?></h2>
                            <?php if ($flash): ?>
                                <div class="mission-feedback is-incorrect" role="status">
                                    <strong>Essa não é a resposta. Tente novamente.</strong>
                                </div>
                            <?php endif; ?>
                            <form method="post" class="mission-answer-form">
                                <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                                <input type="hidden" name="mission" value="<?= e($selectedSlug) ?>">
                                <input type="hidden" name="action" value="answer">
                                <input type="hidden" name="attempt_id" value="<?= e((string)$attempt['id']) ?>">
                                <input type="hidden" name="question_number" value="<?= e((string)$questionNumber) ?>">
                                <?php if ($currentQuestion['choices']): ?>
                                    <fieldset class="mission-choices">
                                        <legend>Escolha uma resposta</legend>
                                        <?php foreach ($currentQuestion['choices'] as $choice): ?>
                                            <label><input type="radio" name="answer" value="<?= e($choice) ?>" required><span><?= e($choice) ?></span></label>
                                        <?php endforeach; ?>
                                    </fieldset>
                                <?php else: ?>
                                    <label class="mission-input-label">Sua resposta
                                        <input type="text" name="answer" inputmode="decimal" autocomplete="off" maxlength="255" required>
                                    </label>
                                <?php endif; ?>
                                <button class="btn" type="submit">Verificar resposta</button>
                            </form>
                        </div>
                    </section>
                <?php endif; ?>
            <?php elseif (!$missions): ?>
                <div class="alert" role="alert">Nenhuma missão está cadastrada. Aplique a migração <code>mission_migration.sql</code> para instalar as missões.</div>
            <?php else: ?>
                <section class="mission-hero">
                    <span class="tag">🚀 Jornada matemática</span>
                    <h1>Missões Espaciais</h1>
                    <p>Explore o universo, resolva desafios matemáticos e conquiste estrelas para evoluir na Lunaris Academy!</p>
                    <?php if (!empty($student['serie'])): ?><small>Desafios para <?= e($student['serie']) ?></small><?php endif; ?>
                </section>

                <div class="mission-summary" aria-label="Resumo da jornada">
                    <article><span>🏅 Missões concluídas</span><strong><?= $completedCount ?> de <?= count($missions) ?></strong></article>
                    <article><span>🚀 Missões disponíveis</span><strong><?= count($missions) - $completedCount ?></strong></article>
                    <article><span>⭐ Estrelas conquistadas</span><strong><?= $totalMissionStars ?></strong></article>
                    <?php if ($nextMission): ?><a href="missoes.php?mission=<?= e($nextMission['slug']) ?>"><span>🧭 Próxima missão</span><strong><?= e($nextMission['title']) ?> →</strong></a><?php endif; ?>
                </div>

                <div class="mission-grid">
                    <?php foreach ($missions as $missionRow):
                        $hasReward = (bool)$missionRow['reward_received'];
                        $isCompleted = $missionRow['latest_completed_at'] !== null;
                        $hasStarted = $missionRow['latest_question'] !== null;
                        $progress = min((int)$missionRow['question_count'], max(0, (int)$missionRow['latest_question'] - 1));
                        $statusClass = $isCompleted ? 'completed' : ($hasStarted ? 'in-progress' : 'available');
                        $statusLabel = $isCompleted ? 'Concluída' : ($hasStarted ? 'Em andamento' : 'Disponível');
                    ?>
                        <article class="mission-card <?= $statusClass ?>">
                            <div class="mission-card-top">
                                <span class="mission-planet" aria-hidden="true"><?= mission_destination_visual($missionRow) ?></span>
                                <span class="mission-status <?= $statusClass ?>"><?= e($statusLabel) ?></span>
                            </div>
                            <p class="mission-destination"><?= e($missionRow['destination']) ?> · <?= e($missionRow['difficulty']) ?></p>
                            <h2><?= e($missionRow['title']) ?></h2>
                            <p><?= e($missionRow['story']) ?></p>
                            <dl>
                                <div><dt>Conteúdo</dt><dd><?= e($missionRow['topic']) ?></dd></div>
                                <div><dt>Desafios</dt><dd><?= e((string)$missionRow['question_count']) ?> respostas corretas</dd></div>
                                <div><dt>Recompensa</dt><dd>+25 ⭐<?= $hasReward ? ' · recebida' : '' ?></dd></div>
                            </dl>
                            <div class="mission-card-progress">
                                <div><span>Progresso</span><strong><?= e((string)$progress) ?>/<?= e((string)$missionRow['question_count']) ?></strong></div>
                                <progress value="<?= e((string)$progress) ?>" max="<?= e((string)$missionRow['question_count']) ?>" aria-label="Progresso em <?= e($missionRow['title']) ?>"></progress>
                            </div>
                            <a class="btn small" href="missoes.php?mission=<?= e($missionRow['slug']) ?>"><?= $isCompleted ? 'Ver conclusão / praticar' : ($hasStarted ? 'Continuar missão' : 'Iniciar missão') ?></a>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>
</body>

</html>
