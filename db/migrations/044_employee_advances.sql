-- 044: Anticipos a vendedores cruzables con liquidaciones (Fase L.1)
--
-- Sistema portado de Lumen: cuando se le da plata a un vendedor antes de
-- liquidar, queda como anticipo (cuenta por cobrar al empleado). Al
-- liquidar comisiones, se cruza FIFO contra el saldo pendiente del
-- vendedor antes de pagar el neto.
--
-- Cuentas PUC nuevas:
--   1365 Cuentas por cobrar a trabajadores (cuenta a 4 dígitos)
--   136525 Anticipos sobre comisiones (subcuenta postable, asset, débito)
--
-- Tablas:
--   employee_advances             — un row por anticipo entregado
--   settlement_advance_payments   — audit FIFO de cada cruce con liquidación
--
-- accounting_settings.account_employee_advance → subaccount 136525

-- ── 1. Cuenta PUC 1365 bajo grupo 13 Deudores ──────────────────────────────
INSERT INTO accounts_accounts (accountID, groupID, accountName, accountDescription, pucCode, created_at)
SELECT * FROM (
    SELECT 1365 AS accountID, 13 AS groupID, 'Cuentas por cobrar a trabajadores' AS accountName,
           'Anticipos a empleados (sueldos, comisiones, viáticos)' AS accountDescription,
           '1365' AS pucCode, NOW() AS created_at
) tmp
WHERE NOT EXISTS (SELECT 1 FROM accounts_accounts WHERE pucCode='1365' AND deleted=0);

-- ── 2. Subcuenta postable 136525 ───────────────────────────────────────────
INSERT INTO subaccounts (accountID, accountName, accountAccount, accountSide, accountBalance, accountDebit, accountCredit, accountOrder, accountStatus, accountStatement, accountType, pucCode, store, created_at)
SELECT * FROM (
    SELECT 1365 AS accountID, 'Anticipos a vendedores' AS accountName, 136525 AS accountAccount,
           '1' AS accountSide, 0.00 AS accountBalance, 0.00 AS accountDebit, 0.00 AS accountCredit,
           22 AS accountOrder, 1 AS accountStatus, '1' AS accountStatement, 'asset' AS accountType,
           '136525' AS pucCode, 1 AS store, NOW() AS created_at
) tmp
WHERE NOT EXISTS (SELECT 1 FROM subaccounts WHERE pucCode='136525' AND store=1 AND deleted=0);

-- ── 3. accounting_settings ─────────────────────────────────────────────────
INSERT INTO accounting_settings (setting_key, subaccount_id, created_at, updated_at)
SELECT 'account_employee_advance', (SELECT id FROM subaccounts WHERE pucCode='136525' AND store=1 AND deleted=0 LIMIT 1), NOW(), NOW()
ON DUPLICATE KEY UPDATE subaccount_id = VALUES(subaccount_id), updated_at = NOW();

-- ── 4. Tabla employee_advances ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `employee_advances` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `code` VARCHAR(20) NOT NULL COMMENT 'Auto-generado AC-XXXXXX',
    `employee_id` VARCHAR(100) NOT NULL COMMENT 'idUser del vendedor (users.idUser)',
    `amount` DECIMAL(15,2) NOT NULL COMMENT 'Monto entregado',
    `outstanding_balance` DECIMAL(15,2) NOT NULL COMMENT 'Saldo pendiente de cruzar',
    `purpose` VARCHAR(255) NULL COMMENT 'Motivo (anticipo de comisión, viático, etc)',
    `type` ENUM('cash','credit','scheduled') NOT NULL DEFAULT 'cash' COMMENT 'cash=efectivo, credit=a futuro, scheduled=cuotas',

    -- Workflow
    `status` ENUM('pendiente','aprobado','desembolsado','pagado','anulado') NOT NULL DEFAULT 'pendiente',
    `approved_by` VARCHAR(100) NULL,
    `approved_at` DATETIME NULL,
    `disbursed_at` DATETIME NULL COMMENT 'Cuando salió la plata',
    `cancelled_at` DATETIME NULL,
    `cancellation_reason` TEXT NULL,

    -- Origen del dinero (cuando se desembolsa)
    `source_type` ENUM('caja','banco') NULL,
    `source_id` INT(11) NULL COMMENT 'FK a cashboxes o bank_accounts',
    `cash_movement_id` INT(11) NULL,

    -- Vínculo contable
    `entry_id` INT(11) NULL COMMENT 'FK al asiento de desembolso',
    `reversal_entry_id` INT(11) NULL COMMENT 'FK al asiento de reversa al anular',

    -- Bodega y meta
    `store_id` INT(11) NOT NULL DEFAULT 1,
    `created_by` VARCHAR(100) NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    `deleted` TINYINT(1) DEFAULT 0,
    `deleted_at` DATETIME NULL,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_code` (`code`),
    KEY `idx_employee` (`employee_id`),
    KEY `idx_status` (`status`),
    KEY `idx_balance` (`outstanding_balance`),
    KEY `idx_disbursed` (`disbursed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Anticipos a vendedores (cuenta por cobrar)';

-- ── 5. Tabla settlement_advance_payments (audit FIFO) ──────────────────────
CREATE TABLE IF NOT EXISTS `settlement_advance_payments` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `settlement_id` INT(11) NOT NULL COMMENT 'FK vendor_settlements.id',
    `advance_id` INT(11) NOT NULL COMMENT 'FK employee_advances.id',
    `amount_applied` DECIMAL(15,2) NOT NULL COMMENT 'Cuánto se cruzó',
    `applied_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `applied_by` VARCHAR(100) NULL,
    PRIMARY KEY (`id`),
    KEY `idx_settlement` (`settlement_id`),
    KEY `idx_advance` (`advance_id`),
    UNIQUE KEY `uk_settlement_advance` (`settlement_id`, `advance_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Audit FIFO de anticipos cruzados con liquidaciones';
