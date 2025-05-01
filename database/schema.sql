-- File: database/schema.sql
CREATE DATABASE IF NOT EXISTS grant_genie;
USE grant_genie;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    institution VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Projects table
CREATE TABLE IF NOT EXISTS projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    grant_type VARCHAR(50) NOT NULL,
    duration_years INT NOT NULL DEFAULT 5,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Budget items table (stores generated and edited budget items)
CREATE TABLE IF NOT EXISTS budget_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    category VARCHAR(50) NOT NULL,
    item_name VARCHAR(255) NOT NULL,
    description TEXT,
    year INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    quantity INT DEFAULT 1,
    justification TEXT,
    is_edited BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);

-- Budget rules table (stores NSF/NIH guidelines)
CREATE TABLE IF NOT EXISTS budget_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    grant_type VARCHAR(50) NOT NULL,
    category VARCHAR(50) NOT NULL,
    rule_name VARCHAR(100) NOT NULL,
    rule_description TEXT NOT NULL,
    validation_type ENUM('min', 'max', 'percentage', 'required', 'forbidden') NOT NULL,
    validation_value VARCHAR(50),
    error_message TEXT NOT NULL,
    suggestion TEXT
);

-- Budget templates table (predefined templates for different grant types)
CREATE TABLE IF NOT EXISTS budget_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    grant_type VARCHAR(50) NOT NULL,
    category VARCHAR(50) NOT NULL,
    item_name VARCHAR(255) NOT NULL,
    description TEXT,
    default_amount DECIMAL(10, 2),
    is_required BOOLEAN DEFAULT FALSE
);

-- AI prompts table (stores optimized prompts for Gemini API)
CREATE TABLE IF NOT EXISTS ai_prompts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    prompt_name VARCHAR(100) NOT NULL,
    prompt_text TEXT NOT NULL,
    grant_type VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);