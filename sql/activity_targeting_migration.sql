ALTER TABLE activities
    ADD COLUMN target_school VARCHAR(180) NULL AFTER professor_id,
    ADD COLUMN target_serie VARCHAR(40) NULL AFTER target_school;

ALTER TABLE questions
    ADD COLUMN first_number DECIMAL(16,6) NULL,
    ADD COLUMN operation VARCHAR(16) NULL,
    ADD COLUMN second_number DECIMAL(16,6) NULL;

CREATE TABLE IF NOT EXISTS submission_answers(
    submission_id INT UNSIGNED NOT NULL,
    question_id INT UNSIGNED NOT NULL,
    answer VARCHAR(120) NOT NULL,
    is_correct TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY(submission_id, question_id),
    FOREIGN KEY(submission_id) REFERENCES submissions(id) ON DELETE CASCADE,
    FOREIGN KEY(question_id) REFERENCES questions(id) ON DELETE CASCADE
);
