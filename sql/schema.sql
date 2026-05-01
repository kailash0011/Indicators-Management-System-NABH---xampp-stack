-- NABH Indicators Management System - Database Schema
CREATE DATABASE IF NOT EXISTS nabh_indicators CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE nabh_indicators;

CREATE TABLE IF NOT EXISTS departments (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(200) NOT NULL,
  code VARCHAR(20) UNIQUE NOT NULL,
  incharge_name VARCHAR(100),
  contact VARCHAR(50),
  status ENUM('active','inactive') DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS users (
  id INT PRIMARY KEY AUTO_INCREMENT,
  username VARCHAR(50) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(100),
  role ENUM('admin','quality_officer','department_incharge') NOT NULL,
  department_id INT NULL,
  status ENUM('active','inactive') DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS indicators (
  id INT PRIMARY KEY AUTO_INCREMENT,
  indicator_code VARCHAR(20) UNIQUE NOT NULL,
  name VARCHAR(300) NOT NULL,
  description TEXT,
  numerator_description VARCHAR(300),
  denominator_description VARCHAR(300),
  unit ENUM('percentage','per_1000','ratio','minutes','number') NOT NULL DEFAULT 'percentage',
  category ENUM('general','department_specific') NOT NULL DEFAULT 'general',
  benchmark VARCHAR(100),
  status ENUM('active','inactive') DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS department_indicators (
  id INT PRIMARY KEY AUTO_INCREMENT,
  department_id INT NOT NULL,
  indicator_id INT NOT NULL,
  assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  assigned_by INT,
  status ENUM('active','inactive') DEFAULT 'active',
  UNIQUE KEY unique_dept_ind (department_id, indicator_id),
  FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
  FOREIGN KEY (indicator_id) REFERENCES indicators(id) ON DELETE CASCADE,
  FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS monthly_data (
  id INT PRIMARY KEY AUTO_INCREMENT,
  department_id INT NOT NULL,
  indicator_id INT NOT NULL,
  month TINYINT NOT NULL,
  year YEAR NOT NULL,
  numerator DECIMAL(15,4),
  denominator DECIMAL(15,4),
  value DECIMAL(15,4),
  remarks TEXT,
  entered_by INT,
  entered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_entry (department_id, indicator_id, month, year),
  FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
  FOREIGN KEY (indicator_id) REFERENCES indicators(id) ON DELETE CASCADE,
  FOREIGN KEY (entered_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS audit_log (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT,
  username VARCHAR(50),
  action VARCHAR(100) NOT NULL,
  entity_type VARCHAR(50),
  entity_id INT,
  old_value TEXT,
  new_value TEXT,
  ip_address VARCHAR(45),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);
