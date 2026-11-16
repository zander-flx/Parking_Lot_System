CREATE DATABASE IF NOT EXISTS parking_lot CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE parking_lot;

CREATE TABLE IF NOT EXISTS space_types (
  type_id INT AUTO_INCREMENT PRIMARY KEY,
  type_name VARCHAR(50) NOT NULL UNIQUE,
  hourly_rate DECIMAL(10,2) NOT NULL
);

CREATE TABLE IF NOT EXISTS spaces (
  space_id INT AUTO_INCREMENT PRIMARY KEY,
  type_id INT NOT NULL,
  space_code VARCHAR(10) NOT NULL UNIQUE,
  FOREIGN KEY (type_id) REFERENCES space_types(type_id)
);

CREATE TABLE IF NOT EXISTS drivers (
  driver_id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(120) NOT NULL,
  plate_number VARCHAR(20) NOT NULL UNIQUE,
  phone VARCHAR(20) NOT NULL DEFAULT ''
);

CREATE TABLE IF NOT EXISTS sessions (
  session_id INT AUTO_INCREMENT PRIMARY KEY,
  space_id INT NOT NULL,
  driver_id INT NOT NULL,
  time_in DATETIME NOT NULL,
  time_out DATETIME NULL,
  rate DECIMAL(10,2) NOT NULL,            -- rate locked in at check-in
  amount_due DECIMAL(10,2) NULL,
  tendered DECIMAL(10,2) NULL,
  amount_paid DECIMAL(10,2) NULL,
  payment_method VARCHAR(20) NULL,
  paid_at DATETIME NULL,
  KEY idx_space_active (space_id, time_out),
  FOREIGN KEY (space_id) REFERENCES spaces(space_id),
  FOREIGN KEY (driver_id) REFERENCES drivers(driver_id)
);

INSERT IGNORE INTO space_types (type_id,type_name,hourly_rate) VALUES
 (1,'Motorcycle',25.00),(2,'Compact',40.00),(3,'Standard',50.00),(4,'SUV / Van',65.00);

INSERT IGNORE INTO spaces (space_id,type_id,space_code) VALUES
 (1,2,'A-01'),(2,2,'A-02'),(3,3,'A-03'),(4,3,'A-04'),
 (5,4,'B-01'),(6,4,'B-02'),(7,1,'M-01'),(8,1,'M-02');

INSERT IGNORE INTO drivers (driver_id,full_name,plate_number,phone) VALUES
 (1,'Juan Dela Cruz','ABC123','0917 123 4567'),
 (2,'Maria Santos','XYZ987','0928 555 0199');

-- Demo: one car parked 2h15m ago, one finished & paid ticket.
INSERT IGNORE INTO sessions
 (session_id,space_id,driver_id,time_in,time_out,rate,amount_due,tendered,amount_paid,payment_method,paid_at) VALUES
 (1,2,1,NOW() - INTERVAL 135 MINUTE,NULL,40.00,NULL,NULL,NULL,NULL,NULL),
 (2,3,2,NOW() - INTERVAL 5 HOUR,NOW() - INTERVAL 3 HOUR,50.00,100.00,200.00,100.00,'Cash',NOW() - INTERVAL 3 HOUR);

CREATE TABLE IF NOT EXISTS users (
  user_id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(30) NOT NULL UNIQUE,
  full_name VARCHAR(120) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','user') NOT NULL DEFAULT 'user',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Demo logins: admin / admin123 and staff / user123 (real bcrypt cost-10 hashes)
INSERT IGNORE INTO users (user_id, username, full_name, password_hash, role) VALUES
 (1,'admin','Lot Administrator','$2y$10$7Yncbp8b70mPWiSUOQf/IOyY32u4zdI9AG5GpoSZL6PElCgHk2daG','admin'),
 (2,'staff','Parking Staff','$2y$10$r4.JPrappuDxJmQfJdj4bOyOdpvlAvaWwBUq/i3HN.sxK6gnZhC9q','user');