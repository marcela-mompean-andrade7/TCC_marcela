<?php
require '../config.php';
teacher_required();
$id = (int)($_GET['id'] ?? 0);
$q = $pdo->prepare('SELECT a.* FROM activities a WHERE a.id=? AND a.professor_id=?');
$q->execute([$id, $_SESSION['user_id']]);
$a = $q->fetch();
if (!$a) exit('Atividade não encontrada.');
$q = $pdo->prepare('SELECT s.*,u.name,u.serie,u.escola FROM submissions s JOIN users u ON u.id=s.student_id WHERE s.activity_id=? ORDER BY s.submitted_at DESC');
$q->execute([$id]);
$rows = $q->fetchAll(); ?>
<!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Resultados</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>

<body>
    <main class="container"><a href="index.php">← Painel</a>
        <h1>Resultados: <?= e($a['title']) ?></h1>
        <div class="card table-wrap">
            <table>
                <tr>
                    <th>Aluno</th>
                    <th>Série</th>
                    <th>Escola</th>
                    <th>Nota</th>
                    <th>Data</th>
                </tr><?php foreach ($rows as $r): ?><tr>
                        <td><?= e($r['name']) ?></td>
                        <td><?= e($r['serie'] ?? '-') ?></td>
                        <td><?= e($r['escola']) ?></td>
                        <td><?= e((string)$r['score']) ?>/<?= e((string)$r['total']) ?></td>
                        <td><?= e($r['submitted_at']) ?></td>
                    </tr><?php endforeach;
                        if (!$rows): ?><tr>
                        <td colspan="5">Nenhum aluno respondeu ainda.</td>
                    </tr><?php endif; ?>
            </table>
        </div>
    </main>
</body>

</html>