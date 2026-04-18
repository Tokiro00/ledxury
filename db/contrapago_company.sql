-- Agregar columna company para manejar guias de otra empresa del grupo (MAM)
ALTER TABLE `contrapago_payments`
  ADD COLUMN `company` ENUM('ledxury','mam') DEFAULT 'ledxury' AFTER `invoice_id`,
  ADD KEY `idx_company` (`company`);

ALTER TABLE `contrapago_invoice_items`
  ADD COLUMN `company` ENUM('ledxury','mam') DEFAULT 'ledxury' AFTER `invoice_system_id`,
  ADD KEY `idx_company` (`company`);
