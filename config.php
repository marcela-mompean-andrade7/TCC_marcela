<?php

declare(strict_types=1);
session_start();

$host = 'sql104.byethost8.com';
$db = 'b8_42774077_lunaris';
$user = 'b8_42774077';
$pass = 'Senai@463';
$port = '3308';
try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
} catch (PDOException $e) {
    exit('Erro ao conectar ao banco. Confira o MySQL e o banco mathplay.');
}

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS activity_progress(
        activity_id INT UNSIGNED NOT NULL,
        student_id INT UNSIGNED NOT NULL,
        question_id INT UNSIGNED NOT NULL,
        answer VARCHAR(120) NOT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY(activity_id,student_id,question_id),
        FOREIGN KEY(activity_id) REFERENCES activities(id) ON DELETE CASCADE,
        FOREIGN KEY(student_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY(question_id) REFERENCES questions(id) ON DELETE CASCADE
    )'
);

function ensure_activity_targeting_schema(PDO $pdo): void
{
    $activityColumns = array_column($pdo->query('SHOW COLUMNS FROM activities')->fetchAll(), 'Field');
    if (!in_array('target_school', $activityColumns, true)) {
        $pdo->exec('ALTER TABLE activities ADD COLUMN target_school VARCHAR(180) NULL AFTER professor_id');
    }
    if (!in_array('target_serie', $activityColumns, true)) {
        $pdo->exec('ALTER TABLE activities ADD COLUMN target_serie VARCHAR(40) NULL AFTER target_school');
    }

    $questionColumns = array_column($pdo->query('SHOW COLUMNS FROM questions')->fetchAll(), 'Field');
    foreach ([
        'first_number' => 'DECIMAL(16,6) NULL',
        'operation' => 'VARCHAR(16) NULL',
        'second_number' => 'DECIMAL(16,6) NULL',
    ] as $column => $definition) {
        if (!in_array($column, $questionColumns, true)) {
            $pdo->exec("ALTER TABLE questions ADD COLUMN $column $definition");
        }
    }

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS submission_answers(
            submission_id INT UNSIGNED NOT NULL,
            question_id INT UNSIGNED NOT NULL,
            answer VARCHAR(120) NOT NULL,
            is_correct TINYINT(1) NOT NULL DEFAULT 0,
            PRIMARY KEY(submission_id, question_id),
            FOREIGN KEY(submission_id) REFERENCES submissions(id) ON DELETE CASCADE,
            FOREIGN KEY(question_id) REFERENCES questions(id) ON DELETE CASCADE
        )'
    );
}

ensure_activity_targeting_schema($pdo);

function registered_student_destinations(PDO $pdo): array
{
    $rows = $pdo->query(
        "SELECT DISTINCT escola, serie
         FROM users
         WHERE role = 'aluno' AND escola <> '' AND serie IS NOT NULL
         ORDER BY escola, serie"
    )->fetchAll();
    $destinations = [];
    foreach ($rows as $row) {
        $destinations[$row['escola']][] = $row['serie'];
    }
    return $destinations;
}

function parse_activity_decimal(mixed $value): ?float
{
    if (!is_string($value) || !preg_match('/^-?\d{1,9}(?:[.,]\d{1,6})?$/D', trim($value))) {
        return null;
    }
    $number = (float)str_replace(',', '.', trim($value));
    return is_finite($number) ? $number : null;
}

function format_activity_decimal(float $number): string
{
    $formatted = number_format(round($number, 6, PHP_ROUND_HALF_UP), 6, '.', '');
    $formatted = rtrim(rtrim($formatted, '0'), '.');
    return $formatted === '-0' || $formatted === '' ? '0' : $formatted;
}

function calculate_activity_result(float $first, string $operation, float $second): ?float
{
    $result = match ($operation) {
        'addition' => $first + $second,
        'subtraction' => $first - $second,
        'multiplication' => $first * $second,
        'division' => $second === 0.0 ? null : $first / $second,
        default => null,
    };
    return $result !== null && is_finite($result) && abs($result) <= 999999999.999999
        ? round($result, 6, PHP_ROUND_HALF_UP)
        : null;
}

function activity_math_answers_match(string $given, string $expected): bool
{
    $givenNumber = parse_activity_decimal($given);
    $expectedNumber = parse_activity_decimal($expected);
    return $givenNumber !== null
        && $expectedNumber !== null
        && abs(round($givenNumber, 6) - round($expectedNumber, 6)) < 0.0000005;
}

