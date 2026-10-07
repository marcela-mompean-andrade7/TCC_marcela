<?php

declare(strict_types=1);
session_start();

$host = 'localhost';
$db = 'mathplay';
$user = 'root';
$pass = '';
$port = '3308';
try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
} catch (PDOException $e) {
    exit('Erro ao conectar ao banco. Confira o XAMPP e o banco mathup.');
}

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS activity_progress(
        activity_id INT UNSIGNED NOT NULL,
        student_id INT UNSIGNED NOT NULL,
        question_id INT UNSIGNED NOT NULL,
        answer VARCHAR(120) NOT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY(activity_id,student_id,question_id),
        FOREIGN KEY(activity_id) REFERENCES activities(id) ON DELETE CASCADE,
        FOREIGN KEY(student_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY(question_id) REFERENCES questions(id) ON DELETE CASCADE
    )'
);

function e(?string $v): string
{
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}
function csrf(): string
{
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function check_csrf(): void
{
    if (!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'])) {
        http_response_code(419);
        exit('Requisição inválida.');
    }
}
function login_required(): void
{
    if (empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}
function teacher_required(): void
{
    login_required();
    if (($_SESSION['role'] ?? '') !== 'professor') {
        http_response_code(403);
        exit('Acesso restrito ao professor.');
    }
}
function redirect(string $url): never
{
    header("Location: $url");
    exit;
}
