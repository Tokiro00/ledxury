-- 047: Tabla inventory_holds (Quality Hold model, port desde Lumen v1.31.17)
--
-- Reemplaza el modelo simple de "devolver stock al aprobar NC" por uno
-- que respeta la condición física del producto retornado:
--
--   bueno      -> stock + (sin hold) — vendible normal
--   defectuoso -> stock + hold(quarantine) — esperando revisión técnica
--   danado     -> stock + hold(scrapped) - stock — baja física
--
-- Las queries de "stock vendible" deben restar las unidades en hold
-- activas (status IN 'quarantine','rma'). Esto evita que un producto
-- defectuoso se venda por error mientras espera revisión.

CREATE TABLE IF NOT EXISTS `inventory_holds` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `store_id` INT(11) NOT NULL,
    `product_id` VARCHAR(50) NOT NULL,
    `quantity` INT(11) NOT NULL,
    `status` ENUM('quarantine','rma','scrapped','released') NOT NULL DEFAULT 'quarantine',
    `credit_note_id` INT(11) DEFAULT NULL COMMENT 'NC origen del defecto',
    `origin_condition` ENUM('defectuoso','danado') NOT NULL,
    `rma_supplier_id` INT(11) DEFAULT NULL,
    `rma_reference` VARCHAR(100) DEFAULT NULL,
    `resolution_notes` TEXT DEFAULT NULL,
    `created_by` VARCHAR(100) DEFAULT NULL,
    `created_at` DATETIME NOT NULL,
    `resolved_at` DATETIME DEFAULT NULL,
    `resolved_by` VARCHAR(100) DEFAULT NULL,
    `entry_id` BIGINT(20) DEFAULT NULL COMMENT 'asiento contable de baja/RMA cuando aplica',
    PRIMARY KEY (`id`),
    KEY `idx_active_stock` (`store_id`, `product_id`, `status`),
    KEY `idx_credit_note` (`credit_note_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Reservas de unidades en cuarentena/baja para Quality Hold de notas crédito';
