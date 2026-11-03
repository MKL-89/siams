-- ============================================================
-- SIAMS - Student Industrial Attachment Management System
-- Database schema (MariaDB 10.11 / MySQL 8), based on the ERD
-- Run once:  mysql -u root < setup.sql
-- ============================================================

DROP DATABASE IF EXISTS siams_db;
CREATE DATABASE siams_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE siams_db;

-- The application connects with its own limited account, not root.
CREATE USER IF NOT EXISTS 'siams_app'@'localhost' IDENTIFIED BY 'Siams#Local2026';
CREATE USER IF NOT EXISTS 'siams_app'@'127.0.0.1' IDENTIFIED BY 'Siams#Local2026';
GRANT SELECT, INSERT, UPDATE, DELETE ON siams_db.* TO 'siams_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON siams_db.* TO 'siams_app'@'127.0.0.1';
FLUSH PRIVILEGES;

-- ------------------------------------------------------------
-- Users and roles
-- ------------------------------------------------------------
CREATE TABLE users (
    user_id        INT AUTO_INCREMENT PRIMARY KEY,
    full_name      VARCHAR(100) NOT NULL,
    email          VARCHAR(120) NOT NULL UNIQUE,
    phone          VARCHAR(20),
    password_hash  VARCHAR(255) NOT NULL,
    role           ENUM('admin','coordinator','lecturer','industrial','student') NOT NULL,
    status         ENUM('active','inactive','pending') NOT NULL DEFAULT 'active',
    last_login     DATETIME NULL,
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE login_attempts (
    attempt_id     INT AUTO_INCREMENT PRIMARY KEY,
    email          VARCHAR(120) NOT NULL,
    ip_address     VARCHAR(45) NOT NULL,
    success        TINYINT(1) NOT NULL DEFAULT 0,
    attempted_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_attempt_email_time (email, attempted_at)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Organizations and user profiles
-- ------------------------------------------------------------
CREATE TABLE organizations (
    org_id         INT AUTO_INCREMENT PRIMARY KEY,
    name           VARCHAR(150) NOT NULL,
    sector         VARCHAR(80),
    county         VARCHAR(60),
    address        VARCHAR(200),
    contact_email  VARCHAR(120),
    contact_phone  VARCHAR(20),
    status         ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    reviewed_by    INT NULL,
    reviewed_at    DATETIME NULL,
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reviewed_by) REFERENCES users(user_id)
) ENGINE=InnoDB;

CREATE TABLE students (
    student_id     INT AUTO_INCREMENT PRIMARY KEY,
    user_id        INT NOT NULL UNIQUE,
    reg_no         VARCHAR(30) NOT NULL UNIQUE,
    programme      VARCHAR(100) NOT NULL,
    year_of_study  TINYINT,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE coordinators (
    coordinator_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id        INT NOT NULL UNIQUE,
    staff_no       VARCHAR(30) NOT NULL UNIQUE,
    school         VARCHAR(120),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE lecturers (
    lecturer_id    INT AUTO_INCREMENT PRIMARY KEY,
    user_id        INT NOT NULL UNIQUE,
    staff_no       VARCHAR(30) NOT NULL UNIQUE,
    department     VARCHAR(120),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE ind_supervisors (
    ind_sup_id     INT AUTO_INCREMENT PRIMARY KEY,
    user_id        INT NOT NULL UNIQUE,
    org_id         INT NOT NULL,
    position       VARCHAR(80),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (org_id)  REFERENCES organizations(org_id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Opportunities, applications and placements
-- ------------------------------------------------------------
-- An attachment period (e.g. January to April). Only one is active at a time (FR-20).
CREATE TABLE attachment_periods (
    period_id      INT AUTO_INCREMENT PRIMARY KEY,
    name           VARCHAR(80) NOT NULL,
    start_date     DATE NOT NULL,
    end_date       DATE NOT NULL,
    is_active      TINYINT(1) NOT NULL DEFAULT 0,
    created_by     INT NULL,
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CHECK (end_date > start_date),
    FOREIGN KEY (created_by) REFERENCES users(user_id)
) ENGINE=InnoDB;

CREATE TABLE opportunities (
    opportunity_id INT AUTO_INCREMENT PRIMARY KEY,
    org_id         INT NOT NULL,
    title          VARCHAR(150) NOT NULL,
    description    TEXT,
    slots          SMALLINT NOT NULL DEFAULT 1,
    deadline       DATE,
    status         ENUM('open','closed') NOT NULL DEFAULT 'open',
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (org_id) REFERENCES organizations(org_id)
) ENGINE=InnoDB;

CREATE TABLE applications (
    application_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id     INT NOT NULL,
    opportunity_id INT NOT NULL,
    -- pending -> accepted (by host) -> approved (by coordinator); or rejected / withdrawn
    status         ENUM('pending','accepted','approved','rejected','withdrawn') NOT NULL DEFAULT 'pending',
    applied_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    decided_at     DATETIME NULL,
    UNIQUE KEY uq_student_opportunity (student_id, opportunity_id),
    FOREIGN KEY (student_id)     REFERENCES students(student_id),
    FOREIGN KEY (opportunity_id) REFERENCES opportunities(opportunity_id)
) ENGINE=InnoDB;

CREATE TABLE placements (
    placement_id   INT AUTO_INCREMENT PRIMARY KEY,
    student_id     INT NOT NULL,
    org_id         INT NOT NULL,
    period_id      INT NULL,
    application_id INT NULL,
    lecturer_id    INT NULL,
    assigned_at    DATETIME NULL,
    ind_sup_id     INT NULL,
    start_date     DATE,
    end_date       DATE,
    status         ENUM('pending','approved','rejected','ongoing','completed') NOT NULL DEFAULT 'pending',
    approved_by    INT NULL,
    decided_at     DATETIME NULL,
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (period_id)      REFERENCES attachment_periods(period_id),
    FOREIGN KEY (student_id)     REFERENCES students(student_id),
    FOREIGN KEY (org_id)         REFERENCES organizations(org_id),
    FOREIGN KEY (application_id) REFERENCES applications(application_id),
    FOREIGN KEY (lecturer_id)    REFERENCES lecturers(lecturer_id),
    FOREIGN KEY (ind_sup_id)     REFERENCES ind_supervisors(ind_sup_id),
    FOREIGN KEY (approved_by)    REFERENCES users(user_id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Monitoring and assessment
-- ------------------------------------------------------------
CREATE TABLE logbook_entries (
    entry_id       INT AUTO_INCREMENT PRIMARY KEY,
    placement_id   INT NOT NULL,
    week_no        TINYINT NOT NULL,
    week_start     DATE,
    activities     TEXT NOT NULL,
    skills_learned TEXT,
    challenges     TEXT,
    submitted_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    confirmed      TINYINT(1) NOT NULL DEFAULT 0,
    confirmed_by   INT NULL,
    confirmed_at   DATETIME NULL,
    supervisor_comment TEXT,
    UNIQUE KEY uq_placement_week (placement_id, week_no),
    FOREIGN KEY (placement_id) REFERENCES placements(placement_id) ON DELETE CASCADE,
    FOREIGN KEY (confirmed_by) REFERENCES users(user_id)
) ENGINE=InnoDB;

CREATE TABLE supervision_visits (
    visit_id       INT AUTO_INCREMENT PRIMARY KEY,
    placement_id   INT NOT NULL,
    lecturer_id    INT NOT NULL,
    visit_date     DATE NOT NULL,
    visit_type     ENUM('physical','phone','virtual') NOT NULL DEFAULT 'physical',
    notes          TEXT,
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (placement_id) REFERENCES placements(placement_id) ON DELETE CASCADE,
    FOREIGN KEY (lecturer_id)  REFERENCES lecturers(lecturer_id)
) ENGINE=InnoDB;

-- One score (0-100) per component; the final mark is weighted using the settings table.
CREATE TABLE evaluations (
    evaluation_id  INT AUTO_INCREMENT PRIMARY KEY,
    placement_id   INT NOT NULL,
    component      ENUM('industrial','university','logbook') NOT NULL,
    evaluator_id   INT NOT NULL,
    score          DECIMAL(5,2) NOT NULL,
    comments       TEXT,
    submitted_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_placement_component (placement_id, component),
    CHECK (score BETWEEN 0 AND 100),
    FOREIGN KEY (placement_id) REFERENCES placements(placement_id) ON DELETE CASCADE,
    FOREIGN KEY (evaluator_id) REFERENCES users(user_id)
) ENGINE=InnoDB;

CREATE TABLE documents (
    document_id    INT AUTO_INCREMENT PRIMARY KEY,
    student_id     INT NOT NULL,
    placement_id   INT NULL,
    doc_type       ENUM('introduction_letter','acceptance_letter','insurance','report','other') NOT NULL,
    original_name  VARCHAR(200) NOT NULL,
    file_path      VARCHAR(255) NOT NULL,
    uploaded_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id)   REFERENCES students(student_id),
    FOREIGN KEY (placement_id) REFERENCES placements(placement_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Activity log of important actions (FR-27)
-- ------------------------------------------------------------
CREATE TABLE audit_log (
    log_id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id        INT NULL,
    action         VARCHAR(60) NOT NULL,
    details        VARCHAR(255),
    ip_address     VARCHAR(45),
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit_time (created_at),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- System settings (university policy values)
-- ------------------------------------------------------------
CREATE TABLE settings (
    setting_key    VARCHAR(60) PRIMARY KEY,
    setting_value  VARCHAR(255) NOT NULL,
    description    VARCHAR(200)
) ENGINE=InnoDB;

INSERT INTO settings (setting_key, setting_value, description) VALUES
('weight_industrial', '40', 'Industrial supervisor assessment (% of final mark)'),
('weight_university', '40', 'University supervisor assessment (% of final mark)'),
('weight_logbook',    '20', 'Logbook assessment (% of final mark)'),
('records_retention_years', '5', 'How long attachment records are kept');
