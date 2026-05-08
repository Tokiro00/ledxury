-- 052: Renombrar new_settlement_method → apply_underprice_penalty_5pct
--
-- El flag activa la regla "castigar venta subprecio bajando comisión a 5%".
-- El nombre antiguo "new_settlement_method" no describe nada.
--
-- Estrategia dual-write para no romper código legacy en mam_helper.php que
-- referencia el campo viejo (las funciones quedaron como fallback). Ambos
-- campos coexisten un tiempo; el código nuevo lee/escribe el nuevo, el viejo
-- queda sincronizado por sí mismo (mismo valor en INSERT/UPDATE).
--
-- Eliminar new_settlement_method en v2.1.0 después de unas semanas de
-- validación.

ALTER TABLE `users`
    ADD COLUMN `apply_underprice_penalty_5pct` TINYINT(1) NOT NULL DEFAULT 0
    COMMENT 'Si activo y vende un ítem bajo precio mínimo, comisión cae a 5%' AFTER `new_settlement_method`;

UPDATE `users`
   SET `apply_underprice_penalty_5pct` = COALESCE(`new_settlement_method`, 0);
