<?php require 'config.php';
if (!empty($_SESSION['user_id'])) redirect('dashboard.php');
?>

<!doctype html>
<html lang="<?= e(current_language()) ?>" <?= accessibility_html_attributes() ?>>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Lunaris Academy - Aprenda matemática jogando e superando desafios pela galáxia">
    <meta name="theme-color" content="#050817">
    <title>Lunaris Academy - Matemática Gamificada</title>
    <link rel="stylesheet" href="assets/style.css">
    <script src="assets/theme.js"></script>
</head>

<body>
    <!-- NAVIGATION BAR -->
    <nav class="navbar">
        <a href="index.php" class="logo" aria-label="Lunaris Academy">
            <img src="logo.png" alt="Logo Lunaris">
            <b>Lunaris</b>
        </a>
        <div class="nav-links">
            <a href="login.php">Login</a>
            <a href="cadastro.php" class="btn">Criar Conta</a>
        </div>
    </nav>

    <!-- HERO SECTION -->
    <section class="hero">
        <div class="hero-content fade-in">
            <h1>
                Aprenda Matemática.
                <span class="lunaris">Supere Desafios.</span>
            </h1>

            <p>
                Aprenda, jogue e evolua através de desafios matemáticos 
                em uma jornada pelo universo mágico da Lunaris Academy.
            </p>

            <div class="actions">
                <a href="cadastro.php" class="btn">Começar Agora</a>
                <a href="#sobre" class="btn btn-secondary">Conhecer Mais</a>
            </div>
        </div>
    </section>

    <!-- ABOUT SECTION -->
    <section id="sobre" class="container features fade-in">
        <h2>Por que Lunaris Academy?</h2>
        
        <div class="cards">
            <div class="card">
                <div class="icon">🔐</div>
                <h3>Segurança</h3>
                <p>Proteção de dados, senhas criptografadas e ambiente seguro para alunos.</p>
            </div>

            <div class="card">
                <div class="icon">🎯</div>
                <h3>Progressão</h3>
                <p>Questões que aumentam de dificuldade conforme seu desempenho.</p>
            </div>

            <div class="card">
                <div class="icon">👩‍🏫</div>
                <h3>Para Professores</h3>
                <p>Crie atividades, acompanhe resultados e gerencie suas turmas.</p>
            </div>

            <div class="card">
                <div class="icon">⭐</div>
                <h3>Gamificação</h3>
                <p>Ganhe pontos, desbloqueie conquistas e suba de nível.</p>
            </div>

            <div class="card">
                <div class="icon">📊</div>
                <h3>Análise</h3>
                <p>Acompanhe seu progresso com relatórios detalhados.</p>
            </div>

            <div class="card">
                <div class="icon">🚀</div>
                <h3>Inovação</h3>
                <p>Tecnologia educacional moderna e interativa.</p>
            </div>
        </div>
    </section>

    <!-- FEATURES SECTION -->
    <section class="container games-section fade-in">
        <h2>Tipos de Atividades</h2>
        
        <div class="games-grid">
            <div class="game-card">
                <div class="game-icon">➕</div>
                <h3>Operações</h3>
                <p>Adição, subtração e multiplicação com níveis progressivos.</p>
            </div>

            <div class="game-card">
                <div class="game-icon">🍕</div>
                <h3>Frações</h3>
                <p>Aprenda frações e proporções de forma interativa.</p>
            </div>

            <div class="game-card">
                <div class="game-icon">⚖️</div>
                <h3>Equações</h3>
                <p>Resolva equações de 1º grau com desafios crescentes.</p>
            </div>

            <div class="game-card">
                <div class="game-icon">📐</div>
                <h3>Geometria</h3>
                <p>Calcule áreas, perímetros e propriedades geométricas.</p>
            </div>
        </div>
    </section>

    <!-- CTA SECTION -->
    <section class="cta-section">
        <div class="container">
            <h2>Pronto para começar sua jornada?</h2>
            <p>Junte-se a milhares de alunos aprendendo matemática de forma divertida.</p>
            <a href="cadastro.php" class="btn">Registre-se Agora</a>
        </div>
    </section>

    <!-- FOOTER -->
    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 Lunaris Academy. Todos os direitos reservados.</p>
            <p>Aprenda. Jogue. Evolua.</p>
        </div>
    </footer>
</body>

</html>