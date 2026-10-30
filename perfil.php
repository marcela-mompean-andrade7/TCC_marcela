<?php
require_once __DIR__ . '/config.php';
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
$preferredLanguage = $u['preferred_language'] ?? 'pt-BR';
$disabilityStatus = $u['disability_status'] ?? 'prefer_not';
$colorVisionStatus = $u['color_vision_status'] ?? 'prefer_not';
$disabilityOther = $u['disability_other'] ?? '';
$accessibilityOther = $u['accessibility_other'] ?? '';
$accessibilityNeeds = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();

    $newNameValue = $_POST['name'] ?? '';
    $newPasswordValue = $_POST['password'] ?? '';
    $newName = is_string($newNameValue) ? trim($newNameValue) : '';
    $newPassword = is_string($newPasswordValue) ? $newPasswordValue : '';
    $preferredLanguage = $_POST['preferred_language'] ?? '';
    $disabilityStatus = $_POST['disability_status'] ?? '';
    $colorVisionStatus = $_POST['color_vision_status'] ?? '';
    $disabilityOtherValue = $_POST['disability_other'] ?? '';
    $disabilityOther = is_string($disabilityOtherValue) ? trim($disabilityOtherValue) : '';
    $accessibilityOtherValue = $_POST['accessibility_other'] ?? '';
    $accessibilityOther = is_string($accessibilityOtherValue) ? trim($accessibilityOtherValue) : '';
    $submittedNeeds = $_POST['accessibility_needs'] ?? [];
    $accessibilityPaletteValue = $_POST['accessibility_palette'] ?? '';
    $accessibilityPalette = is_string($accessibilityPaletteValue) ? $accessibilityPaletteValue : '';
    $presentationPreferences = array_values(array_intersect(
        normalize_accessibility_preferences($u['accessibility_needs'] ?? null),
        ['readable_text', 'accessible_font']
    ));
    $accessibilityNeeds = [];

    if ($newName === '') {
        $errors[] = 'Digite um nome válido.';
    }

    if ($newPassword !== '' && strlen($newPassword) < 6) {
        $errors[] = 'A senha precisa ter pelo menos 6 caracteres.';
    }

    if (!is_string($preferredLanguage) || !array_key_exists($preferredLanguage, language_options())) {
        $errors[] = 'Escolha um idioma válido.';
    }

    if (!is_string($disabilityStatus) || !array_key_exists($disabilityStatus, accessibility_status_options())) {
        $errors[] = 'Escolha uma opção válida para deficiência.';
    }

    if (!is_string($colorVisionStatus) || !array_key_exists($colorVisionStatus, color_vision_status_options())) {
        $errors[] = 'Escolha uma opção válida para percepção das cores.';
    }

    if ($accessibilityPalette !== '' && !array_key_exists($accessibilityPalette, accessibility_palette_options())) {
        $errors[] = 'Escolha uma paleta de cores válida.';
    }
    if (!is_string($accessibilityPaletteValue)) {
        $errors[] = 'Escolha uma paleta de cores válida.';
    }

    if ($disabilityStatus === 'other' && mb_strlen($disabilityOther) > 255) {
        $errors[] = 'A descrição da deficiência deve ter até 255 caracteres.';
    }

    if (!is_array($submittedNeeds)
        || !array_is_list($submittedNeeds)
        || count(array_filter($submittedNeeds, 'is_string')) !== count($submittedNeeds)
        || array_diff($submittedNeeds, array_keys(accessibility_resource_options()))
    ) {
        $errors[] = 'Selecione recursos de acessibilidade válidos.';
    } else {
        $accessibilityNeeds = array_values(array_unique(array_merge($submittedNeeds, $presentationPreferences)));
    }

    if ($accessibilityPalette !== '' && array_key_exists($accessibilityPalette, accessibility_palette_options())) {
        $accessibilityNeeds = array_values(array_diff($accessibilityNeeds, array_keys(accessibility_palette_options())));
        $accessibilityNeeds[] = $accessibilityPalette;
    }

    if (in_array('other', $accessibilityNeeds, true) && mb_strlen($accessibilityOther) > 255) {
        $errors[] = 'A descrição dos recursos deve ter até 255 caracteres.';
    }

    if (is_string($preferredLanguage) && array_key_exists($preferredLanguage, language_options())) {
        set_language($preferredLanguage);
    }

    $photoPath = $u['profile_photo'] ?? null;

    if (!$errors && isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] !== UPLOAD_ERR_NO_FILE) {
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
        $sql = 'UPDATE users SET name = ?, profile_photo = ?, preferred_language = ?, disability_status = ?, disability_other = ?, color_vision_status = ?, accessibility_needs = ?, accessibility_other = ?';
        $params = [
            $newName,
            $photoPath,
            $preferredLanguage,
            $disabilityStatus,
            $disabilityStatus === 'other' && $disabilityOther !== '' ? $disabilityOther : null,
            $colorVisionStatus,
            $accessibilityNeeds ? json_encode($accessibilityNeeds, JSON_THROW_ON_ERROR) : null,
            in_array('other', $accessibilityNeeds, true) && $accessibilityOther !== '' ? $accessibilityOther : null,
        ];

        if ($newPassword !== '') {
            $sql .= ', password_hash = ?';
            $params[] = password_hash($newPassword, PASSWORD_DEFAULT);
        }

        $sql .= ' WHERE id = ?';
        $params[] = $_SESSION['user_id'];

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $_SESSION['lunaris_accessibility'] = $accessibilityNeeds;
        $_SESSION['lunaris_preferences_loaded'] = true;
        $notice = 'Perfil atualizado com sucesso.';

        $q = $pdo->prepare('SELECT * FROM users WHERE id=?');
        $q->execute([$_SESSION['user_id']]);
        $u = $q->fetch();
    }
}

