-- Smart Budget Database Structure
CREATE DATABASE IF NOT EXISTS smart_budget;
USE smart_budget;

CREATE TABLE `users` (
  `user_id` INT(11) NOT NULL AUTO_INCREMENT,
  `fullname` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `incomes` (
  `income_id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `source` VARCHAR(255) NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `date_added` DATE DEFAULT (CURRENT_DATE),
  PRIMARY KEY (`income_id`),
  KEY `fk_income_user` (`user_id`),
  CONSTRAINT `fk_income_user` FOREIGN KEY (`user_id`) 
    REFERENCES `users`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `expenses` (
  `expense_id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `category` VARCHAR(100) NOT NULL,
  `description` TEXT,
  `amount` DECIMAL(10,2) NOT NULL,
  `date_spent` DATE DEFAULT (CURRENT_DATE),
  PRIMARY KEY (`expense_id`),
  KEY `fk_expense_user` (`user_id`),
  CONSTRAINT `fk_expense_user` FOREIGN KEY (`user_id`) 
    REFERENCES `users`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `goals` (
  `goal_id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `goal_name` VARCHAR(255) NOT NULL,
  `target_amount` DECIMAL(10,2) NOT NULL,
  `saved_amount` DECIMAL(10,2) DEFAULT 0.00,
  `deadline` DATE DEFAULT NULL,
  `status` ENUM('In Progress', 'Completed', 'Failed') DEFAULT 'In Progress',
  PRIMARY KEY (`goal_id`),
  KEY `fk_goal_user` (`user_id`),
  CONSTRAINT `fk_goal_user` FOREIGN KEY (`user_id`) 
    REFERENCES `users`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `reports` (
  `report_id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `month` VARCHAR(20) NOT NULL,
  `total_income` DECIMAL(10,2) DEFAULT 0.00,
  `total_expense` DECIMAL(10,2) DEFAULT 0.00,
  `net_balance` DECIMAL(10,2) GENERATED ALWAYS AS (`total_income` - `total_expense`) STORED,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`report_id`),
  KEY `fk_report_user` (`user_id`),
  CONSTRAINT `fk_report_user` FOREIGN KEY (`user_id`) 
    REFERENCES `users`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
