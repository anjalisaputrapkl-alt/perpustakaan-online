-- Migration: Add Email Verification Columns
-- Purpose: Support email verification for new school registrations
-- Date: 2026-01-22

ALTER TABLE `users` ADD COLUMN `verification_code` VARCHAR(10) NULL AFTER `password`;
ALTER TABLE `users` ADD COLUMN `is_verified` TINYINT(1) DEFAULT 0 AFTER `verification_code`;
ALTER TABLE `users` ADD COLUMN `verified_at` TIMESTAMP NULL AFTER `is_verified`;

-- Add index for faster lookup
ALTER TABLE `users` ADD INDEX `idx_verification_code` (`verification_code`);
ALTER TABLE `users` ADD INDEX `idx_is_verified` (`is_verified`);

-- Update any existing users to be verified (for backward compatibility)
UPDATE `users` SET `is_verified` = 1, `verified_at` = NOW() WHERE `is_verified` = 0;
