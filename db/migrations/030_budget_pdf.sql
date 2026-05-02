-- 030_budget_pdf.sql
-- Almacenamiento de presupuestos en PDF para consulta posterior por admins.
-- pdf_url: ruta relativa al PDF guardado (ej. "budgets/123.pdf")
-- pdf_generated_at: timestamp de la última generación

ALTER TABLE `budgets`
  ADD COLUMN `pdf_url` VARCHAR(255) DEFAULT NULL AFTER `printed`,
  ADD COLUMN `pdf_generated_at` DATETIME DEFAULT NULL AFTER `pdf_url`;
