-- 019: Notificaciones in-app para vendedores
-- Se insertan cuando un cliente hace pedido desde la PWA, etc.

CREATE TABLE IF NOT EXISTS `notifications` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `userId` VARCHAR(100) NOT NULL COMMENT 'vendorId que recibe la notificación',
  `type` VARCHAR(30) NOT NULL DEFAULT 'order' COMMENT 'order, payment, alert',
  `title` VARCHAR(255) NOT NULL,
  `body` TEXT,
  `data` JSON DEFAULT NULL COMMENT 'Metadata extra (budgetId, clientId, etc)',
  `read_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_read` (`userId`, `read_at`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
