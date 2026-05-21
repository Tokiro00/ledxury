-- 046_bot_pending_alternatives.sql
-- Fase 1: detección de productos agotados en el bot + sugerencia de alternativa al cliente.
--
-- Cuando el bot detecta que el SKU pedido está en blocked_products, en vez de
-- rechazar o crear un budget silenciosamente:
--   1. Busca SKUs hermanos disponibles (mismo modelo+voltaje, distinto color).
--   2. Envía WhatsApp al cliente con la lista de alternativas.
--   3. Crea una fila en esta tabla con status='awaiting'.
--   4. La respuesta del cliente al bot dispara la transición de status.
--
-- Esta tabla NO toca budgets/invoices/inventory. Es un staging entre el bot y
-- la creación del budget definitivo.

CREATE TABLE IF NOT EXISTS `bot_pending_alternatives` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `bot_config_id` INT NOT NULL,
  `conversation_id` INT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `client_id` INT NULL,
  `original_sku` VARCHAR(50) NOT NULL,
  `original_qty` INT NOT NULL DEFAULT 1,
  `original_product_name` VARCHAR(255) NULL,
  `suggested_skus` TEXT NOT NULL COMMENT 'CSV de SKUs alternativos ofrecidos',
  `status` ENUM('awaiting','reprompted','accepted','rejected','timeout','escalated','error') NOT NULL DEFAULT 'awaiting',
  `reprompt_count` TINYINT NOT NULL DEFAULT 0,
  `client_response` TEXT NULL COMMENT 'Texto literal de la respuesta del cliente',
  `resolved_sku` VARCHAR(50) NULL COMMENT 'SKU finalmente elegido (si status=accepted)',
  `resulting_budget_id` INT NULL COMMENT 'Budget creado tras la aceptación',
  `original_payload` TEXT NULL COMMENT 'Payload original del webhook para reproducir el resto de items',
  `error_message` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `resolved_at` DATETIME NULL,
  KEY `idx_phone_status` (`phone`, `status`),
  KEY `idx_status_created` (`status`, `created_at`),
  KEY `idx_bot_config` (`bot_config_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
