CREATE DATABASE IF NOT EXISTS mathup CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE mathup;

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
 points INT NOT NULL DEFAULT 0,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS activities(
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 professor_id INT UNSIGNED NOT NULL,
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

ALTER TABLE users ADD COLUMN IF NOT EXISTS profile_photo VARCHAR(255) NULL AFTER password_hash;

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