$avatar = !empty($u['profile_photo']) ? $u['profile_photo'] : null;
$preferredLanguage = $_SERVER['REQUEST_METHOD'] === 'POST' && $errors
    ? ($_POST['preferred_language'] ?? $preferredLanguage)
    : ($u['preferred_language'] ?? 'pt-BR');
$disabilityStatus = $_SERVER['REQUEST_METHOD'] === 'POST' && $errors
    ? ($_POST['disability_status'] ?? $disabilityStatus)
    : ($u['disability_status'] ?? 'prefer_not');
$colorVisionStatus = $_SERVER['REQUEST_METHOD'] === 'POST' && $errors
    ? (is_string($_POST['color_vision_status'] ?? null) ? $_POST['color_vision_status'] : $colorVisionStatus)
    : ($u['color_vision_status'] ?? 'prefer_not');
$disabilityOther = $_SERVER['REQUEST_METHOD'] === 'POST' && $errors
    ? (is_string($_POST['disability_other'] ?? null) ? $_POST['disability_other'] : $disabilityOther)
    : ($u['disability_other'] ?? '');
$accessibilityOther = $_SERVER['REQUEST_METHOD'] === 'POST' && $errors
    ? (is_string($_POST['accessibility_other'] ?? null) ? $_POST['accessibility_other'] : $accessibilityOther)
    : ($u['accessibility_other'] ?? '');
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$errors) {
    $accessibilityNeeds = json_decode($u['accessibility_needs'] ?? '[]', true);
    if (!is_array($accessibilityNeeds)) {
        error_log('Preferências de acessibilidade inválidas para o usuário ' . (int)$_SESSION['user_id']);
        $accessibilityNeeds = [];
    }
} elseif (!is_array($_POST['accessibility_needs'] ?? [])) {
    $accessibilityNeeds = [];
} else {
    $accessibilityNeeds = array_values(array_filter($_POST['accessibility_needs'], 'is_string'));
}

$q = $pdo->prepare('SELECT p.* FROM student_planets sp JOIN planets p ON p.id = sp.planet_id WHERE sp.student_id = ? ORDER BY p.display_order ASC');
$q->execute([$_SESSION['user_id']]);
$ownedPlanets = $q->fetchAll();
$planetArtwork = [
    'terra' => ['path' => 'assets/planets/terra.png', 'alt' => 'Terra vista do espaço'],
    'marte' => ['path' => 'assets/planets/marte.jpg', 'alt' => 'Marte visto do espaço'],
    'jupiter' => ['path' => 'assets/planets/jupiter.jpg', 'alt' => 'Júpiter visto do espaço'],
    'saturno' => ['path' => 'assets/planets/saturno.jpg', 'alt' => 'Saturno visto do espaço'],
    'netuno' => ['path' => 'assets/planets/netuno.jpg', 'alt' => 'Netuno visto do espaço'],
    'urano' => ['path' => 'assets/planets/urano.jpg', 'alt' => 'Urano visto do espaço'],
    'sol' => ['path' => 'assets/planets/sol.jpg', 'alt' => 'Sol visto do espaço'],
];

