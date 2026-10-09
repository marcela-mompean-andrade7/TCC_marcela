USE mathplay;

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

SET @avatar_image_path_exists = (
 SELECT COUNT(*) FROM information_schema.columns
 WHERE table_schema = DATABASE() AND table_name = 'avatars' AND column_name = 'image_path'
);
SET @avatar_image_path_ddl = IF(
 @avatar_image_path_exists = 0,
 'ALTER TABLE avatars ADD COLUMN image_path VARCHAR(255) NULL AFTER icon',
 'SET @avatar_migration_noop = 1'
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

INSERT INTO avatars(slug, name, description, icon, image_path, price, rarity, display_order) VALUES
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
 ('lyra', 'Lyra — Cantora da Galáxia', 'Alegre e inspiradora, Lyra transforma melodias em poeira de estrelas.', '🎼', 'assets/avatars/lyra.svg', 1250, 'epico', 19)
ON DUPLICATE KEY UPDATE
 name = VALUES(name),
 description = VALUES(description),
 icon = VALUES(icon),
 image_path = VALUES(image_path),
 price = VALUES(price),
 rarity = VALUES(rarity),
 display_order = VALUES(display_order);

UPDATE avatars SET display_order = 99 WHERE slug = 'explorador-das-galaxias';

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
