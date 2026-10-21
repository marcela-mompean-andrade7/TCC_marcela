USE mathplay;

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