function student_can_access_activity(PDO $pdo, int $activityId, int $studentId): bool
{
    $query = $pdo->prepare(
        "SELECT a.id
         FROM activities a
         JOIN users student ON student.id = ? AND student.role = 'aluno'
         WHERE a.id = ?
           AND (a.target_school IS NULL OR (a.target_school = student.escola AND a.target_serie = student.serie))
           AND (a.due_date IS NULL OR a.due_date >= CURRENT_DATE)"
    );
    $query->execute([$studentId, $activityId]);
    return (bool)$query->fetchColumn();
}

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS planets(
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(80) NOT NULL,
        slug VARCHAR(80) NOT NULL UNIQUE,
        description TEXT NOT NULL,
        icon VARCHAR(20) NOT NULL,
        price INT NOT NULL DEFAULT 0,
        display_order INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )'
);

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS student_planets(
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        student_id INT UNSIGNED NOT NULL,
        planet_id INT UNSIGNED NOT NULL,
        purchased_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_student_planet(student_id, planet_id),
        FOREIGN KEY(student_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY(planet_id) REFERENCES planets(id) ON DELETE CASCADE
    )'
);

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS avatars(
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        slug VARCHAR(80) NOT NULL UNIQUE,
        name VARCHAR(100) NOT NULL,
        description VARCHAR(255) NOT NULL,
        icon VARCHAR(20) NOT NULL,
        image_path VARCHAR(255) NOT NULL,
        price INT UNSIGNED NOT NULL DEFAULT 0,
        display_order INT UNSIGNED NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )'
);

$imagePathColumn = $pdo->query("SHOW COLUMNS FROM avatars LIKE 'image_path'")->fetch();
if (!$imagePathColumn) {
    $pdo->exec("ALTER TABLE avatars ADD COLUMN image_path VARCHAR(255) NOT NULL DEFAULT '' AFTER icon");
}

$rarityColumn = $pdo->query("SHOW COLUMNS FROM avatars LIKE 'rarity'")->fetch();
if (!$rarityColumn) {
    $pdo->exec("ALTER TABLE avatars ADD COLUMN rarity VARCHAR(20) NOT NULL DEFAULT 'comum' AFTER price");
}

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS student_avatars(
        student_id INT UNSIGNED NOT NULL,
        avatar_id INT UNSIGNED NOT NULL,
        acquired_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY(student_id, avatar_id),
        FOREIGN KEY(student_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY(avatar_id) REFERENCES avatars(id) ON DELETE CASCADE
    )'
);

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS student_avatar_selection(
        student_id INT UNSIGNED NOT NULL PRIMARY KEY,
        avatar_id INT UNSIGNED NOT NULL,
        selected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY(student_id, avatar_id) REFERENCES student_avatars(student_id, avatar_id) ON DELETE CASCADE
    )'
);

