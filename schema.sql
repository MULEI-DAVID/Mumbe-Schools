CREATE DATABASE IF NOT EXISTS `mumbe_schools` 
DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `mumbe_schools`;

-- School Settings Table
CREATE TABLE `school_settings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `school_name` VARCHAR(255) NOT NULL DEFAULT 'Mumbe Group of Schools',
  `school_email` VARCHAR(255) NOT NULL DEFAULT 'info@mumbeschools.com',
  `academic_year` VARCHAR(9) NOT NULL DEFAULT '2024/2025',
  `current_term` ENUM('Term 1', 'Term 2', 'Term 3') NOT NULL DEFAULT 'Term 1',
  `timezone` VARCHAR(50) NOT NULL DEFAULT 'Africa/Nairobi',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Departments Table
CREATE TABLE `departments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL UNIQUE,
  `description` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Admin Users Table
CREATE TABLE `admins` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('superadmin', 'registrar', 'accountant', 'support') NOT NULL DEFAULT 'support',
  `last_login` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Authentication Codes for Faculty Registration
CREATE TABLE `faculty_registration_codes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `code` CHAR(12) NOT NULL UNIQUE COMMENT 'Unique registration code',
  `created_by` INT UNSIGNED NOT NULL COMMENT 'Admin ID who generated',
  `used_by` INT UNSIGNED DEFAULT NULL COMMENT 'Faculty ID who used',
  `expires_at` DATETIME NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `used_at` DATETIME DEFAULT NULL,
  KEY `expiration_idx` (`expires_at`),
  CONSTRAINT `fk_code_creator` FOREIGN KEY (`created_by`) REFERENCES `admins`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Faculty/Teachers Table
CREATE TABLE `faculty` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `phone` VARCHAR(20) NOT NULL,
  `password_hash` VARCHAR(255) DEFAULT NULL COMMENT 'Null before registration',
  `department_id` INT UNSIGNED DEFAULT NULL,
  `qualifications` TEXT,
  `photo` VARCHAR(255) DEFAULT NULL COMMENT 'Profile photo path',
  `bio` TEXT COMMENT 'Faculty bio',
  `registration_code` CHAR(12) DEFAULT NULL COMMENT 'Initial auth code',
  `status` ENUM('pending', 'active', 'suspended', 'left') NOT NULL DEFAULT 'pending',
  `last_login` DATETIME DEFAULT NULL,
  `join_date` DATE NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_faculty_department` FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Parents Table
CREATE TABLE `parents` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `phone` VARCHAR(20) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `primary_student_id` INT UNSIGNED DEFAULT NULL,
  `last_login` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Students Table
CREATE TABLE `students` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `admission_number` VARCHAR(20) NOT NULL UNIQUE COMMENT 'Auth for student/parent',
  `parent_id` INT UNSIGNED DEFAULT NULL,
  `grade_level` VARCHAR(10) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `status` ENUM('active', 'graduated', 'transferred', 'inactive') NOT NULL DEFAULT 'active',
  `last_login` DATETIME DEFAULT NULL,
  `join_date` DATE NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_student_parent` FOREIGN KEY (`parent_id`) REFERENCES `parents`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Courses Table
CREATE TABLE `courses` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(100) NOT NULL,
  `description` TEXT,
  `teacher_id` INT UNSIGNED NOT NULL,
  `grade_level` VARCHAR(10) NOT NULL,
  `status` ENUM('active', 'archived') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_course_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `faculty`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Classes Table (Teaching sessions/groups)
CREATE TABLE `classes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `course_id` INT UNSIGNED NOT NULL,
  `teacher_id` INT UNSIGNED NOT NULL,
  `academic_year` VARCHAR(9) NOT NULL,
  `term` ENUM('Term 1', 'Term 2', 'Term 3') NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_class_course` FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_class_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `faculty`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Class Students (Enrollment)
CREATE TABLE `class_students` (
  `class_id` INT UNSIGNED NOT NULL,
  `student_id` INT UNSIGNED NOT NULL,
  `enrolled_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`class_id`, `student_id`),
  CONSTRAINT `fk_class_enrollment` FOREIGN KEY (`class_id`) REFERENCES `classes`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_student_enrollment` FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Assignments Table
CREATE TABLE `assignments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `class_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `due_date` DATETIME NOT NULL,
  `max_points` DECIMAL(5,2) NOT NULL,
  `status` ENUM('draft', 'published', 'graded') NOT NULL DEFAULT 'draft',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_assignment_class` FOREIGN KEY (`class_id`) REFERENCES `classes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Student Assignments (Submissions)
CREATE TABLE `student_assignments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `assignment_id` INT UNSIGNED NOT NULL,
  `student_id` INT UNSIGNED NOT NULL,
  `submitted_at` DATETIME DEFAULT NULL,
  `file_path` VARCHAR(255) DEFAULT NULL,
  `points_earned` DECIMAL(5,2) DEFAULT NULL,
  `comments` TEXT,
  `status` ENUM('not_submitted', 'submitted', 'late', 'graded') NOT NULL DEFAULT 'not_submitted',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_submission_assignment` FOREIGN KEY (`assignment_id`) REFERENCES `assignments`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_submission_student` FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Attendance Table
