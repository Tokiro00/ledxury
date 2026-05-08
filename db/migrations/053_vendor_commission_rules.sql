-- 053: vendor_commission_rules — tabla unificada con histórico
--
-- Reemplaza las 3 columnas dispersas en users:
--   by_commission (TINYINT)
--   commission_perc (INT)
--   apply_underprice_penalty_5pct (TINYINT)
--
-- por una tabla con UNA fila por regla activa por vendedor. Beneficios:
--
--   1. HISTÓRICO: cuando se cambia el % del vendedor, la regla vieja queda
--      con valid_to=ayer, is_active=0; la nueva entra con valid_from=hoy.
--      Así una liquidación retroactiva del mes pasado usa el % de ese mes.
--
--   2. AUDITORÍA: created_by + created_at en cada regla → quién y cuándo
--      cambió las comisiones de cada vendedor.
--
--   3. ESCALABILIDAD: futuro — agregar reglas tipo override por categoría,
--      por bodega, por cliente, etc. solo requiere agregar rule_kind.
--
-- Estrategia dual-write durante transición v2.0.2:
--   · Migration crea la tabla y migra desde users.*
--   · Commissions_lib lee de aquí; fallback a users.* si no hay regla
--   · Vendors controller escribe a AMBOS (rules + users.* sincronizados)
--   · En v2.1.0 se eliminan las columnas viejas de users
--
-- Eliminar la migración no es trivial: si se rollback, hay que recopiar
-- los valores actuales de las rules a users.*. Por eso el dual-write es
-- safety net.

CREATE TABLE IF NOT EXISTS `vendor_commission_rules` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `vendor_id` VARCHAR(100) NOT NULL,
    `rule_kind` ENUM('by_commission', 'underprice_penalty_5pct') NOT NULL,
    `percentage` DECIMAL(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Para by_commission: % del vendedor. Para penalty: 5.00 (constante).',
    `valid_from` DATE NOT NULL,
    `valid_to` DATE NULL COMMENT 'NULL = vigente sin fin',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `created_by` VARCHAR(100) NULL,
    `notes` VARCHAR(255) NULL,
    PRIMARY KEY (`id`),
    KEY `idx_vendor_active` (`vendor_id`, `is_active`),
    KEY `idx_dates` (`valid_from`, `valid_to`),
    KEY `idx_kind` (`rule_kind`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Reglas de comisión por vendedor con histórico. Reemplaza users.by_commission/commission_perc/apply_underprice_penalty_5pct';

-- Migrar datos actuales: por cada usuario con by_commission=1 → 1 fila
-- rule_kind='by_commission'. Por cada usuario con apply_underprice_penalty_5pct=1
-- → 1 fila rule_kind='underprice_penalty_5pct'.
INSERT INTO vendor_commission_rules (vendor_id, rule_kind, percentage, valid_from, is_active, created_at, created_by, notes)
SELECT idUser, 'by_commission', COALESCE(commission_perc, 0), '2020-01-01', 1, NOW(), 'migration_053',
       CONCAT('Migrado desde users.commission_perc (era ', COALESCE(commission_perc, 0), ')')
  FROM users
 WHERE by_commission = 1;

INSERT INTO vendor_commission_rules (vendor_id, rule_kind, percentage, valid_from, is_active, created_at, created_by, notes)
SELECT idUser, 'underprice_penalty_5pct', 5.00, '2020-01-01', 1, NOW(), 'migration_053',
       'Migrado desde users.apply_underprice_penalty_5pct'
  FROM users
 WHERE apply_underprice_penalty_5pct = 1;
