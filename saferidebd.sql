CREATE DATABASE IF NOT EXISTS saferidebd;
USE saferidebd;

CREATE TABLE IF NOT EXISTS fare_chart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    `from` VARCHAR(100) NOT NULL,
    `to` VARCHAR(100) NOT NULL,
    fare DECIMAL(10, 2) NOT NULL,
    distance_km DECIMAL(5, 2) NOT NULL,
    operating_bus TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_from (`from`),
    INDEX idx_to (`to`),
    INDEX idx_from_to (`from`, `to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_route_search ON fare_chart (`from`, `to`, distance_km);

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(15) UNIQUE,
    bio TEXT,
    address VARCHAR(255),
    gender ENUM('male', 'female', 'other', 'prefer_not_to_say'),
    date_of_birth DATE,
    password VARCHAR(255) NOT NULL,
    google_id VARCHAR(100) UNIQUE,
    email_verified BOOLEAN DEFAULT FALSE,
    phone_verified BOOLEAN DEFAULT FALSE,
    verification_code VARCHAR(10),
    code_expiry DATETIME,
    reset_token VARCHAR(100),
    reset_expiry DATETIME,
    profile_image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100),
    phone VARCHAR(15),
    token VARCHAR(100) NOT NULL,
    expiry DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_token (token),
    INDEX idx_email (email),
    INDEX idx_phone (phone)
);

CREATE TABLE IF NOT EXISTS saved_routes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    from_location VARCHAR(100) NOT NULL,
    to_location VARCHAR(100) NOT NULL,
    distance_km DECIMAL(5, 2) NOT NULL,
    regular_fare DECIMAL(10, 2) NOT NULL,
    student_fare DECIMAL(10, 2) NOT NULL,
    operating_bus TEXT,
    saved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_saved_at (saved_at),
    INDEX idx_user_route (user_id, from_location, to_location)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('super_admin', 'admin') NOT NULL DEFAULT 'admin',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE SET NULL,
    INDEX idx_role (role),
    INDEX idx_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Admin activity log
CREATE TABLE IF NOT EXISTS admin_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE CASCADE,
    INDEX idx_admin_id (admin_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Fare chart update history
CREATE TABLE IF NOT EXISTS fare_update_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    update_type ENUM('single', 'bulk') NOT NULL,
    records_affected INT NOT NULL,
    file_name VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE CASCADE,
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS incident_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    is_anonymous BOOLEAN DEFAULT FALSE,
    full_name VARCHAR(100),
    email VARCHAR(100),
    phone VARCHAR(15),
    address TEXT,
    incident_type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    severity ENUM('low', 'medium', 'high') NOT NULL,
    location VARCHAR(100) NOT NULL,
    specific_location VARCHAR(255),
    incident_date DATE NOT NULL,
    incident_time TIME,
    bus_number VARCHAR(50),
    bus_details TEXT,
    driver_name VARCHAR(100),
    helper_name VARCHAR(100),
    witnesses TEXT,
    evidence_files TEXT,
    additional_info TEXT,
    follow_up VARCHAR(20) DEFAULT 'email',
    status ENUM('pending', 'reviewing', 'published', 'rejected') DEFAULT 'pending',
    admin_notes TEXT,
    reviewed_by INT,
    reviewed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_incident_date (incident_date),
    INDEX idx_location (location)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS published_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    incident_report_id INT,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    cover_image VARCHAR(255),
    author VARCHAR(100),
    category VARCHAR(50),
    source VARCHAR(255),
    publish_date DATE,
    views INT DEFAULT 0,
    status ENUM('published', 'draft') DEFAULT 'published',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    published_by INT,
    FOREIGN KEY (incident_report_id) REFERENCES incident_reports(id) ON DELETE SET NULL,
    FOREIGN KEY (published_by) REFERENCES admin_users(id) ON DELETE SET NULL,
    INDEX idx_category (category),
    INDEX idx_publish_date (publish_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Insert sample data
INSERT INTO fare_chart (`from`, `to`, fare, distance_km) VALUES
('কালশি', 'মিরপুর-১২', 10.00, 2.20);