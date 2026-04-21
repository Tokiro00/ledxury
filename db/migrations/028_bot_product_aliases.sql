-- ============================================================================
-- 028_bot_product_aliases.sql
-- Tabla de alias descripcion -> codigo de producto, para ventas de bot.
-- El bot a veces envia el texto natural del producto en vez del codigo exacto.
-- Si el codigo no existe en `products` probamos esta tabla como fallback.
-- ============================================================================

CREATE TABLE IF NOT EXISTS `bot_product_aliases` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `alias_norm` VARCHAR(255) NOT NULL COMMENT 'Texto normalizado: UPPER + trim + collapse whitespace',
  `alias_raw` VARCHAR(255) NOT NULL COMMENT 'Texto original enviado por el bot',
  `product_code` VARCHAR(100) NOT NULL COMMENT 'Codigo real en products.code',
  `hits` INT DEFAULT 0 COMMENT 'Veces que se uso este alias al procesar una venta',
  `created_by` VARCHAR(50) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_alias_norm` (`alias_norm`),
  KEY `idx_product_code` (`product_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
