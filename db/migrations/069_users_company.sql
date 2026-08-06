-- 069: etiqueta de empresa externa en usuarios.
--
-- Empresas externas (ej. Axonia) que usan nuestro canal de ventas/envíos con
-- su propio vendedor. NULL = Ledxury (comportamiento normal). Permite cruzar y
-- conciliar la actividad de cada empresa por su(s) vendedor(es): todo lo que
-- crea ese vendedor (budgets/invoices/guías con su vendorId) es de esa empresa.

ALTER TABLE `users`
  ADD COLUMN `company` VARCHAR(50) DEFAULT NULL
    COMMENT 'Empresa externa que usa el canal (ej: axonia); NULL = Ledxury'
    AFTER `is_vendor`;
