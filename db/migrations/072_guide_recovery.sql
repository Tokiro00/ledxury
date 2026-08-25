-- 072: Mesa de trabajo para recuperar las guias perdidas con la instancia
-- (23/08/2026). Guarda lo que responde ConsultarEstadosGuiasCliente de
-- Interrapidisimo por cada guia huerfana (las que estan en contrapagos o
-- cortes pero no existen en shipping_guides porque se perdieron).
CREATE TABLE IF NOT EXISTS guide_recovery (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_guia VARCHAR(50) NOT NULL,
    estado_actual VARCHAR(80) NULL,
    id_estado INT NULL,
    fecha_primer_estado DATETIME NULL,
    fecha_ultimo_estado DATETIME NULL,
    ciudad_origen VARCHAR(120) NULL,
    ciudad_destino VARCHAR(120) NULL,
    motivo_devolucion VARCHAR(255) NULL,
    raw_json MEDIUMTEXT NULL,
    consultada_at DATETIME NULL,
    applied_shipping_guide TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_guia (numero_guia)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
