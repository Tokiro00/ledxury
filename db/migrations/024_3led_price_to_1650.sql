-- Migration 024: Subir precio unitario de módulos 3LED normales a $1.650.
-- (Antes $1.250 según migration 022). Las variantes alta potencia 2835 NO se tocan.

UPDATE products
SET price = 1650
WHERE idProduct REGEXP '^3LED-(12V|24V)-[A-K]$'
  AND deleted = 0;
