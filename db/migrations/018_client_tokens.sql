-- 018: Tokens de acceso directo para clientes (PWA clientes sin login)
-- El vendedor genera un link con token único que identifica al cliente

CREATE TABLE IF NOT EXISTS `client_tokens` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `token` VARCHAR(64) NOT NULL,
  `clientId` INT(11) NOT NULL,
  `vendorId` VARCHAR(20) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `expires_at` DATETIME DEFAULT NULL,
  `last_used_at` DATETIME DEFAULT NULL,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_token` (`token`),
  KEY `idx_client` (`clientId`),
  KEY `idx_active_token` (`active`, `token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
