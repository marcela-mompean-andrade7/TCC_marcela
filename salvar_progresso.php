<?php
require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');

function progress_response(int $status, array $body): never
{
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    progress_response(405, ['message' => 'Método não permitido.']);
}
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'aluno') {
    progress_response(403, ['message' => 'Acesso restrito ao aluno.']);
}
if (!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf'] ?? '', (string)$_POST['csrf'])) {
    progress_response(419, ['message' => 'Requisição inválida. Atualize a página e tente novamente.']);
}

$activityId = filter_var($_POST['activity_id'] ?? null, FILTER_VALIDATE_INT);
$answers = $_POST['answers'] ?? null;
if (!$activityId || !is_array($answers)) {
    progress_response(400, ['message' => 'Dados de progresso inválidos.']);
}

$studentId = (int)$_SESSION['user_id'];
if (!student_can_access_activity($pdo, $activityId, $studentId)) {
    progress_response(404, ['message' => 'Atividade não encontrada ou indisponível para sua turma ou prazo.']);
}

$query = $pdo->prepare('SELECT id FROM questions WHERE activity_id=?');
$query->execute([$activityId]);
$questionIds = array_fill_keys(array_column($query->fetchAll(), 'id'), true);

foreach ($answers as $questionId => $answer) {
    $questionId = filter_var($questionId, FILTER_VALIDATE_INT);
    if (!$questionId || !isset($questionIds[$questionId]) || !is_string($answer) || mb_strlen($answer) > 120) {
        progress_response(400, ['message' => 'Uma ou mais respostas são inválidas.']);
    }
}

$query = $pdo->prepare('SELECT id FROM submissions WHERE activity_id=? AND student_id=?');
$query->execute([$activityId, $studentId]);
if ($query->fetch()) {
    progress_response(409, ['message' => 'Esta atividade já foi enviada.']);
}

$pdo->beginTransaction();
$saveAnswer = $pdo->prepare(
    'INSERT INTO activity_progress (activity_id, student_id, question_id, answer)
     VALUES (?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE answer=VALUES(answer), updated_at=CURRENT_TIMESTAMP'
);
$deleteAnswer = $pdo->prepare(
    'DELETE FROM activity_progress WHERE activity_id=? AND student_id=? AND question_id=?'
);

foreach ($answers as $questionId => $answer) {
    $questionId = (int)$questionId;
    $answer = trim($answer);
    if ($answer === '') {
        $deleteAnswer->execute([$activityId, $studentId, $questionId]);
    } else {
        $saveAnswer->execute([$activityId, $studentId, $questionId, $answer]);
    }
}

$pdo->commit();
progress_response(200, ['message' => 'Progresso salvo.']);
