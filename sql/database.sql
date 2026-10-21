CREATE DATABASE IF NOT EXISTS mathplay CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE mathplay;

CREATE TABLE IF NOT EXISTS users(
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(120) NOT NULL,
 email VARCHAR(180) NOT NULL UNIQUE,
 password_hash VARCHAR(255) NOT NULL,
 profile_photo VARCHAR(255) NULL,
 role ENUM('aluno','professor') NOT NULL,
 serie ENUM('6º ano','7º ano','8º ano','9º ano') NULL,
 regiao ENUM('Norte','Nordeste','Centro-Oeste','Sudeste','Sul') NOT NULL,
 escola VARCHAR(180) NOT NULL,
 preferred_language VARCHAR(5) NOT NULL DEFAULT 'pt-BR',
 disability_status VARCHAR(32) NOT NULL DEFAULT 'prefer_not',
 disability_other VARCHAR(255) NULL,
 color_vision_status VARCHAR(32) NOT NULL DEFAULT 'prefer_not',
 accessibility_needs TEXT NULL,
 accessibility_other VARCHAR(255) NULL,
 points INT NOT NULL DEFAULT 0,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS activities(
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 professor_id INT UNSIGNED NOT NULL,
 target_school VARCHAR(180) NULL,
 target_serie VARCHAR(40) NULL,
 title VARCHAR(160) NOT NULL,
 description TEXT NOT NULL,
 difficulty ENUM('facil','medio','dificil') NOT NULL DEFAULT 'facil',
 due_date DATE NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(professor_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS questions(
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 activity_id INT UNSIGNED NOT NULL,
 question_text TEXT NOT NULL,
 answer VARCHAR(120) NOT NULL,
 first_number DECIMAL(16,6) NULL,
 operation VARCHAR(16) NULL,
 second_number DECIMAL(16,6) NULL,
 FOREIGN KEY(activity_id) REFERENCES activities(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS submissions(
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 activity_id INT UNSIGNED NOT NULL,
 student_id INT UNSIGNED NOT NULL,
 score INT NOT NULL DEFAULT 0,
 total INT NOT NULL DEFAULT 0,
 submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY one_submission(activity_id,student_id),
 FOREIGN KEY(activity_id) REFERENCES activities(id) ON DELETE CASCADE,
 FOREIGN KEY(student_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS submission_answers(
 submission_id INT UNSIGNED NOT NULL,
 question_id INT UNSIGNED NOT NULL,
 answer VARCHAR(120) NOT NULL,
 is_correct TINYINT(1) NOT NULL DEFAULT 0,
 PRIMARY KEY(submission_id,question_id),
 FOREIGN KEY(submission_id) REFERENCES submissions(id) ON DELETE CASCADE,
 FOREIGN KEY(question_id) REFERENCES questions(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS activity_progress(
 activity_id INT UNSIGNED NOT NULL,
 student_id INT UNSIGNED NOT NULL,
 question_id INT UNSIGNED NOT NULL,
 answer VARCHAR(120) NOT NULL,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 PRIMARY KEY(activity_id,student_id,question_id),
 FOREIGN KEY(activity_id) REFERENCES activities(id) ON DELETE CASCADE,
 FOREIGN KEY(student_id) REFERENCES users(id) ON DELETE CASCADE,
 FOREIGN KEY(question_id) REFERENCES questions(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS game_results(
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 student_id INT UNSIGNED NOT NULL,
 game VARCHAR(60) NOT NULL,
 difficulty ENUM('facil','medio','dificil') NOT NULL,
 score INT NOT NULL,
 correct_answers INT NOT NULL,
 total_questions INT NOT NULL,
 played_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(student_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS missions(
 id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 slug VARCHAR(80) NOT NULL UNIQUE,
 title VARCHAR(160) NOT NULL,
 destination VARCHAR(80) NOT NULL,
 destination_icon VARCHAR(20) NOT NULL,
 story TEXT NOT NULL,
 topic VARCHAR(160) NOT NULL,
 difficulty VARCHAR(40) NOT NULL,
 question_count SMALLINT UNSIGNED NOT NULL,
 reward_stars SMALLINT UNSIGNED NOT NULL DEFAULT 25,
 display_order SMALLINT UNSIGNED NOT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS mission_attempts(
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 mission_id SMALLINT UNSIGNED NOT NULL,
 student_id INT UNSIGNED NOT NULL,
 current_question SMALLINT UNSIGNED NOT NULL DEFAULT 1,
 correct_answers SMALLINT UNSIGNED NOT NULL DEFAULT 0,
 started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 completed_at TIMESTAMP NULL DEFAULT NULL,
 KEY mission_attempt_student (student_id, mission_id, id),
 FOREIGN KEY(mission_id) REFERENCES missions(id) ON DELETE CASCADE,
 FOREIGN KEY(student_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS mission_answers(
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 attempt_id BIGINT UNSIGNED NOT NULL,
 question_number SMALLINT UNSIGNED NOT NULL,
 answer VARCHAR(255) NOT NULL,
 is_correct TINYINT(1) NOT NULL DEFAULT 0,
 answered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 KEY mission_answer_attempt_question (attempt_id, question_number),
 FOREIGN KEY(attempt_id) REFERENCES mission_attempts(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS mission_rewards(
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 student_id INT UNSIGNED NOT NULL,
 mission_id SMALLINT UNSIGNED NOT NULL,
 attempt_id BIGINT UNSIGNED NOT NULL,
 stars SMALLINT UNSIGNED NOT NULL DEFAULT 25,
 awarded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY one_mission_reward_per_student (student_id, mission_id),
 UNIQUE KEY one_mission_reward_per_attempt (attempt_id),
 FOREIGN KEY(student_id) REFERENCES users(id) ON DELETE CASCADE,
 FOREIGN KEY(mission_id) REFERENCES missions(id) ON DELETE CASCADE,
 FOREIGN KEY(attempt_id) REFERENCES mission_attempts(id) ON DELETE CASCADE
);

INSERT INTO missions(slug, title, destination, destination_icon, story, topic, difficulty, question_count, reward_stars, display_order)
VALUES
 ('despertar-da-lua', 'O Despertar da Lua', 'Lua', '🌙', 'A base lunar perdeu parte de sua energia. Resolva os cálculos para reativar os sistemas da estação.', 'Adição e subtração', 'Fácil', 5, 25, 1),
 ('enigma-de-marte', 'O Enigma de Marte', 'Marte', '🔴', 'Uma equipe encontrou um antigo mapa marciano. Resolva os cálculos para localizar a base científica.', 'Multiplicação e divisão', 'Fácil', 5, 25, 2),
 ('fortaleza-de-jupiter', 'A Fortaleza de Júpiter', 'Júpiter', '🟡', 'Códigos matemáticos protegem a fortaleza orbital. Resolva as expressões e abra os portões.', 'Expressões numéricas e ordem das operações', 'Média', 6, 25, 3),
 ('portal-de-saturno', 'O Portal de Saturno', 'Saturno', '🪐', 'Encontre as proporções corretas para estabilizar os anéis e ativar o portal.', 'Frações e números decimais', 'Média', 6, 25, 4),
 ('nebulosa-dos-enigmas', 'A Nebulosa dos Enigmas', 'Nebulosa cósmica', '🌌', 'Uma nebulosa esconde a rota para uma nova galáxia. Resolva equações e enigmas para encontrá-la.', 'Equações e raciocínio lógico', 'Difícil', 7, 25, 5),
 ('ultima-fronteira', 'A Última Fronteira', 'Centro da galáxia', '✨', 'O núcleo galáctico está perdendo energia. Combine tudo o que aprendeu para restaurar o equilíbrio do universo.', 'Problemas matemáticos variados', 'Desafiadora', 10, 25, 6)
ON DUPLICATE KEY UPDATE
 title = VALUES(title),
 destination = VALUES(destination),
 destination_icon = VALUES(destination_icon),
 story = VALUES(story),
 topic = VALUES(topic),
 difficulty = VALUES(difficulty),
 question_count = VALUES(question_count),
 reward_stars = 25,
 display_order = VALUES(display_order);

SET @profile_photo_exists = (
 SELECT COUNT(*) FROM information_schema.columns
 WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'profile_photo'
);
SET @profile_photo_ddl = IF(
 @profile_photo_exists = 0,
 'ALTER TABLE users ADD COLUMN profile_photo VARCHAR(255) NULL AFTER password_hash',
 'SET @profile_photo_migration_noop = 1'
);
PREPARE profile_photo_stmt FROM @profile_photo_ddl;
EXECUTE profile_photo_stmt;
DEALLOCATE PREPARE profile_photo_stmt;

CREATE TABLE IF NOT EXISTS planets(
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(80) NOT NULL,
 slug VARCHAR(80) NOT NULL UNIQUE,
 description TEXT NOT NULL,
 icon VARCHAR(20) NOT NULL,
 price INT NOT NULL DEFAULT 0,
 display_order INT NOT NULL DEFAULT 0,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS student_planets(
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 student_id INT UNSIGNED NOT NULL,
 planet_id INT UNSIGNED NOT NULL,
 purchased_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY unique_student_planet(student_id, planet_id),
 FOREIGN KEY(student_id) REFERENCES users(id) ON DELETE CASCADE,
 FOREIGN KEY(planet_id) REFERENCES planets(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS avatars(
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 slug VARCHAR(80) NOT NULL UNIQUE,
 name VARCHAR(100) NOT NULL,
 description VARCHAR(255) NOT NULL,
 icon VARCHAR(20) NOT NULL,
 image_path VARCHAR(255) NOT NULL,
 price INT UNSIGNED NOT NULL DEFAULT 0,
 rarity VARCHAR(20) NOT NULL DEFAULT 'comum',
 display_order INT UNSIGNED NOT NULL DEFAULT 0,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

SET @avatar_image_path_exists = (
 SELECT COUNT(*) FROM information_schema.columns
 WHERE table_schema = DATABASE() AND table_name = 'avatars' AND column_name = 'image_path'
);
SET @avatar_image_path_ddl = IF(
 @avatar_image_path_exists = 0,
 'ALTER TABLE avatars ADD COLUMN image_path VARCHAR(255) NULL AFTER icon',
 'SET @avatar_image_path_migration_noop = 1'
);
PREPARE avatar_image_path_stmt FROM @avatar_image_path_ddl;
EXECUTE avatar_image_path_stmt;
DEALLOCATE PREPARE avatar_image_path_stmt;

SET @avatar_rarity_exists = (
 SELECT COUNT(*) FROM information_schema.columns
 WHERE table_schema = DATABASE() AND table_name = 'avatars' AND column_name = 'rarity'
);
SET @avatar_rarity_ddl = IF(
 @avatar_rarity_exists = 0,
 'ALTER TABLE avatars ADD COLUMN rarity VARCHAR(20) NOT NULL DEFAULT ''comum'' AFTER price',
 'SET @avatar_migration_rarity_noop = 1'
);
PREPARE avatar_rarity_stmt FROM @avatar_rarity_ddl;
EXECUTE avatar_rarity_stmt;
DEALLOCATE PREPARE avatar_rarity_stmt;

CREATE TABLE IF NOT EXISTS student_avatars(
 student_id INT UNSIGNED NOT NULL,
 avatar_id INT UNSIGNED NOT NULL,
 acquired_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY(student_id, avatar_id),
 FOREIGN KEY(student_id) REFERENCES users(id) ON DELETE CASCADE,
 FOREIGN KEY(avatar_id) REFERENCES avatars(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS student_avatar_selection(
 student_id INT UNSIGNED NOT NULL PRIMARY KEY,
 avatar_id INT UNSIGNED NOT NULL,
 selected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 FOREIGN KEY(student_id, avatar_id) REFERENCES student_avatars(student_id, avatar_id) ON DELETE CASCADE
);

INSERT INTO avatars(slug, name, description, icon, image_path, price, rarity, display_order)
VALUES
 ('guardiao-lunar', 'Luna — Guardiã Lunar', 'Acolhedora e misteriosa, Luna guia os primeiros passos da jornada matemática.', '🌙', 'assets/avatars/luna.svg', 0, 'comum', 1),
 ('explorador-espacial', 'Astrion — Explorador Cósmico', 'Curioso e destemido, Astrion transforma cada desafio em uma nova descoberta.', '🚀', 'assets/avatars/astrion.svg', 150, 'comum', 2),
 ('guardiao-das-estrelas', 'Aurea — Maga Dourada', 'Sábia e luminosa, Aurea revela o conhecimento escondido nas estrelas.', '✨', 'assets/avatars/aurea.svg', 300, 'raro', 3),
 ('viajante-dos-planetas', 'Nébula — Guardiã das Estrelas', 'Criativa e imaginativa, Nébula protege os sonhos que iluminam a galáxia.', '💜', 'assets/avatars/nebula.svg', 450, 'epico', 4),
 ('astronauta-matematico', 'Orion — Mestre das Constelações', 'Estrategista experiente, Orion encontra padrões e soluções no céu noturno.', '🌌', 'assets/avatars/orion.svg', 650, 'epico', 5),
 ('mago-cosmico', 'Solarius — Guardião Solar', 'Dedicado e radiante, Solarius representa a força de quem nunca para de evoluir.', '☀️', 'assets/avatars/solarius.svg', 900, 'epico', 6),
 ('mestre-lunaris', 'Galaxia — Lenda Lunaris', 'Uma lenda que une luz, coragem e sabedoria para alcançar grandes objetivos.', '👑', 'assets/avatars/galaxia.svg', 1500, 'lendario', 7),
 ('cometa', 'Cometa — Mensageiro Estelar', 'Veloz e otimista, Cometa cruza a galáxia deixando trilhas de luz azul.', '☄️', 'assets/avatars/cometa.svg', 200, 'comum', 8),
 ('cosmo', 'Cosmo — Inventor Galáctico', 'Inventivo e curioso, Cosmo transforma sucata espacial em engenhocas brilhantes.', '⚙️', 'assets/avatars/cosmo.svg', 250, 'comum', 9),
 ('selene', 'Selene — Princesa do Eclipse', 'Elegante e serena, Selene equilibra luz e sombra em cada descoberta.', '🌘', 'assets/avatars/selene.svg', 500, 'raro', 10),
 ('estella', 'Estella — Arqueira Celeste', 'Precisa e corajosa, Estella mira alto guiada pelas estrelas.', '🏹', 'assets/avatars/estella.svg', 550, 'raro', 11),
 ('atlas', 'Atlas — Viajante dos Planetas', 'Generoso e aventureiro, Atlas carrega mapas de mundos por explorar.', '🧭', 'assets/avatars/atlas.svg', 600, 'raro', 12),
 ('draco', 'Draco — Cavaleiro Nebular', 'Leal e destemido, Draco protege a galáxia com sua armadura estelar.', '🐉', 'assets/avatars/draco.svg', 700, 'epico', 13),
 ('vega', 'Vega — Dançarina das Estrelas', 'Expressiva e graciosa, Vega dança entre constelações cintilantes.', '💫', 'assets/avatars/vega.svg', 750, 'epico', 14),
 ('iris', 'Íris — Oráculo Cósmico', 'Intuitiva e sábia, Íris lê caminhos possíveis em seus cristais cósmicos.', '🔮', 'assets/avatars/iris.svg', 800, 'epico', 15),
 ('noctis', 'Noctis — Guardião da Noite', 'Tranquilo e protetor, Noctis ilumina os caminhos mais silenciosos.', '🌙', 'assets/avatars/noctis.svg', 850, 'epico', 16),
 ('aurora', 'Aurora — Maga das Nebulosas', 'Criativa e gentil, Aurora tece cores de aurora em feitiços luminosos.', '🌈', 'assets/avatars/aurora.svg', 1000, 'epico', 17),
 ('meteor', 'Meteor — Piloto Estelar', 'Audacioso e habilidoso, Meteor pilota sua nave por rotas desconhecidas.', '🛸', 'assets/avatars/meteor.svg', 1100, 'epico', 18),
 ('lyra', 'Lyra — Cantora da Galáxia', 'Alegre e inspiradora, Lyra transforma melodias em poeira de estrelas.', '🎼', 'assets/avatars/lyra.svg', 1250, 'epico', 19),
 ('sirius', 'Sirius', 'Personagem da coleção Lunaris.', '✨', 'assets/avatars/orion.svg', 1300, 'epico', 20),
 ('zenith', 'Zenith', 'Personagem da coleção Lunaris.', '🌟', 'assets/avatars/galaxia.svg', 1400, 'epico', 21)
ON DUPLICATE KEY UPDATE
 name = VALUES(name),
 description = VALUES(description),
 icon = VALUES(icon),
 image_path = VALUES(image_path),
 price = VALUES(price),
 rarity = VALUES(rarity),
 display_order = VALUES(display_order);

UPDATE avatars SET display_order = 99 WHERE slug = 'explorador-das-galaxias';

INSERT INTO planets(name, slug, description, icon, price, display_order)
VALUES
 ('Terra', 'terra', 'Seu planeta de origem', '🌍', 0, 1),
 ('Marte', 'marte', 'Planeta da coragem e dos desafios', '🔴', 500, 2),
 ('Júpiter', 'jupiter', 'Planeta dos grandes desafios', '🟡', 1000, 3),
 ('Saturno', 'saturno', 'Planeta da persistência', '🪐', 1500, 4),
 ('Netuno', 'netuno', 'Planeta dos grandes exploradores', '🔵', 2000, 5),
 ('Urano', 'urano', 'Planeta dos desafios avançados', '🟣', 2500, 6),
 ('Sol', 'sol', 'Símbolo máximo da jornada', '☀️', 5000, 7)
ON DUPLICATE KEY UPDATE
 name = VALUES(name),
 description = VALUES(description),
 icon = VALUES(icon),
 price = VALUES(price),
 display_order = VALUES(display_order);

INSERT INTO student_planets(student_id, planet_id)
SELECT u.id, p.id
FROM users u
JOIN planets p ON p.slug = 'terra'
WHERE u.role = 'aluno'
AND NOT EXISTS (
    SELECT 1 FROM student_planets sp WHERE sp.student_id = u.id AND sp.planet_id = p.id
);

INSERT IGNORE INTO student_avatars(student_id, avatar_id)
SELECT u.id, a.id
FROM users u
JOIN avatars a ON a.slug = 'guardiao-lunar'
WHERE u.role = 'aluno';

INSERT IGNORE INTO student_avatar_selection(student_id, avatar_id)
SELECT sa.student_id, sa.avatar_id
FROM student_avatars sa
JOIN avatars a ON a.id = sa.avatar_id AND a.slug = 'guardiao-lunar'
JOIN users u ON u.id = sa.student_id AND u.role = 'aluno';
