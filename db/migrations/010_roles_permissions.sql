-- =============================================================
-- 010: Tabla role_permissions para gestion de permisos por rol
-- =============================================================
-- La tabla `roles` ya debe existir en la base de datos.
-- Esta migracion crea la tabla `role_permissions` y asegura
-- que la tabla `roles` tenga las columnas necesarias.

-- Agregar columnas faltantes a roles (si no existen)
ALTER TABLE `roles` ADD COLUMN IF NOT EXISTS `deleted` TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE `roles` ADD COLUMN IF NOT EXISTS `deleted_at` DATETIME DEFAULT NULL;
ALTER TABLE `roles` ADD COLUMN IF NOT EXISTS `created_at` DATETIME DEFAULT NULL;
ALTER TABLE `roles` ADD COLUMN IF NOT EXISTS `updated_at` DATETIME DEFAULT NULL;

-- Crear tabla de permisos por rol
CREATE TABLE IF NOT EXISTS `role_permissions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `role_id` INT(11) NOT NULL,
  `module_key` VARCHAR(100) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_role_module` (`role_id`, `module_key`),
  KEY `idx_role_id` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
