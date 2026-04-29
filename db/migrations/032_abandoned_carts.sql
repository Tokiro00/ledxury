-- ============================================================================
-- 032_abandoned_carts.sql
-- Carritos abandonados de la tienda pública.
-- Cuando un cliente llega al /checkout y empieza a llenar el form, el JS
-- guarda lo que tenga via /tienda/saveCart. Si en 24h no termina el pedido,
-- un cron le manda WhatsApp para invitarlo a completar.
-- ============================================================================

CREATE TABLE IF NOT EXISTS `tienda_abandoned_carts` (
    `id`            INT AUTO_INCREMENT PRIMARY KEY,
    `phone`         VARCHAR(20)  NOT NULL,
    `name`          VARCHAR(255) DEFAULT NULL,
    `email`         VARCHAR(255) DEFAULT NULL,
    `address`       VARCHAR(255) DEFAULT NULL,
    `cart_json`     LONGTEXT     NOT NULL COMMENT 'Array de items: [{id, name, price, qty}]',
    `cart_total`    INT          DEFAULT 0,
    `ip`            VARCHAR(45)  DEFAULT NULL,
    `user_agent`    VARCHAR(255) DEFAULT NULL,
    `status`        ENUM('pending','reminded','recovered','expired','optout')
                    NOT NULL DEFAULT 'pending'
                    COMMENT 'pending=esperando, reminded=ya le mandamos WA, recovered=convirtio, expired=>72h, optout=cliente no quiere',
    `reminded_at`   DATETIME     DEFAULT NULL,
    `recovered_budget_id` INT     DEFAULT NULL COMMENT 'idBudget si terminó comprando',
    `created_at`    DATETIME     DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_phone_status` (`phone`, `status`),
    INDEX `idx_status_created` (`status`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
