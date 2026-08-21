-- ============================================================================
-- 071: Libro diario con asientos compuestos (N líneas)
--
-- Se ejecuta contra mamdb (producción). Idempotente.
--
-- POR QUÉ
-- La pantalla de asiento manual que había (sisvent/accounting/entries/add) solo
-- permitía UNA línea de débito y UNA de crédito, y además guardaba
-- entryStatus = 'activo' en una columna int(1): el asiento nacía con estado 0,
-- o sea muerto. Nunca se creó un solo asiento manual en toda la historia de la
-- base — la pantalla no servía.
--
-- Este es el modelo del ERP de stockaccessories, que Alex quiere replicar:
-- un asiento como lo entiende un contador (encabezado + N líneas, estilo
-- CONTASOL/SAP), que se captura tal cual y además se descompone en pares
-- balanceados hacia `entries`, para que todos los reportes que ya existen —
-- que asumen un débito y un crédito por fila — sigan funcionando sin cambios.
--
-- DIFERENCIA CON stockaccessories: aquí las líneas llevan auxiliar. El plan de
-- cuentas de Ledxury depende de ellos (proveedores en 220505, comisiones de bot
-- en 233525, anticipos en 136525), así que sin auxiliar no se podría asentar
-- contra MAM ni contra una persona.
-- ============================================================================

-- Encabezado del asiento, tal como se captura.
CREATE TABLE IF NOT EXISTS `entry_groups` (
  `id`          INT(11) NOT NULL AUTO_INCREMENT,
  `group_date`  DATE NOT NULL,
  `description` VARCHAR(255) NOT NULL,
  `store_id`    INT(11) NOT NULL DEFAULT 1,
  `total`       DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `created_by`  VARCHAR(100) DEFAULT NULL,
  `created_at`  DATETIME DEFAULT NULL,
  `deleted`     TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_eg_date` (`group_date`),
  KEY `idx_eg_deleted` (`deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Las líneas como las digitó el usuario. `debe` y `haber` son excluyentes:
-- una línea tiene una cosa o la otra, nunca las dos.
CREATE TABLE IF NOT EXISTS `entry_group_lines` (
  `id`            INT(11) NOT NULL AUTO_INCREMENT,
  `group_id`      INT(11) NOT NULL,
  `ord`           INT(11) NOT NULL DEFAULT 1,
  `subaccount_id` INT(11) NOT NULL,
  `aux_id`        INT(11) DEFAULT NULL,
  `concepto`      VARCHAR(255) DEFAULT NULL,
  `debe`          DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `haber`         DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `idx_egl_group` (`group_id`),
  KEY `idx_egl_sub` (`subaccount_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Enlaza cada par de `entries` con el asiento del que salió, para poder volver
-- del mayor al asiento como lo capturó el usuario.
SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'entries'
             AND COLUMN_NAME = 'entryGroupId');
SET @s := IF(@c = 0,
    'ALTER TABLE `entries` ADD COLUMN `entryGroupId` INT(11) NULL DEFAULT NULL AFTER `entryTransactionId`',
    'SELECT "entries.entryGroupId ya existe" AS nota');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @k := (SELECT COUNT(*) FROM information_schema.STATISTICS
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'entries'
             AND INDEX_NAME = 'idx_entries_group');
SET @s := IF(@k = 0,
    'ALTER TABLE `entries` ADD INDEX `idx_entries_group` (`entryGroupId`)',
    'SELECT "el indice ya existe" AS nota');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- ============================================================================
-- VERIFICACIÓN
-- ============================================================================
-- SHOW CREATE TABLE entry_groups;
-- SHOW CREATE TABLE entry_group_lines;
-- SHOW COLUMNS FROM entries LIKE 'entryGroupId';
