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

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS planets(
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(80) NOT NULL,
        slug VARCHAR(80) NOT NULL UNIQUE,
        description TEXT NOT NULL,
        icon VARCHAR(20) NOT NULL,
        price INT NOT NULL DEFAULT 0,
        display_order INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )'
);

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS student_planets(
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        student_id INT UNSIGNED NOT NULL,
        planet_id INT UNSIGNED NOT NULL,
        purchased_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_student_planet(student_id, planet_id),
        FOREIGN KEY(student_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY(planet_id) REFERENCES planets(id) ON DELETE CASCADE
    )'
);

function seed_default_planets(PDO $pdo): void
{
    $count = (int)$pdo->query('SELECT COUNT(*) FROM planets')->fetchColumn();
    if ($count > 0) {
        return;
    }

    $planets = [
        ['Terra', 'terra', 'Seu planeta de origem', '🌍', 0, 1],
        ['Marte', 'marte', 'Planeta da coragem e dos desafios', '🔴', 500, 2],
        ['Júpiter', 'jupiter', 'Planeta dos grandes desafios', '🟡', 1000, 3],
        ['Saturno', 'saturno', 'Planeta da persistência', '🪐', 1500, 4],
        ['Netuno', 'netuno', 'Planeta dos grandes exploradores', '🔵', 2000, 5],
        ['Urano', 'urano', 'Planeta dos desafios avançados', '🟣', 2500, 6],
        ['Sol', 'sol', 'Símbolo máximo da jornada', '☀️', 5000, 7],
    ];

    $stmt = $pdo->prepare('INSERT INTO planets(name, slug, description, icon, price, display_order) VALUES(?,?,?,?,?,?)');
    foreach ($planets as $planet) {
        $stmt->execute($planet);
    }
}

seed_default_planets($pdo);

function ensure_student_starter_planet(PDO $pdo, int $studentId): void
{
    $q = $pdo->prepare('SELECT id FROM planets WHERE slug = ? LIMIT 1');
    $q->execute(['terra']);
    $planet = $q->fetch();

    if (!$planet) {
        return;
    }

    $q = $pdo->prepare('INSERT IGNORE INTO student_planets(student_id, planet_id) VALUES(?, ?)');
    $q->execute([$studentId, $planet['id']]);
}

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

    if (($GLOBALS['pdo'] ?? null) instanceof PDO && ($_SESSION['role'] ?? '') === 'aluno') {
        ensure_student_starter_planet($GLOBALS['pdo'], (int)$_SESSION['user_id']);
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
