-- =====================================================
-- TCM Trace SaaS Platform Database Schema
-- MySQL 5.7+ Compatible
-- =====================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 创建数据库
CREATE DATABASE IF NOT EXISTS `tcm_trace` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `tcm_trace`;

-- =====================================================
-- 表1: 租户表 (tenants)
-- =====================================================
DROP TABLE IF EXISTS `tenants`;
CREATE TABLE `tenants` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(50) NOT NULL,
  `license_no` VARCHAR(50) DEFAULT NULL,
  `contact_name` VARCHAR(50) DEFAULT NULL,
  `contact_phone` VARCHAR(20) DEFAULT NULL,
  `address` TEXT,
  `logo` VARCHAR(500) DEFAULT NULL,
  `plan` ENUM('free', 'basic', 'pro', 'enterprise') NOT NULL DEFAULT 'free',
  `status` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '0-禁用 1-正常 2-过期',
  `max_products` INT(11) NOT NULL DEFAULT 100,
  `max_qrcodes` INT(11) NOT NULL DEFAULT 1000,
  `max_users` INT(11) NOT NULL DEFAULT 10,
  `expires_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 表2: 用户表 (users)
-- =====================================================
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id` INT UNSIGNED NOT NULL,
  `username` VARCHAR(50) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `real_name` VARCHAR(50) DEFAULT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `role` ENUM('admin', 'staff', 'viewer') NOT NULL DEFAULT 'staff',
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  `last_login_at` DATETIME DEFAULT NULL,
  `last_login_ip` VARCHAR(45) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tenant_id` (`tenant_id`),
  UNIQUE KEY `uk_tenant_username` (`tenant_id`, `username`),
  CONSTRAINT `fk_users_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 表3: 产品表 (products)
-- =====================================================
DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `alias` VARCHAR(100) DEFAULT NULL,
  `origin` VARCHAR(100) DEFAULT NULL,
  `category` VARCHAR(50) DEFAULT NULL,
  `specification` VARCHAR(200) DEFAULT NULL,
  `quality_grade` VARCHAR(20) DEFAULT NULL,
  `image_url` VARCHAR(500) DEFAULT NULL,
  `description` TEXT,
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tenant_created` (`tenant_id`, `created_at`),
  CONSTRAINT `fk_products_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 表4: 批次表 (batches)
-- =====================================================
DROP TABLE IF EXISTS `batches`;
CREATE TABLE `batches` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `batch_no` VARCHAR(50) NOT NULL,
  `farm_name` VARCHAR(100) DEFAULT NULL,
  `farm_address` TEXT,
  `farm_area` DECIMAL(10,2) DEFAULT NULL,
  `plant_date` DATE DEFAULT NULL,
  `harvest_date` DATE DEFAULT NULL,
  `harvest_qty` DECIMAL(12,2) DEFAULT NULL,
  `grow_years` INT(11) DEFAULT NULL,
  `process_method` VARCHAR(100) DEFAULT NULL,
  `process_date` DATE DEFAULT NULL,
  `process_unit` VARCHAR(100) DEFAULT NULL,
  `inspect_date` DATE DEFAULT NULL,
  `inspect_result` VARCHAR(20) DEFAULT NULL,
  `inspect_report` VARCHAR(500) DEFAULT NULL,
  `inspect_unit` VARCHAR(100) DEFAULT NULL,
  `package_spec` VARCHAR(100) DEFAULT NULL,
  `package_qty` INT(11) DEFAULT NULL,
  `warehouse_info` TEXT,
  `status` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '0-草稿 1-在库 2-已售出',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tenant_product` (`tenant_id`, `product_id`),
  UNIQUE KEY `uk_tenant_batch` (`tenant_id`, `batch_no`),
  CONSTRAINT `fk_batches_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_batches_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 表5: 溯源记录表 (trace_records)
-- =====================================================
DROP TABLE IF EXISTS `trace_records`;
CREATE TABLE `trace_records` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id` INT UNSIGNED NOT NULL,
  `batch_id` INT UNSIGNED NOT NULL,
  `stage` ENUM('plant', 'harvest', 'process', 'inspect', 'store', 'transport', 'sale') NOT NULL,
  `operator_id` INT UNSIGNED DEFAULT NULL,
  `operator_name` VARCHAR(50) DEFAULT NULL,
  `operate_time` DATETIME NOT NULL,
  `detail` TEXT DEFAULT NULL,
  `attachments` TEXT DEFAULT NULL,
  `remark` TEXT,
  `latitude` DECIMAL(10,7) DEFAULT NULL,
  `longitude` DECIMAL(10,7) DEFAULT NULL,
  `location_desc` VARCHAR(200) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tenant_batch` (`tenant_id`, `batch_id`),
  KEY `idx_batch_stage` (`batch_id`, `stage`),
  CONSTRAINT `fk_trace_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_trace_batch` FOREIGN KEY (`batch_id`) REFERENCES `batches` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 表6: 二维码表 (qrcodes)
