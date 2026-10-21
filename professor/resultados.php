<?php
require_once __DIR__ . '/../config.php';

teacher_required();

/* ================================
   CSRF
================================ */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/* ================================
   EXCLUIR ATIVIDADE
================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['excluir_atividade'])) {

    $csrf = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $csrf)) {
        exit('Token de segurança inválido.');
    }

    $idExcluir = (int)($_POST['atividade_id'] ?? 0);

    if ($idExcluir <= 0) {
        exit('Atividade inválida.');
    }

    try {

        $pdo->beginTransaction();

        /* Confirma que a atividade pertence ao professor logado */
        $check = $pdo->prepare(
            'SELECT id, title
             FROM activities
             WHERE id = ? AND professor_id = ?'
        );

        $check->execute([
            $idExcluir,
            $_SESSION['user_id']
        ]);

        $atividade = $check->fetch();

        if (!$atividade) {
            $pdo->rollBack();
            exit('Atividade não encontrada ou você não tem permissão para excluí-la.');
        }

        /* Exclui respostas dos alunos */
        $deleteSubmissions = $pdo->prepare(
            'DELETE FROM submissions WHERE activity_id = ?'
        );

        $deleteSubmissions->execute([$idExcluir]);

        /* Exclui perguntas */
        $deleteQuestions = $pdo->prepare(
            'DELETE FROM questions WHERE activity_id = ?'
        );

        $deleteQuestions->execute([$idExcluir]);

        /* Exclui a atividade */
        $deleteActivity = $pdo->prepare(
            'DELETE FROM activities
             WHERE id = ? AND professor_id = ?'
        );

        $deleteActivity->execute([
            $idExcluir,
            $_SESSION['user_id']
        ]);

        $pdo->commit();

        header('Location: index.php?excluida=1');
        exit;

    } catch (Throwable $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        exit('Não foi possível excluir a atividade.');
    }
}

/* ================================
   BUSCAR ATIVIDADE
================================ */
$id = (int)($_GET['id'] ?? 0);

$q = $pdo->prepare(
    'SELECT a.*
     FROM activities a
     WHERE a.id = ? AND a.professor_id = ?'
);

$q->execute([
    $id,
    $_SESSION['user_id']
]);

$a = $q->fetch();

if (!$a) {
    exit('Atividade não encontrada.');
}

/* ================================
   BUSCAR RESULTADOS
================================ */
$q = $pdo->prepare(
    'SELECT
        s.*,
        u.name,
        u.serie,
        u.escola
     FROM submissions s
     JOIN users u ON u.id = s.student_id
     WHERE s.activity_id = ?
     ORDER BY s.submitted_at DESC'
);

$q->execute([$id]);

$rows = $q->fetchAll();

?>
<!doctype html>
<html lang="<?= e(current_language()) ?>" <?= accessibility_html_attributes() ?>>

<head>

    <meta charset="utf-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1">

    <meta name="description"
          content="Resultados da atividade - Lunaris Academy">

    <title>
        Resultados | Lunaris Academy
    </title>

    <link rel="stylesheet" href="<?= e(application_asset_url('css/style.css')) ?>">
    <script src="<?= e(application_asset_url('js/theme.js')) ?>"></script>

</head>

<body>

<header class="nav teacher-create-nav">
    <a class="nav-brand teacher-nav-brand" href="../dashboard.php">
        <img src="<?= e(application_asset_url('imagens/logo.png')) ?>" alt="" aria-hidden="true">
        Lunaris Academy <span>• Professor</span>
    </a>
    <nav lang="<?= e(current_language()) ?>">
        <a href="index.php"><?= e(t('Minhas atividades')) ?></a>
        <a href="criar.php"><?= e(t('Nova atividade')) ?></a>
        <a href="../logout.php"><?= e(t('Sair')) ?></a>
    </nav>
</header>

