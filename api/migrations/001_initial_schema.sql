-- الهجرة 001: schema الأساسي للمنصة

CREATE TABLE IF NOT EXISTS questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    grade_key VARCHAR(20) NOT NULL,
    unit_index INT NOT NULL,
    lesson_index INT NOT NULL,
    question_text TEXT NOT NULL,
    question_hash CHAR(64) NOT NULL,
    option_a VARCHAR(500) NOT NULL,
    option_b VARCHAR(500) NOT NULL,
    option_c VARCHAR(500) NOT NULL,
    option_d VARCHAR(500) NOT NULL,
    correct_answer INT NOT NULL,
    explanation TEXT NULL,
    image_url VARCHAR(500) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_q (grade_key, unit_index, lesson_index, question_hash),
    INDEX idx_lookup (grade_key, unit_index, lesson_index)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    grade_level VARCHAR(20) NOT NULL DEFAULT 'first',
    total_points INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS student_scores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    student_name VARCHAR(100) NOT NULL,
    grade_key VARCHAR(20) NOT NULL,
    unit_index INT NOT NULL,
    lesson_index INT NOT NULL,
    score INT NOT NULL,
    total INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_score_user (user_id, grade_key, unit_index, lesson_index),
    INDEX idx_score_user (user_id),
    INDEX idx_grade (grade_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS lesson_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    grade_key VARCHAR(20) NOT NULL,
    unit_index INT NOT NULL,
    lesson_index INT NOT NULL,
    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_progress (user_id, grade_key, unit_index, lesson_index),
    INDEX idx_user (user_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS video_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    grade_key VARCHAR(20) NOT NULL,
    unit_index INT NOT NULL,
    lesson_index INT NOT NULL,
    watched_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_video (user_id, grade_key, unit_index, lesson_index),
    INDEX idx_vuser (user_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS lesson_content (
    id INT AUTO_INCREMENT PRIMARY KEY,
    grade_key VARCHAR(20) NOT NULL,
    unit_index INT NOT NULL,
    lesson_index INT NOT NULL,
    content LONGTEXT NOT NULL,
    video_url VARCHAR(500) NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_lesson (grade_key, unit_index, lesson_index)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS curriculum_units (
    id INT AUTO_INCREMENT PRIMARY KEY,
    grade_key VARCHAR(20) NOT NULL,
    unit_index INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    emoji VARCHAR(10) NOT NULL DEFAULT '📚',
    UNIQUE KEY uniq_cu (grade_key, unit_index)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS curriculum_lessons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    grade_key VARCHAR(20) NOT NULL,
    unit_index INT NOT NULL,
    lesson_index INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    UNIQUE KEY uniq_cl (grade_key, unit_index, lesson_index)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
