-- 054: contrapago_invoice_items — campos de revisión "sin match"
--
-- Cuando importás un CORTE de Inter, las guías que no matchean con
-- shipping_guides quedan en limbo. Esta migration agrega 4 campos de
-- workflow para resolverlas:
--
--   company        → quién paga este flete (ledxury|mam|no_invoice|disputa)
--   notes          → razón del marcaje (texto libre, ej: "muestra para feria")
--   reviewed_at    → cuándo se revisó (auditoría)
--   reviewed_by    → uname del usuario que la marcó
--
-- Estados:
--   - NULL                  = pendiente de revisar (default tras import)
--   - 'ledxury'             = match correcto (auto al importar si hay shipping_guide_id)
--   - 'mam'                 = guía de la sister company → cobrar a MAM intercompany
--   - 'no_invoice'          = gasto adicional sin venta (muestra/devolución/aliado)
--   - 'disputa'             = línea incorrecta, en reclamo con Inter
--
-- Idempotente: usa un procedure helper para que no falle si ya se aplicó.

DROP PROCEDURE IF EXISTS migration_054_add_column;

DELIMITER $$
CREATE PROCEDURE migration_054_add_column(
    IN tbl VARCHAR(64),
    IN col VARCHAR(64),
    IN ddl VARCHAR(500)
)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = tbl
          AND COLUMN_NAME = col
    ) THEN
        SET @sql = CONCAT('ALTER TABLE `', tbl, '` ADD COLUMN ', ddl);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$
DELIMITER ;

CALL migration_054_add_column('contrapago_invoice_items', 'company',
    "`company` ENUM('ledxury','mam','no_invoice','disputa') DEFAULT NULL COMMENT 'NULL = sin revisar; ver migration 054'");
CALL migration_054_add_column('contrapago_invoice_items', 'notes',
    "`notes` VARCHAR(255) DEFAULT NULL");
CALL migration_054_add_column('contrapago_invoice_items', 'reviewed_at',
    "`reviewed_at` DATETIME DEFAULT NULL");
CALL migration_054_add_column('contrapago_invoice_items', 'reviewed_by',
    "`reviewed_by` VARCHAR(100) DEFAULT NULL");

DROP PROCEDURE IF EXISTS migration_054_add_column;

-- Backfill: items que YA tienen shipping_guide_id quedan como 'ledxury' por defecto
-- (asumimos que el match auto del importador acertó y no necesitan revisión).
UPDATE contrapago_invoice_items
   SET company = 'ledxury'
 WHERE company IS NULL
   AND shipping_guide_id IS NOT NULL;

-- Index opcional para filtrar rápido por estado de revisión
CREATE INDEX IF NOT EXISTS idx_company ON contrapago_invoice_items(invoice_id, company);
