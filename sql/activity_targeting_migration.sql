USE mathplay;

SET @target_school_exists = (
 SELECT COUNT(*) FROM information_schema.columns
 WHERE table_schema = DATABASE() AND table_name = 'activities' AND column_name = 'target_school'
);
SET @target_school_ddl = IF(
 @target_school_exists = 0,
 'ALTER TABLE activities ADD COLUMN target_school VARCHAR(180) NULL AFTER professor_id',
 'SET @target_school_migration_noop = 1'
);
PREPARE target_school_stmt FROM @target_school_ddl;
EXECUTE target_school_stmt;
DEALLOCATE PREPARE target_school_stmt;

SET @target_serie_exists = (
 SELECT COUNT(*) FROM information_schema.columns
 WHERE table_schema = DATABASE() AND table_name = 'activities' AND column_name = 'target_serie'
);
SET @target_serie_ddl = IF(
 @target_serie_exists = 0,
 'ALTER TABLE activities ADD COLUMN target_serie VARCHAR(40) NULL AFTER target_school',
 'SET @target_serie_migration_noop = 1'
);
PREPARE target_serie_stmt FROM @target_serie_ddl;
EXECUTE target_serie_stmt;
DEALLOCATE PREPARE target_serie_stmt;

SET @first_number_exists = (
 SELECT COUNT(*) FROM information_schema.columns
 WHERE table_schema = DATABASE() AND table_name = 'questions' AND column_name = 'first_number'
);
SET @first_number_ddl = IF(
 @first_number_exists = 0,
 'ALTER TABLE questions ADD COLUMN first_number DECIMAL(16,6) NULL',
 'SET @first_number_migration_noop = 1'
);
PREPARE first_number_stmt FROM @first_number_ddl;
EXECUTE first_number_stmt;
DEALLOCATE PREPARE first_number_stmt;

SET @operation_exists = (
 SELECT COUNT(*) FROM information_schema.columns
 WHERE table_schema = DATABASE() AND table_name = 'questions' AND column_name = 'operation'
);
SET @operation_ddl = IF(
 @operation_exists = 0,
 'ALTER TABLE questions ADD COLUMN operation VARCHAR(16) NULL',
 'SET @operation_migration_noop = 1'
);
PREPARE operation_stmt FROM @operation_ddl;
EXECUTE operation_stmt;
DEALLOCATE PREPARE operation_stmt;

SET @second_number_exists = (
 SELECT COUNT(*) FROM information_schema.columns
 WHERE table_schema = DATABASE() AND table_name = 'questions' AND column_name = 'second_number'
);
SET @second_number_ddl = IF(
 @second_number_exists = 0,
 'ALTER TABLE questions ADD COLUMN second_number DECIMAL(16,6) NULL',
 'SET @second_number_migration_noop = 1'
);
PREPARE second_number_stmt FROM @second_number_ddl;
EXECUTE second_number_stmt;
DEALLOCATE PREPARE second_number_stmt;

CREATE TABLE IF NOT EXISTS submission_answers(
    submission_id INT UNSIGNED NOT NULL,
    question_id INT UNSIGNED NOT NULL,
    answer VARCHAR(120) NOT NULL,
    is_correct TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY(submission_id, question_id),
    FOREIGN KEY(submission_id) REFERENCES submissions(id) ON DELETE CASCADE,
    FOREIGN KEY(question_id) REFERENCES questions(id) ON DELETE CASCADE
);
