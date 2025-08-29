-- MySQL Database Setup for Dashboard Market
-- Execute these commands in phpMyAdmin or MySQL command line

-- 1. Create Database (if not exists)
CREATE DATABASE IF NOT EXISTS `realtime_marketplace` 
DEFAULT CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

-- 2. Use the database
USE `realtime_marketplace`;

-- 3. Create dm_settings table
CREATE TABLE IF NOT EXISTS `dm_settings` (
    `scope` VARCHAR(50) NOT NULL COMMENT 'Platform name (shopee, lazada, tiktok)',
    `name` VARCHAR(100) NOT NULL COMMENT 'Setting key name',
    `value` TEXT NULL COMMENT 'Setting value',
    `updated_at` BIGINT NULL COMMENT 'Unix timestamp of last update',
    PRIMARY KEY (`scope`, `name`)
) ENGINE=InnoDB 
DEFAULT CHARSET=utf8mb4 
COLLATE=utf8mb4_unicode_ci
COMMENT='Settings storage for different platforms';

-- 4. Create index for better performance
CREATE INDEX `idx_scope` ON `dm_settings` (`scope`);
CREATE INDEX `idx_updated_at` ON `dm_settings` (`updated_at`);

-- 5. Insert sample data (optional)
INSERT INTO `dm_settings` (`scope`, `name`, `value`, `updated_at`) VALUES
('shopee', 'enabled', 'false', UNIX_TIMESTAMP()),
('lazada', 'enabled', 'false', UNIX_TIMESTAMP()),
('tiktok', 'enabled', 'false', UNIX_TIMESTAMP())
ON DUPLICATE KEY UPDATE 
    `value` = VALUES(`value`),
    `updated_at` = VALUES(`updated_at`);

-- 6. Create orders table (สำหรับเก็บข้อมูล orders ที่ดึงจาก API)
CREATE TABLE IF NOT EXISTS `orders` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY COMMENT 'Auto increment primary key',
    `platform` VARCHAR(20) NOT NULL COMMENT 'Platform name (shopee, lazada, tiktok)',
    `order_id` VARCHAR(100) NOT NULL COMMENT 'Platform order ID',
    `amount` DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Order total amount',
    `status` VARCHAR(50) NULL COMMENT 'Order status',
    `created_at` DATETIME NULL COMMENT 'Order creation timestamp',
    `items` JSON NULL COMMENT 'Order items data in JSON format',
    `raw_data` JSON NULL COMMENT 'Full raw order data from API',
    `fetched_at` BIGINT NULL COMMENT 'Unix timestamp when order was fetched',
    UNIQUE KEY `uk_platform_order_id` (`platform`, `order_id`)
) ENGINE=InnoDB 
DEFAULT CHARSET=utf8mb4 
COLLATE=utf8mb4_unicode_ci
COMMENT='Orders data from marketplace platforms';

-- 7. Create indexes for orders table
CREATE INDEX `idx_orders_platform_created_at` ON `orders` (`platform`, `created_at` DESC);
CREATE INDEX `idx_orders_fetched_at` ON `orders` (`fetched_at` DESC);
CREATE INDEX `idx_orders_status` ON `orders` (`status`);

-- 8. Create user for the application (optional, for security)
-- Replace 'your_password' with a strong password
-- CREATE USER 'dashboard_user'@'localhost' IDENTIFIED BY 'your_password';
-- GRANT SELECT, INSERT, UPDATE, DELETE ON `realtime_marketplace`.* TO 'dashboard_user'@'localhost';
-- FLUSH PRIVILEGES;

-- Check if everything is created correctly
SHOW TABLES;
DESCRIBE `dm_settings`;
SELECT * FROM `dm_settings` LIMIT 10;
