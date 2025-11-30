CREATE DATABASE IF NOT EXISTS aviator_game;
USE aviator_game;

CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    balance DECIMAL(10,2) DEFAULT 0.00,
    is_admin TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    type ENUM('deposit', 'withdrawal', 'bet_win', 'bet_loss') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE bets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    bet_amount DECIMAL(10,2) NOT NULL,
    cashout_multiplier DECIMAL(10,2) DEFAULT NULL,
    win_amount DECIMAL(10,2) DEFAULT 0.00,
    game_crash_point DECIMAL(10,2) NOT NULL,
    status ENUM('active', 'won', 'lost') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE game_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    min_bet DECIMAL(10,2) DEFAULT 1.00,
    max_bet DECIMAL(10,2) DEFAULT 1000.00,
    max_multiplier DECIMAL(10,2) DEFAULT 100.00,
    game_duration INT DEFAULT 10
);

INSERT INTO game_settings (min_bet, max_bet, max_multiplier, game_duration) 
VALUES (1.00, 1000.00, 100.00, 10);

-- Default admin user (password: admin123)
INSERT INTO users (username, email, password, balance, is_admin) 
VALUES ('admin', 'admin@aviator.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 10000.00, 1);