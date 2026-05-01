-- ============================================================================
-- 037_garantias_tickets.sql
--
-- 1. Extiende builderbot_configs con channel_type + meta_phone_number_id
--    para distinguir bots de BuilderBot vs canales conectados directo a Meta
--    Cloud API. Permite reutilizar la UI de chat existente para ambos.
--
-- 2. Crea garantias_tickets para gestionar casos de garantía/devolución que
--    lleguen al número WhatsApp +573330512998 (Cloud API directo, sin
--    BuilderBot). La conversación cruda sigue viviendo en bot_conversations +
--    builderbot_messages usando un bot_config_id con channel_type='meta_direct'.
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

-- ----------------------------------------------------------------------------
-- 1. Extender builderbot_configs para soportar canal Meta directo
-- ----------------------------------------------------------------------------
CALL _add_col_or_index_if_missing(
    'builderbot_configs', 'COLUMN', 'channel_type',
    "`channel_type` ENUM('builderbot','meta_direct') NOT NULL DEFAULT 'builderbot' COMMENT 'Tipo de canal: builderbot usa BuilderBot Cloud, meta_direct usa WhatsApp Cloud API de Meta directamente'"
);

CALL _add_col_or_index_if_missing(
    'builderbot_configs', 'COLUMN', 'meta_phone_number_id',
    "`meta_phone_number_id` VARCHAR(50) DEFAULT NULL COMMENT 'Phone Number ID de Meta para canales meta_direct (no es el número, es el ID interno de Meta)'"
);

DROP PROCEDURE IF EXISTS _add_col_or_index_if_missing;


CREATE TABLE IF NOT EXISTS `garantias_tickets` (
    `id`                INT(11) NOT NULL AUTO_INCREMENT,
    `ticket_number`     VARCHAR(30) NOT NULL,
    `conversation_id`   INT(11) DEFAULT NULL COMMENT 'FK a bot_conversations.id (canal Meta directo)',
    `client_phone`      VARCHAR(20) NOT NULL COMMENT 'Número del cliente (sin prefijo 57 si Colombia, normalizado)',
    `client_name`       VARCHAR(120) DEFAULT NULL,
    `client_id`         INT(11) DEFAULT NULL COMMENT 'FK a clients.idClient si se identifica',

    -- Categoría del caso
    `case_type`         ENUM('garantia','devolucion','reclamo','otro') NOT NULL DEFAULT 'garantia',
    `description`       TEXT DEFAULT NULL,

    -- Vínculos opcionales con la venta original
    `invoice_id`        INT(11) DEFAULT NULL COMMENT 'FK invoices.idInvoice si aplica',
    `budget_id`         INT(11) DEFAULT NULL COMMENT 'FK budgets.idBudget si aplica',
    `product_id`        VARCHAR(50) DEFAULT NULL,

    -- Estado y resolución
    `status`            ENUM('abierto','en_revision','resuelto','cerrado','cancelado') NOT NULL DEFAULT 'abierto',
    `resolution_type`   ENUM('reembolso','reemplazo','reparacion','garantia_proveedor','sin_lugar','otro') DEFAULT NULL,
    `resolution_notes`  TEXT DEFAULT NULL,
    `priority`          ENUM('baja','media','alta','urgente') NOT NULL DEFAULT 'media',

    -- Asignación
    `assigned_to`       VARCHAR(100) DEFAULT NULL COMMENT 'users.uname del agente responsable',

    -- Timestamps
    `opened_at`         DATETIME DEFAULT NULL,
    `resolved_at`       DATETIME DEFAULT NULL,
    `closed_at`         DATETIME DEFAULT NULL,
    `created_by`        VARCHAR(100) DEFAULT NULL,
    `created_at`        DATETIME DEFAULT NULL,
    `updated_at`        DATETIME DEFAULT NULL,
    `deleted`           TINYINT(1) NOT NULL DEFAULT 0,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_ticket_number` (`ticket_number`),
    KEY `idx_status` (`status`, `deleted`),
    KEY `idx_phone` (`client_phone`),
    KEY `idx_conversation` (`conversation_id`),
    KEY `idx_invoice` (`invoice_id`),
    KEY `idx_assigned` (`assigned_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
