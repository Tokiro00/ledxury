-- 021: Overrides manuales para catálogo de ofertas/remate
-- Permite al admin controlar qué productos salen en el catálogo de clientes

CREATE TABLE IF NOT EXISTS `catalog_overrides` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `productId` VARCHAR(50) NOT NULL,
  `tab` ENUM('hot','ofertas','remate','excluido') DEFAULT NULL COMMENT 'Forzar a una tab o excluir',
  `price_override` FLOAT DEFAULT NULL COMMENT 'Precio manual (NULL = usar automático)',
  `discount_override` INT DEFAULT NULL COMMENT 'Descuento manual % (NULL = usar automático)',
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  `notes` VARCHAR(255) DEFAULT NULL,
  `updated_by` VARCHAR(100) DEFAULT NULL,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_product` (`productId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
