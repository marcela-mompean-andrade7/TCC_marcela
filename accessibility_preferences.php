<?php
require 'config.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido.']);
    exit;
}

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Entre na sua conta para salvar suas preferências.']);
    exit;
}

if (!isset($_POST['csrf'])
    || !is_string($_POST['csrf'])
    || !hash_equals((string)($_SESSION['csrf'] ?? ''), $_POST['csrf'])
) {
    http_response_code(403);
    echo json_encode(['error' => 'Sua sessão expirou. Atualize a página e tente novamente.']);
    exit;
}

$submittedPreferences = $_POST['accessibility_needs'] ?? [];
$validPreferences = accessibility_preference_keys();
if (!is_array($submittedPreferences)
    || !array_is_list($submittedPreferences)
    || count(array_filter($submittedPreferences, 'is_string')) !== count($submittedPreferences)
    || array_diff($submittedPreferences, $validPreferences)
    || count(array_intersect($submittedPreferences, array_keys(accessibility_palette_options()))) > 1
) {
    http_response_code(422);
    echo json_encode(['error' => 'As preferências de acessibilidade enviadas são inválidas.']);
    exit;
}

$preferences = array_values(array_unique($submittedPreferences));
$encodedPreferences = $preferences ? json_encode($preferences, JSON_THROW_ON_ERROR) : null;
$update = $pdo->prepare('UPDATE users SET accessibility_needs = ? WHERE id = ?');
$update->execute([$encodedPreferences, $_SESSION['user_id']]);

if ($update->rowCount() === 0) {
    $exists = $pdo->prepare('SELECT 1 FROM users WHERE id = ?');
    $exists->execute([$_SESSION['user_id']]);
    if (!$exists->fetchColumn()) {
        http_response_code(401);
        echo json_encode(['error' => 'Sua conta não está mais disponível. Entre novamente.']);
        exit;
    }
}

$_SESSION['lunaris_accessibility'] = $preferences;
$_SESSION['lunaris_preferences_loaded'] = true;
echo json_encode(['preferences' => $preferences]);