<main class="container results-page">

    <!-- ================================
         CABEÇALHO
    ================================= -->

    <div class="page-header">

        <div>

            <a href="index.php" class="btn-back">
                <span>←</span>
                Voltar ao painel
            </a>

            <p class="page-tag">
                PAINEL DO PROFESSOR
            </p>

            <h1>
                Resultados da atividade
            </h1>

            <h2 class="activity-title">
                <?= e($a['title']) ?>
            </h2>

        </div>

        <div class="header-actions">

            <a href="criar.php" class="btn">
                Postar Atividade
            </a>

            <form method="POST"
                  class="delete-form"
                  onsubmit="return confirmarExclusao();">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e($_SESSION['csrf_token']) ?>"
                >

                <input
                    type="hidden"
                    name="atividade_id"
                    value="<?= e((string)$a['id']) ?>"
                >

                <button
                    type="submit"
                    name="excluir_atividade"
                    class="btn btn-danger"
                >
                    🗑️ Excluir Atividade
                </button>

            </form>

        </div>

    </div>

    <!-- ================================
         INFORMAÇÕES
    ================================= -->

    <div class="activity-info">

        <div class="info-card">

            <span class="info-icon">📚</span>

            <div>
                <strong>Atividade</strong>
                <p><?= e($a['title']) ?></p>
            </div>

        </div>

        <div class="info-card">

            <span class="info-icon">📝</span>

            <div>
                <strong>Respostas</strong>
                <p><?= count($rows) ?> aluno(s)</p>
            </div>

        </div>

        <div class="info-card">

            <span class="info-icon">🏫</span>

            <div>
                <strong>Escola e turma</strong>
                <p><?= $a['target_school'] !== null && $a['target_serie'] !== null ? e($a['target_school'] . ' · ' . $a['target_serie']) : 'Atividade antiga sem destino definido' ?></p>
            </div>

        </div>

        <div class="info-card">

            <span class="info-icon">👩‍🏫</span>

            <div>
                <strong>Professor</strong>
                <p>Painel do professor</p>
            </div>

        </div>

    </div>

    <!-- ================================
         RESULTADOS
    ================================= -->

    <div class="card results-card">

        <div class="results-heading">

            <div>
                <h2>Resultados dos alunos</h2>

                <p>
                    Confira o desempenho dos alunos nesta atividade.
                </p>
            </div>

            <span class="result-count">
                <?= count($rows) ?> respostas
            </span>

        </div>

        <?php if ($rows): ?>

            <div class="table-wrap">

                <table>

                    <thead>

                        <tr>

                            <th>Aluno</th>
                            <th>Série</th>
                            <th>Escola</th>
                            <th>Nota</th>
                            <th>Data</th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php foreach ($rows as $r): ?>

                        <?php
                        $score = (int)$r['score'];
                        $total = max(1, (int)$r['total']);

                        $percentage = round(
                            ($score / $total) * 100
                        );
                        ?>

                        <tr>

                            <td>
                                <strong>
                                    <?= e($r['name']) ?>
                                </strong>
                            </td>

                            <td>
                                <?= e($r['serie'] ?? '-') ?>
                            </td>

                            <td>
                                <?= e($r['escola']) ?>
                            </td>

                            <td>

                                <span class="score-badge
                                    <?= $percentage >= 70
                                        ? 'score-good'
                                        : 'score-low' ?>">

                                    <?= $score ?>/<?= $total ?>

                                </span>

                            </td>

                            <td>
                                <?= e($r['submitted_at']) ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php else: ?>

            <div class="empty-results">

                <div class="empty-icon">
                    📚
                </div>

                <h3>
                    Nenhum aluno respondeu ainda
                </h3>

                <p>
                    Assim que os alunos responderem,
                    os resultados aparecerão aqui.
                </p>

                <a href="criar.php" class="btn">
                    Criar outra atividade
                </a>

            </div>

        <?php endif; ?>

    </div>

</main>


<script>

function confirmarExclusao() {

    return confirm(
        "⚠️ Tem certeza que deseja excluir esta atividade?\n\n" +
        "A atividade, suas perguntas e as respostas dos alunos " +
        "serão excluídas permanentemente."
    );

}

</script>

</body>
</html>