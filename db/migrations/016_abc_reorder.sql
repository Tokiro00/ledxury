-- ============================================================
-- Migration 016: ABC Classification & Supplier Reorder System
-- ============================================================

-- 1. Add ABC classification to products
ALTER TABLE products
  ADD COLUMN abc_type ENUM('A','B','C','N') NOT NULL DEFAULT 'N' AFTER provider,
  ADD COLUMN abc_revenue DECIMAL(15,2) DEFAULT 0 AFTER abc_type,
  ADD COLUMN abc_calculated_at DATETIME DEFAULT NULL AFTER abc_revenue;

ALTER TABLE products ADD INDEX idx_abc_type (abc_type);

-- 2. Multi-provider per product
CREATE TABLE IF NOT EXISTS product_providers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  productId VARCHAR(50) NOT NULL,
  providerId INT NOT NULL,
  cost DECIMAL(15,2) DEFAULT 0,
  leadTimeDays INT DEFAULT 30,
  minOrderQty INT DEFAULT 1,
  isDefault TINYINT(1) DEFAULT 0,
  priority INT DEFAULT 1,
  is_active TINYINT(1) DEFAULT 1,
  created_at DATETIME DEFAULT NULL,
  updated_at DATETIME DEFAULT NULL,
  UNIQUE KEY uk_product_provider (productId, providerId),
  INDEX idx_provider (providerId),
  INDEX idx_default (productId, isDefault)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Seed from existing products.provider
INSERT IGNORE INTO product_providers (productId, providerId, cost, isDefault, priority, created_at, updated_at)
SELECT p.idProduct, p.provider, p.cost_cop, 1, 1, NOW(), NOW()
FROM products p
WHERE p.deleted = 0 AND p.provider IS NOT NULL AND p.provider > 0;

-- 3. Supplier orders (ordenes de compra a proveedores)
CREATE TABLE IF NOT EXISTS supplier_orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  orderNumber VARCHAR(30) NOT NULL,
  providerId INT NOT NULL,
  storeId INT NOT NULL,
  status ENUM('borrador','pendiente','enviada','parcial','recibida','cancelada') DEFAULT 'borrador',
  total DECIMAL(15,2) DEFAULT 0,
  orderDate DATE NOT NULL,
  expectedDate DATE DEFAULT NULL,
  receivedDate DATE DEFAULT NULL,
  observations TEXT,
  generatedBy ENUM('manual','agente') DEFAULT 'manual',
  created_by VARCHAR(100),
  created_at DATETIME DEFAULT NULL,
  updated_at DATETIME DEFAULT NULL,
  deleted TINYINT(4) DEFAULT 0,
  deleted_at DATETIME DEFAULT NULL,
  UNIQUE KEY uk_order_number (orderNumber),
  INDEX idx_provider (providerId),
  INDEX idx_store (storeId),
  INDEX idx_status (status),
  INDEX idx_deleted (deleted)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 4. Supplier order details with transit tracking
CREATE TABLE IF NOT EXISTS supplier_order_details (
  id INT AUTO_INCREMENT PRIMARY KEY,
  orderId INT NOT NULL,
  productId VARCHAR(50) NOT NULL,
  quantityOrdered INT NOT NULL DEFAULT 0,
  quantityReceived INT NOT NULL DEFAULT 0,
  unitCost DECIMAL(15,2) DEFAULT 0,
  subtotal DECIMAL(15,2) DEFAULT 0,
  status ENUM('pendiente','parcial','recibido') DEFAULT 'pendiente',
  INDEX idx_order (orderId),
  INDEX idx_product (productId),
  INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 5. Add permission key for reorder module
INSERT IGNORE INTO role_permissions (role_id, module_key, created_at)
SELECT r.idRoles, 'compras_reorden', NOW()
FROM roles r WHERE r.idRoles = 1;
