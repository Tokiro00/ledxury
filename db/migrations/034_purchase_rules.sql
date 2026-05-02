-- ============================================================================
-- 034_purchase_rules.sql
-- Módulo de Compras profesional y parametrizable.
--
-- Cambios:
--   1. Extiende `providers` con info operacional (lead time, contactos, formato
--      preferido de exportación, activo/inactivo).
--   2. Crea tabla `purchase_rules` para reglas recurrentes parametrizables
--      (semanal, mensual, custom; filtro de productos; ventana de ventas).
--   3. Extiende `purchases` con tracking del flujo (PO generada por rule,
--      timestamps por estado, formato exportado).
--   4. Extiende `purchase_detail` para soportar recepción parcial.
--
-- Convención de `purchases.state` (queda en INT por compat, valores nuevos):
--   0 = borrador      (recién generada por la rule, editable)
--   1 = enviada       (mandada al proveedor)
--   2 = recibida_parcial
--   3 = recibida_total
--   4 = cerrada       (cuadrada, no se modifica más)
--   5 = cancelada
-- ============================================================================

DROP PROCEDURE IF EXISTS _add_col_or_index_if_missing;

DELIMITER //
CREATE PROCEDURE _add_col_or_index_if_missing(
    IN p_table VARCHAR(64),
    IN p_kind  VARCHAR(10),  -- 'COLUMN' o 'INDEX'
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

-- ----------------------------------------------------------------------------
-- 1. Extender providers
-- ----------------------------------------------------------------------------
CALL _add_col_or_index_if_missing('providers', 'COLUMN', 'lead_time_days',
    "`lead_time_days` INT DEFAULT 3 COMMENT 'Días que tarda el proveedor en entregar tras enviarle la orden'");

CALL _add_col_or_index_if_missing('providers', 'COLUMN', 'contact_email',
    "`contact_email` VARCHAR(120) DEFAULT NULL COMMENT 'Email para enviar órdenes de compra'");

CALL _add_col_or_index_if_missing('providers', 'COLUMN', 'contact_phone',
    "`contact_phone` VARCHAR(50) DEFAULT NULL");

CALL _add_col_or_index_if_missing('providers', 'COLUMN', 'contact_whatsapp',
    "`contact_whatsapp` VARCHAR(50) DEFAULT NULL");

CALL _add_col_or_index_if_missing('providers', 'COLUMN', 'preferred_export',
    "`preferred_export` ENUM('excel','pdf','csv') DEFAULT 'excel' COMMENT 'Formato preferido para exportar la orden de compra'");

CALL _add_col_or_index_if_missing('providers', 'COLUMN', 'notes',
    "`notes` TEXT DEFAULT NULL");

CALL _add_col_or_index_if_missing('providers', 'COLUMN', 'active',
    "`active` TINYINT(1) DEFAULT 1 COMMENT 'Si 0, el proveedor no aparece en dropdowns ni recibe órdenes automáticas'");

-- ----------------------------------------------------------------------------
-- 2. Crear purchase_rules
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `purchase_rules` (
    `id`                INT(11) NOT NULL AUTO_INCREMENT,
    `name`              VARCHAR(120) NOT NULL,
    `providerId`        INT(11) NOT NULL,
    `storeId`           INT(11) NOT NULL DEFAULT 1,

    -- Frecuencia: weekly | monthly | custom
    `frequency_type`    ENUM('weekly','monthly','custom') NOT NULL DEFAULT 'weekly',
    -- JSON con detalle: weekly: {"day_of_week":1,"hour":6}; monthly: {"day_of_month":1,"hour":6}; custom: {"cron":"0 6 * * 1"}
    `frequency_config`  TEXT NOT NULL,

    -- Ventana de ventas: lookback_days = cuántos días atrás contar para sumar las ventas
    `lookback_days`     INT(11) NOT NULL DEFAULT 7,

    -- Filtro de productos: all_sold | specific_list | all_provider
    `product_filter`    ENUM('all_sold','specific_list','all_provider') NOT NULL DEFAULT 'all_sold',
    -- JSON array de SKUs si product_filter='specific_list', NULL en otro caso
    `product_list`      TEXT DEFAULT NULL,

    -- Excluye productos en blocked_products (agotados en el proveedor)
    `exclude_blocked`   TINYINT(1) NOT NULL DEFAULT 1,

    -- Estado
    `active`            TINYINT(1) NOT NULL DEFAULT 1,
    `last_run_at`       DATETIME DEFAULT NULL,
    `next_run_at`       DATETIME DEFAULT NULL,

    -- Auditoría
    `created_by`        VARCHAR(100) DEFAULT NULL,
    `created_at`        DATETIME DEFAULT NULL,
    `updated_at`        DATETIME DEFAULT NULL,
    `deleted`           TINYINT(1) NOT NULL DEFAULT 0,

    PRIMARY KEY (`id`),
    KEY `idx_provider` (`providerId`),
    KEY `idx_active_next` (`active`, `next_run_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------------------------------------------------------
-- 3. Extender purchases
-- ----------------------------------------------------------------------------
CALL _add_col_or_index_if_missing('purchases', 'COLUMN', 'purchase_rule_id',
    "`purchase_rule_id` INT(11) DEFAULT NULL COMMENT 'Si se generó automáticamente desde una rule'");

CALL _add_col_or_index_if_missing('purchases', 'COLUMN', 'generated_at',
    "`generated_at` DATETIME DEFAULT NULL COMMENT 'Cuándo el cron creó esta PO en estado borrador'");

CALL _add_col_or_index_if_missing('purchases', 'COLUMN', 'sent_at',
    "`sent_at` DATETIME DEFAULT NULL COMMENT 'Cuándo se envió al proveedor (state→1)'");

CALL _add_col_or_index_if_missing('purchases', 'COLUMN', 'received_at',
    "`received_at` DATETIME DEFAULT NULL COMMENT 'Cuándo se cerró la recepción completa'");

CALL _add_col_or_index_if_missing('purchases', 'COLUMN', 'export_format',
    "`export_format` VARCHAR(10) DEFAULT NULL COMMENT 'excel/pdf/csv usado al enviar'");

CALL _add_col_or_index_if_missing('purchases', 'INDEX', 'idx_purchase_state',
    "`idx_purchase_state` (state, deleted)");

CALL _add_col_or_index_if_missing('purchases', 'INDEX', 'idx_purchase_rule',
    "`idx_purchase_rule` (purchase_rule_id)");

-- ----------------------------------------------------------------------------
-- 4. Extender purchase_detail (recepción parcial)
-- ----------------------------------------------------------------------------
-- `quantity` ya existe = lo que se pidió originalmente. Agregamos lo que llegó.
CALL _add_col_or_index_if_missing('purchase_detail', 'COLUMN', 'quantity_received',
    "`quantity_received` INT DEFAULT NULL COMMENT 'Cuánto realmente llegó del proveedor (NULL hasta recepción)'");

CALL _add_col_or_index_if_missing('purchase_detail', 'COLUMN', 'line_state',
    "`line_state` ENUM('pendiente','recibido_parcial','recibido_total','no_disponible','cancelado') DEFAULT 'pendiente'");

CALL _add_col_or_index_if_missing('purchase_detail', 'COLUMN', 'received_at',
    "`received_at` DATETIME DEFAULT NULL");

CALL _add_col_or_index_if_missing('purchase_detail', 'INDEX', 'idx_purchase_detail_state',
    "`idx_purchase_detail_state` (purchaseId, line_state)");

DROP PROCEDURE IF EXISTS _add_col_or_index_if_missing;
