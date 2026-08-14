-- =====================================================================
-- Migration 061: mam_returns + asiento contable
-- =====================================================================
-- Agrega columnas para que la devolución física a MAM genere nota crédito
-- de proveedor (asiento contable inverso: DR Proveedores / CR Inventario).
-- =====================================================================

ALTER TABLE mam_returns
    ADD COLUMN total_cost DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER total_skus,
    ADD COLUMN entry_id INT DEFAULT NULL AFTER total_cost,
    ADD INDEX idx_entry (entry_id);
