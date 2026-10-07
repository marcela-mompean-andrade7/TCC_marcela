<?php
require '../config.php';
teacher_required();
$err = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $t = trim($_POST['title'] ?? '');
    $d = trim($_POST['description'] ?? '');
    $dif = $_POST['difficulty'] ?? '';
    $date = $_POST['due_date'] ?: null;
    if ($t === '' || $d === '') $err[] = 'Preencha título e descrição.';
    if (!in_array($dif, ['facil', 'medio', 'dificil'], true)) $err[] = 'Dificuldade inválida.';
    if (!$err) {
        $q = $pdo->prepare('INSERT INTO activities(professor_id,title,description,difficulty,due_date) VALUES(?,?,?,?,?)');
        $q->execute([$_SESSION['user_id'], $t, $d, $dif, $date]);
        $id = (int)$pdo->lastInsertId();
        redirect("editar.php?id=$id");
    }
}
?>
<!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Nova atividade</title>
    <link rel="stylesheet" href="../assets/style.css">
    <script src="../assets/theme.js"></script>
</head>

<body>
    <main class="container narrow"><a href="index.php">← Voltar</a>
        <div class="card">
            <h1>Nova atividade</h1><?php foreach ($err as $x): ?><div class="alert"><?= e($x) ?></div><?php endforeach; ?><form method="post"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><label>Título<input name="title" required maxlength="160"></label><label>Descrição<textarea name="description" rows="6" required></textarea></label><label>Dificuldade<select name="difficulty">
                        <option value="facil">Fácil</option>
                        <option value="medio">Médio</option>
                        <option value="dificil">Difícil</option>
                    </select></label><label>Data de entrega<input type="date" name="due_date"></label><button class="btn">Criar e adicionar questões</button></form>
        </div>
    </main>
</body>

</html>