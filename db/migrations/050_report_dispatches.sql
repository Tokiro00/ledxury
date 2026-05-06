-- v1.30.0 — Audit log de reportes despachados
--
-- Cada vez que un reporte se renderiza/descarga/envía, se persiste una fila acá.
-- Permite responder reclamos del tipo "no recibí mi estado de cuenta" con
-- timestamp + canal + status. También cubre record-keeping de comunicaciones
-- outbound de WhatsApp Business.

CREATE TABLE IF NOT EXISTS report_dispatches (
  id INT PRIMARY KEY AUTO_INCREMENT,

  -- Qué reporte se despachó
  report_id VARCHAR(64) NOT NULL COMMENT 'ReportInterface::id() — ej. client_statement',

  -- En qué formato y por qué canal
  format ENUM('html','pdf','xlsx','csv') NOT NULL,
  channel ENUM('download','email','whatsapp','schedule') NOT NULL
    COMMENT 'download = renderizado interactivo en browser, no se mando a nadie',

  -- Para quién (si aplica)
  recipient VARCHAR(255) NULL COMMENT 'email o phone del destinatario',
  recipient_client_id INT NULL COMMENT 'FK clients.idClient (nullable, no todos los reportes son por cliente)',

  -- Filtros aplicados — para reproducir exactamente qué se generó si hay reclamo
  params_json JSON NULL,

  -- Quién y cuándo
  dispatched_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  dispatched_by VARCHAR(64) NOT NULL COMMENT 'session uname',

  -- Resultado
  status ENUM('sent','failed') DEFAULT 'sent',
  error_message TEXT NULL COMMENT 'Solo si status = failed',

  INDEX idx_report_client (report_id, recipient_client_id, dispatched_at),
  INDEX idx_dispatched_by (dispatched_by, dispatched_at),
  INDEX idx_channel_status (channel, status, dispatched_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
