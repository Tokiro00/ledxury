-- ============================================================
-- 026: BuilderBot Cloud Integration
-- Tablas para administrar bots de WhatsApp desde MAM
-- ============================================================

-- Configuraciones de bots BuilderBot Cloud
CREATE TABLE IF NOT EXISTS `builderbot_configs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL COMMENT 'Nombre del bot, ej: GerMAM Medellin',
  `bot_id` VARCHAR(50) NOT NULL COMMENT 'UUID del bot en BuilderBot Cloud',
  `api_key` VARCHAR(100) NOT NULL COMMENT 'API key de BuilderBot Cloud',
  `base_url` VARCHAR(255) DEFAULT 'https://app.builderbot.cloud' COMMENT 'Base URL de la API',
  `webhook_secret` VARCHAR(64) DEFAULT NULL COMMENT 'Secret para validar webhooks entrantes',
  `default_vendor_id` VARCHAR(20) DEFAULT NULL COMMENT 'Vendedor por defecto para ventas de este bot',
  `default_store_id` INT DEFAULT 1 COMMENT 'Tienda por defecto',
  `sheet_id` VARCHAR(100) DEFAULT NULL COMMENT 'ID del Google Sheet para replicar ventas',
  `sheet_gid` VARCHAR(20) DEFAULT '0' COMMENT 'GID de la hoja dentro del Sheet',
  `script_url` VARCHAR(500) DEFAULT NULL COMMENT 'URL del Apps Script para escribir en Sheet',
  `is_active` TINYINT(1) DEFAULT 1,
  `created_by` VARCHAR(20) NOT NULL COMMENT 'idUser del creador',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_bot_id` (`bot_id`),
  INDEX `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Configuraciones de bots BuilderBot Cloud';

-- Log de mensajes enviados/recibidos via BuilderBot
CREATE TABLE IF NOT EXISTS `builderbot_messages` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `bot_config_id` INT NOT NULL COMMENT 'FK a builderbot_configs',
  `direction` ENUM('outgoing', 'incoming') DEFAULT 'outgoing',
  `phone_number` VARCHAR(20) NOT NULL,
  `content` TEXT NOT NULL,
  `media_url` VARCHAR(500) DEFAULT NULL,
  `status` ENUM('queued', 'sent', 'delivered', 'failed') DEFAULT 'queued',
  `api_response` TEXT DEFAULT NULL COMMENT 'JSON de respuesta de la API',
  `sent_by` VARCHAR(20) DEFAULT NULL COMMENT 'idUser que envio el mensaje',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_bot` (`bot_config_id`),
  INDEX `idx_phone` (`phone_number`),
  INDEX `idx_created` (`created_at`),
  FOREIGN KEY (`bot_config_id`) REFERENCES `builderbot_configs`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Log de mensajes enviados/recibidos via BuilderBot';

-- Webhooks entrantes desde BuilderBot Cloud
CREATE TABLE IF NOT EXISTS `builderbot_webhooks` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `bot_config_id` INT DEFAULT NULL,
  `event_type` VARCHAR(50) DEFAULT 'sale' COMMENT 'Tipo: sale, message, status',
  `raw_payload` LONGTEXT NOT NULL,
  `status` ENUM('received', 'transformed', 'processed', 'failed') DEFAULT 'received',
  `queue_id` INT DEFAULT NULL COMMENT 'FK a bot_sales_queue si se proceso como venta',
  `error_message` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_bot` (`bot_config_id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Webhooks entrantes desde BuilderBot Cloud';

-- Seed: Bot GerMAM Medellin
INSERT INTO `builderbot_configs`
  (`name`, `bot_id`, `api_key`, `default_vendor_id`, `default_store_id`,
   `sheet_id`, `sheet_gid`, `webhook_secret`, `created_by`)
VALUES
  ('GerMAM Medellín', '1cafcdaf-ee82-4896-a7c0-25dd79f40782',
   'bb-7600a770-3f99-42a5-b640-70c52bff1e3c', '1234567', 1,
   '1c_gA80cdq_IXekDY_mqoMqWjQKHUhbO6IV1mPxNqq84', '0',
   'wh_mam_builderbot_2026', '71211970');
