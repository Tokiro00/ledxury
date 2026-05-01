-- 037: Modelo de datos estructurado para liquidaciones de vendedor
--
-- Hoy una liquidación = una fila en `expenses` con `description` como string
-- concatenado tipo "Liquidación de Juan Facturas: (123) (456) Comisión: (789)".
-- No hay forma de auditar qué regla aplicó a qué factura ni reproducir el cálculo.
--
-- Esta migración agrega 3 tablas que conviven con la lógica actual:
--   * vendor_settlements         → cabecera (1 fila por click "Liquidar")
--   * vendor_settlement_items    → detalle por factura procesada
--   * vendor_settlement_vouchers → vales consumidos en la liquidación
-- y un FK opcional desde `expenses` para drill-down.
--
-- Importante: la comisión se causa al RECAUDO (factura state=2). Las nuevas
-- tablas espejan esa semántica — `invoice_id` apunta a una factura ya pagada.
-- Próximas fases (engine de reglas, workflow draft→approved→paid, vista detalle)
-- se construirán sobre este modelo sin tocar lo existente.

-- ── Cabecera: una liquidación por vendedor ───────────────────────────────
CREATE TABLE IF NOT EXISTS `vendor_settlements` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `vendor_id` VARCHAR(100) NOT NULL,
  `vendor_name` VARCHAR(255) DEFAULT NULL,
  `store_id` INT(11) DEFAULT NULL,

  -- Rango informativo (cuando se filtra por fechas, opcional)
  `period_start` DATE DEFAULT NULL,
  `period_end`   DATE DEFAULT NULL,

  -- Totales calculados (snapshot del momento de liquidar)
  `invoice_count`     INT(11) DEFAULT 0,
  `voucher_count`     INT(11) DEFAULT 0,
  `total_recaudado`   DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Suma de invoice.total de las facturas recaudadas incluidas',
  `total_comision`    DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Suma positiva de comisiones causadas',
  `total_descuentos`  DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Ajustes negativos: legal_collection, descuento, e_commerce, etc.',
  `total_vouchers`    DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Vales consumidos (descontados del neto)',
  `total_neto`        DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Neto a pagar (puede ser negativo si quedó debiendo el vendedor)',

  -- Workflow (Fase 1: solo se usa "pagado" porque el flujo actual es 1-paso;
  -- Fase 3 introducirá calculado→aprobado→pagado real)
  `status` ENUM('calculado','aprobado','pagado','reversado') NOT NULL DEFAULT 'pagado',
  `expense_id` INT(11) DEFAULT NULL COMMENT 'FK expenses.idExpense — gasto contable creado',
  `notes` TEXT DEFAULT NULL,

  `created_by` VARCHAR(20) DEFAULT NULL,
  `created_at` DATETIME DEFAULT current_timestamp(),
  `approved_by` VARCHAR(20) DEFAULT NULL,
  `approved_at` DATETIME DEFAULT NULL,
  `paid_by` VARCHAR(20) DEFAULT NULL,
  `paid_at` DATETIME DEFAULT NULL,
  `reversed_by` VARCHAR(20) DEFAULT NULL,
  `reversed_at` DATETIME DEFAULT NULL,

  PRIMARY KEY (`id`),
  KEY `idx_vendor` (`vendor_id`),
  KEY `idx_status` (`status`),
  KEY `idx_expense` (`expense_id`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Cabecera de liquidaciones de vendedor (1 fila por evento de Liquidar)';

-- ── Detalle: una fila por factura procesada en la liquidación ────────────
CREATE TABLE IF NOT EXISTS `vendor_settlement_items` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `settlement_id` INT(11) NOT NULL,
  `invoice_id` INT(11) NOT NULL,
  `payment_id` INT(11) DEFAULT NULL COMMENT 'Pago que cerró la factura, si se puede atribuir',

  -- Snapshot de la factura (para drill-down sin re-consultar)
  `invoice_date`  DATETIME DEFAULT NULL,
  `invoice_total` DECIMAL(15,2) DEFAULT 0.00,
  `client_id`     VARCHAR(50) DEFAULT NULL,
  `client_name`   VARCHAR(255) DEFAULT NULL,

  -- Cálculo
  `rule_applied` ENUM(
    'default',
    'by_commission',
    'list_price',
    'invoice_discount',
    'e_commerce',
    'iva',
    'legal_collection',
    'national_skipped',
    'blacklisted_skipped'
  ) NOT NULL COMMENT 'Cuál de las ramas del if/elseif de approve() aplicó',

  `is_underpriced`   TINYINT(1) DEFAULT 0 COMMENT '1 si new_settlement_method y algún ítem por debajo del precio (override 5%)',
  `is_self_invoice`  TINYINT(1) DEFAULT 0 COMMENT '1 si vendedor==cliente — la comisión RESTA en lugar de sumar',

  `not_settle_amount`  DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Suma de subtotales con detail.not_settle=1, excluidos de la base',
  `base_amount`        DECIMAL(15,2) NOT NULL COMMENT 'Base sobre la que se calcula la comisión',
  `percentage`         DECIMAL(5,2) DEFAULT 0.00,
  `commission_amount`  DECIMAL(15,2) NOT NULL COMMENT 'Resultado: positivo (suma) o negativo (resta self_invoice)',

  `notes`      VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME DEFAULT current_timestamp(),

  PRIMARY KEY (`id`),
  KEY `idx_settlement` (`settlement_id`),
  KEY `idx_invoice` (`invoice_id`),
  KEY `idx_rule` (`rule_applied`),
  UNIQUE KEY `uk_settlement_invoice` (`settlement_id`,`invoice_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Detalle de cada factura dentro de una liquidación';

-- ── Vales consumidos en la liquidación ──────────────────────────────────
CREATE TABLE IF NOT EXISTS `vendor_settlement_vouchers` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `settlement_id` INT(11) NOT NULL,
  `voucher_id` INT(11) NOT NULL,
  `voucher_value` DECIMAL(15,2) NOT NULL,
  `created_at` DATETIME DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_settlement` (`settlement_id`),
  KEY `idx_voucher` (`voucher_id`),
  UNIQUE KEY `uk_settlement_voucher` (`settlement_id`,`voucher_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Vales descontados por liquidación';

-- ── Vincular expenses con la liquidación estructurada ───────────────────
-- Permite drill-down: dado un gasto, recuperar el detalle por factura.
ALTER TABLE `expenses`
  ADD COLUMN `settlement_id` INT(11) DEFAULT NULL AFTER `deleted`,
  ADD KEY `idx_settlement` (`settlement_id`);
