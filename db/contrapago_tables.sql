CREATE TABLE IF NOT EXISTS `contrapago_batches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL,
  `sheet_name` varchar(100) DEFAULT NULL,
  `total_guias` int(11) DEFAULT 0,
  `total_valor` decimal(15,2) DEFAULT 0.00,
  `fecha_pago` date DEFAULT NULL,
  `banco` varchar(100) DEFAULT NULL,
  `matched` int(11) DEFAULT 0,
  `unmatched` int(11) DEFAULT 0,
  `cash_movement_id` int(11) DEFAULT NULL,
  `status` enum('importado','conciliado','registrado') DEFAULT 'importado',
  `created_by` varchar(20) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `contrapago_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `batch_id` int(11) NOT NULL,
  `numeroGuia` varchar(50) NOT NULL,
  `fechaVenta` datetime DEFAULT NULL,
  `valorTotal` decimal(15,2) NOT NULL DEFAULT 0.00,
  `nombreDestinatario` varchar(255) DEFAULT NULL,
  `conciliacion` varchar(50) DEFAULT NULL,
  `fechaPago` date DEFAULT NULL,
  `valorPago` decimal(15,2) DEFAULT NULL,
  `banco` varchar(100) DEFAULT NULL,
  `observacion` text DEFAULT NULL,
  `shipping_guide_id` int(11) DEFAULT NULL,
  `invoice_id` int(11) DEFAULT NULL,
  `status` enum('pendiente','conciliado','sin_match') DEFAULT 'pendiente',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_batch` (`batch_id`),
  KEY `idx_guia` (`numeroGuia`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
