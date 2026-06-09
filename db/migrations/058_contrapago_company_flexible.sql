-- ============================================================================
-- 058: Permitir múltiples empresas externas en clasificación de items
-- ============================================================================
-- Antes: enum('ledxury','mam','no_invoice','disputa','sin_revisar')
-- Ahora: varchar(20) — soporta mam_online y futuras empresas sin migrar enum
-- ============================================================================

ALTER TABLE contrapago_invoice_items
    MODIFY COLUMN company VARCHAR(20) NULL DEFAULT NULL;

ALTER TABLE contrapago_payments
    MODIFY COLUMN company VARCHAR(20) NULL DEFAULT NULL;

-- Sanity check
SELECT company, COUNT(*) FROM contrapago_invoice_items GROUP BY company;
