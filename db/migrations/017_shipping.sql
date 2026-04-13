-- ============================================================
-- Migration 017: Interrapidisimo Shipping Integration
-- ============================================================

CREATE TABLE IF NOT EXISTS shipping_guides (
  id INT AUTO_INCREMENT PRIMARY KEY,
  invoiceId INT NOT NULL,
  numeroPreenvio BIGINT DEFAULT NULL,
  idPreenvio INT DEFAULT NULL,
  status VARCHAR(30) DEFAULT 'cotizado',
  -- Datos del envío
  peso DECIMAL(10,2) DEFAULT 0,
  largo DECIMAL(10,2) DEFAULT 10,
  ancho DECIMAL(10,2) DEFAULT 10,
  alto DECIMAL(10,2) DEFAULT 10,
  valorDeclarado DECIMAL(15,2) DEFAULT 0,
  diceContener VARCHAR(100) DEFAULT '',
  idServicio INT DEFAULT 3,
  idTipoEnvio INT DEFAULT 3,
  idTipoEntrega INT DEFAULT 1,
  -- Costos
  valorFlete DECIMAL(15,2) DEFAULT 0,
  valorSeguro DECIMAL(15,2) DEFAULT 0,
  valorTotal DECIMAL(15,2) DEFAULT 0,
  -- Destino
  ciudadDestinoId VARCHAR(10) DEFAULT '',
  ciudadDestinoNombre VARCHAR(100) DEFAULT '',
  -- Tracking
  estadoGuia INT DEFAULT 0,
  estadoNombre VARCHAR(50) DEFAULT '',
  fechaEstado DATETIME DEFAULT NULL,
  -- Recogida
  idRecogida INT DEFAULT NULL,
  -- Metadata
  guiaPdf LONGTEXT DEFAULT NULL,
  observations TEXT DEFAULT NULL,
  created_by VARCHAR(100),
  created_at DATETIME DEFAULT NULL,
  updated_at DATETIME DEFAULT NULL,
  INDEX idx_invoice (invoiceId),
  INDEX idx_preenvio (numeroPreenvio),
  INDEX idx_status (status)

) ENGINE=InnoDB DEFAULT CHARSET=utf8;
