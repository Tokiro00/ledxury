-- ============================================================
-- Migration 027: Normalizar celular como llave de búsqueda
-- ============================================================
-- Contexto Ledxury: a diferencia de MAM (donde el documento es
-- la llave principal del cliente), Ledxury vende casi todo por
-- WhatsApp. El celular pasa a ser la llave principal de búsqueda.
--
-- Formato canónico Ledxury: 10 dígitos, SIN prefijo +57 ni 57.
-- (Es el formato que ya se usa en facturación/envío.)
--
-- Esta migración:
--   1. Normaliza los celulares existentes (quita +57, 57, espacios,
--      guiones, paréntesis) → deja solo dígitos.
--   2. Recorta a los últimos 10 dígitos cuando aplica.
--   3. Crea un índice (NO único) sobre cellphone para lookup rápido
--      del bot. No se fuerza UNIQUE para no romper carga histórica.
--   4. Crea una vista temporal de duplicados para revisión manual.
-- ============================================================

-- 1. Normalizar cellphone
UPDATE clients
SET cellphone = REGEXP_REPLACE(cellphone, '[^0-9]', '')
WHERE cellphone IS NOT NULL AND cellphone <> '';

-- 2. Quitar prefijo 57 cuando el número quede con 12 dígitos
UPDATE clients
SET cellphone = SUBSTRING(cellphone, 3)
WHERE CHAR_LENGTH(cellphone) = 12 AND cellphone LIKE '57%';

-- 3. Mismo tratamiento para phone (campo legacy)
UPDATE clients
SET phone = REGEXP_REPLACE(phone, '[^0-9]', '')
WHERE phone IS NOT NULL AND phone <> '';

UPDATE clients
SET phone = SUBSTRING(phone, 3)
WHERE CHAR_LENGTH(phone) = 12 AND phone LIKE '57%';

-- 4. Si phone tiene valor pero cellphone está vacío, copiar
UPDATE clients
SET cellphone = phone
WHERE (cellphone IS NULL OR cellphone = '')
  AND phone IS NOT NULL AND phone <> '';

-- 5. Índice de búsqueda (NO UNIQUE — datos legacy pueden tener duplicados)
ALTER TABLE clients ADD INDEX idx_clients_cellphone (cellphone);

-- 6. Reporte de duplicados para limpieza manual posterior
DROP TABLE IF EXISTS _clients_cellphone_duplicates;
CREATE TABLE _clients_cellphone_duplicates AS
SELECT cellphone, COUNT(*) AS cuantos, GROUP_CONCAT(idClient) AS ids, GROUP_CONCAT(name SEPARATOR ' | ') AS nombres
FROM clients
WHERE deleted = 0 AND cellphone IS NOT NULL AND cellphone <> ''
GROUP BY cellphone
HAVING COUNT(*) > 1;

-- Para revisar después:
--   SELECT * FROM _clients_cellphone_duplicates ORDER BY cuantos DESC;
