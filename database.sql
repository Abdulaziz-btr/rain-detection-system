-- =============================================
-- Rain Detection and Control System Database
-- =============================================

CREATE DATABASE IF NOT EXISTS rain_system;
USE rain_system;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Sensor readings table
CREATE TABLE IF NOT EXISTS sensor_readings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    rain_status BOOLEAN DEFAULT FALSE,
    temperature FLOAT,
    humidity FLOAT,
    reading_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Commands table
CREATE TABLE IF NOT EXISTS commands (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    command_type VARCHAR(50),
    command_status VARCHAR(50),
    command_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- System status table
CREATE TABLE IF NOT EXISTS system_status (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rainline_position VARCHAR(50) DEFAULT 'Outside',
    last_updated DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Insert default system status
INSERT INTO system_status (rainline_position) VALUES ('Outside');

-- Insert default admin user (password: admin123)
INSERT INTO users (name, email, password) VALUES 
('Admin', 'admin@mkuhostel.rw', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Insert sample sensor data
INSERT INTO sensor_readings (user_id, rain_status, temperature, humidity, reading_time) VALUES
(1, 0, 27.5, 61.0, NOW() - INTERVAL 30 MINUTE),
(1, 0, 27.3, 62.0, NOW() - INTERVAL 25 MINUTE),
(1, 0, 27.6, 60.5, NOW() - INTERVAL 20 MINUTE),
(1, 0, 27.4, 61.5, NOW() - INTERVAL 15 MINUTE),
(1, 0, 27.8, 63.0, NOW() - INTERVAL 10 MINUTE),
(1, 0, 27.5, 61.0, NOW() - INTERVAL 5 MINUTE),
(1, 0, 27.5, 61.0, NOW());

-- Insert sample commands
INSERT INTO commands (user_id, command_type, command_status, command_time) VALUES
(1, 'retract', 'sent successfully', NOW() - INTERVAL 2 HOUR),
(1, 'extend', 'sent successfully', NOW() - INTERVAL 1 HOUR),
(1, 'connect', 'connected to monitoring system', NOW() - INTERVAL 30 MINUTE),
(1, 'account', 'Account created', NOW() - INTERVAL 1 DAY);