function seed_default_avatars(PDO $pdo): void
{
    $avatars = [
        ['guardiao-lunar', 'Luna — Guardiã Lunar', 'Acolhedora e misteriosa, Luna guia os primeiros passos da jornada matemática.', '🌙', 'assets/avatars/luna.svg', 0, 'comum', 1],
        ['explorador-espacial', 'Astrion — Explorador Cósmico', 'Curioso e destemido, Astrion transforma cada desafio em uma nova descoberta.', '🚀', 'assets/avatars/astrion.svg', 150, 'comum', 2],
        ['guardiao-das-estrelas', 'Aurea — Maga Dourada', 'Sábia e luminosa, Aurea revela o conhecimento escondido nas estrelas.', '✨', 'assets/avatars/aurea.svg', 300, 'raro', 3],
        ['viajante-dos-planetas', 'Nébula — Guardiã das Estrelas', 'Criativa e imaginativa, Nébula protege os sonhos que iluminam a galáxia.', '💜', 'assets/avatars/nebula.svg', 450, 'epico', 4],
        ['astronauta-matematico', 'Orion — Mestre das Constelações', 'Estrategista experiente, Orion encontra padrões e soluções no céu noturno.', '🌌', 'assets/avatars/orion.svg', 650, 'epico', 5],
        ['mago-cosmico', 'Solarius — Guardião Solar', 'Dedicado e radiante, Solarius representa a força de quem nunca para de evoluir.', '☀️', 'assets/avatars/solarius.svg', 900, 'epico', 6],
        ['mestre-lunaris', 'Galaxia — Lenda Lunaris', 'Uma lenda que une luz, coragem e sabedoria para alcançar grandes objetivos.', '👑', 'assets/avatars/galaxia.svg', 1500, 'lendario', 7],
        ['cometa', 'Cometa — Mensageiro Estelar', 'Veloz e otimista, Cometa cruza a galáxia deixando trilhas de luz azul.', '☄️', 'assets/avatars/cometa.svg', 200, 'comum', 8],
        ['cosmo', 'Cosmo — Inventor Galáctico', 'Inventivo e curioso, Cosmo transforma sucata espacial em engenhocas brilhantes.', '⚙️', 'assets/avatars/cosmo.svg', 250, 'comum', 9],
        ['selene', 'Selene — Princesa do Eclipse', 'Elegante e serena, Selene equilibra luz e sombra em cada descoberta.', '🌘', 'assets/avatars/selene.svg', 500, 'raro', 10],
        ['estella', 'Estella — Arqueira Celeste', 'Precisa e corajosa, Estella mira alto guiada pelas estrelas.', '🏹', 'assets/avatars/estella.svg', 550, 'raro', 11],
        ['atlas', 'Atlas — Viajante dos Planetas', 'Generoso e aventureiro, Atlas carrega mapas de mundos por explorar.', '🧭', 'assets/avatars/atlas.svg', 600, 'raro', 12],
        ['draco', 'Draco — Cavaleiro Nebular', 'Leal e destemido, Draco protege a galáxia com sua armadura estelar.', '🐉', 'assets/avatars/draco.svg', 700, 'epico', 13],
        ['vega', 'Vega — Dançarina das Estrelas', 'Expressiva e graciosa, Vega dança entre constelações cintilantes.', '💫', 'assets/avatars/vega.svg', 750, 'epico', 14],
        ['iris', 'Íris — Oráculo Cósmico', 'Intuitiva e sábia, Íris lê caminhos possíveis em seus cristais cósmicos.', '🔮', 'assets/avatars/iris.svg', 800, 'epico', 15],
        ['noctis', 'Noctis — Guardião da Noite', 'Tranquilo e protetor, Noctis ilumina os caminhos mais silenciosos.', '🌙', 'assets/avatars/noctis.svg', 850, 'epico', 16],
        ['aurora', 'Aurora — Maga das Nebulosas', 'Criativa e gentil, Aurora tece cores de aurora em feitiços luminosos.', '🌈', 'assets/avatars/aurora.svg', 1000, 'epico', 17],
        ['meteor', 'Meteor — Piloto Estelar', 'Audacioso e habilidoso, Meteor pilota sua nave por rotas desconhecidas.', '🛸', 'assets/avatars/meteor.svg', 1100, 'epico', 18],
        ['lyra', 'Lyra — Cantora da Galáxia', 'Alegre e inspiradora, Lyra transforma melodias em poeira de estrelas.', '🎼', 'assets/avatars/lyra.svg', 1250, 'epico', 19],
        ['sirius', 'Sirius', 'Personagem da coleção Lunaris.', '✨', 'assets/avatars/orion.svg', 1300, 'epico', 20],
        ['zenith', 'Zenith', 'Personagem da coleção Lunaris.', '🌟', 'assets/avatars/galaxia.svg', 1400, 'epico', 21],
    ];

    $stmt = $pdo->prepare('INSERT IGNORE INTO avatars(slug, name, description, icon, image_path, price, rarity, display_order) VALUES(?,?,?,?,?,?,?,?)');
    $update = $pdo->prepare('UPDATE avatars SET name = ?, description = ?, icon = ?, image_path = ?, price = ?, rarity = ?, display_order = ? WHERE slug = ?');
    foreach ($avatars as $avatar) {
        $stmt->execute($avatar);
        $update->execute([$avatar[1], $avatar[2], $avatar[3], $avatar[4], $avatar[5], $avatar[6], $avatar[7], $avatar[0]]);
    }

    $pdo->exec("UPDATE avatars SET display_order = 99 WHERE slug = 'explorador-das-galaxias'");
}

seed_default_avatars($pdo);

