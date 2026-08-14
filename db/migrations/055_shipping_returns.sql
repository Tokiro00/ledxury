-- 055: shipping_returns — workflow de devoluciones de transportadora.
--
-- Cuando una guía pasa a status='returned' (o estadoGuia ∈ {13,14,15} para
-- Inter), se crea una fila acá con status='detectada'. El workflow:
--
--   detectada       → guía reportada como devuelta, paquete aún no llega
--   en_camino       → confirmamos que el carrier nos lo está devolviendo
--   recibida        → paquete físicamente recibido en bodega + checkeado
--   nota_credito_emitida → ya se canceló la venta y devolvió plata al cliente
--   reembarcada     → se generó nueva guía (segundo intento)
--   perdida         → write-off (carrier no devolvió o llegó destruido)
--
-- product_condition aplica solo a status='recibida' o posterior:
--   bueno      → restock al inventario sin merma
--   dañado     → restock parcial, parte se va a write-off
--   incompleto → faltan piezas, write-off proporcional
--   no_recibido→ paquete nunca llegó (carrier perdido)
--
-- Cada acción graba reviewed_by + reviewed_at para auditoría.
--
-- KPIs derivables: tasa de devolución por carrier/vendedor/cliente,
-- costo de fletes asumidos en devoluciones, tiempo promedio detección→cierre.

CREATE TABLE IF NOT EXISTS `shipping_returns` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `shipping_guide_id` INT NOT NULL,
    `invoice_id` INT NULL COMMENT 'idInvoice del sistema, copiado de shipping_guides al crear',
    `client_id` INT NULL,
    `vendor_id` VARCHAR(100) NULL,
    `store_id` INT NULL,
    `status` ENUM('detectada','en_camino','recibida','nota_credito_emitida','reembarcada','perdida') NOT NULL DEFAULT 'detectada',
    `detected_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `received_back_at` DATETIME NULL,
    `received_back_by` VARCHAR(100) NULL,
    `product_condition` ENUM('bueno','danado','incompleto','no_recibido') NULL COMMENT 'Estado físico del paquete al recibirlo',
    `restock_inventory` TINYINT(1) DEFAULT 0 COMMENT '1 = se devolvieron unidades al stock',
    `credit_note_id` INT NULL COMMENT 'FK a refunds o credit_notes según el módulo',
    `new_guide_id` INT NULL COMMENT 'Si fue reembarcada, FK a la nueva shipping_guides.id',
    `flete_devolucion` DECIMAL(15,2) DEFAULT 0 COMMENT 'Costo de flete del retorno (si el carrier lo cobra)',
    `flete_perdido` DECIMAL(15,2) DEFAULT 0 COMMENT 'Costo total perdido = flete ida + flete vuelta',
    `notes` VARCHAR(500) NULL,
    `created_by` VARCHAR(100) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_shipping_guide` (`shipping_guide_id`) COMMENT 'Una devolución por guía (si se devuelve dos veces se sobreescribe)',
    KEY `idx_status` (`status`, `detected_at`),
    KEY `idx_invoice` (`invoice_id`),
    KEY `idx_vendor` (`vendor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  COMMENT='Workflow de devoluciones de transportadora. v1.32.x';
