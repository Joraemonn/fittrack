USE fittrack;

CREATE TABLE IF NOT EXISTS custom_exercises (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    exercise_name VARCHAR(120) NOT NULL,
    muscle_group VARCHAR(80) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_custom_exercises_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_custom_exercise_per_user (user_id, exercise_name),
    INDEX idx_custom_exercises_user (user_id),
    INDEX idx_custom_exercises_muscle_group (muscle_group)
);