function avatar_character_images(string $slug): array
{
    static $images = [
        'guardiao-lunar' => ['profile' => 'imagens/avatars-transparent/luna_perfil.png', 'full' => 'imagens/luna.jpg'],
        'explorador-espacial' => ['profile' => 'imagens/avatars-transparent/astrion_perfil.png', 'full' => 'imagens/astrion.jpg'],
        'guardiao-das-estrelas' => ['profile' => 'imagens/avatars-transparent/aurea_perfil.png', 'full' => 'imagens/aurea.jpg'],
        'viajante-dos-planetas' => ['profile' => 'imagens/avatars-transparent/nebula_perfil.png', 'full' => 'imagens/nebula.jpg'],
        'astronauta-matematico' => ['profile' => 'imagens/avatars-transparent/orion_perfil.png', 'full' => 'imagens/orion.jpg'],
        'mago-cosmico' => ['profile' => 'imagens/avatars-transparent/solarius_perfil.png', 'full' => 'imagens/solarius.jpg'],
        'mestre-lunaris' => ['profile' => 'imagens/avatars-transparent/galaxia_perfil.png', 'full' => 'imagens/galaxia.jpg'],
        'cometa' => ['profile' => 'imagens/avatars-transparent/cometa_perfil.png', 'full' => 'imagens/cometa.jpg'],
        'cosmo' => ['profile' => 'imagens/avatars-transparent/cosmo_perfil.png', 'full' => 'imagens/cosmo.jpg'],
        'selene' => ['profile' => 'imagens/avatars-transparent/selene_perfil.png', 'full' => 'imagens/selene.jpg'],
        'estella' => ['profile' => 'imagens/avatars-transparent/estella_perfil.png', 'full' => 'imagens/estella.jpg'],
        'atlas' => ['profile' => 'imagens/avatars-transparent/atlas_perfil.png', 'full' => 'imagens/atlas.jpg'],
        'draco' => ['profile' => 'imagens/avatars-transparent/draco_perfil.png', 'full' => 'imagens/draco.jpg'],
        'vega' => ['profile' => 'imagens/avatars-transparent/vega_perfil.png', 'full' => 'imagens/vega.jpg'],
        'iris' => ['profile' => 'imagens/avatars-transparent/iris_pefil.png', 'full' => 'imagens/iris.jpg'],
        'noctis' => ['profile' => 'imagens/avatars-transparent/noctis_perfil.png', 'full' => 'imagens/noctis.jpg'],
        'aurora' => ['profile' => 'imagens/avatars-transparent/auror_perfil.png', 'full' => 'imagens/aurora.jpg'],
        'meteor' => ['profile' => 'imagens/avatars-transparent/meteor_perfil.png', 'full' => 'imagens/meteor.jpg'],
        'lyra' => ['profile' => 'imagens/avatars-transparent/lyra_perfil.png', 'full' => 'imagens/lyra.jpg'],
        'sirius' => ['profile' => 'imagens/avatars-transparent/sirius_perfil.png', 'full' => 'imagens/sirius.jpg'],
        'zenith' => ['profile' => 'imagens/avatars-transparent/zenith_perfil.png', 'full' => 'imagens/zenith.jpg'],
    ];

    if (!isset($images[$slug])) {
        return ['profile' => null, 'full' => null];
    }

    $paths = [];
    foreach ($images[$slug] as $type => $relativePath) {
        $absolutePath = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        $paths[$type] = is_file($absolutePath) ? $relativePath : null;
    }

    return $paths;
}

function avatar_profile_image(array $avatar): string
{
    $images = avatar_character_images((string)($avatar['slug'] ?? ''));
    return $images['profile'] ?? ((string)($avatar['image_path'] ?? '') ?: 'assets/avatars/galaxia.svg');
}

function seed_default_planets(PDO $pdo): void
{
    $planets = [
        ['Terra', 'terra', 'Seu planeta de origem', '🌍', 0, 1],
        ['Marte', 'marte', 'Planeta da coragem e dos desafios', '🔴', 500, 2],
        ['Júpiter', 'jupiter', 'Planeta dos grandes desafios', '🟡', 1000, 3],
        ['Saturno', 'saturno', 'Planeta da persistência', '🪐', 1500, 4],
        ['Netuno', 'netuno', 'Planeta dos grandes exploradores', '🔵', 2000, 5],
        ['Urano', 'urano', 'Planeta dos desafios avançados', '🟣', 2500, 6],
        ['Sol', 'sol', 'Símbolo máximo da jornada', '☀️', 5000, 7],
    ];

    $stmt = $pdo->prepare(
        'INSERT INTO planets(name, slug, description, icon, price, display_order)
         VALUES(?,?,?,?,?,?)
         ON DUPLICATE KEY UPDATE
            name = VALUES(name),
            description = VALUES(description),
            icon = VALUES(icon),
            price = VALUES(price),
            display_order = VALUES(display_order)'
    );
    foreach ($planets as $planet) {
        $stmt->execute($planet);
    }
}

