-- 061: Flag semántico "actúa como vendedor" — desacopla la condición de
-- vendedor del rol del usuario. Un gerente/admin con is_vendor=1 aparece en
-- listas de vendedores, tableros y liquidaciones, y sus comisiones se
-- administran en el módulo Vendedores (vendor_commission_rules) como
-- cualquier rol 3. Sustituye el patrón "role = 3" por
-- "(role = 3 OR is_vendor = 1)" en los lectores.
--
-- Seed: Christina Morales (5210750, gerente) habilitada como vendedora con
-- comisión 7% (igual a los vendedores Germam) — cierra su regla previa del 3%.

ALTER TABLE users ADD COLUMN is_vendor TINYINT(1) NOT NULL DEFAULT 0
    COMMENT 'Actúa como vendedor (listas/comisiones) sin importar el rol';

-- Christina Morales: habilitar como vendedora
UPDATE users SET is_vendor = 1, commission_perc = 7 WHERE idUser = '5210750';

-- Cerrar su regla de comisión anterior (3%) y abrir la nueva al 7% (como Germam)
UPDATE vendor_commission_rules
   SET is_active = 0, valid_to = CURDATE()
 WHERE vendor_id = '5210750' AND rule_kind = 'by_commission' AND is_active = 1;

INSERT INTO vendor_commission_rules
    (vendor_id, rule_kind, percentage, valid_from, valid_to, is_active, created_at, created_by, notes)
VALUES
    ('5210750', 'by_commission', 7.00, CURDATE(), NULL, 1, NOW(), 'migration_061',
     'Habilitada como vendedora (is_vendor) con % igual a vendedores Germam');
