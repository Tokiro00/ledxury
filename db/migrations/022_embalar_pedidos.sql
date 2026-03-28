-- ============================================================
-- Migration 022: Embalar pedidos workflow
-- Agrega columnas de embalaje a presupuestos y permisos nuevos
-- ============================================================

ALTER TABLE budgets
  ADD COLUMN embalado TINYINT(1) DEFAULT 0 AFTER archived,
  ADD COLUMN embalado_by VARCHAR(100) DEFAULT NULL AFTER embalado,
  ADD COLUMN embalado_at DATETIME DEFAULT NULL AFTER embalado_by;

-- Permisos: embalar para bodegueros, facturar para logística
INSERT IGNORE INTO role_permissions (role_id, module_key, created_at) VALUES
(5, 'embalar_pedidos', NOW()),
(9, 'facturar', NOW());
