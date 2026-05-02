-- Bot commissions module
-- Config por usuario (admin, operador, publicidad) con % y a qué aplica
-- Períodos: del 21 del mes anterior al 20 del mes actual
-- Detalle por período y usuario
-- Items: facturas específicas que componen la comisión (histórico fino)

CREATE TABLE IF NOT EXISTS `bot_commission_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(100) NOT NULL,
  `commission_type` enum('admin_bots','operator','ads_manager') NOT NULL,
  `percentage` decimal(5,2) NOT NULL,
  `applies_to` varchar(50) DEFAULT 'all',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `bot_commission_periods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `period_label` varchar(50) NOT NULL,
  `total_cobrado` decimal(15,2) DEFAULT 0.00,
  `total_comisiones` decimal(15,2) DEFAULT 0.00,
  `status` enum('abierto','liquidado') DEFAULT 'abierto',
  `liquidated_by` varchar(20) DEFAULT NULL,
  `liquidated_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_period` (`period_start`,`period_end`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `bot_commission_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `period_id` int(11) NOT NULL,
  `user_id` varchar(100) NOT NULL,
  `user_name` varchar(255) DEFAULT NULL,
  `commission_type` varchar(50) NOT NULL,
  `percentage` decimal(5,2) NOT NULL,
  `base_amount` decimal(15,2) NOT NULL,
  `commission_amount` decimal(15,2) NOT NULL,
  `bot_config_id` int(11) DEFAULT NULL,
  `bot_name` varchar(100) DEFAULT NULL,
  `status` enum('pendiente','pagado') DEFAULT 'pendiente',
  `paid_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_period` (`period_id`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Items: facturas que componen cada línea de bot_commission_details
-- Permite al vendedor ver EN CADA PERIODO histórico qué facturas generaron su comisión.
CREATE TABLE IF NOT EXISTS `bot_commission_invoice_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `period_id` int(11) NOT NULL,
  `detail_id` int(11) NOT NULL,
  `user_id` varchar(100) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `budget_id` int(11) DEFAULT NULL,
  `client_id` varchar(50) DEFAULT NULL,
  `client_name` varchar(255) DEFAULT NULL,
  `vendor_id` varchar(100) DEFAULT NULL,
  `vendor_name` varchar(255) DEFAULT NULL,
  `bot_config_id` int(11) DEFAULT NULL,
  `invoice_date` datetime DEFAULT NULL,
  `invoice_total` decimal(15,2) DEFAULT 0.00,
  `percentage` decimal(5,2) NOT NULL,
  `commission_amount` decimal(15,2) NOT NULL,
  `invoice_state` tinyint(4) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_period` (`period_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_detail` (`detail_id`),
  KEY `idx_invoice` (`invoice_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
