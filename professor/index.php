<?php
require '../config.php';
teacher_required();
$me = (int)$_SESSION['user_id'];
$q = $pdo->prepare('SELECT a.*,COUNT(q.id) questions FROM activities a LEFT JOIN questions q ON q.activity_id=a.id WHERE a.professor_id=? GROUP BY a.id ORDER BY a.created_at DESC');
$q->execute([$me]);
$acts = $q->fetchAll();
?>
<!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Painel do Professor</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>

<body>
    <header class="nav"><b>🎮 MathPlay • Professor</b>
        <nav><a href="../dashboard.php">Painel</a><a href="criar.php">Nova atividade</a><a href="../logout.php">Sair</a></nav>
    </header>
    <main class="container">
        <h1>👩‍🏫 Minhas atividades</h1>
        <div class="actions"><a class="btn" href="criar.php">+ Criar atividade</a></div>
        <div class="activity-list"><?php foreach ($acts as $a): ?><article class="card"><span class="tag"><?= e($a['difficulty']) ?></span>
                    <h2><?= e($a['title']) ?></h2>
                    <p><?= nl2br(e($a['description'])) ?></p><small><?= $a['questions'] ?> questão(ões)<?php if ($a['due_date']): ?> • Entrega: <?= e($a['due_date']) ?><?php endif; ?></small>
                    <div class="actions"><a class="btn" href="editar.php?id=<?= $a['id'] ?>">Editar / questões</a><a class="btn light" href="resultados.php?id=<?= $a['id'] ?>">Resultados</a></div>
                </article><?php endforeach;
                                    if (!$acts): ?><div class="card">Você ainda não publicou atividades.</div><?php endif; ?></div>
    </main>
</body>

</html>