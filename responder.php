<?php
require_once __DIR__ . '/config.php';
login_required();
if (($_SESSION['role'] ?? '') !== 'aluno') redirect('dashboard.php');
check_csrf();
$aid = (int)($_POST['activity_id'] ?? 0);
$studentId = (int)$_SESSION['user_id'];
if (!student_can_access_activity($pdo, $aid, $studentId)) {
    http_response_code(404);
    exit('Atividade não encontrada ou indisponível para sua turma ou prazo.');
}
$answers = $_POST['answers'] ?? [];
$q = $pdo->prepare('SELECT * FROM questions WHERE activity_id=? ORDER BY id');
$q->execute([$aid]);
$questions = $q->fetchAll();
if (!$questions) redirect('dashboard.php');

$questionsById = [];
foreach ($questions as $question) {
    $questionsById[(int)$question['id']] = $question;
}
if (!is_array($answers) || count($answers) !== count($questionsById)) {
    http_response_code(400);
    exit('Envie uma resposta para cada questão da atividade.');
}

$normalizedAnswers = [];
foreach ($answers as $questionId => $given) {
    $questionId = filter_var((string)$questionId, FILTER_VALIDATE_INT);
    if (!$questionId || !isset($questionsById[$questionId]) || !is_string($given) || trim($given) === '' || mb_strlen($given) > 120) {
        http_response_code(400);
        exit('Uma ou mais respostas são inválidas.');
    }
    $normalizedAnswers[$questionId] = trim($given);
}

$score = 0;
$answerResults = [];
foreach ($questions as $x) {
    $questionId = (int)$x['id'];
    $given = $normalizedAnswers[$questionId] ?? null;
    if ($given === null) {
        http_response_code(400);
        exit('Responda todas as questões antes de enviar a atividade.');
    }
    if ($x['operation'] !== null && $x['first_number'] !== null && $x['second_number'] !== null) {
        $correctResult = calculate_activity_result((float)$x['first_number'], $x['operation'], (float)$x['second_number']);
        if ($correctResult === null) {
            http_response_code(500);
            exit('Não foi possível corrigir uma questão matemática. Avise o professor.');
        }
        $isCorrect = activity_math_answers_match($given, format_activity_decimal($correctResult));
    } else {
        $isCorrect = mb_strtolower(str_replace(' ', '', $given)) === mb_strtolower(str_replace(' ', '', $x['answer']));
    }
    if ($isCorrect) {
        $score++;
    }
    $answerResults[] = [$questionId, $given, $isCorrect ? 1 : 0];
}

try {
    $pdo->beginTransaction();
    $q = $pdo->prepare('INSERT INTO submissions(activity_id,student_id,score,total) VALUES(?,?,?,?)');
    $q->execute([$aid, $studentId, $score, count($questions)]);
    $submissionId = (int)$pdo->lastInsertId();
    $saveAnswer = $pdo->prepare(
        'INSERT INTO submission_answers(submission_id,question_id,answer,is_correct) VALUES(?,?,?,?)'
    );
    foreach ($answerResults as [$questionId, $given, $isCorrect]) {
        $saveAnswer->execute([$submissionId, $questionId, $given, $isCorrect]);
    }
    $q = $pdo->prepare('DELETE FROM activity_progress WHERE activity_id=? AND student_id=?');
    $q->execute([$aid, $studentId]);
    $points = $score * 5;
    $q = $pdo->prepare('UPDATE users SET points=points+? WHERE id=?');
    $q->execute([$points, $studentId]);
    $pdo->commit();
} catch (PDOException $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if (($error->errorInfo[1] ?? null) === 1062) {
        http_response_code(409);
        exit('Esta atividade já foi enviada.');
    }
    error_log('Falha ao salvar resposta da atividade: ' . $error->getMessage());
    http_response_code(500);
    exit('Não foi possível salvar suas respostas. Tente novamente.');
}
redirect('dashboard.php?atividade=ok');
