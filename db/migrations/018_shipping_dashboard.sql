-- ============================================================
-- Migration 018: Shipping Dashboard - tracking events, DANE codes
-- ============================================================

-- 1. Mejorar shipping_guides con más campos
ALTER TABLE shipping_guides
  ADD COLUMN carrierId INT DEFAULT 1 AFTER invoiceId,
  ADD COLUMN carrierName VARCHAR(50) DEFAULT 'Interrapidisimo' AFTER carrierId,
  ADD COLUMN recipientName VARCHAR(200) DEFAULT '' AFTER ciudadDestinoNombre,
  ADD COLUMN recipientPhone VARCHAR(25) DEFAULT '' AFTER recipientName,
  ADD COLUMN recipientAddress TEXT AFTER recipientPhone,
  ADD COLUMN recipientDoc VARCHAR(25) DEFAULT '' AFTER recipientAddress,
  ADD COLUMN isContrapago TINYINT DEFAULT 0 AFTER idTipoEntrega,
  ADD COLUMN contrapagoCost DECIMAL(15,2) DEFAULT 0 AFTER isContrapago,
  ADD COLUMN shippingCharged DECIMAL(15,2) DEFAULT 0 AFTER valorTotal,
  ADD COLUMN estimatedDelivery DATE DEFAULT NULL AFTER shippingCharged,
  ADD COLUMN actualDelivery DATETIME DEFAULT NULL AFTER estimatedDelivery,
  ADD COLUMN storeId INT DEFAULT 1 AFTER observations,
  ADD COLUMN lastTrackingCheck DATETIME DEFAULT NULL AFTER storeId,
  ADD INDEX idx_status_active (status, actualDelivery),
  ADD INDEX idx_store (storeId);

-- 2. Historial de tracking por guía
CREATE TABLE IF NOT EXISTS shipping_tracking_events (
  id INT AUTO_INCREMENT PRIMARY KEY,
  guideId INT NOT NULL,
  statusCode INT DEFAULT 0,
  statusName VARCHAR(50) DEFAULT '',
  description TEXT,
  location VARCHAR(100) DEFAULT '',
  eventDate DATETIME DEFAULT NULL,
  source ENUM('api','manual','webhook') DEFAULT 'api',
  created_at DATETIME DEFAULT NULL,
  INDEX idx_guide (guideId),
  INDEX idx_date (eventDate)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 3. Tabla de municipios DANE (se llena desde la API)
CREATE TABLE IF NOT EXISTS dane_municipalities (
  id INT AUTO_INCREMENT PRIMARY KEY,
  daneCode VARCHAR(10) NOT NULL,
  name VARCHAR(100) NOT NULL,
  shortName VARCHAR(100) DEFAULT '',
  department VARCHAR(100) DEFAULT '',
  postalCode VARCHAR(10) DEFAULT '',
  hasPickup TINYINT DEFAULT 0,
  UNIQUE KEY uk_dane (daneCode),
  INDEX idx_name (name),
  INDEX idx_short (shortName)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 4. Permiso para el módulo de envíos
INSERT IGNORE INTO role_permissions (role_id, module_key, created_at)
SELECT r.idRoles, 'envios', NOW()
FROM roles r WHERE r.idRoles IN (1, 2);
