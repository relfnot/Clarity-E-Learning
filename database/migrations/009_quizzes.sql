CREATE TABLE IF NOT EXISTS quizzes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id BIGINT UNSIGNED NOT NULL,
    meeting_id BIGINT UNSIGNED NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NULL,
    open_at DATETIME NOT NULL,
    close_at DATETIME NOT NULL,
    duration_min INT UNSIGNED NOT NULL DEFAULT 30,
    max_attempts INT UNSIGNED NOT NULL DEFAULT 1,
    shuffle TINYINT(1) NOT NULL DEFAULT 0,
    show_answers TINYINT(1) NOT NULL DEFAULT 0,
    weight DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_quizzes_course_window (course_id, open_at, close_at),
    CONSTRAINT fk_quizzes_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    CONSTRAINT fk_quizzes_meeting FOREIGN KEY (meeting_id) REFERENCES meetings(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS questions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    quiz_id BIGINT UNSIGNED NOT NULL,
    type ENUM('pg', 'esai') NOT NULL DEFAULT 'pg',
    text TEXT NOT NULL,
    points DECIMAL(6,2) NOT NULL DEFAULT 10.00,
    position INT UNSIGNED NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_questions_quiz_position (quiz_id, position),
    CONSTRAINT fk_questions_quiz FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS options (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    question_id BIGINT UNSIGNED NOT NULL,
    label VARCHAR(10) NULL,
    text TEXT NOT NULL,
    is_correct TINYINT(1) NOT NULL DEFAULT 0,
    position INT UNSIGNED NOT NULL DEFAULT 1,
    INDEX idx_options_question_position (question_id, position),
    CONSTRAINT fk_options_question FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS quiz_attempts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    quiz_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    attempt_number INT UNSIGNED NOT NULL DEFAULT 1,
    question_order JSON NULL,
    option_orders JSON NULL,
    started_at DATETIME NOT NULL,
    expires_at DATETIME NOT NULL,
    submitted_at DATETIME NULL,
    score DECIMAL(5,2) NULL,
    status ENUM('in_progress', 'submitted', 'menunggu_penilaian', 'graded') NOT NULL DEFAULT 'in_progress',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_quiz_attempts_user_num (quiz_id, user_id, attempt_number),
    INDEX idx_quiz_attempts_quiz_user (quiz_id, user_id),
    CONSTRAINT fk_quiz_attempts_quiz FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE,
    CONSTRAINT fk_quiz_attempts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS attempt_answers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    attempt_id BIGINT UNSIGNED NOT NULL,
    question_id BIGINT UNSIGNED NOT NULL,
    option_id BIGINT UNSIGNED NULL,
    answer_text TEXT NULL,
    score DECIMAL(6,2) NULL,
    graded_by BIGINT UNSIGNED NULL,
    graded_at DATETIME NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_attempt_answers_attempt_question (attempt_id, question_id),
    INDEX idx_attempt_answers_question (question_id),
    CONSTRAINT fk_attempt_answers_attempt FOREIGN KEY (attempt_id) REFERENCES quiz_attempts(id) ON DELETE CASCADE,
    CONSTRAINT fk_attempt_answers_question FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
    CONSTRAINT fk_attempt_answers_option FOREIGN KEY (option_id) REFERENCES options(id) ON DELETE SET NULL,
    CONSTRAINT fk_attempt_answers_grader FOREIGN KEY (graded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
