-- ============================================================
-- Migration 023: Logistics report - transportadora en facturas
-- ============================================================

-- Transportadora para despachos que no usan Interrapidisimo
ALTER TABLE invoices
  ADD COLUMN transportadora ENUM('interrapidisimo','estelar','coordinadora','carro_mam','moto_mam','particular','recoge_cliente','sin_despacho') DEFAULT 'sin_despacho' AFTER comments,
  ADD COLUMN despacho_destino VARCHAR(100) DEFAULT '' AFTER transportadora,
  ADD COLUMN despachado_at DATETIME DEFAULT NULL AFTER despacho_destino,
  ADD COLUMN despachado_by VARCHAR(100) DEFAULT NULL AFTER despachado_at;

-- Permiso para el reporte de logística
INSERT IGNORE INTO role_permissions (role_id, module_key, created_at) VALUES
(9, 'reporte_logistica', NOW()),
(2, 'reporte_logistica', NOW());
