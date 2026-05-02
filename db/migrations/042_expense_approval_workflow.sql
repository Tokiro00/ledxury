-- 042: Workflow de aprobación de gastos (E.1)
--
-- Agrega columnas para el ciclo pendiente → aprobado → pagado, con
-- separación de funciones (quien crea ≠ quien aprueba). Anular registra
-- razón y timestamp para auditoría.
--
-- Status enum lógico (la columna sigue siendo VARCHAR por compatibilidad):
--   pendiente  → causación posteada, sin aprobación
--   aprobado   → aprobado por admin/gerente, listo para pago
--   pagado     → cash movement + asiento de pago
--   anulado    → reversa contable + soft delete

ALTER TABLE expense_records
    ADD COLUMN IF NOT EXISTS approved_by      VARCHAR(100) NULL COMMENT 'Usuario que aprobó la causación',
    ADD COLUMN IF NOT EXISTS approved_at      DATETIME     NULL COMMENT 'Timestamp de aprobación',
    ADD COLUMN IF NOT EXISTS rejected_at      DATETIME     NULL COMMENT 'Timestamp de anulación/rechazo',
    ADD COLUMN IF NOT EXISTS rejection_reason TEXT         NULL COMMENT 'Motivo de la anulación';
