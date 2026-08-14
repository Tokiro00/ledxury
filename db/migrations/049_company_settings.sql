-- 202604211700_company_settings.sql
-- Tabla de settings de negocio editables desde la UI admin, con auditoria
-- completa de cambios. Reemplaza constantes hardcodeadas que hoy viven
-- en controllers/sisvent/admin/ (META_VENTAS, META_RECAUDO, MARGEN_BRUTO,
-- STORES_MDE, STORES_INV).
--
-- Accede via application/libraries/Settings_lib.php:
--     $this->settings_lib->get('meta_ventas')
--
-- Rollback en db/migrations/down/ con el mismo filename.

CREATE TABLE IF NOT EXISTS company_settings (
    setting_key  VARCHAR(100) NOT NULL PRIMARY KEY,
    value        TEXT NULL,
    type         ENUM('int', 'decimal', 'json', 'string') NOT NULL DEFAULT 'string',
    description  VARCHAR(255) NULL,
    updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by   VARCHAR(100) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS company_settings_log (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    setting_key  VARCHAR(100) NOT NULL,
    old_value    TEXT NULL,
    new_value    TEXT NULL,
    changed_by   VARCHAR(100) NOT NULL,
    changed_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_key_time (setting_key, changed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed: valores actuales de las constantes hardcodeadas. Quedan
-- sincronizados con Tracking.php y Salesboard.php al momento del
-- deploy. Si la tabla pierde filas por error, los callers siguen
-- funcionando con el fallback const en sus clases.
INSERT IGNORE INTO company_settings (setting_key, value, type, description) VALUES
    ('meta_ventas',  '500000000', 'int',     'Meta mensual de ventas brutas (COP)'),
    ('meta_recaudo', '500000000', 'int',     'Meta mensual de recaudo / cobros a clientes (COP)'),
    ('margen_bruto', '0.527',     'decimal', 'Margen bruto asumido para proyecciones (ej: 0.527 = 52.7%)'),
    ('stores_mde',   '[1,3,5]',   'json',    'IDs de stores Medellin contados en ventas, ranking y metas'),
    ('stores_inv',   '[1,8]',     'json',    'IDs de stores contados para inventario valorizado');
