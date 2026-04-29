-- Migration 025: Rate limiting básico para endpoints públicos
-- Cuenta requests por IP+endpoint en una ventana de tiempo. Si supera el límite, bloquea.

CREATE TABLE IF NOT EXISTS rate_limit (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    ip          VARCHAR(45)  NOT NULL,
    endpoint    VARCHAR(50)  NOT NULL,
    created_at  DATETIME     DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip_ep_time (ip, endpoint, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
