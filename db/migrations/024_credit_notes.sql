-- ============================================================
-- Migration 024: Notas Crédito / Devoluciones
-- ============================================================

CREATE TABLE IF NOT EXISTS credit_notes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  invoiceId INT DEFAULT NULL COMMENT 'Factura de origen (puede ser NULL si no aplica)',
  clientId INT NOT NULL,
  vendorId VARCHAR(100) NOT NULL,
  storeId INT NOT NULL DEFAULT 1,
  type ENUM('devolucion','garantia') DEFAULT 'devolucion',
  reason ENUM('defecto','dano','inconformidad','garantia','error_facturacion','otro') DEFAULT 'otro',
  total DECIMAL(15,2) DEFAULT 0,
  status ENUM('pendiente','aprobada','rechazada') DEFAULT 'pendiente',
  observations TEXT,
  rejection_reason VARCHAR(255) DEFAULT NULL,
  approved_by VARCHAR(100) DEFAULT NULL,
  approved_at DATETIME DEFAULT NULL,
  created_by VARCHAR(100) NOT NULL,
  created_at DATETIME DEFAULT NULL,
  updated_at DATETIME DEFAULT NULL,
  deleted TINYINT(1) DEFAULT 0,
  INDEX idx_client (clientId),
  INDEX idx_invoice (invoiceId),
  INDEX idx_vendor (vendorId),
  INDEX idx_status (status),
  INDEX idx_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS credit_note_details (
  id INT AUTO_INCREMENT PRIMARY KEY,
  creditNoteId INT NOT NULL,
  productId VARCHAR(50) NOT NULL,
  quantity INT NOT NULL DEFAULT 1,
  price DECIMAL(15,2) DEFAULT 0,
  subtotal DECIMAL(15,2) DEFAULT 0,
  `condition` ENUM('bueno','danado','defectuoso') DEFAULT 'bueno',
  INDEX idx_credit_note (creditNoteId),
  INDEX idx_product (productId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Permisos
INSERT IGNORE INTO role_permissions (role_id, module_key, created_at) VALUES
(2, 'notas_credito', NOW()),
(3, 'notas_credito', NOW()),
(9, 'notas_credito', NOW()),
(9, 'aprobar_notas_credito', NOW());
