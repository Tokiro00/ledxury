SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS tracking_weekly (
  id INT AUTO_INCREMENT PRIMARY KEY,
  year INT NOT NULL DEFAULT 2026,
  month INT NOT NULL,
  week INT NOT NULL,
  vendorId VARCHAR(100) NOT NULL,
  ventas BIGINT DEFAULT 0,
  cobros BIGINT DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_week_vendor (year, month, week, vendorId),
  KEY idx_month (year, month)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS tracking_weekly_extras (
  id INT AUTO_INCREMENT PRIMARY KEY,
  year INT NOT NULL DEFAULT 2026,
  month INT NOT NULL,
  week INT NOT NULL,
  cartera_total BIGINT DEFAULT 0,
  inventario BIGINT DEFAULT 0,
  gastos_semana BIGINT DEFAULT 0,
  notas TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_week (year, month, week)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS cierre_mensual (
  id INT AUTO_INCREMENT PRIMARY KEY,
  year INT NOT NULL,
  month INT NOT NULL,
  ventas_brutas BIGINT DEFAULT 0,
  desc_pp BIGINT DEFAULT 0,
  sueldos_adm BIGINT DEFAULT 0,
  sueldo_vend BIGINT DEFAULT 0,
  seg_social BIGINT DEFAULT 0,
  beneficios BIGINT DEFAULT 0,
  comisiones BIGINT DEFAULT 0,
  arriendo BIGINT DEFAULT 0,
  reparacion BIGINT DEFAULT 0,
  viaticos BIGINT DEFAULT 0,
  equipos BIGINT DEFAULT 0,
  fletes BIGINT DEFAULT 0,
  legales BIGINT DEFAULT 0,
  impuestos BIGINT DEFAULT 0,
  intereses BIGINT DEFAULT 0,
  castigo BIGINT DEFAULT 0,
  otros_gastos BIGINT DEFAULT 0,
  cobros_clientes BIGINT DEFAULT 0,
  antic_baq BIGINT DEFAULT 0,
  pago_china BIGINT DEFAULT 0,
  prov_nacionales BIGINT DEFAULT 0,
  prestamo_empl BIGINT DEFAULT 0,
  retiro_accionistas BIGINT DEFAULT 0,
  pago_baq BIGINT DEFAULT 0,
  mov_bancarios BIGINT DEFAULT 0,
  cartera_total BIGINT DEFAULT 0,
  inventario BIGINT DEFAULT 0,
  caja_bancos BIGINT DEFAULT 0,
  utilidad_operativa BIGINT DEFAULT 0,
  bono_ventas BIGINT DEFAULT 0,
  bono_recaudo BIGINT DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_month (year, month)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

SET FOREIGN_KEY_CHECKS = 1;
