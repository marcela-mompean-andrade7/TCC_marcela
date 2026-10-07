<?php
require 'config.php';
login_required();
if (($_SESSION['role'] ?? '') !== 'aluno') redirect('dashboard.php');
check_csrf();
$aid = (int)($_POST['activity_id'] ?? 0);
$answers = $_POST['answers'] ?? [];
$q = $pdo->prepare('SELECT * FROM questions WHERE activity_id=?');
$q->execute([$aid]);
$questions = $q->fetchAll();
if (!$questions) redirect('dashboard.php');
$score = 0;
foreach ($questions as $x) {
    $given = $answers[$x['id']] ?? null;
    if (!is_string($given) || trim($given) === '') {
        http_response_code(400);
        exit('Responda todas as questões antes de enviar a atividade.');
    }
    $given = trim($given);
    if (mb_strtolower(str_replace(' ', '', $given)) === mb_strtolower(str_replace(' ', '', $x['answer']))) $score++;
}

$pdo->beginTransaction();
$q = $pdo->prepare('INSERT INTO submissions(activity_id,student_id,score,total) VALUES(?,?,?,?)');
$q->execute([$aid, $_SESSION['user_id'], $score, count($questions)]);
$q = $pdo->prepare('DELETE FROM activity_progress WHERE activity_id=? AND student_id=?');
$q->execute([$aid, $_SESSION['user_id']]);
$points = $score * 5;
$q = $pdo->prepare('UPDATE users SET points=points+? WHERE id=?');
$q->execute([$points, $_SESSION['user_id']]);
$pdo->commit();
redirect('dashboard.php?atividade=ok');
