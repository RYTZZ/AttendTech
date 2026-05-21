CREATE DATABASE IF NOT EXISTS attendance_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE attendance_system;

CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150),
    profile_photo VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username)
) ENGINE=InnoDB;

CREATE TABLE system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE grade_levels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    grade_name VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE sections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    section_name VARCHAR(100) NOT NULL,
    grade_level_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (grade_level_id) REFERENCES grade_levels(id) ON DELETE CASCADE,
    INDEX idx_grade (grade_level_id)
) ENGINE=InnoDB;

CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lrn VARCHAR(12) NOT NULL UNIQUE,
    full_name VARCHAR(200) NOT NULL,
    gender ENUM('Male','Female') NOT NULL,
    grade_level_id INT NOT NULL,
    section_id INT NOT NULL,
    contact_number VARCHAR(20),
    address TEXT,
    parent_name VARCHAR(200),
    parent_contact VARCHAR(20),
    photo VARCHAR(255),
    qr_code VARCHAR(255),
    status ENUM('Active','Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (grade_level_id) REFERENCES grade_levels(id) ON DELETE RESTRICT,
    FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE RESTRICT,
    INDEX idx_lrn (lrn),
    INDEX idx_grade_section (grade_level_id, section_id),
    INDEX idx_status (status)
) ENGINE=InnoDB;

CREATE TABLE attendance_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    log_date DATE NOT NULL,
    time_in TIME,
    time_out TIME,
    status ENUM('Present','Late','Absent') DEFAULT 'Present',
    remarks VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    UNIQUE KEY unique_student_date (student_id, log_date),
    INDEX idx_log_date (log_date),
    INDEX idx_student_date (student_id, log_date)
) ENGINE=InnoDB;

CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    message TEXT NOT NULL,
    type ENUM('time_in','time_out','late','duplicate') NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    INDEX idx_is_read (is_read),
    INDEX idx_created (created_at)
) ENGINE=InnoDB;

INSERT INTO admins (username, password, full_name, email) VALUES
('admin', '$2y$10$VnkBPbA1WJLr574cCnrznO4oJMrcFszcxdLBhDINAX2eGzNDcB0gK', 'System Administrator', 'admin@attendtech.edu.ph');

INSERT INTO system_settings (setting_key, setting_value) VALUES
('school_name', 'Bicol University Gubat Campus'),
('academic_year', '2025-2026'),
('late_time_threshold', '07:30:00'),
('system_logo', ''),
('school_address', 'Gubat, Sorsogon'),
('contact_number', ''),
('timezone', 'Asia/Manila');

INSERT INTO grade_levels (grade_name) VALUES
('Grade 7'),
('Grade 8'),
('Grade 9'),
('Grade 10');

INSERT INTO sections (section_name, grade_level_id) VALUES
('Section A', 1), ('Section B', 1), ('Section C', 1),
('Section A', 2), ('Section B', 2), ('Section C', 2),
('Section A', 3), ('Section B', 3), ('Section C', 3),
('Section A', 4), ('Section B', 4), ('Section C', 4);
