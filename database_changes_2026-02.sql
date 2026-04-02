-- ==========================================
-- CAMBIOS DE BASE DE DATOS - FEBRERO 2026
-- Dropshipping SisVent
-- ==========================================

-- ==========================================
-- 1. BOT IMPORT - Tabla users
-- Fecha: 2026-02-09
-- Commits: 646acb5, b57e37d
-- Descripción: Campos para configuración del bot de IA que importa ventas desde Google Sheets
-- ==========================================

ALTER TABLE `users`
ADD COLUMN `bot_sheet_id` VARCHAR(255) DEFAULT NULL COMMENT 'ID del Google Sheet del vendedor',
ADD COLUMN `bot_script_url` VARCHAR(500) DEFAULT NULL COMMENT 'URL del Google Apps Script para importar',
ADD COLUMN `bot_gid` VARCHAR(50) DEFAULT '0' COMMENT 'GID de la hoja específica dentro del Sheet';

-- ==========================================
-- 2. TRACKING DE GUÍAS - Tabla invoices
-- Fecha: 2026-02-09
-- Descripción: Campos para rastreo de envíos con transportadoras (Interrapidísimo, etc.)
-- ==========================================

ALTER TABLE `invoices`
ADD COLUMN `tracking_number` VARCHAR(50) DEFAULT NULL COMMENT 'Numero de guia de la transportadora',
ADD COLUMN `tracking_carrier` VARCHAR(50) DEFAULT 'interrapidisimo' COMMENT 'Transportadora: interrapidisimo, servientrega, coordinadora, otro',
ADD COLUMN `tracking_status` VARCHAR(50) DEFAULT NULL COMMENT 'Estado: pending, in_transit, out_for_delivery, delivered, returned, exception',
ADD COLUMN `tracking_location` VARCHAR(255) DEFAULT NULL COMMENT 'Ubicacion actual del envio',
ADD COLUMN `tracking_last_update` DATETIME DEFAULT NULL COMMENT 'Ultima actualizacion del estado',
ADD COLUMN `shipped_at` DATETIME DEFAULT NULL COMMENT 'Fecha de envio',
ADD COLUMN `delivered_at` DATETIME DEFAULT NULL COMMENT 'Fecha de entrega';

-- Índice para búsquedas de tracking
ALTER TABLE `invoices` ADD INDEX `idx_tracking` (`tracking_number`, `tracking_status`);


-- ==========================================
-- 3. BOT WEBHOOK - Cola de ventas
-- Fecha: 2026-02-17
-- Descripción: Tabla para recibir ventas directamente del bot via webhook
-- ==========================================

CREATE TABLE IF NOT EXISTS `bot_sales_queue` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `payload` JSON NOT NULL COMMENT 'JSON con los datos de la venta',
  `status` ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
  `budget_id` INT DEFAULT NULL COMMENT 'ID del presupuesto creado',
  `vendor_id` VARCHAR(20) DEFAULT NULL COMMENT 'ID del vendedor',
  `api_key` VARCHAR(64) DEFAULT NULL COMMENT 'API key usada',
  `error_message` TEXT DEFAULT NULL COMMENT 'Mensaje de error si fallo',
  `attempts` INT DEFAULT 0 COMMENT 'Intentos de procesamiento',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `processed_at` DATETIME DEFAULT NULL,
  INDEX `idx_status` (`status`),
  INDEX `idx_vendor` (`vendor_id`),
  INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Cola de ventas recibidas por webhook del bot';

-- Campo API key en users para autenticación del bot
ALTER TABLE `users`
ADD COLUMN `bot_api_key` VARCHAR(64) DEFAULT NULL COMMENT 'API key para webhook del bot';

-- ==========================================
-- VERIFICACIÓN DE CAMBIOS
-- Ejecutar después de aplicar los cambios
-- ==========================================

-- Verificar columnas de bot en users:
-- SHOW COLUMNS FROM users WHERE Field LIKE 'bot_%';

-- Verificar columnas de tracking en invoices:
-- SHOW COLUMNS FROM invoices WHERE Field LIKE 'tracking_%' OR Field IN ('shipped_at', 'delivered_at');

-- Verificar índice de tracking:
-- SHOW INDEX FROM invoices WHERE Key_name = 'idx_tracking';

-- Verificar tabla bot_sales_queue:
-- DESCRIBE bot_sales_queue;