seed_default_planets($pdo);

function ensure_student_starter_planet(PDO $pdo, int $studentId): void
{
    $q = $pdo->prepare('SELECT id FROM planets WHERE slug = ? LIMIT 1');
    $q->execute(['terra']);
    $planet = $q->fetch();

    if (!$planet) {
        throw new RuntimeException('O planeta inicial Terra não está cadastrado.');
    }

    $q = $pdo->prepare('INSERT IGNORE INTO student_planets(student_id, planet_id) VALUES(?, ?)');
    $q->execute([$studentId, $planet['id']]);
}

function ensure_student_starter_avatar(PDO $pdo, int $studentId): void
{
    $q = $pdo->prepare('SELECT id FROM avatars WHERE slug = ? LIMIT 1');
    $q->execute(['guardiao-lunar']);
    $avatar = $q->fetch();

    if (!$avatar) {
        throw new RuntimeException('O avatar inicial não está cadastrado.');
    }

    $q = $pdo->prepare('INSERT IGNORE INTO student_avatars(student_id, avatar_id) VALUES(?, ?)');
    $q->execute([$studentId, $avatar['id']]);

    $q = $pdo->prepare('INSERT IGNORE INTO student_avatar_selection(student_id, avatar_id) VALUES(?, ?)');
    $q->execute([$studentId, $avatar['id']]);
}

function e(?string $v): string
{
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}

function current_language(): string
{
    $language = $_SESSION['lunaris_language'] ?? 'pt-BR';
    return in_array($language, ['pt-BR', 'en', 'es'], true) ? $language : 'pt-BR';
}

function set_language(string $language): void
{
    if (in_array($language, ['pt-BR', 'en', 'es'], true)) {
        $_SESSION['lunaris_language'] = $language;
    }
}

function t(string $message): string
{
    static $translations = [];
    $language = current_language();

    if ($language === 'pt-BR') {
        return $message;
    }

    if (!isset($translations[$language])) {
        $translations[$language] = require __DIR__ . '/assets/i18n/' . $language . '.php';
    }

    return $translations[$language][$message] ?? $message;
}

function math_game_titles(): array
{
    $titles = [
        'operacoes' => 'Operações com Números',
        'fracoes' => 'Frações e Razões',
        'equacoes' => 'Equações de 1º Grau',
        'geometria' => 'Área e Perímetro',
    ];

    return array_map('t', $titles);
}

function accessibility_status_options(): array
{
    return [
        'prefer_not' => 'Prefiro não informar.',
        'none' => 'Não possuo deficiência.',
        'visual' => 'Deficiência visual.',
        'hearing' => 'Deficiência auditiva.',
        'mobility' => 'Deficiência física ou motora.',
        'intellectual' => 'Deficiência intelectual.',
        'psychosocial' => 'Deficiência psicossocial.',
        'multiple' => 'Deficiência múltipla.',
        'other' => 'Outra deficiência.',
    ];
}

function color_vision_status_options(): array
{
    return [
        'none' => 'Não possuo daltonismo.',
        'protanopia' => 'Protanopia — dificuldade para perceber tons de vermelho.',
        'deuteranopia' => 'Deuteranopia — dificuldade para distinguir determinadas tonalidades de vermelho e verde.',
        'tritanopia' => 'Tritanopia — dificuldade para distinguir determinadas tonalidades de azul e amarelo.',
        'other' => 'Outro tipo ou dificuldade relacionada à percepção das cores.',
        'prefer_not' => 'Prefiro não informar.',
    ];
}

