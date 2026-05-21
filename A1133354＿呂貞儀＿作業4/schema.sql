-- 大量郵件/電子報寄送系統（僅限已同意收件人）
-- DB: MySQL / MariaDB

CREATE DATABASE IF NOT EXISTS `mailtest` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `mailtest`;

CREATE TABLE IF NOT EXISTS `recipients` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(254) NOT NULL,
  `is_opt_in` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

