<?php
require 'config.php';
login_required();
if (($_SESSION['role'] ?? '') !== 'aluno') redirect('dashboard.php');
$names = ['operacoes' => 'Operações com Números', 'fracoes' => 'Frações e Razões', 'equacoes' => 'Equações de 1º Grau', 'geometria' => 'Área e Perímetro'];
$game = $_GET['game'] ?? 'operacoes';
if (!isset($names[$game])) $game = 'operacoes';
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
        return ["Quanto é $a $op $b?", (string)$ans];
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
        return ["Quanto é $n/$d + $k/$m? (fração simplificada)", ($num / $z) . '/' . ($den / $z)];
    }
    if ($game === 'equacoes') {
        $x = random_int(1, $n);
        $a = random_int(2, $l === 1 ? 5 : 12);
        $b = random_int(1, $n);
        return ["Resolva: {$a}x + {$b} = " . ($a * $x + $b) . ". Qual é x?", (string)$x];
    }
    $lado = random_int(2, $n);
    return random_int(0, 1) ? ["Quadrado de lado $lado. Qual é a área?", (string)($lado * $lado)] : ["Quadrado de lado $lado. Qual é o perímetro?", (string)(4 * $lado)];
}
[$question, $answer] = q($game, $level);
?>
<!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Jogo</title>
    <link rel="stylesheet" href="assets/style.css">
</head>

<body>
    <header class="nav"><b>🎮 MathPlay</b>
        <nav><a href="dashboard.php">Painel</a></nav>
    </header>
    <main class="container narrow">
        <div class="levelbar"><b>Nível <?= $level ?> • <?= $labels[$level - 1] ?></b><span>⭐ até <?= $level * 10 ?> pontos</span></div>
        <div class="card quiz"><span class="tag"><?= e($names[$game]) ?></span>
            <h1><?= e($question) ?></h1>
            <form method="post" action="jogo_resultado.php"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="game" value="<?= e($game) ?>"><input type="hidden" name="level" value="<?= $level ?>"><input type="hidden" name="answer" value="<?= e($answer) ?>"><input name="user_answer" required autocomplete="off" placeholder="Digite sua resposta"><button class="btn full">Responder</button></form>
        </div>
        <p class="hint">💡 Resolva com calma. Se acertar, você pode avançar de nível.</p>
    </main>
</body>

</html>