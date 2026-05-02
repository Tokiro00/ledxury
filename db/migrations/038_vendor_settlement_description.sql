-- 038: Persistir el string de descripción en la cabecera de la liquidación.
--
-- Hoy expenses.description guarda "Liquidación de Juan Facturas: (123) ..."
-- Pero ese string sólo existe DESPUÉS de aprobar (cuando se crea el expense).
-- Para soportar el workflow de Fase 3 (calcular → pagar) necesitamos guardar
-- la descripción al momento del cálculo, antes de existir el expense.
ALTER TABLE `vendor_settlements`
  ADD COLUMN `description` TEXT DEFAULT NULL AFTER `notes`;
