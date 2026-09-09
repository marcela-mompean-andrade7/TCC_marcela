CREATE DATABASE IF NOT EXISTS mathplay CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE mathplay;

CREATE TABLE users(
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(120) NOT NULL,
 email VARCHAR(180) NOT NULL UNIQUE,
 password_hash VARCHAR(255) NOT NULL,
 role ENUM('aluno','professor') NOT NULL,
 serie ENUM('6º ano','7º ano','8º ano','9º ano') NULL,
 regiao ENUM('Norte','Nordeste','Centro-Oeste','Sudeste','Sul') NOT NULL,
 escola VARCHAR(180) NOT NULL,
 points INT NOT NULL DEFAULT 0,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE activities(
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 professor_id INT UNSIGNED NOT NULL,
 title VARCHAR(160) NOT NULL,
 description TEXT NOT NULL,
 difficulty ENUM('facil','medio','dificil') NOT NULL DEFAULT 'facil',
 due_date DATE NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(professor_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE questions(
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 activity_id INT UNSIGNED NOT NULL,
 question_text TEXT NOT NULL,
 answer VARCHAR(120) NOT NULL,
 FOREIGN KEY(activity_id) REFERENCES activities(id) ON DELETE CASCADE
);

CREATE TABLE submissions(
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

CREATE TABLE game_results(
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
