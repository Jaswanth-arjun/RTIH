-- =============================================================================
-- TaskFlow — Task Tracker
-- MySQL 8.0+ / MariaDB 10.4+ schema and sample data
--
-- WARNING: importing this file recreates the `users` and `tasks` tables in the
-- `task_tracker` database. Back up any existing data before importing.
--
-- Import:
--   mysql -u root -p --default-character-set=utf8mb4 < database.sql
-- =============================================================================

CREATE DATABASE IF NOT EXISTS `task_tracker`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `task_tracker`;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `tasks`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- -----------------------------------------------------------------------------
-- Users
-- Passwords are stored as bcrypt hashes generated with PHP password_hash().
-- Roles: admin (can manage account access) and user (standard member).
-- Task content is private to its owner for BOTH roles.
-- -----------------------------------------------------------------------------
CREATE TABLE `users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(190) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_email` (`email`),
    KEY `idx_users_role_active` (`role`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Tasks
-- Every task belongs to exactly one user. ON DELETE CASCADE removes a user's
-- tasks if the account row itself is deleted (accounts are normally deactivated,
-- not deleted). Composite indexes support the dashboard filters and sorting.
-- -----------------------------------------------------------------------------
CREATE TABLE `tasks` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(150) NOT NULL,
    `description` TEXT NULL,
    `status` ENUM('pending', 'in_progress', 'completed') NOT NULL DEFAULT 'pending',
    `priority` ENUM('low', 'medium', 'high') NOT NULL DEFAULT 'medium',
    `due_date` DATE NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_tasks_user_status` (`user_id`, `status`),
    KEY `idx_tasks_user_created` (`user_id`, `created_at`),
    KEY `idx_tasks_user_due` (`user_id`, `due_date`),
    KEY `idx_tasks_title` (`title`),
    CONSTRAINT `fk_tasks_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Sample users
-- Member: demo@taskflow.test   / Demo@12345
-- Member: jordan@taskflow.test / Jordan@12345
-- Admin:  admin@taskflow.test  / Admin@12345
-- -----------------------------------------------------------------------------
INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `role`, `is_active`, `created_at`) VALUES
(1, 'Alex Morgan', 'admin@taskflow.test', '$2y$12$woE2EhG2xG9A18t0QDnT/Om/c7uXzBS58Lw9v69QOWDc2OLftiL3S', 'admin', 1, DATE_SUB(NOW(), INTERVAL 40 DAY)),
(2, 'Demo User', 'demo@taskflow.test', '$2y$12$vndZzuqkpGSXhhY6KT02.uir1rOluGfRUyaW/sOY6BJjE39AL1WBq', 'user', 1, DATE_SUB(NOW(), INTERVAL 30 DAY)),
(3, 'Jordan Lee', 'jordan@taskflow.test', '$2y$12$vhI9glSAapc12Z/cS7IQceTU4zbtHUvaab8GyANq8OVaKZimxGnlG', 'user', 1, DATE_SUB(NOW(), INTERVAL 20 DAY));

-- -----------------------------------------------------------------------------
-- Sample tasks (relative due dates keep the demo useful on any import date)
-- -----------------------------------------------------------------------------
INSERT INTO `tasks` (`user_id`, `title`, `description`, `status`, `priority`, `due_date`, `created_at`) VALUES
(2, 'Prepare quarterly report', 'Collect revenue and delivery metrics from each team, then prepare the Q4 summary deck for the leadership review.', 'pending', 'high', DATE_ADD(CURDATE(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY)),
(2, 'Review pull requests', 'Review the open pull requests for the authentication module and leave actionable feedback.', 'in_progress', 'medium', CURDATE(), DATE_SUB(NOW(), INTERVAL 1 DAY)),
(2, 'Update onboarding docs', 'Refresh the developer onboarding guide with the new local setup steps and environment variables.', 'completed', 'low', DATE_SUB(CURDATE(), INTERVAL 4 DAY), DATE_SUB(NOW(), INTERVAL 9 DAY)),
(2, 'Plan sprint retrospective', 'Prepare a short agenda and gather feedback from the team before Friday''s retrospective.', 'pending', 'medium', DATE_ADD(CURDATE(), INTERVAL 6 DAY), DATE_SUB(NOW(), INTERVAL 4 DAY)),
(2, 'Fix login page layout', 'The sign-in form overlaps the footer on small screens. Reproduce, patch the CSS and verify on mobile.', 'in_progress', 'high', DATE_SUB(CURDATE(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 6 DAY)),
(2, 'Write API documentation', 'Document the task endpoints, request parameters and example responses for internal consumers.', 'pending', 'low', NULL, DATE_SUB(NOW(), INTERVAL 8 DAY)),
(2, 'Schedule team sync', 'Book the weekly team sync and share the agenda in the shared calendar.', 'completed', 'medium', DATE_ADD(CURDATE(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY)),
(2, 'Refactor auth module', 'Split the authentication helpers into smaller services and add integration coverage for login throttling.', 'in_progress', 'high', NULL, DATE_SUB(NOW(), INTERVAL 5 DAY)),
(2, 'Test database backup', 'Run a full mysqldump, restore it into a scratch database and verify the task counts match.', 'pending', 'high', DATE_SUB(CURDATE(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 7 DAY)),
(2, 'Clean up old tickets', 'Close or archive completed tickets that have had no activity for more than 60 days.', 'completed', 'low', NULL, DATE_SUB(NOW(), INTERVAL 12 DAY)),
(2, 'Design dashboard cards', 'Create the four summary cards for total, pending, in-progress and completed tasks.', 'pending', 'medium', DATE_ADD(CURDATE(), INTERVAL 5 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY)),
(2, 'Audit dependencies', 'Review the current dependency list and remove anything that is no longer used.', 'in_progress', 'low', DATE_ADD(CURDATE(), INTERVAL 8 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY)),
(3, 'Draft marketing copy', 'Write the launch announcement and short social posts for the new task tracker release.', 'pending', 'medium', DATE_ADD(CURDATE(), INTERVAL 4 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY)),
(3, 'Review analytics', 'Check the weekly engagement metrics and note any unusual changes in activation.', 'completed', 'low', DATE_SUB(CURDATE(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 6 DAY)),
(1, 'Review access policies', 'Confirm the admin role can only manage account access and cannot read other users'' tasks.', 'pending', 'high', DATE_ADD(CURDATE(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY)),
(1, 'Archive old reports', 'Move last year''s exported reports into cold storage and remove local copies.', 'completed', 'low', NULL, DATE_SUB(NOW(), INTERVAL 10 DAY));
