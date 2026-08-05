-- 068: marca de envío del WhatsApp "tu pedido está en proceso de envío".
--
-- Cuando un cliente consulta su pedido en ledxury.com/tienda/mis-pedidos y el
-- pedido aún no tiene guía pero sí un presupuesto pendiente/aprobado, el sistema
-- le manda UN mensaje de tranquilidad por WhatsApp. Esta columna evita reenviarlo
-- en cada visita: se estampa la fecha del envío y no se vuelve a mandar.

ALTER TABLE `budgets`
  ADD COLUMN `proceso_wa_at` DATETIME NULL
    COMMENT 'Fecha en que se envio el WhatsApp de "en proceso de envio" (una sola vez)'
    AFTER `is_domicilio`;
