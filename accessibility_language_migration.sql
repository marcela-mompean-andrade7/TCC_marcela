USE mathplay;

SET @preferred_language_exists = (
 SELECT COUNT(*) FROM information_schema.columns
 WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'preferred_language'
);
SET @preferred_language_ddl = IF(
 @preferred_language_exists = 0,
 'ALTER TABLE users ADD COLUMN preferred_language VARCHAR(5) NOT NULL DEFAULT ''pt-BR'' AFTER escola',
 'SET @preferred_language_migration_noop = 1'
);
PREPARE preferred_language_stmt FROM @preferred_language_ddl;
EXECUTE preferred_language_stmt;
DEALLOCATE PREPARE preferred_language_stmt;

SET @disability_status_exists = (
 SELECT COUNT(*) FROM information_schema.columns
 WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'disability_status'
);
SET @disability_status_ddl = IF(
 @disability_status_exists = 0,
 'ALTER TABLE users ADD COLUMN disability_status VARCHAR(32) NOT NULL DEFAULT ''prefer_not'' AFTER preferred_language',
 'SET @disability_status_migration_noop = 1'
);
PREPARE disability_status_stmt FROM @disability_status_ddl;
EXECUTE disability_status_stmt;
DEALLOCATE PREPARE disability_status_stmt;

SET @disability_other_exists = (
 SELECT COUNT(*) FROM information_schema.columns
 WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'disability_other'
);
SET @disability_other_ddl = IF(
 @disability_other_exists = 0,
 'ALTER TABLE users ADD COLUMN disability_other VARCHAR(255) NULL AFTER disability_status',
 'SET @disability_other_migration_noop = 1'
);
PREPARE disability_other_stmt FROM @disability_other_ddl;
EXECUTE disability_other_stmt;
DEALLOCATE PREPARE disability_other_stmt;

SET @accessibility_needs_exists = (
 SELECT COUNT(*) FROM information_schema.columns
 WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'accessibility_needs'
);
SET @accessibility_needs_ddl = IF(
 @accessibility_needs_exists = 0,
 'ALTER TABLE users ADD COLUMN accessibility_needs TEXT NULL AFTER disability_other',
 'SET @accessibility_needs_migration_noop = 1'
);
PREPARE accessibility_needs_stmt FROM @accessibility_needs_ddl;
EXECUTE accessibility_needs_stmt;
DEALLOCATE PREPARE accessibility_needs_stmt;

SET @accessibility_other_exists = (
 SELECT COUNT(*) FROM information_schema.columns
 WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'accessibility_other'
);
SET @accessibility_other_ddl = IF(
 @accessibility_other_exists = 0,
 'ALTER TABLE users ADD COLUMN accessibility_other VARCHAR(255) NULL AFTER accessibility_needs',
 'SET @accessibility_other_migration_noop = 1'
);
PREPARE accessibility_other_stmt FROM @accessibility_other_ddl;
EXECUTE accessibility_other_stmt;
DEALLOCATE PREPARE accessibility_other_stmt;

SET @color_vision_status_exists = (
 SELECT COUNT(*) FROM information_schema.columns
 WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'color_vision_status'
);
SET @color_vision_status_ddl = IF(
 @color_vision_status_exists = 0,
 'ALTER TABLE users ADD COLUMN color_vision_status VARCHAR(32) NOT NULL DEFAULT ''prefer_not'' AFTER disability_other',
 'SET @color_vision_status_migration_noop = 1'
);
PREPARE color_vision_status_stmt FROM @color_vision_status_ddl;
EXECUTE color_vision_status_stmt;
DEALLOCATE PREPARE color_vision_status_stmt;
