-- ============================================================================
-- 033_pending_response_tracking.sql
-- Trackeo de mensajes pendientes de respuesta. Usado por el cron
-- /cron/check_pending_responses que envía un "estamos contigo en un momento"
-- automático cuando el cliente pregunta algo y nadie responde en > 90 segundos.
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

CALL _add_col_or_index_if_missing(
    'bot_conversations', 'COLUMN', 'pending_holder_sent_at',
    "`pending_holder_sent_at` DATETIME DEFAULT NULL COMMENT 'Cuándo se mandó el auto-reply estamos contigo para este turno del cliente'"
);

CALL _add_col_or_index_if_missing(
    'bot_conversations', 'INDEX', 'idx_pending_response',
    "`idx_pending_response` (last_direction, last_message_at, pending_holder_sent_at)"
);

DROP PROCEDURE IF EXISTS _add_col_or_index_if_missing;
