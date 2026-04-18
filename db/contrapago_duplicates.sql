-- Agregar estados 'duplicada' y columna para referenciar el pago original
ALTER TABLE `contrapago_payments`
  MODIFY COLUMN `status` ENUM('pendiente','conciliado','sin_match','duplicada') DEFAULT 'pendiente',
  ADD COLUMN `duplicate_of_id` INT(11) DEFAULT NULL AFTER `company`,
  ADD KEY `idx_duplicate` (`duplicate_of_id`);
