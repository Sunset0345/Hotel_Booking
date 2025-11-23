-- Migration: Add avatar column to users table
-- Created: 2025-11-23
-- Usage: Run this SQL against your application's database to add an 'avatar' column
-- Rollback: See the 'DOWN' section at the bottom to remove the column if needed

/*
 * UP: Add the avatar column
 * - Column: avatar (nullable, stores relative path under public/)
 * - Type: VARCHAR(512) NULL
 */

ALTER TABLE `users`
  ADD COLUMN `avatar` VARCHAR(512) NULL AFTER `email`;

/*
 * DOWN: To rollback this migration, run:
 * ALTER TABLE `users` DROP COLUMN `avatar`;
 */
