-- ============================================================
-- Migration 025: Metas colectivas por bodega/año
-- ============================================================

CREATE TABLE IF NOT EXISTS company_goals (
  id INT AUTO_INCREMENT PRIMARY KEY,
  storeId INT NOT NULL,
  year INT NOT NULL,
  meta_ventas BIGINT DEFAULT 0 COMMENT 'Meta de ventas anual en COP',
  meta_cobros BIGINT DEFAULT 0 COMMENT 'Meta de cobros anual en COP',
  updated_by VARCHAR(100) DEFAULT NULL,
  updated_at DATETIME DEFAULT NULL,
  UNIQUE KEY uk_store_year (storeId, year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Seed: Medellín $500M, Barranquilla $200M para 2026
INSERT IGNORE INTO company_goals (storeId, year, meta_ventas, meta_cobros, updated_at) VALUES
(1, 2026, 500000000, 500000000, NOW()),
(3, 2026, 200000000, 200000000, NOW());