$selectedAvatar = null;
$ownedAvatars = [];
$highestLevel = 0;
$missionSummary = null;
$missionHistory = [];
if (($u['role'] ?? '') === 'aluno') {
    $q = $pdo->prepare('SELECT a.* FROM student_avatar_selection s JOIN avatars a ON a.id = s.avatar_id WHERE s.student_id = ?');
    $q->execute([$_SESSION['user_id']]);
    $selectedAvatar = $q->fetch();
    if ($selectedAvatar) {
        $selectedAvatar['profile_image_path'] = avatar_profile_image($selectedAvatar);
    }

    $q = $pdo->prepare('SELECT a.* FROM student_avatars sa JOIN avatars a ON a.id = sa.avatar_id WHERE sa.student_id = ? ORDER BY a.display_order, a.id');
    $q->execute([$_SESSION['user_id']]);
    $ownedAvatars = $q->fetchAll();
    foreach ($ownedAvatars as &$ownedAvatar) {
        $ownedAvatar['profile_image_path'] = avatar_profile_image($ownedAvatar);
    }
    unset($ownedAvatar);

    $q = $pdo->prepare("SELECT COALESCE(MAX(CASE difficulty WHEN 'dificil' THEN 3 WHEN 'medio' THEN 2 ELSE 1 END), 0) FROM game_results WHERE student_id = ?");
    $q->execute([$_SESSION['user_id']]);
    $highestLevel = (int)$q->fetchColumn();

    $q = $pdo->prepare(
        'SELECT COUNT(*) AS total_missions,
                (SELECT COUNT(*) FROM mission_rewards WHERE student_id = ?) AS completed_missions,
                (SELECT COALESCE(SUM(stars), 0) FROM mission_rewards WHERE student_id = ?) AS mission_stars
         FROM missions'
    );
    $q->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
    $missionSummary = $q->fetch();

    $q = $pdo->prepare(
        'SELECT m.title, r.stars, r.awarded_at
         FROM mission_rewards r
         JOIN missions m ON m.id = r.mission_id
         WHERE r.student_id = ?
         ORDER BY r.awarded_at DESC, r.id DESC'
    );
    $q->execute([$_SESSION['user_id']]);
    $missionHistory = $q->fetchAll();
}
?>
<!doctype html>
<html lang="<?= e(current_language()) ?>" <?= accessibility_html_attributes() ?>>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="theme-color" content="#F5F7FF">
    <title><?= e(t('Perfil')) ?> — Lunaris Academy</title>
    <link rel="stylesheet" href="<?= e(application_asset_url('css/style.css')) ?>">
    <script src="<?= e(application_asset_url('js/theme.js')) ?>"></script>
</head>