-- =====================================================
DROP TABLE IF EXISTS `qrcodes`;
CREATE TABLE `qrcodes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id` INT UNSIGNED NOT NULL,
  `batch_id` INT UNSIGNED NOT NULL,
  `qr_serial` VARCHAR(32) NOT NULL,
  `qr_url` VARCHAR(500) NOT NULL,
  `qr_image_url` VARCHAR(500) DEFAULT NULL,
  `scan_count` INT(11) NOT NULL DEFAULT 0,
  `first_scan_at` DATETIME DEFAULT NULL,
  `first_scan_ip` VARCHAR(45) DEFAULT NULL,
  `last_scan_at` DATETIME DEFAULT NULL,
  `is_disabled` TINYINT(1) NOT NULL DEFAULT 0,
  `status` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '0-已生成 1-已打印 2-已激活 3-已失效',
  `print_batch_no` VARCHAR(50) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_qr_serial` (`qr_serial`),
  KEY `idx_tenant_id` (`tenant_id`),
  KEY `idx_batch_id` (`batch_id`),
  CONSTRAINT `fk_qrcodes_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_qrcodes_batch` FOREIGN KEY (`batch_id`) REFERENCES `batches` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 表7: 扫码日志表 (scan_logs)
-- =====================================================
DROP TABLE IF EXISTS `scan_logs`;
CREATE TABLE `scan_logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `qr_id` INT UNSIGNED NOT NULL,
  `tenant_id` INT UNSIGNED NOT NULL,
  `scan_time` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `scan_ip` VARCHAR(45) DEFAULT NULL,
  `scan_location` TEXT DEFAULT NULL,
  `user_agent` TEXT,
  `device_fingerprint` VARCHAR(64) DEFAULT NULL,
  `is_first_scan` TINYINT(1) NOT NULL DEFAULT 0,
  `risk_level` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0-正常 1-可疑 2-高风险',
  `risk_reason` VARCHAR(200) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_qr_id` (`qr_id`),
  KEY `idx_tenant_scan_time` (`tenant_id`, `scan_time`),
  CONSTRAINT `fk_scan_qr` FOREIGN KEY (`qr_id`) REFERENCES `qrcodes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_scan_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 表8: 审计日志表 (audit_logs)
-- =====================================================
DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE `audit_logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id` INT UNSIGNED NOT NULL,
  `actor_type` ENUM('user', 'system', 'api_key') NOT NULL,
  `actor_id` VARCHAR(50) NOT NULL,
  `actor_name` VARCHAR(100) DEFAULT NULL,
  `action` VARCHAR(50) NOT NULL,
  `resource_type` VARCHAR(50) NOT NULL,
  `resource_id` VARCHAR(50) DEFAULT NULL,
  `before_data` TEXT DEFAULT NULL,
  `after_data` TEXT DEFAULT NULL,
  `source_ip` VARCHAR(45) DEFAULT NULL,
  `user_agent` TEXT,
  `geo_location` TEXT DEFAULT NULL,
  `occurred_at` DATETIME NOT NULL,
  `trace_id` VARCHAR(64) DEFAULT NULL,
  `request_id` VARCHAR(64) DEFAULT NULL,
  `result` VARCHAR(20) NOT NULL DEFAULT 'success',
  `error_message` TEXT,
  PRIMARY KEY (`id`),
  KEY `idx_tenant_occurred` (`tenant_id`, `occurred_at`),
  KEY `idx_actor_occurred` (`actor_id`, `occurred_at`),
  KEY `idx_resource` (`resource_type`, `resource_id`),
  CONSTRAINT `fk_audit_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 插入初始管理员账号
-- 密码: admin123 (需在生产环境修改)
-- =====================================================
INSERT INTO `tenants` (`name`, `slug`, `plan`, `status`, `max_products`, `max_qrcodes`, `max_users`) VALUES
('系统管理员', 'admin', 'enterprise', 1, 10000, 100000, 100);

INSERT INTO `users` (`tenant_id`, `username`, `password_hash`, `real_name`, `role`, `status`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '系统管理员', 'admin', 1);

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- 索引优化建议（可选）
-- =====================================================
-- 对于高并发场景，可考虑添加以下索引：
-- ALTER TABLE `scan_logs` ADD INDEX `idx_scan_ip` (`scan_ip`);
-- ALTER TABLE `scan_logs` ADD INDEX `idx_device_fingerprint` (`device_fingerprint`);
