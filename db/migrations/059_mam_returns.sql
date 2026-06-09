-- ============================================================================
-- 059: Tabla mam_returns + mam_return_items
-- ============================================================================
-- Registro de devoluciones físicas de Ledxury a MAM. No genera asiento
-- contable (Ledxury opera sin inventario en libros). Solo trazabilidad +
-- baja del stock al confirmar.
-- ============================================================================

CREATE TABLE IF NOT EXISTS mam_returns (
    id              INT(11) NOT NULL AUTO_INCREMENT,
    provider_id     INT(11) NOT NULL DEFAULT 12 COMMENT 'MAM por default',
    return_date     DATE NOT NULL,
    return_code     VARCHAR(30) NULL COMMENT 'DEV-MAM-YYYYMMDD-HHMMSS',
    total_units     INT(11) NOT NULL DEFAULT 0,
    total_skus      INT(11) NOT NULL DEFAULT 0,
    status          ENUM('borrador','entregado','anulado') NOT NULL DEFAULT 'entregado',
    notes           TEXT NULL,
    created_by      VARCHAR(100) NULL,
    delivered_to    VARCHAR(150) NULL COMMENT 'Persona MAM que recibe',
    delivered_at    DATETIME NULL,
    created_at      DATETIME NOT NULL,
    updated_at      DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted         TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_provider (provider_id),
    KEY idx_date (return_date),
    KEY idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS mam_return_items (
    id              INT(11) NOT NULL AUTO_INCREMENT,
    mam_return_id   INT(11) NOT NULL,
    product_id      VARCHAR(50) NOT NULL,
    store_id        INT(11) NOT NULL DEFAULT 1,
    qty             INT(11) NOT NULL,
    notes           VARCHAR(255) NULL,
    created_at      DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_return (mam_return_id),
    KEY idx_product (product_id),
    CONSTRAINT fk_mam_return FOREIGN KEY (mam_return_id) REFERENCES mam_returns(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SELECT 'Migration 059 aplicada' AS resultado;
