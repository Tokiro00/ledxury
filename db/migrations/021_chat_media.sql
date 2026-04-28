-- Migration: add media support to internal chat tables
-- Adds columns for image/audio/video/file attachments

ALTER TABLE `user_messages`
  ADD COLUMN `media_url` VARCHAR(500) NULL DEFAULT NULL AFTER `message`,
  ADD COLUMN `media_type` ENUM('image','audio','video','file') NULL DEFAULT NULL AFTER `media_url`,
  ADD COLUMN `media_name` VARCHAR(255) NULL DEFAULT NULL AFTER `media_type`,
  ADD COLUMN `media_size` INT(11) NULL DEFAULT NULL AFTER `media_name`;

ALTER TABLE `internal_chat`
  ADD COLUMN `media_url` VARCHAR(500) NULL DEFAULT NULL AFTER `message`,
  ADD COLUMN `media_type` ENUM('image','audio','video','file') NULL DEFAULT NULL AFTER `media_url`,
  ADD COLUMN `media_name` VARCHAR(255) NULL DEFAULT NULL AFTER `media_type`,
  ADD COLUMN `media_size` INT(11) NULL DEFAULT NULL AFTER `media_name`;
