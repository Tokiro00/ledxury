-- 048: Llevar employee_advances a paridad con Lumen (formulario Anticipo/Préstamo).
--
-- Añade campos que el formulario Lumen tiene y que el Ledxury actual no:
--   - advance_date     : fecha del anticipo (lo que pidió el usuario, hasta
--                        ahora solo guardábamos created_at).
--   - num_installments : número de cuotas (1 para anticipo simple, N para préstamo).
--   - installment_amount : valor de cada cuota (auto-calculado en JS).
--   - observations     : notas adicionales del aprobador.
--
-- También extiende el ENUM de type para aceptar los nombres nuevos
-- ('anticipo','prestamo') manteniendo los viejos ('cash','credit','scheduled')
-- para no romper el registro existente en prod (AC-000001 type='cash').

ALTER TABLE `employee_advances`
    ADD COLUMN `advance_date`        DATE          NULL AFTER `purpose`,
    ADD COLUMN `num_installments`    INT(11)       NOT NULL DEFAULT 1 AFTER `amount`,
    ADD COLUMN `installment_amount`  DECIMAL(15,2) NULL AFTER `num_installments`,
    ADD COLUMN `observations`        TEXT          NULL AFTER `purpose`;

ALTER TABLE `employee_advances`
    MODIFY COLUMN `type` ENUM('cash','credit','scheduled','anticipo','prestamo') NOT NULL DEFAULT 'anticipo';

-- Backfill: registros sin advance_date toman su created_at como fecha del anticipo.
UPDATE `employee_advances` SET `advance_date` = DATE(`created_at`) WHERE `advance_date` IS NULL;
