-- 020: Mensajes entre clientes y vendedores (via IA o directo)
-- Permite comunicación bidireccional con historial

CREATE TABLE IF NOT EXISTS `chat_messages` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `clientId` INT(11) NOT NULL,
  `vendorId` VARCHAR(100) NOT NULL,
  `sender` ENUM('client','vendor','ai') NOT NULL,
  `message` TEXT NOT NULL,
  `read_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_client_vendor` (`clientId`, `vendorId`),
  KEY `idx_vendor_unread` (`vendorId`, `read_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
