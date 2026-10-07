<?php
require 'config.php';
login_required();

try {
    $pdo->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS profile_photo VARCHAR(255) NULL AFTER password_hash");
} catch (Throwable $e) {
}

$notice = '';
$errors = [];

$q = $pdo->prepare('SELECT * FROM users WHERE id=?');
$q->execute([$_SESSION['user_id']]);
$u = $q->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();

    $newName = trim((string)($_POST['name'] ?? ''));
    $newPassword = (string)($_POST['password'] ?? '');

    if ($newName === '') {
        $errors[] = 'Digite um nome válido.';
    }

    if ($newPassword !== '' && strlen($newPassword) < 6) {
        $errors[] = 'A senha precisa ter pelo menos 6 caracteres.';
    }

    $photoPath = $u['profile_photo'] ?? null;

    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['profile_photo'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Erro ao enviar a foto de perfil.';
        } else {
            $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            $type = mime_content_type($file['tmp_name']);

            if (!in_array($type, $allowed, true)) {
                $errors[] = 'Formato de imagem inválido. Use JPG, PNG, WEBP ou GIF.';
            } elseif ($file['size'] > 2 * 1024 * 1024) {
                $errors[] = 'A foto deve ter no máximo 2MB.';
            } else {
                $uploadDir = __DIR__ . '/uploads/profile';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $fileName = 'user_' . $_SESSION['user_id'] . '_' . time() . '.' . strtolower($ext ?: 'png');
                $target = $uploadDir . '/' . $fileName;

                if (move_uploaded_file($file['tmp_name'], $target)) {
                    $photoPath = 'uploads/profile/' . $fileName;

                    if (!empty($u['profile_photo']) && file_exists(__DIR__ . '/' . $u['profile_photo'])) {
                        unlink(__DIR__ . '/' . $u['profile_photo']);
                    }
                } else {
                    $errors[] = 'Não foi possível salvar a foto.';
                }
            }
        }
    }

    if (!$errors) {
        $sql = 'UPDATE users SET name = ?, profile_photo = ?';
        $params = [$newName, $photoPath];

        if ($newPassword !== '') {
            $sql .= ', password_hash = ?';
            $params[] = password_hash($newPassword, PASSWORD_DEFAULT);
        }

        $sql .= ' WHERE id = ?';
        $params[] = $_SESSION['user_id'];

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $notice = 'Perfil atualizado com sucesso.';

        $q = $pdo->prepare('SELECT * FROM users WHERE id=?');
        $q->execute([$_SESSION['user_id']]);
        $u = $q->fetch();
    }
}

$avatar = !empty($u['profile_photo']) ? $u['profile_photo'] : null;

$q = $pdo->prepare('SELECT p.* FROM student_planets sp JOIN planets p ON p.id = sp.planet_id WHERE sp.student_id = ? ORDER BY p.display_order ASC');
$q->execute([$_SESSION['user_id']]);
$ownedPlanets = $q->fetchAll();
?>
<!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Perfil</title>
    <link rel="stylesheet" href="assets/style.css">
    <script src="assets/theme.js"></script>
</head>

<body>
    <header class="nav"><b>MathUp</b>
        <nav><a href="dashboard.php">Painel</a><a href="planetas.php">🪐 Planetas</a><a href="logout.php">Sair</a></nav>
    </header>

    <main class="container narrow profile-page">
        <section class="profile-shell">
            <div class="profile-header">
                <div class="profile-avatar-large">
                    <?php if ($avatar): ?>
                        <img src="<?= e($avatar) ?>" alt="Foto de perfil">
                    <?php else: ?>
                        <?= e(strtoupper(substr($u['name'], 0, 1))) ?>
                    <?php endif; ?>
                </div>

                <div class="profile-title-wrap">
                    <span class="tag">Perfil</span>
                    <h1>Meu perfil</h1>
                </div>

                <div class="points">⭐ <?= e((string)$u['points']) ?> pontos</div>
            </div>

            <?php if ($notice): ?>
                <div class="success-message"><?= e($notice) ?></div>
            <?php endif; ?>

            <?php foreach ($errors as $error): ?>
                <div class="alert"><?= e($error) ?></div>
            <?php endforeach; ?>

            <div class="profile-grid">
                <div class="profile-card">
                    <h3>Dados atuais</h3>
                    <div class="info-row"><span>Nome</span><strong><?= e($u['name']) ?></strong></div>
                    <div class="info-row"><span>E-mail</span><strong><?= e($u['email']) ?></strong></div>
                    <div class="info-row"><span>Tipo</span><strong><?= e($u['role']) ?></strong></div>
                    <div class="info-row"><span>Escola</span><strong><?= e($u['escola']) ?></strong></div>
                </div>

                <div class="profile-card">
                    <h3>Editar perfil</h3>
                    <form method="post" enctype="multipart/form-data" class="profile-form">
                        <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">

                        <label>
                            Nome
                            <input type="text" name="name" value="<?= e($u['name']) ?>" required>
                        </label>

                        <label>
                            Nova senha
                            <input type="password" name="password" placeholder="Deixe em branco para manter a atual">
                        </label>

                        <label>
                            Foto de perfil
                            <input type="file" name="profile_photo" accept="image/*">
                        </label>

                        <button type="submit" class="btn">Salvar alterações</button>
                    </form>
                </div>
            </div>

            <?php if (($u['role'] ?? '') === 'aluno'): ?>
                <section class="collection-section">
                    <div class="section-heading">
                        <h2>🏅 Minha Coleção Cósmica</h2>
                    </div>

                    <?php if ($ownedPlanets): ?>
                        <div class="planet-collection">
                            <?php foreach ($ownedPlanets as $planet): ?>
                                <article class="collection-item">
                                    <div class="collection-icon"><?= e($planet['icon']) ?></div>
                                    <strong><?= e($planet['name']) ?></strong>
                                    <small><?= $planet['slug'] === 'terra' ? 'Seu planeta de origem' : 'Desbloqueado' ?></small>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="card">Ainda não há planetas na sua coleção.</div>
                    <?php endif; ?>
                </section>
            <?php endif; ?>
        </section>
    </main>
</body>

</html>