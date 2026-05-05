-- 046: Tabla de despachos MAM (intercompany shipments)
--
-- Inter factura a Ledxury fletes que mezclan guías de Ledxury y guías
-- de MAM (las dos empresas comparten contrato Inter). Hoy la separación
-- se hace MANUAL via markCompany() botón por botón.
--
-- Esta tabla almacena el listado de despachos que MAM hizo, importado
-- desde el reporte Excel del ERP de MAM. Se usa para:
--   1. Auto-marcar items de facturas Inter como company='mam' cuando
--      su numero_guia coincide.
--   2. Auto-marcar contrapago_payments igual.
--   3. Alimentar el módulo intercompañías (CxC a MAM por fletes).
--
-- UNIQUE en numero_guia → re-importar el archivo NO duplica filas;
-- ON DUPLICATE KEY UPDATE refresca los datos (flete, fecha, etc).

CREATE TABLE IF NOT EXISTS `mam_dispatches` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `numero_guia`     BIGINT       NOT NULL COMMENT 'Número guía Inter (col F del Excel)',
    `factura_mam`     VARCHAR(50)  NULL COMMENT 'Número de factura interno de MAM (col A)',
    `fecha_despacho`  DATETIME     NULL,
    `cliente`         VARCHAR(255) NULL,
    `destino`         VARCHAR(255) NULL,
    `transportadora`  VARCHAR(50)  NULL DEFAULT 'Interrapidisimo',
    `cajas`           INT(11)      NULL,
    `peso`            DECIMAL(10,2) NULL,
    `valor_factura`   DECIMAL(15,2) NULL COMMENT 'Valor de la factura MAM',
    `flete`           DECIMAL(15,2) NULL COMMENT 'Flete cobrado al cliente',
    `vendedor`        VARCHAR(100) NULL,
    `separado_por`    VARCHAR(100) NULL,
    `despachado_por`  VARCHAR(100) NULL,
    `bodega`          VARCHAR(100) NULL,

    -- Auditoría
    `imported_filename` VARCHAR(255) NULL,
    `imported_by`     VARCHAR(50)  NULL,
    `imported_at`     DATETIME     DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_numero_guia` (`numero_guia`),
    KEY `idx_factura_mam` (`factura_mam`),
    KEY `idx_fecha` (`fecha_despacho`),
    KEY `idx_vendedor` (`vendedor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Despachos MAM con guías Inter — para tagging intercompany automático';
