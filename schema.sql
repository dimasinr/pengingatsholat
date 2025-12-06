-- Run this if you prefer CLI/GUI instead of auto-migrate
CREATE DATABASE IF NOT EXISTS `jadwalsholat` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `jadwalsholat`;
CREATE TABLE IF NOT EXISTS `prayer_timings` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `city` varchar(100) NOT NULL,
  `country` varchar(100) NOT NULL,
  `method` int NOT NULL,
  `date` date NOT NULL,
  `fajr` varchar(10) DEFAULT NULL,
  `sunrise` varchar(10) DEFAULT NULL,
  `dhuhr` varchar(10) DEFAULT NULL,
  `asr` varchar(10) DEFAULT NULL,
  `maghrib` varchar(10) DEFAULT NULL,
  `isha` varchar(10) DEFAULT NULL,
  `imsak` varchar(10) DEFAULT NULL,
  `midnight` varchar(10) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_loc_method_date` (`city`,`country`,`method`,`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Users table for login
CREATE TABLE IF NOT EXISTS `users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL UNIQUE,
  `password_hash` varchar(255) NOT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'user',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Site info table for editable content (admin only)
CREATE TABLE IF NOT EXISTS `site_info` (
  `id` tinyint unsigned NOT NULL,
  `content` text NOT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed default user (admin/admin123). If your client/tool supports SQL variables you can insert directly:
-- INSERT INTO users (username, password_hash) VALUES ('admin', '<hash>');
-- Generate hash in PHP: password_hash('admin123', PASSWORD_DEFAULT)
