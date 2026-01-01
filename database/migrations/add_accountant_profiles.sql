-- Create accountant_profiles table for accountant users

CREATE TABLE IF NOT EXISTS accountant_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    accountant_id VARCHAR(20) NOT NULL UNIQUE,
    qualification VARCHAR(255) NULL,
    certification VARCHAR(255) NULL COMMENT 'Professional certifications like ICAN, ACCA, etc.',
    department VARCHAR(100) NULL,
    employment_type ENUM('Full-time', 'Part-time', 'Contract') DEFAULT 'Full-time',
    employment_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_accountant_id (accountant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
