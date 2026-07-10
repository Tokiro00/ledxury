-- 066: Flag de entrega por domicilio local en presupuestos.
--
-- Pedidos dentro del área metropolitana (Medellín) se entregan por
-- domiciliario en moto, sin guía Interrapidísimo, y el cliente paga la
-- factura directamente al recibir (un admin la marca pagada).
--
--   is_domicilio = 1 → domicilio local (icono de moto en listados,
--                      aviso "1-2 días hábiles" en tienda/mis-pedidos)
--   is_domicilio = 0 → flujo normal con guía de transportadora
--
-- Lo marca el vendedor al editar el presupuesto en el PWA de ventas.
-- No se reutiliza el catálogo legacy `delivery_type` (textos/costos 2024
-- desactualizados y semántica distinta).

ALTER TABLE `budgets`
  ADD COLUMN `is_domicilio` TINYINT(1) NOT NULL DEFAULT 0
    COMMENT '1 = entrega por domicilio local (moto); 0 = guia transportadora'
    AFTER `delivery_type`;