CREATE TABLE `attendance` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `class_id` INT UNSIGNED NOT NULL,
  `student_id` INT UNSIGNED NOT NULL,
  `date` DATE NOT NULL,
  `status` ENUM('present', 'absent', 'late', 'excused') NOT NULL DEFAULT 'absent',
  `remarks` VARCHAR(255) DEFAULT NULL,
  `recorded_by` INT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `attendance_record` (`class_id`, `student_id`, `date`),
  CONSTRAINT `fk_attendance_class` FOREIGN KEY (`class_id`) REFERENCES `classes`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_attendance_student` FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_attendance_recorder` FOREIGN KEY (`recorded_by`) REFERENCES `faculty`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Class Schedule (Timetable)
CREATE TABLE `class_schedule` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `class_id` INT UNSIGNED NOT NULL,
  `day_of_week` ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday') NOT NULL,
  `start_time` TIME NOT NULL,
  `end_time` TIME NOT NULL,
  `location` VARCHAR(100) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_schedule_class` FOREIGN KEY (`class_id`) REFERENCES `classes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Events Table
CREATE TABLE `events` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `event_date` DATETIME NOT NULL,
  `location` VARCHAR(255) NOT NULL,
  `audience` ENUM('all', 'students', 'faculty', 'parents') NOT NULL DEFAULT 'all',
  `created_by` INT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_event_creator` FOREIGN KEY (`created_by`) REFERENCES `admins`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Announcements Table
CREATE TABLE `announcements` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `content` TEXT NOT NULL,
  `audience` ENUM('all', 'students', 'faculty', 'parents') NOT NULL DEFAULT 'all',
  `created_by` INT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_announcement_creator` FOREIGN KEY (`created_by`) REFERENCES `admins`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Messages Table
CREATE TABLE `messages` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `sender_id` INT UNSIGNED NOT NULL,
  `sender_type` ENUM('admin', 'faculty', 'parent', 'student') NOT NULL,
  `recipient_id` INT UNSIGNED NOT NULL,
  `recipient_type` ENUM('admin', 'faculty', 'parent', 'student') NOT NULL,
  `subject` VARCHAR(255) NOT NULL,
  `content` TEXT NOT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `sent_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `sender_idx` (`sender_id`, `sender_type`),
  KEY `recipient_idx` (`recipient_id`, `recipient_type`)
) ENGINE=InnoDB;

-- Resources Table
CREATE TABLE `resources` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `file_path` VARCHAR(255) NOT NULL,
  `class_id` INT UNSIGNED DEFAULT NULL,
  `uploaded_by` INT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_resource_class` FOREIGN KEY (`class_id`) REFERENCES `classes`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_resource_uploader` FOREIGN KEY (`uploaded_by`) REFERENCES `faculty`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Parent-Student Relationship Table (Many-to-Many)
CREATE TABLE `parent_students` (
  `parent_id` INT UNSIGNED NOT NULL,
  `student_id` INT UNSIGNED NOT NULL,
  `relationship` ENUM('mother', 'father', 'guardian') NOT NULL,
  `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`parent_id`, `student_id`),
  CONSTRAINT `fk_parent_child` FOREIGN KEY (`parent_id`) REFERENCES `parents`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_student_parent` FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Login Audit Table
CREATE TABLE `login_attempts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `identifier` VARCHAR(255) NOT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `success` TINYINT(1) NOT NULL DEFAULT 0,
  `portal_type` ENUM('admin', 'faculty', 'parent', 'student') NOT NULL,
  `attempted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `identifier_idx` (`identifier`)
) ENGINE=InnoDB;

-- Initial Data
INSERT INTO `school_settings` 
  (`school_name`, `school_email`, `academic_year`, `current_term`)
VALUES
  ('Mumbe Group of Schools', 'info@mumbeschools.com', '2024/2025', 'Term 1');

INSERT INTO `departments` (`name`, `description`)
VALUES
  ('Mathematics', 'Number theory, algebra, geometry and calculus'),
  ('Sciences', 'Biology, chemistry, physics and environmental science'),
  ('Languages', 'English, Swahili and foreign languages'),
  ('Humanities', 'History, geography and social studies'),
  ('Technology', 'Computer science and ICT');

-- Create initial admin user (password: Admin123!)
INSERT INTO `admins` 
  (`name`, `email`, `password_hash`, `role`)
VALUES
  ('System Admin', 'admin@mumbeschools.com', 
   '$2y$10$4Vg9u7dE3vO1Jk5s6z8r0eYbWcXfRtS0gT1uHv2iL3nM4pQ5w6y7a', 
   'superadmin');

-- Create sample faculty registration code
INSERT INTO `faculty_registration_codes`
  (`code`, `created_by`, `expires_at`)
VALUES
  ('FAC-REG-2023', 1, DATE_ADD(NOW(), INTERVAL 30 DAY));