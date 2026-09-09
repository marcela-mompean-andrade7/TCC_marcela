<?php require 'config.php';
if (!empty($_SESSION['user_id'])) redirect('dashboard.php');
?>

<!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>MathPlay Solutions</title>
    <link rel="stylesheet" href="assets/style.css">
</head>

<body>
    <header class="nav">
        <b>🎮 MathPlay</b>
        <nav>
            <a href="login.php">Entrar</a>
            <a class="btn" href="cadastro.php">Criar conta</a>
        </nav>
    </header>
    <main class="hero">
        <section>
            <span class="tag">MATEMÁTICA GAMIFICADA</span>
            <h1>Aprenda. Jogue. Evolua.</h1>
            <p>Jogos e atividades de matemática com níveis progressivos, pontuação e acompanhamento do aprendizado.</p>
            <div class="actions">
                <a class="btn" href="cadastro.php">Começar</a>
                <a class="btn light" href="#sobre">Conhecer</a>
            </div>
        </section>
        <div class="card hero-card">
            <div class="orb">➗</div>
            <h2>Trilha MathPlay</h2>
            <p>Fácil → Médio → Difícil</p>
            <div class="bar">
                <i></i>
            </div>
            <p>⭐ Pontos • 🏅 Conquistas • 📈 Progresso</p>
        </div>
    </main>
    <section id="sobre" class="features">
        <div class="card">
            <b>🔐 Segurança</b>
            <p>Senha protegida, sessões e consultas preparadas.</p>
        </div>
        <div class="card">
            <b>🎯 Progressão</b>
            <p>Questões aumentam de dificuldade conforme o nível.</p>
        </div>
        <div class="card">
            <b>👩‍🏫 Professor</b>
            <p>Criação de atividades e tarefas para os alunos.</p>
        </div>
    </section>
    <footer>MathPlay Solutions</footer>
</body>

</html>