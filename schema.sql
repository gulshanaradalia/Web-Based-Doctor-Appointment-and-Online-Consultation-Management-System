CREATE DATABASE IF NOT EXISTS doctor_appointment;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(120) NOT NULL UNIQUE,
  phone VARCHAR(20) NOT NULL UNIQUE,
  role ENUM('patient','doctor','admin') NOT NULL DEFAULT 'patient',
  location VARCHAR(100) NULL,
  specialty VARCHAR(100) NULL,
  password_hash VARCHAR(255) NOT NULL,
  status ENUM('active','blocked') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);