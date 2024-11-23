-- Drop databases if they exist
DROP DATABASE IF EXISTS login_db;

-- Drop tables if they exist
DROP TABLE IF EXISTS password_reset_tokens;
DROP TABLE IF EXISTS login_attempts;
DROP TABLE IF EXISTS users;


--Create Database

CREATE DATABASE login_db;

USE login_db;

-- Table for storing user information
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    username VARCHAR(255),
    url_address VARCHAR(255),
    verify_status ENUM('0', '1') DEFAULT '0',
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table for storing login attempts
CREATE TABLE login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    attempt_count INT DEFAULT 0,
    last_attempt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    lock_time TIMESTAMP NULL,
    UNIQUE (ip_address)
);



CREATE TABLE password_reset_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE users
ADD COLUMN verify_token VARCHAR(255) DEFAULT NULL,
ADD COLUMN date Date NOT NULL;
