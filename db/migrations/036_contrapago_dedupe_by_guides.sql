-- 036: Reescribir import_hash de contrapagos en función del set de guías
--
-- Causa: el hash original incluía el nombre de la hoja, así que un mismo lote
-- subido en archivos distintos (ej: "Hoja1" en inter1.xlsx vs "PAGO 3" en
-- MULTI ACCESORIOS.xlsx) generaba hashes distintos y se duplicaban los lotes.
--
-- Esta migración:
--   1. Elimina lotes duplicados (no registrados) cuyas guías ya están en otro
--      lote ya registrado en banco.
--   2. Recalcula import_hash de todos los lotes con la nueva fórmula:
--      MD5 del GROUP_CONCAT de números de guía ordenados.

-- 1) Identificar duplicados a eliminar: lotes en estado importado/conciliado
--    cuyo set de guías coincide exactamente con otro lote ya registrado.
DROP TABLE IF EXISTS `_dupes_to_delete`;
CREATE TABLE `_dupes_to_delete` (
  `batch_id` INT(11) NOT NULL,
  PRIMARY KEY (`batch_id`)
) ENGINE=InnoDB;

INSERT INTO `_dupes_to_delete` (batch_id)
SELECT b1.id
FROM contrapago_batches b1
WHERE b1.status IN ('importado','conciliado')
  AND EXISTS (
    SELECT 1
    FROM contrapago_batches b2
    WHERE b2.status = 'registrado'
      AND b2.id <> b1.id
      AND (
        SELECT MD5(GROUP_CONCAT(numeroGuia ORDER BY numeroGuia SEPARATOR ','))
        FROM contrapago_payments WHERE batch_id = b1.id
      ) = (
        SELECT MD5(GROUP_CONCAT(numeroGuia ORDER BY numeroGuia SEPARATOR ','))
        FROM contrapago_payments WHERE batch_id = b2.id
      )
  );

-- 2) Borrar pagos parciales (contrapago_invoice_payments) de los lotes duplicados.
DELETE cip FROM contrapago_invoice_payments cip
INNER JOIN `_dupes_to_delete` d ON d.batch_id = cip.batch_id;

-- 3) Borrar las filas de contrapago_payments de los lotes duplicados.
DELETE p FROM contrapago_payments p
INNER JOIN `_dupes_to_delete` d ON d.batch_id = p.batch_id;

-- 4) Borrar los lotes duplicados.
DELETE b FROM contrapago_batches b
INNER JOIN `_dupes_to_delete` d ON d.batch_id = b.id;

DROP TABLE `_dupes_to_delete`;

-- 5) Backfill: recalcular import_hash con la nueva fórmula para todos los lotes.
--    Se hace en dos pasos (NULL primero) para evitar colisiones temporales con
--    el UNIQUE KEY mientras se actualizan filas una por una.
UPDATE contrapago_batches SET import_hash = NULL;

UPDATE contrapago_batches b
SET b.import_hash = (
  SELECT MD5(GROUP_CONCAT(numeroGuia ORDER BY numeroGuia SEPARATOR ','))
  FROM contrapago_payments
  WHERE batch_id = b.id
)
WHERE EXISTS (
  SELECT 1 FROM contrapago_payments WHERE batch_id = b.id
);