function accessibility_resource_options(): array
{
    return [
        'screen_reader' => 'Compatibilidade com leitores de tela.',
        'larger_text' => 'Textos maiores.',
        'largest_text' => 'Aumento adicional do texto.',
        'high_contrast' => 'Alto contraste.',
        'reduced_motion' => 'Reduzir animações e movimentos.',
        'keyboard' => 'Navegação pelo teclado.',
        'captions_visual' => 'Legendas e instruções visuais.',
        'simple_instructions' => 'Instruções mais simples e organizadas.',
        'other' => 'Outros recursos de acessibilidade.',
        'color_vision' => 'Ajudar a distinguir informações apresentadas por cores.',
    ];
}

function accessibility_palette_options(): array
{
    return [
        'color_palette_blue_orange' => 'Azul e laranja',
        'color_palette_purple_yellow' => 'Roxo e amarelo',
    ];
}

function accessibility_preference_keys(): array
{
    return array_merge(
        array_keys(accessibility_resource_options()),
        array_keys(accessibility_palette_options()),
        ['readable_text', 'accessible_font']
    );
}

function normalize_accessibility_preferences(mixed $preferences): array
{
    if (is_string($preferences)) {
        $decoded = json_decode($preferences, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('Preferências de acessibilidade com JSON inválido.');
            return [];
        }
        $preferences = $decoded;
    }

    if (!is_array($preferences)) {
        return [];
    }

    $validPreferences = accessibility_preference_keys();
    $normalized = array_values(array_unique(array_filter(
        $preferences,
        static fn($preference): bool => is_string($preference) && in_array($preference, $validPreferences, true)
    )));

    if (count($normalized) !== count($preferences)) {
        error_log('Preferências de acessibilidade inválidas ou desconhecidas foram ignoradas.');
    }

    return $normalized;
}

function application_base_path(): string
{
    $scriptDirectory = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    if (basename($scriptDirectory) === 'professor') {
        $scriptDirectory = dirname($scriptDirectory);
    }
    return rtrim($scriptDirectory, '/');
}

function application_asset_url(string $path): string
{
    return application_base_path() . '/' . ltrim($path, '/');
}

function accessibility_html_attributes(): string
{
    $preferences = normalize_accessibility_preferences($_SESSION['lunaris_accessibility'] ?? []);
    $attributes = 'data-accessibility-preferences="' . e(json_encode($preferences, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) . '"';
    $attributes .= ' data-accessibility-endpoint="' . e(application_base_path() . '/accessibility_preferences.php') . '"';

    if (!empty($_SESSION['user_id'])) {
        $attributes .= ' data-accessibility-csrf="' . e(csrf()) . '"';
    }

    return $attributes;
}

function language_options(): array
{
    return [
        'pt-BR' => 'Português (Brasil)',
        'en' => 'English',
        'es' => 'Español',
    ];
}

function csrf(): string
{
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function check_csrf(): void
{
    if (!isset($_POST['csrf'])
        || !is_string($_POST['csrf'])
        || !hash_equals((string)($_SESSION['csrf'] ?? ''), $_POST['csrf'])
    ) {
        http_response_code(403);
        exit('Requisição inválida.');
    }
}
function login_required(): void
{
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . application_base_path() . '/login.php');
        exit;
    }

    if (!isset($_SESSION['lunaris_preferences_loaded']) && ($GLOBALS['pdo'] ?? null) instanceof PDO) {
        $preferencesQuery = $GLOBALS['pdo']->prepare('SELECT preferred_language, accessibility_needs FROM users WHERE id = ?');
        $preferencesQuery->execute([$_SESSION['user_id']]);
        $preferences = $preferencesQuery->fetch();
        if ($preferences) {
            set_language((string)($preferences['preferred_language'] ?: 'pt-BR'));
            $_SESSION['lunaris_accessibility'] = normalize_accessibility_preferences($preferences['accessibility_needs'] ?? null);
            $_SESSION['lunaris_preferences_loaded'] = true;
        }
    }

    if (($GLOBALS['pdo'] ?? null) instanceof PDO && ($_SESSION['role'] ?? '') === 'aluno') {
        ensure_student_starter_planet($GLOBALS['pdo'], (int)$_SESSION['user_id']);
        ensure_student_starter_avatar($GLOBALS['pdo'], (int)$_SESSION['user_id']);
    }
}
function teacher_required(): void
{
    login_required();
    if (($_SESSION['role'] ?? '') !== 'professor') {
        http_response_code(403);
        exit('Acesso restrito ao professor.');
    }
}
function redirect(string $url): never
{
    header("Location: $url");
    exit;
}
