<?php
require_once __DIR__ . '/config.php';
login_required();
if (($_SESSION['role'] ?? '') !== 'aluno') redirect('dashboard.php');
$q = $pdo->prepare('SELECT a.slug, a.name, a.icon, a.image_path FROM student_avatar_selection s JOIN avatars a ON a.id = s.avatar_id WHERE s.student_id = ?');
$q->execute([$_SESSION['user_id']]);
$currentAvatar = $q->fetch();
if ($currentAvatar) {
    $currentAvatar['profile_image_path'] = avatar_profile_image($currentAvatar);
}
$names = math_game_titles();
$game = $_GET['game'] ?? 'operacoes';
if (!is_string($game) || !isset($names[$game])) $game = 'operacoes';
$level = max(1, min(3, (int)($_GET['level'] ?? 1)));
$labels = ['Fácil', 'Médio', 'Difícil'];
function q(string $game, int $l): array
{
    $n = $l === 1 ? 10 : ($l === 2 ? 30 : 100);
    if ($game === 'operacoes') {
        $a = random_int(-$n, $n);
        $b = random_int(-$n, $n);
        $op = ['+', '-', '×'][array_rand(['+', '-', '×'])];
        $ans = $op === '+' ? $a + $b : ($op === '-' ? $a - $b : $a * $b);
        $spokenOperation = ['+' => t('mais'), '-' => t('menos'), '×' => t('vezes')][$op];
        return [
            sprintf(t('Quanto é %s %s %s?'), $a, $op, $b),
            (string)$ans,
            sprintf(t('Quanto é %s %s %s?'), $a, $spokenOperation, $b),
        ];
    }
    if ($game === 'fracoes') {
        $d = random_int(2, $l === 1 ? 5 : 10);
        $n = random_int(1, $d - 1);
        $m = random_int(2, $l === 1 ? 5 : 10);
        $k = random_int(1, $m - 1);
        $num = $n * $m + $k * $d;
        $den = $d * $m;
        $g = function ($a, $b) use (&$g) {
            return $b ? $g($b, $a % $b) : abs($a);
        };
        $z = $g($num, $den);
        return [
            sprintf(t('Quanto é %s/%s + %s/%s? (fração simplificada)'), $n, $d, $k, $m),
            ($num / $z) . '/' . ($den / $z),
            sprintf(t('Quanto é %s sobre %s mais %s sobre %s? Responda com uma fração simplificada.'), $n, $d, $k, $m),
        ];
    }
    if ($game === 'equacoes') {
        $x = random_int(1, $n);
        $a = random_int(2, $l === 1 ? 5 : 12);
        $b = random_int(1, $n);
        $total = $a * $x + $b;
        return [
            sprintf(t('Resolva: %sx + %s = %s. Qual é x?'), $a, $b, $total),
            (string)$x,
            sprintf(t('Resolva a equação: %s vezes x mais %s é igual a %s. Qual é o valor de x?'), $a, $b, $total),
        ];
    }
    $lado = random_int(2, $n);
    if (random_int(0, 1)) {
        return [
            sprintf(t('Quadrado de lado %s. Qual é a área?'), $lado),
            (string)($lado * $lado),
            sprintf(t('Um quadrado tem lado de %s unidades. Qual é a área?'), $lado),
        ];
    }
    return [
        sprintf(t('Quadrado de lado %s. Qual é o perímetro?'), $lado),
        (string)(4 * $lado),
        sprintf(t('Um quadrado tem lado de %s unidades. Qual é o perímetro?'), $lado),
    ];
}






[$question, $answer, $accessibleQuestion] = q($game, $level);
?>
<!doctype html>
<html lang="<?= e(current_language()) ?>" <?= accessibility_html_attributes() ?>>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= e(t('Jogo')) ?> — Lunaris Academy</title>
    <link rel="stylesheet" href="<?= e(application_asset_url('css/style.css')) ?>">
    <script src="<?= e(application_asset_url('js/theme.js')) ?>"></script>
</head>

<body>
    <header class="nav">
        <a class="nav-brand" href="dashboard.php">
            <img src="<?= e(application_asset_url('imagens/logo.png')) ?>" alt="Logo da Lunaris Academy" width="38" height="38">
            Lunaris Academy
        </a>
        <nav>
            <?php if ($currentAvatar): ?><img class="nav-avatar nav-avatar-image" src="<?= e($currentAvatar['profile_image_path']) ?>" alt="<?= e(sprintf(t('Avatar atual: %s'), $currentAvatar['name'])) ?>" width="38" height="38"><?php endif; ?>
            <a href="universo.php"><?= e(t('Universo')) ?></a>
            <a href="dashboard.php"><?= e(t('Painel')) ?></a>
            <a href="avatares.php"><?= e(t('Avatares')) ?></a>
        </nav>
    </header>
    <main class="container narrow">
        <div class="levelbar"><b><?= e(sprintf(t('Nível %s • %s'), $level, t($labels[$level - 1]))) ?></b><span>⭐ <?= e(sprintf(t('%s pontos para avançar'), $level * 10)) ?></span></div>
        <div class="card quiz"><span class="tag"><?= e($names[$game]) ?></span>
            <h1 aria-label="<?= e($accessibleQuestion) ?>"><?= e($question) ?></h1>
            <form method="post" action="jogo_resultado.php">
                <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                <input type="hidden" name="game" value="<?= e($game) ?>">
                <input type="hidden" name="level" value="<?= $level ?>">
                <input type="hidden" name="answer" value="<?= e($answer) ?>">
                <label for="gameAnswer"><?= e(t('Sua resposta')) ?></label>
                <input id="gameAnswer" name="user_answer" required autocomplete="off" placeholder="<?= e(t('Digite sua resposta')) ?>" aria-describedby="gameAnswerHint">
                <p class="hint" id="gameAnswerHint"><?= e(t('Digite sua resposta e pressione Responder.')) ?></p>
                <button class="btn full" type="submit"><?= e(t('Responder')) ?></button>
            </form>
        </div>
        <p class="hint"><?= e(t('Resolva com calma. Se acertar, você pode avançar de nível.')) ?></p>
    </main>
</body>

</html>