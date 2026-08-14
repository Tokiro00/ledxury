-- 051: Hacer bot_commission_config parametrizable desde UI.
--
-- Agrega campos:
--   - description: nombre legible (reemplaza commission_type enum)
--   - basis: ventas | recaudo | margen — sobre qué se calcula el %
--   - valid_from / valid_to: vigencia (NULL = sin límite)
--
-- Backfill: pone description con la traducción del enum existente +
-- basis='recaudo' para todas las filas (es lo que el código actual usa).

ALTER TABLE `bot_commission_config`
    ADD COLUMN `description` VARCHAR(255) NULL AFTER `user_id`,
    ADD COLUMN `basis` ENUM('ventas','recaudo','margen') NOT NULL DEFAULT 'recaudo' AFTER `percentage`,
    ADD COLUMN `valid_from` DATE NULL AFTER `is_active`,
    ADD COLUMN `valid_to` DATE NULL AFTER `valid_from`;

UPDATE `bot_commission_config` SET
    `description` = CASE
        WHEN `commission_type` = 'admin_bots' THEN 'Admin de bots'
        WHEN `commission_type` = 'operator'   THEN 'Operador de bot'
        WHEN `commission_type` = 'ads_manager' THEN 'Coordinador de publicidad'
        ELSE `commission_type`
    END,
    `basis` = 'recaudo'
WHERE `description` IS NULL;
