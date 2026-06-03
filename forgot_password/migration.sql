-- ============================================================
-- Forgot Password Migration
-- Run this once in phpMyAdmin or via MySQL CLI:
--   SOURCE /path/to/forgot_password/migration.sql;
-- ============================================================

-- 1. Add email column to teachers_staff (students already have it)
ALTER TABLE `teachers_staff`
  ADD COLUMN IF NOT EXISTS `email` varchar(100) DEFAULT NULL AFTER `employee_id`;

ALTER TABLE `teachers_staff`
  ADD UNIQUE KEY IF NOT EXISTS `email` (`email`);

-- 2. Create password_resets table
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id`         int(11)      NOT NULL AUTO_INCREMENT,
  `user_type`  enum('student','teacher') NOT NULL,
  `user_id`    int(11)      NOT NULL,
  `token`      varchar(64)  NOT NULL,
  `expires_at` datetime     NOT NULL,
  `used`       tinyint(1)   NOT NULL DEFAULT 0,
  `created_at` datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `idx_user` (`user_type`, `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
