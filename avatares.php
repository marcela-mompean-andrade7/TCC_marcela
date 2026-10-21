<?php
require_once __DIR__ . '/config.php';
login_required();

if (($_SESSION['role'] ?? '') !== 'aluno') {
    redirect('dashboard.php');
}

$studentId = (int)$_SESSION['user_id'];
$notice = '';
$purchaseComplete = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $avatarId = filter_var($_POST['avatar_id'] ?? null, FILTER_VALIDATE_INT);
    $action = (string)($_POST['action'] ?? '');

    if (!$avatarId || $avatarId < 1) {
        $errors[] = 'Avatar inválido.';
    } elseif (!in_array($action, ['buy', 'select'], true)) {
        $errors[] = 'Ação inválida.';
    } else {
        try {
            $pdo->beginTransaction();
            $q = $pdo->prepare("SELECT id, points, role FROM users WHERE id = ? FOR UPDATE");
            $q->execute([$studentId]);
            $student = $q->fetch();

            if (!$student || $student['role'] !== 'aluno') {
                throw new RuntimeException('Apenas alunos podem gerenciar avatares.');
            }

            $q = $pdo->prepare('SELECT id, name, price FROM avatars WHERE id = ? FOR UPDATE');
            $q->execute([$avatarId]);
            $avatar = $q->fetch();

            if (!$avatar) {
                throw new RuntimeException('Este avatar não existe.');
            }

            $q = $pdo->prepare('SELECT 1 FROM student_avatars WHERE student_id = ? AND avatar_id = ?');
            $q->execute([$studentId, $avatarId]);
            $owned = (bool)$q->fetchColumn();

            if ($action === 'buy') {
                if ($owned) {
                    throw new RuntimeException('Você já desbloqueou este avatar.');
                }

                $price = (int)$avatar['price'];
                if ((int)$student['points'] < $price) {
                    throw new RuntimeException('Você não tem pontos suficientes para este avatar.');
                }

                if ($price > 0) {
                    $q = $pdo->prepare('UPDATE users SET points = points - ? WHERE id = ? AND points >= ?');
                    $q->execute([$price, $studentId, $price]);
                    if ($q->rowCount() !== 1) {
                        throw new RuntimeException('Não foi possível concluir a compra. Confira seu saldo e tente novamente.');
                    }
                }

                $q = $pdo->prepare('INSERT INTO student_avatars(student_id, avatar_id) VALUES(?, ?)');
                $q->execute([$studentId, $avatarId]);
            } else {
                if (!$owned) {
                    throw new RuntimeException('Desbloqueie este avatar antes de selecioná-lo.');
                }

                $q = $pdo->prepare('INSERT INTO student_avatar_selection(student_id, avatar_id) VALUES(?, ?) ON DUPLICATE KEY UPDATE avatar_id = VALUES(avatar_id)');
                $q->execute([$studentId, $avatarId]);
            }

            $pdo->commit();
            if ($action === 'buy') {
                $notice = 'NOVO AVATAR DESBLOQUEADO! Você acaba de ganhar um novo companheiro para sua jornada Lunaris!';
                $purchaseComplete = true;
            } else {
                $notice = '🎭 ' . $avatar['name'] . ' agora é seu avatar atual.';
            }
        } catch (RuntimeException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $notice = '';
            $errors[] = $e->getMessage();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $notice = '';
            error_log('Erro ao atualizar avatar do aluno ' . $studentId . ': ' . $e->getMessage());
            $errors[] = 'Não foi possível atualizar seu avatar. Tente novamente.';
        }
    }
}

$q = $pdo->prepare('SELECT id, name, points FROM users WHERE id = ?');
$q->execute([$studentId]);
$student = $q->fetch();

$q = $pdo->prepare(
    'SELECT a.*,
            (sa.avatar_id IS NOT NULL) AS owned,
            (s.avatar_id = a.id) AS selected
     FROM avatars a
     LEFT JOIN student_avatars sa ON sa.avatar_id = a.id AND sa.student_id = ?
     LEFT JOIN student_avatar_selection s ON s.student_id = ?
     WHERE a.display_order < 99 OR sa.avatar_id IS NOT NULL
     ORDER BY (a.display_order >= 99), a.price, a.display_order, a.id'
);
$q->execute([$studentId, $studentId]);
$avatars = $q->fetchAll();
foreach ($avatars as &$avatar) {
    $images = avatar_character_images((string)$avatar['slug']);
    $avatar['profile_image_path'] = avatar_profile_image($avatar);
    $avatar['full_image_path'] = $images['full'];
}
unset($avatar);

