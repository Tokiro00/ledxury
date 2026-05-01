-- ============================================================================
-- 036_purchase_rules_since_date.sql
-- Agrega columna `since_date` a purchase_rules para soportar override
-- one-shot del lookback_days en la primera ejecución de una rule.
--
-- Caso de uso: Ledxury arranca operación el 2026-05-01 con inventario en 0.
-- La rule "Reposición semanal MAM" tiene lookback_days=7, pero el primer
-- run del lunes 4-may debe sumar ventas SOLO desde el 1-may (no desde el
-- 28-abr). Setear since_date='2026-05-01 00:00:00' fuerza ese cutoff
-- exacto en la próxima ejecución; el cron luego nullea la columna y la
-- rule vuelve al ciclo lookback_days normal.
-- ============================================================================

DROP PROCEDURE IF EXISTS _add_col_or_index_if_missing;

DELIMITER //
CREATE PROCEDURE _add_col_or_index_if_missing(
    IN p_table VARCHAR(64),
    IN p_kind  VARCHAR(10),
    IN p_name  VARCHAR(64),
    IN p_definition TEXT
)
BEGIN
    DECLARE v_exists INT DEFAULT 0;
    IF p_kind = 'COLUMN' THEN
        SELECT COUNT(*) INTO v_exists FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table AND COLUMN_NAME = p_name;
    ELSEIF p_kind = 'INDEX' THEN
        SELECT COUNT(*) INTO v_exists FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table AND INDEX_NAME = p_name;
    END IF;
    IF v_exists = 0 THEN
        SET @ddl = CONCAT('ALTER TABLE `', p_table, '` ADD ', p_kind, ' ', p_definition);
        PREPARE stmt FROM @ddl;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END //
DELIMITER ;

CALL _add_col_or_index_if_missing(
    'purchase_rules', 'COLUMN', 'since_date',
    "`since_date` DATETIME DEFAULT NULL COMMENT 'Override one-shot del lookback_days. Si seteado, el próximo run usa este cutoff y la columna se nullea después.'"
);

DROP PROCEDURE IF EXISTS _add_col_or_index_if_missing;
