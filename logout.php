<?php require_once __DIR__ . '/config.php';
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    setcookie(session_name(), ' ', time() - 42000, '/');
}
session_destroy();
redirect('index.php');
