CREATE DATABASE homebank;
USE homebank;

-- Users table
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email_address VARCHAR(100) UNIQUE NOT NULL,
  username VARCHAR(100) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin','user') DEFAULT 'user',
  status ENUM('pending','approved') DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Accounts table
CREATE TABLE accounts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  account_number VARCHAR(20) UNIQUE NOT NULL,
  balance DECIMAL(10,2) DEFAULT 0.00,
  type ENUM('current','savings') DEFAULT 'current',
  status ENUM('pending','approved') DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Cards table
CREATE TABLE cards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_id INT NOT NULL,
    card_number VARCHAR(16) NOT NULL UNIQUE,
    expiry_month INT NOT NULL,
    expiry_year INT NOT NULL,
    cvc VARCHAR(4) NOT NULL,
    type ENUM('credit', 'debit') NOT NULL DEFAULT 'debit',
    status ENUM('pending', 'approved', 'declined') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (account_id) REFERENCES accounts(id)
);

-- Transactions table
CREATE TABLE transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_id INT NOT NULL,
    type ENUM('credit', 'debit') NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    message VARCHAR(255) DEFAULT NULL,
    status ENUM('pending', 'success', 'failed') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE
);

-- Apps table
CREATE TABLE apps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    secret_key VARCHAR(64) NOT NULL,
    permissions JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE
);

-- Card Tokens table
CREATE TABLE card_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_id INT NOT NULL,
    card_id INT NOT NULL,
    token VARCHAR(100) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (card_id) REFERENCES cards(id)
);

-- Bank Charges table
/*
* Bank Charges table to store various charges applied by the bank
* It's configurable by admin and can be applied to specific accounts or all accounts
*
* id: Primary key
* type: string not enum (e.g., 'card_transaction', 'account_maintenance', 'app_usage', 'card_issuance', 'card_maintenance', 'low_balance')
* percentage_amount: decimal (percentage charge, e.g., 2.5 for 2.5%)
* reason: string (description of the charge)
* account_id: comma separated list of account IDs the charge applies to, or 'all' for all accounts
* created_at: timestamp (when the charge was created)
*/
CREATE TABLE bank_charges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(50) NOT NULL,
    percentage_amount DECIMAL(5,2) NOT NULL,
    reason VARCHAR(255) DEFAULT NULL,
    account_id VARCHAR(255) DEFAULT 'all',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
-- Insert default charges
INSERT INTO bank_charges (type, percentage_amount, reason, account_id) VALUES
('card_transaction', 2.5, 'Charge for card transactions', 'all'),
('account_maintenance', 1.0, 'Monthly account maintenance fee', 'all'),
('app_usage', 1.5, 'Charge for using third-party apps', 'all'),
('card_issuance', 5.0, 'One-time card issuance fee', 'all'),
('card_maintenance', 0.5, 'Monthly card maintenance fee', 'all'),
('low_balance', 3.0, 'Charge for low balance accounts', 'all');

-- Bank Charge Cycle table
-- To track when cron cycle ran last time on all accounts to apply charges from configs (bank_charges table)
-- Fields: id (primary key), total_amount(double), last_run TIMESTAMP
CREATE TABLE bank_charge_cycle (
    id INT AUTO_INCREMENT PRIMARY KEY,
    total_amount DECIMAL(12,2) DEFAULT 0.00,
    last_run TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
