-- Migration: add profile-related columns to `users` table
-- Backup your database before running this file.
-- Up: adds columns if they don't already exist
ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `avatar` VARCHAR(512) NULL AFTER `password`,
  ADD COLUMN IF NOT EXISTS `cover_photo` VARCHAR(512) NULL AFTER `avatar`,
  ADD COLUMN IF NOT EXISTS `bio` TEXT NULL AFTER `cover_photo`,
  ADD COLUMN IF NOT EXISTS `gender` VARCHAR(16) NULL AFTER `email`,
  ADD COLUMN IF NOT EXISTS `date_of_birth` DATE NULL AFTER `gender`,
  ADD COLUMN IF NOT EXISTS `phone` VARCHAR(32) NULL AFTER `date_of_birth`,
  ADD COLUMN IF NOT EXISTS `address` TEXT NULL AFTER `phone`;

-- Down: remove the columns (rollback)
-- Note: running the down block will drop these columns and their data.
-- ALTER TABLE `users`
--   DROP COLUMN IF EXISTS `avatar`,
--   DROP COLUMN IF EXISTS `cover_photo`,
--   DROP COLUMN IF EXISTS `bio`,
--   DROP COLUMN IF EXISTS `gender`,
--   DROP COLUMN IF EXISTS `date_of_birth`,
--   DROP COLUMN IF EXISTS `phone`,
--   DROP COLUMN IF EXISTS `address`;
