CREATE DATABASE IF NOT EXISTS parking_lot_system
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE parking_lot_system;


DROP TABLE IF EXISTS assignments;
DROP TABLE IF EXISTS parking_lots;

CREATE TABLE parking_lots (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    location VARCHAR(150) NOT NULL,
    total_spaces INT UNSIGNED NOT NULL DEFAULT 0,
    available_spaces INT UNSIGNED NOT NULL DEFAULT 0,
    price_per_hour DECIMAL(8,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE assignments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parking_lot_id INT UNSIGNED NOT NULL,
    driver_name VARCHAR(100) NOT NULL,
    vehicle_plate VARCHAR(20) NOT NULL,
    entry_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_assignment_lot
        FOREIGN KEY (parking_lot_id)
        REFERENCES parking_lots(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
    user_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(64) NOT NULL,
    role ENUM('admin','user') NOT NULL DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO users (username, password_hash, role) VALUES
('admin', SHA2('admin123', 256), 'admin'),
('user', SHA2('user123', 256), 'user');

INSERT INTO parking_lots (name, location, total_spaces, available_spaces, price_per_hour) VALUES
('Downtown Plaza Garage', '123 Main Street', 250, 42, 3.50),
('Airport Long Stay', '456 Airport Road', 600, 180, 2.25),
('Central Mall Parking', '789 Central Avenue', 320, 0, 1.75);