-- Migration 023: Códigos OTP para verificación de pedidos en la tienda pública.
-- El cliente entra su celular, recibe un código de 6 dígitos por WhatsApp,
-- lo escribe y obtiene una sesión de 1h para ver SOLO sus pedidos.

CREATE TABLE IF NOT EXISTS tienda_otp_codes (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    phone       VARCHAR(20)  NOT NULL,
    code        VARCHAR(6)   NOT NULL,
    expires_at  DATETIME     NOT NULL,
    used        TINYINT(1)   NOT NULL DEFAULT 0,
    ip          VARCHAR(45)  NULL,
    created_at  DATETIME     DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_phone_active (phone, used, expires_at),
    INDEX idx_throttle    (phone, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
