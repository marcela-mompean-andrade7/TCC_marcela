<?php
require '../config.php';
teacher_required();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_activity'])) {
    check_csrf();

    $id = (int)($_POST['activity_id'] ?? 0);
    if ($id > 0) {
        $pdo->prepare('DELETE FROM submissions WHERE activity_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM questions WHERE activity_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM activities WHERE id = ? AND professor_id = ?')->execute([$id, $_SESSION['user_id']]);
    }
    redirect('index.php');
}

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
    <script src="../assets/theme.js"></script>
</head>

<body>
    <header class="nav"><b>MathUp • Professor</b>
        <nav><a href="../dashboard.php">Painel</a><a href="criar.php">Nova atividade</a><a href="../logout.php">Sair</a></nav>
    </header>
    <main class="container teacher-panel">
        <div class="teacher-header">
            <div>
                <span class="tag">Professor</span>
                <h1>Minhas atividades</h1>
            </div>
            <a class="btn" href="criar.php">+ Criar atividade</a>
        </div>

        <div class="activity-list">
            <?php foreach ($acts as $a): ?>
                <article class="card activity-item">
                    <div class="activity-head">
                        <span class="tag"><?= e($a['difficulty']) ?></span>
                        <span class="meta-pill"><?= (int)$a['questions'] ?> questão(ões)</span>
                    </div>

                    <h2><?= e($a['title']) ?></h2>
                    <p><?= nl2br(e($a['description'])) ?></p>
                    <small><?php if ($a['due_date']): ?>Entrega: <?= e($a['due_date']) ?><?php endif; ?></small>

                    <div class="activity-actions">
                        <a class="btn" href="editar.php?id=<?= $a['id'] ?>">Editar / questões</a>
                        <a class="btn btn-secondary" href="resultados.php?id=<?= $a['id'] ?>">Resultados</a>

                        <form method="post" class="inline-form" onsubmit="return confirm('Deseja excluir esta atividade?');">
                            <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                            <input type="hidden" name="activity_id" value="<?= (int)$a['id'] ?>">
                            <button type="submit" name="delete_activity" class="btn btn-danger">Excluir</button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>

            <?php if (!$acts): ?>
                <div class="card empty-state">Você ainda não publicou atividades.</div>
            <?php endif; ?>
        </div>
    </main>
</body>

</html>