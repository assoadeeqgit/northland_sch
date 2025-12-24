-- School Financial Management System Schema
-- Module F: Database Schema (Phase F1)

SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------
-- Users & Roles
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `roles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL UNIQUE,
  `description` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `email` VARCHAR(100) UNIQUE,
  `role_id` INT,
  `full_name` VARCHAR(100),
  `status` ENUM('active', 'inactive') DEFAULT 'active',
  `last_login` DATETIME,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`)
);

-- -----------------------------------------------------
-- Academic Structure
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `academic_years` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(20) NOT NULL, -- e.g., 2024-2025
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `is_active` BOOLEAN DEFAULT FALSE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS `classes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL, -- e.g., Grade 1, SS 1
  `level` INT, -- Ordering
  `description` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS `sections` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `class_id` INT NOT NULL,
  `name` VARCHAR(20) NOT NULL, -- e.g., A, B, Gold
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`class_id`) REFERENCES `classes`(`id`) ON DELETE CASCADE
);

-- -----------------------------------------------------
-- Students
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `students` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `admission_number` VARCHAR(20) NOT NULL UNIQUE,
  `first_name` VARCHAR(50) NOT NULL,
  `last_name` VARCHAR(50) NOT NULL,
  `date_of_birth` DATE,
  `gender` ENUM('Male', 'Female', 'Other'),
  `current_class_id` INT,
  `current_section_id` INT,
  `parent_name` VARCHAR(100),
  `parent_phone` VARCHAR(20),
  `parent_email` VARCHAR(100),
  `address` TEXT,
  `status` ENUM('active', 'graduated', 'transferred', 'suspended') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`current_class_id`) REFERENCES `classes`(`id`),
  FOREIGN KEY (`current_section_id`) REFERENCES `sections`(`id`)
);

-- -----------------------------------------------------
-- Fee Management
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `fee_types` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL UNIQUE, -- e.g., Tuition, Bus, Uniform
  `description` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS `fee_structures` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `academic_year_id` INT NOT NULL,
  `class_id` INT NOT NULL,
  `fee_type_id` INT NOT NULL,
  `amount` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  `due_date` DATE,
  `frequency` ENUM('one-time', 'monthly', 'termly', 'yearly') DEFAULT 'termly',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years`(`id`),
  FOREIGN KEY (`class_id`) REFERENCES `classes`(`id`),
  FOREIGN KEY (`fee_type_id`) REFERENCES `fee_types`(`id`)
);

-- Assign fees to specific students (if not covered by general class structure or for tracking balance)
CREATE TABLE IF NOT EXISTS `student_fees` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT NOT NULL,
  `fee_structure_id` INT NOT NULL,
  `amount` DECIMAL(10, 2) NOT NULL, -- Original amount
  `discount_amount` DECIMAL(10, 2) DEFAULT 0.00,
  `paid_amount` DECIMAL(10, 2) DEFAULT 0.00,
  `status` ENUM('unpaid', 'partial', 'paid', 'overdue') DEFAULT 'unpaid',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`),
  FOREIGN KEY (`fee_structure_id`) REFERENCES `fee_structures`(`id`)
);

-- -----------------------------------------------------
-- Payments
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `payments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `receipt_number` VARCHAR(20) NOT NULL UNIQUE,
  `student_id` INT NOT NULL,
  `academic_year_id` INT,
  `amount_paid` DECIMAL(10, 2) NOT NULL,
  `payment_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `payment_method` ENUM('cash', 'bank_transfer', 'card', 'cheque', 'online') NOT NULL,
  `reference_number` VARCHAR(50), -- Check number, transaction ID
  `collected_by` INT, -- User ID
  `status` ENUM('completed', 'pending', 'failed', 'reversed') DEFAULT 'completed',
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`),
  FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years`(`id`),
  FOREIGN KEY (`collected_by`) REFERENCES `users`(`id`)
);

-- Link payments to specific fee items
CREATE TABLE IF NOT EXISTS `payment_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `payment_id` INT NOT NULL,
  `student_fee_id` INT NOT NULL,
  `amount` DECIMAL(10, 2) NOT NULL,
  FOREIGN KEY (`payment_id`) REFERENCES `payments`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`student_fee_id`) REFERENCES `student_fees`(`id`)
);

SET FOREIGN_KEY_CHECKS = 1;

-- Seed Initial Data
INSERT INTO `roles` (`name`, `description`) VALUES ('admin', 'System Administrator'), ('accountant', 'Accountant/Cashier');
-- Default admin user (password: admin123 - in production use proper hashing)
INSERT INTO `users` (`username`, `password_hash`, `role_id`, `full_name`) VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 'System Admin');

INSERT INTO `fee_types` (`name`) VALUES ('Tuition Fee'), ('exam Fee'), ('Transport Fee'), ('Uniform'), ('Books');