$q = $pdo->prepare('SELECT a.slug, a.name, a.icon, a.image_path FROM student_avatar_selection s JOIN avatars a ON a.id = s.avatar_id WHERE s.student_id = ?');
$q->execute([$studentId]);
$currentAvatar = $q->fetch();
if ($currentAvatar) {
    $currentAvatar['profile_image_path'] = avatar_profile_image($currentAvatar);
}
?>
<!doctype html>
<html lang="<?= e(current_language()) ?>" <?= accessibility_html_attributes() ?>>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="theme-color" content="#F5F7FF">
    <title>Galeria Galáctica</title>
    <link rel="stylesheet" href="<?= e(application_asset_url('css/style.css')) ?>">
    <script src="<?= e(application_asset_url('js/theme.js')) ?>"></script>
    <script src="<?= e(application_asset_url('js/avatar-gallery.js')) ?>" defer></script>
</head>

<body>
    <header class="nav">
        <a class="nav-brand" href="dashboard.php"><img src="<?= e(application_asset_url('imagens/logo.png')) ?>" alt="" aria-hidden="true">Lunaris Academy</a>
        <nav lang="<?= e(current_language()) ?>">
            <a href="dashboard.php"><?= e(t('Painel')) ?></a>
            <a href="universo.php"><?= e(t('Universo')) ?></a>
            <a href="missoes.php"><?= e(t('Missões')) ?></a>
            <a href="planetas.php"><?= e(t('Planetas')) ?></a>
            <a href="avatares.php" aria-current="page"><?= e(t('Avatares')) ?></a>
            <a href="perfil.php"><?= e(t('Perfil')) ?></a>
            <a href="logout.php"><?= e(t('Sair')) ?></a>
        </nav>
    </header>

    <main class="container narrow avatar-page">
        <section class="avatar-shell">
            <div class="page-header">
                <div>
                    <span class="tag">Coleção Lunaris</span>
                    <h1>Galeria Galáctica</h1>
                    <p>Conheça personagens de toda a galáxia, desbloqueie seus favoritos e escolha quem acompanha suas aventuras.</p>
                </div>
                <div class="points">🌟<?= e((string)$student['points']) ?> pontos</div>
            </div>

            <div class="avatar-current" aria-live="polite">
                <img class="avatar-current-icon" src="<?= e($currentAvatar['profile_image_path'] ?? 'assets/avatars/luna.svg') ?>" alt="Avatar atual: <?= e($currentAvatar['name'] ?? 'Luna — Guardiã Lunar') ?>" width="72" height="84">
                <span><small>Seu avatar atual</small><strong><?= e($currentAvatar['name'] ?? 'Luna — Guardiã Lunar') ?></strong></span>
            </div>

            <?php if ($notice): ?>
                <?php if ($purchaseComplete): ?>
                <div class="avatar-purchase-success" role="status" aria-live="polite">
                    <strong>NOVO AVATAR DESBLOQUEADO!</strong>
                    <span>Você acaba de ganhar um novo companheiro para sua jornada Lunaris!</span>
                </div>
                <?php else: ?>
                    <div class="success-message" role="status"><?= e($notice) ?></div>
                <?php endif; ?>
            <?php endif; ?>
            <?php foreach ($errors as $error): ?>
                <div class="alert" role="alert"><?= e($error) ?></div>
            <?php endforeach; ?>

            <div class="avatar-filters" aria-label="Filtros da galeria">
                <label>
                    Raridade
                    <select data-avatar-rarity>
                        <option value="all">Todas as raridades</option>
                        <option value="comum">Comum</option>
                        <option value="raro">Raro</option>
                        <option value="epico">Épico</option>
                        <option value="lendario">Lendário</option>
                    </select>
                </label>
                <label>
                    Coleção
                    <select data-avatar-status>
                        <option value="all">Todos os avatares</option>
                        <option value="owned">Desbloqueados</option>
                        <option value="available">Disponíveis para compra</option>
                        <option value="locked">Bloqueados por pontos</option>
                    </select>
                </label>
                <p data-avatar-count aria-live="polite"><?= count($avatars) ?> personagens na galeria</p>
            </div>

            <div class="avatar-grid">
                <?php foreach ($avatars as $avatar):
                    $owned = (bool)$avatar['owned'];
                    $selected = (bool)$avatar['selected'];
                    $price = (int)$avatar['price'];
                    $canAfford = (int)$student['points'] >= $price;
                    $rarity = (string)($avatar['rarity'] ?? 'comum');
                    $filterStatus = $owned ? 'owned' : ($canAfford ? 'available' : 'locked');
                    $rarityNames = ['comum' => 'Comum', 'raro' => 'Raro', 'epico' => 'Épico', 'lendario' => 'Lendário'];
                    $rarityName = $rarityNames[$rarity] ?? 'Comum';
                ?>
                    <article class="avatar-card rarity-<?= e($rarity) ?><?= $selected ? ' is-selected' : '' ?>" data-avatar-card data-rarity="<?= e($rarity) ?>" data-status="<?= e($filterStatus) ?>">
                        <?php if ($selected): ?>
                            <span class="avatar-current-badge">✓ Avatar atual</span>
                        <?php elseif ($owned): ?>
                            <span class="avatar-owned-badge">✓ Desbloqueado</span>
                        <?php endif; ?>
                        <?php if ($avatar['full_image_path']): ?>
                            <button type="button" class="avatar-art-button" data-avatar-details
                                data-avatar-name="<?= e($avatar['name']) ?>"
                                data-avatar-full-image="<?= e($avatar['full_image_path']) ?>"
                                aria-haspopup="dialog"
                                aria-label="Ver imagem completa de <?= e($avatar['name']) ?>">
                                <img class="avatar-art" src="<?= e($avatar['profile_image_path']) ?>" alt="Avatar de <?= e($avatar['name']) ?>" width="180" height="210">
                            </button>
                        <?php else: ?>
                            <img class="avatar-art" src="<?= e($avatar['profile_image_path']) ?>" alt="Avatar de <?= e($avatar['name']) ?>" width="180" height="210">
                        <?php endif; ?>
                        <span class="avatar-rarity"><?= e($rarityName) ?></span>
                        <h2><?= e($avatar['name']) ?></h2>
                        <p><?= e($avatar['description']) ?></p>
                        <strong class="avatar-price"><?= $price === 0 ? 'Gratuito' : '⭐ ' . e((string)$price) . ' pontos' ?></strong>

                        <?php if ($selected): ?>
                            <button type="button" class="btn small avatar-button" disabled aria-current="true">Avatar atual</button>
                        <?php elseif ($owned): ?>
                            <form method="post">
                                <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                                <input type="hidden" name="avatar_id" value="<?= (int)$avatar['id'] ?>">
                                <input type="hidden" name="action" value="select">
                                <button type="submit" class="btn small avatar-button">Selecionar avatar</button>
                            </form>
                        <?php elseif (!$canAfford): ?>
                            <span class="avatar-locked-badge">🔒 Bloqueado</span>
                            <span class="avatar-status-insufficient">Faltam <?= e((string)($price - (int)$student['points'])) ?> pontos</span>
                            <button type="button" class="btn small light avatar-button" disabled>Desbloquear</button>
                        <?php else: ?>
                            <span class="avatar-locked-badge">🔒 Bloqueado</span>
                            <form method="post">
                                <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                                <input type="hidden" name="avatar_id" value="<?= (int)$avatar['id'] ?>">
                                <input type="hidden" name="action" value="buy">
                                <button type="submit" class="btn small avatar-button">Desbloquear</button>
                            </form>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    </main>
    <dialog class="avatar-detail-dialog" aria-labelledby="avatarDetailTitle">
        <div class="avatar-detail-content">
            <div class="avatar-detail-heading">
                <h2 id="avatarDetailTitle"></h2>
                <button type="button" class="avatar-detail-close" aria-label="Fechar detalhes do personagem">&times;</button>
            </div>
            <img class="avatar-detail-image" src="" alt="">
        </div>
    </dialog>
</body>

</html>
