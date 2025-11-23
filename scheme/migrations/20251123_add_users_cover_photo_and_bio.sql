-- Migration: add cover_photo and bio to users
-- Up
ALTER TABLE `users`
  ADD COLUMN `cover_photo` VARCHAR(512) NULL AFTER `avatar`,
  ADD COLUMN `bio` TEXT NULL AFTER `cover_photo`;

-- Down
-- ALTER TABLE `users` DROP COLUMN `bio`, DROP COLUMN `cover_photo`;