<body>
    <header class="nav">
        <a class="nav-brand" href="dashboard.php"><img src="<?= e(application_asset_url('imagens/logo.png')) ?>" alt="" aria-hidden="true">Lunaris Academy</a>
        <nav lang="<?= e(current_language()) ?>">
            <a href="dashboard.php"><?= e(t('Painel')) ?></a>
            <?php if (($u['role'] ?? '') === 'aluno'): ?>
                <a href="universo.php"> <?= e(t('Universo')) ?></a>
                <a href="missoes.php"> <?= e(t('Missões')) ?></a>
                <a href="planetas.php"> <?= e(t('Planetas')) ?></a>
                <a href="avatares.php"> <?= e(t('Avatares')) ?></a>
            <?php endif; ?>
            <a href="perfil.php" aria-current="page"> <?= e(t('Perfil')) ?></a>
            <a href="logout.php"><?= e(t('Sair')) ?></a>
        </nav>
    </header>

    <main class="container narrow profile-page">
        <section class="profile-shell">
            <div class="profile-header">
                <div class="profile-avatar-large avatar-emblem">
                    <img src="<?= e($selectedAvatar['profile_image_path'] ?? 'assets/avatars/luna.svg') ?>" alt="<?= e(t('Avatar atual')) ?>: <?= e($selectedAvatar['name'] ?? 'Luna — Guardiã Lunar') ?>">
                </div>

                <div class="profile-title-wrap">
                    <span class="tag">🌙 <?= e(t('Perfil Lunaris')) ?></span>
                    <h1><?= e($u['name']) ?></h1>
                    <?php if ($selectedAvatar): ?><p class="profile-avatar-name"><?= e($selectedAvatar['name']) ?></p><?php endif; ?>
                </div>

                <div class="points">⭐ <?= e((string)$u['points']) ?> <?= e(t('pontos')) ?></div>
            </div>

            <?php if (($u['role'] ?? '') === 'aluno'): ?>
                <div class="profile-stats">
                    <article class="profile-stat"><span> <?= e(t('Avatares desbloqueados')) ?></span><strong><?= count($ownedAvatars) ?></strong></article>
                    <article class="profile-stat"><span> <?= e(t('Maior nível jogado')) ?></span><strong><?= $highestLevel > 0 ? e((string)$highestLevel) : '—' ?></strong></article>
                    <article class="profile-stat"><span> <?= e(t('Planetas na coleção')) ?></span><strong><?= count($ownedPlanets) ?></strong></article>
                    <article class="profile-stat"><span> <?= e(t('Estrelas de missões')) ?></span><strong><?= e((string)$missionSummary['mission_stars']) ?></strong></article>
                </div>
            <?php endif; ?>

            <?php if ($notice): ?>
                <div class="success-message" role="status"><?= e(t($notice)) ?></div>
            <?php endif; ?>

            <?php foreach ($errors as $error): ?>
                <div class="alert" role="alert"><?= e(t($error)) ?></div>
            <?php endforeach; ?>

            <div class="profile-grid">
                <div class="profile-card">
                    <h3><?= e(t('Dados atuais')) ?></h3>
                    <div class="info-row"><span><?= e(t('Nome')) ?></span><strong><?= e($u['name']) ?></strong></div>
                    <div class="info-row"><span><?= e(t('E-mail')) ?></span><strong><?= e($u['email']) ?></strong></div>
                    <div class="info-row"><span><?= e(t('Tipo')) ?></span><strong><?= e(t($u['role'])) ?></strong></div>
                    <div class="info-row"><span><?= e(t('Escola')) ?></span><strong><?= e($u['escola']) ?></strong></div>
                </div>

                <div class="profile-card">
                    <h3><?= e(t('Editar perfil e preferências')) ?></h3>
                    <form method="post" enctype="multipart/form-data" class="profile-form">
                        <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">

                        <label>
                            <?= e(t('Nome')) ?>
                            <input type="text" name="name" value="<?= e($u['name']) ?>" required>
                        </label>

                        <label>
                            <?= e(t('Nova senha')) ?>
                            <input type="password" name="password" placeholder="<?= e(t('Deixe em branco para manter a atual')) ?>">
                        </label>

                        <label>
                            <?= e(t('Foto de perfil')) ?>
                            <?php if ($avatar): ?><img class="profile-photo-preview" src="<?= e($avatar) ?>" alt="<?= e(t('Foto de perfil atual')) ?>"><?php endif; ?>
                            <input type="file" name="profile_photo" accept="image/*">
                        </label>

                        <fieldset class="profile-preference-section">
                            <legend><?= e(t('Idioma da plataforma')) ?></legend>
                            <label for="profileLanguage"><?= e(t('Qual idioma você prefere utilizar?')) ?></label>
                            <select id="profileLanguage" name="preferred_language">
                                <?php foreach (language_options() as $value => $label): ?>
                                    <option value="<?= e($value) ?>"<?= $preferredLanguage === $value ? ' selected' : '' ?>><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </fieldset>

                        <fieldset class="profile-preference-section">
                            <legend><?= e(t('Acessibilidade e necessidades especiais')) ?></legend>
                            <label for="profileDisability"><?= e(t('Você possui algum tipo de deficiência?')) ?></label>
                            <select id="profileDisability" name="disability_status" aria-describedby="profileDisabilityPrivacy">
                                <?php foreach (accessibility_status_options() as $value => $label): ?>
                                    <option value="<?= e($value) ?>"<?= $disabilityStatus === $value ? ' selected' : '' ?>><?= e(t($label)) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p id="profileDisabilityPrivacy" class="signup-privacy-note"><?= e(t('Essa informação é privada, opcional e não afeta seu acesso às atividades ou recursos da plataforma.')) ?></p>
                            <label for="profileColorVision"><?= e(t('Você tem daltonismo ou dificuldade para distinguir determinadas cores?')) ?></label>
                            <select id="profileColorVision" name="color_vision_status" aria-describedby="profileColorVisionPrivacy">
                                <?php foreach (color_vision_status_options() as $value => $label): ?>
                                    <option value="<?= e($value) ?>"<?= $colorVisionStatus === $value ? ' selected' : '' ?>><?= e(t($label)) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p id="profileColorVisionPrivacy" class="signup-privacy-note"><?= e(t('Esta resposta não é um diagnóstico médico. Ela serve apenas para orientar os recursos visuais que você pode escolher.')) ?></p>
                            <div id="profileDisabilityOtherField"<?= $disabilityStatus === 'other' ? '' : ' hidden' ?>>
                                <label for="profileDisabilityOther"><?= e(t('Se desejar, descreva a deficiência')) ?></label>
                                <input id="profileDisabilityOther" name="disability_other" maxlength="255" value="<?= e($disabilityOther) ?>">
                            </div>
                            <fieldset id="profileAccessibilityNeedsField" class="signup-resource-options">
                                <legend><?= e(t('Quais recursos de acessibilidade poderiam ajudar? (opcional)')) ?></legend>
                                <p class="signup-privacy-note"><?= e(t('A informação sobre deficiência não ativa adaptações automaticamente. Escolha abaixo os recursos adequados às suas necessidades; por exemplo, texto ampliado e alto contraste para baixa visão, compatibilidade com leitores de tela e teclado para cegueira, ou paleta alternativa para daltonismo. As escolhas são aplicadas às páginas e aos jogos.')) ?></p>
                                <?php foreach (accessibility_resource_options() as $value => $label): ?>
                                    <label class="signup-checkbox">
                                        <input type="checkbox" name="accessibility_needs[]" value="<?= e($value) ?>"<?= in_array($value, $accessibilityNeeds, true) ? ' checked' : '' ?>>
                                        <span><?= e(t($label)) ?></span>
                                    </label>
                                <?php endforeach; ?>
                                <label for="profileAccessibilityPalette"><?= e(t('Paleta alternativa de cores')) ?></label>
                                <select id="profileAccessibilityPalette" name="accessibility_palette">
                                    <option value=""><?= e(t('Padrão')) ?></option>
                                    <?php foreach (accessibility_palette_options() as $value => $label): ?>
                                        <option value="<?= e($value) ?>"<?= in_array($value, $accessibilityNeeds, true) ? ' selected' : '' ?>><?= e(t($label)) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <label id="profileAccessibilityOtherField"<?= in_array('other', $accessibilityNeeds, true) ? '' : ' hidden' ?>>
                                    <?= e(t('Se desejar, descreva outros recursos')) ?>
                                    <input name="accessibility_other" maxlength="255" value="<?= e($accessibilityOther) ?>">
                                </label>
                            </fieldset>
                        </fieldset>

                        <button type="submit" class="btn"><?= e(t('Salvar alterações')) ?></button>
                    </form>
                </div>
            </div>

            <?php if (($u['role'] ?? '') === 'aluno'): ?>
                <section class="collection-section avatar-collection-section">
                    <div class="section-heading profile-collection-heading">
                        <h2>🌙 <?= e(t('Meu Universo Pessoal')) ?></h2>
                        <a class="btn small" href="avatares.php"><?= e(t('Ver loja de avatares')) ?></a>
                    </div>
                    <div class="personal-universe-current">
                        <img src="<?= e($selectedAvatar['profile_image_path'] ?? 'assets/avatars/luna.svg') ?>" alt="<?= e(t('Personagem atual')) ?>: <?= e($selectedAvatar['name'] ?? 'Luna — Guardiã Lunar') ?>">
                        <div><small><?= e(t('Seu companheiro Lunaris')) ?></small><strong><?= e($selectedAvatar['name'] ?? 'Luna — Guardiã Lunar') ?></strong></div>
                    </div>
                    <h3 class="collection-subheading">🎭 <?= e(t('Avatares desbloqueados')) ?></h3>
                    <div class="avatar-owned-list">
                        <?php foreach ($ownedAvatars as $ownedAvatar): ?>
                            <article class="avatar-owned-item<?= (int)$ownedAvatar['id'] === (int)($selectedAvatar['id'] ?? 0) ? ' is-current' : '' ?>">
                                <img src="<?= e($ownedAvatar['profile_image_path']) ?>" alt="<?= e($ownedAvatar['name']) ?>" width="54" height="62">
                                <strong><?= e($ownedAvatar['name']) ?></strong>
                                <?php if ((int)$ownedAvatar['id'] === (int)($selectedAvatar['id'] ?? 0)): ?><small><?= e(t('Avatar atual')) ?></small><?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    </div>
                    <h3 class="collection-subheading">🏅 <?= e(t('Planetas conquistados')) ?></h3>

                    <?php if ($ownedPlanets): ?>
                        <div class="planet-collection">
                            <?php foreach ($ownedPlanets as $planet): ?>
                                <article class="collection-item">
                                    <?php if (isset($planetArtwork[$planet['slug']])): ?>
                                        <div class="collection-planet-image-wrap">
                                            <img
                                                class="planet-art-image collection-planet-image"
                                                src="<?= e(application_asset_url($planetArtwork[$planet['slug']]['path'])) ?>"
                                                alt="<?= e($planetArtwork[$planet['slug']]['alt']) ?>"
                                                width="76"
                                                height="76"
                                                loading="lazy"
                                                decoding="async">
                                        </div>
                                    <?php else: ?>
                                        <div class="collection-icon"><?= e($planet['icon']) ?></div>
                                    <?php endif; ?>
                                    <strong><?= e($planet['name']) ?></strong>
                                    <small><?= e(t($planet['slug'] === 'terra' ? 'Seu planeta de origem' : 'Desbloqueado')) ?></small>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="card"><?= e(t('Ainda não há planetas na sua coleção.')) ?></div>
                    <?php endif; ?>

                    <div class="mission-profile-heading">
                        <div>
                            <h3 class="collection-subheading">🚀 <?= e(t('Missões Espaciais')) ?></h3>
                            <p><?= e((string)$missionSummary['completed_missions']) ?> <?= e(t('de')) ?> <?= e((string)$missionSummary['total_missions']) ?> <?= e(t('concluídas')) ?> · <?= e((string)$missionSummary['mission_stars']) ?> <?= e(t('estrelas conquistadas')) ?></p>
                        </div>
                        <a class="btn small" href="missoes.php"><?= e(t('Ver missões')) ?></a>
                    </div>
                    <?php if ($missionHistory): ?>
                        <div class="mission-history-list">
                            <?php foreach ($missionHistory as $missionRecord): ?>
                                <article>
                                    <span>✓ <?= e($missionRecord['title']) ?></span>
                                    <strong>+<?= e((string)$missionRecord['stars']) ?> ⭐</strong>
                                    <small><?= e(date('d/m/Y', strtotime($missionRecord['awarded_at']))) ?></small>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="card mission-history-empty"><?= e(t('Conclua uma missão espacial para registrar suas primeiras estrelas nesta coleção.')) ?></div>
                    <?php endif; ?>
                </section>
            <?php endif; ?>
        </section>
    </main>
    <script>
    (() => {
        const status = document.getElementById('profileDisability');
        const otherField = document.getElementById('profileDisabilityOtherField');
        const needsField = document.getElementById('profileAccessibilityNeedsField');
        const otherNeedsField = document.getElementById('profileAccessibilityOtherField');
        if (!status || !otherField || !needsField || !otherNeedsField) return;

        function updateAccessibilityFields() {
            otherField.hidden = status.value !== 'other';
            otherNeedsField.hidden = !needsField.querySelector('input[value="other"]').checked;
        }

        status.addEventListener('change', updateAccessibilityFields);
        needsField.addEventListener('change', updateAccessibilityFields);
        updateAccessibilityFields();
    })();
    </script>
</body>

</html>