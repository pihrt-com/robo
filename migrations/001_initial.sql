CREATE TABLE IF NOT EXISTS app_settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value TEXT NOT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS competitions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    competition_year SMALLINT UNSIGNED NOT NULL,
    competition_date DATE NOT NULL,
    locale CHAR(2) NOT NULL DEFAULT 'cs',
    team_count SMALLINT UNSIGNED NOT NULL,
    title_cs VARCHAR(160) NOT NULL DEFAULT 'ROBO',
    title_en VARCHAR(160) NOT NULL DEFAULT 'ROBO',
    is_active TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY competitions_year_unique (competition_year),
    KEY competitions_active_idx (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS roles (
    code VARCHAR(40) PRIMARY KEY,
    label_cs VARCHAR(100) NOT NULL,
    label_en VARCHAR(100) NOT NULL,
    permissions_json JSON NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(80) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role_code VARCHAR(40) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_login_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY users_username_unique (username),
    CONSTRAINT users_role_fk FOREIGN KEY (role_code) REFERENCES roles (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS teams (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    competition_id INT UNSIGNED NOT NULL,
    team_number SMALLINT UNSIGNED NOT NULL,
    school_name VARCHAR(255) NOT NULL DEFAULT '',
    contestant_names VARCHAR(500) NOT NULL DEFAULT '',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY teams_competition_number_unique (competition_id, team_number),
    CONSTRAINT teams_competition_fk FOREIGN KEY (competition_id) REFERENCES competitions (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS page_texts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    competition_id INT UNSIGNED NOT NULL,
    page_key VARCHAR(80) NOT NULL,
    text_key VARCHAR(80) NOT NULL,
    locale CHAR(2) NOT NULL,
    text_value VARCHAR(255) NOT NULL,
    UNIQUE KEY page_texts_unique (competition_id, page_key, text_key, locale),
    CONSTRAINT page_texts_competition_fk FOREIGN KEY (competition_id) REFERENCES competitions (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS penalties (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    competition_id INT UNSIGNED NOT NULL,
    code VARCHAR(40) NOT NULL,
    label_cs VARCHAR(100) NOT NULL,
    label_en VARCHAR(100) NOT NULL,
    seconds_value SMALLINT UNSIGNED NOT NULL,
    UNIQUE KEY penalties_unique (competition_id, code),
    CONSTRAINT penalties_competition_fk FOREIGN KEY (competition_id) REFERENCES competitions (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS technical_inspections (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    competition_id INT UNSIGNED NOT NULL,
    team_id INT UNSIGNED NOT NULL,
    criterion_1 ENUM('unknown', 'passed', 'failed') NOT NULL DEFAULT 'unknown',
    criterion_2 ENUM('unknown', 'passed', 'failed') NOT NULL DEFAULT 'unknown',
    criterion_3 ENUM('unknown', 'passed', 'failed') NOT NULL DEFAULT 'unknown',
    note TEXT NOT NULL,
    penalty_seconds SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    is_passed TINYINT(1) NOT NULL DEFAULT 0,
    completed_at DATETIME NULL,
    updated_by INT UNSIGNED NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY inspections_unique (competition_id, team_id),
    CONSTRAINT inspections_competition_fk FOREIGN KEY (competition_id) REFERENCES competitions (id) ON DELETE CASCADE,
    CONSTRAINT inspections_team_fk FOREIGN KEY (team_id) REFERENCES teams (id) ON DELETE CASCADE,
    CONSTRAINT inspections_user_fk FOREIGN KEY (updated_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rides (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    competition_id INT UNSIGNED NOT NULL,
    team_id INT UNSIGNED NOT NULL,
    track_number TINYINT UNSIGNED NOT NULL,
    checkpoint_1 SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    checkpoint_2 SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    checkpoint_3 SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    checkpoint_4 SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    checkpoint_5 SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    checkpoint_6 SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    finish_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    lap_1_ms INT UNSIGNED NULL,
    lap_2_ms INT UNSIGNED NULL,
    lap_3_ms INT UNSIGNED NULL,
    lap_4_ms INT UNSIGNED NULL,
    lap_5_ms INT UNSIGNED NULL,
    best_time_ms INT UNSIGNED NULL,
    did_not_stop TINYINT(1) NOT NULL DEFAULT 0,
    stop_penalty_seconds SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    is_confirmed TINYINT(1) NOT NULL DEFAULT 0,
    confirmed_at DATETIME NULL,
    updated_by INT UNSIGNED NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY rides_unique (competition_id, team_id),
    KEY rides_track_idx (competition_id, track_number),
    CONSTRAINT rides_competition_fk FOREIGN KEY (competition_id) REFERENCES competitions (id) ON DELETE CASCADE,
    CONSTRAINT rides_team_fk FOREIGN KEY (team_id) REFERENCES teams (id) ON DELETE CASCADE,
    CONSTRAINT rides_user_fk FOREIGN KEY (updated_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS judging_categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    competition_id INT UNSIGNED NOT NULL,
    code VARCHAR(40) NOT NULL,
    label_cs VARCHAR(100) NOT NULL,
    label_en VARCHAR(100) NOT NULL,
    is_enabled TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY judging_categories_unique (competition_id, code),
    CONSTRAINT judging_categories_competition_fk FOREIGN KEY (competition_id) REFERENCES competitions (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS judging_criteria (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED NOT NULL,
    criterion_order TINYINT UNSIGNED NOT NULL,
    label_cs VARCHAR(255) NOT NULL,
    label_en VARCHAR(255) NOT NULL,
    maximum_points TINYINT UNSIGNED NOT NULL,
    UNIQUE KEY judging_criteria_unique (category_id, criterion_order),
    CONSTRAINT judging_criteria_category_fk FOREIGN KEY (category_id) REFERENCES judging_categories (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS judging_scores (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    criterion_id INT UNSIGNED NOT NULL,
    team_id INT UNSIGNED NOT NULL,
    judge_user_id INT UNSIGNED NOT NULL,
    points TINYINT UNSIGNED NOT NULL,
    note VARCHAR(500) NOT NULL DEFAULT '',
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY judging_scores_unique (criterion_id, team_id, judge_user_id),
    CONSTRAINT judging_scores_criterion_fk FOREIGN KEY (criterion_id) REFERENCES judging_criteria (id) ON DELETE CASCADE,
    CONSTRAINT judging_scores_team_fk FOREIGN KEY (team_id) REFERENCES teams (id) ON DELETE CASCADE,
    CONSTRAINT judging_scores_user_fk FOREIGN KEY (judge_user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS playoff_matches (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    competition_id INT UNSIGNED NOT NULL,
    round_code VARCHAR(30) NOT NULL,
    match_order TINYINT UNSIGNED NOT NULL,
    home_team_id INT UNSIGNED NULL,
    away_team_id INT UNSIGNED NULL,
    winner_team_id INT UNSIGNED NULL,
    decided_at DATETIME NULL,
    decided_by INT UNSIGNED NULL,
    UNIQUE KEY playoff_matches_unique (competition_id, round_code, match_order),
    CONSTRAINT playoff_matches_competition_fk FOREIGN KEY (competition_id) REFERENCES competitions (id) ON DELETE CASCADE,
    CONSTRAINT playoff_matches_home_fk FOREIGN KEY (home_team_id) REFERENCES teams (id) ON DELETE SET NULL,
    CONSTRAINT playoff_matches_away_fk FOREIGN KEY (away_team_id) REFERENCES teams (id) ON DELETE SET NULL,
    CONSTRAINT playoff_matches_winner_fk FOREIGN KEY (winner_team_id) REFERENCES teams (id) ON DELETE SET NULL,
    CONSTRAINT playoff_matches_user_fk FOREIGN KEY (decided_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_rate_limits (
    ip_address VARCHAR(45) PRIMARY KEY,
    failure_count SMALLINT UNSIGNED NOT NULL,
    window_started_at DATETIME NOT NULL,
    locked_until DATETIME NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    competition_id INT UNSIGNED NULL,
    event_type VARCHAR(100) NOT NULL,
    message VARCHAR(500) NOT NULL,
    context_json JSON NOT NULL,
    ip_address VARCHAR(45) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY audit_log_created_idx (created_at),
    KEY audit_log_competition_idx (competition_id),
    CONSTRAINT audit_log_user_fk FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT audit_log_competition_fk FOREIGN KEY (competition_id) REFERENCES competitions (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO roles (code, label_cs, label_en, permissions_json) VALUES
('administrator', 'Správce', 'Administrator', JSON_ARRAY('*')),
('technical_inspection', 'Technická přejímka', 'Technical inspection', JSON_ARRAY('technical.view', 'technical.edit')),
('track_1', 'Dráha 1', 'Track 1', JSON_ARRAY('track_1.view', 'track_1.edit')),
('track_2', 'Dráha 2', 'Track 2', JSON_ARRAY('track_2.view', 'track_2.edit')),
('viewer', 'Divák', 'Viewer', JSON_ARRAY('results.view')),
('judge', 'Porotce', 'Judge', JSON_ARRAY('technical.view', 'technical.edit', 'track_1.view', 'track_1.edit', 'track_2.view', 'track_2.edit', 'results.view')),
('guest', 'Host', 'Guest', JSON_ARRAY('results.view'))
ON DUPLICATE KEY UPDATE label_cs = VALUES(label_cs), label_en = VALUES(label_en), permissions_json = VALUES(permissions_json);
