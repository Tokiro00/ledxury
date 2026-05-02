-- ============================================================================
-- 035_product_providers_unique.sql
-- Agrega UNIQUE INDEX (productId, providerId) a product_providers para que
-- la carga de costos por proveedor sea idempotente vía
-- INSERT ... ON DUPLICATE KEY UPDATE.
--
-- El módulo de Compras (cron run_purchase_rules) lee providerPrice de
-- product_providers para pre-llenar unitCost en supplier_order_details
-- cuando genera órdenes automáticas.
--
-- NOTA: en producción ya se cargaron 1597 costos del catálogo MAM
-- (proveedor id=12) desde un Excel. Esta migración solo asegura que
-- futuros uploads no dupliquen el par (productId, providerId).
-- ============================================================================

DROP PROCEDURE IF EXISTS _add_unique_if_missing;

DELIMITER //
CREATE PROCEDURE _add_unique_if_missing(
    IN p_table VARCHAR(64),
    IN p_index VARCHAR(64),
    IN p_definition TEXT
)
BEGIN
    DECLARE v_exists INT DEFAULT 0;
    SELECT COUNT(*) INTO v_exists FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table AND INDEX_NAME = p_index;
    IF v_exists = 0 THEN
        SET @ddl = CONCAT('ALTER TABLE `', p_table, '` ADD ', p_definition);
        PREPARE stmt FROM @ddl;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END //
DELIMITER ;

CALL _add_unique_if_missing(
    'product_providers',
    'uniq_product_provider',
    'UNIQUE INDEX `uniq_product_provider` (productId, providerId)'
);

DROP PROCEDURE IF EXISTS _add_unique_if_missing;
