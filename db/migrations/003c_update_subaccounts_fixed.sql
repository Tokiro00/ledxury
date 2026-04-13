-- ============================================================================
-- MIGRACIÓN 003c: Actualización de Subcuentas Existentes (CORREGIDA)
-- Fecha: 2026-01-25
-- Descripción: Copia accountID → pucCode y determina accountType
-- ============================================================================

-- DESCUBRIMIENTO: El campo accountID en subaccounts YA contiene el código PUC
-- Solo necesitamos copiarlo y determinar el tipo basado en el primer dígito

UPDATE `subaccounts`
SET
    pucCode = accountID,
    accountType = CASE
        WHEN LEFT(accountID, 1) = '1' THEN 'asset'
        WHEN LEFT(accountID, 1) = '2' THEN 'liability'
        WHEN LEFT(accountID, 1) = '3' THEN 'equity'
        WHEN LEFT(accountID, 1) = '4' THEN 'revenue'
        WHEN LEFT(accountID, 1) = '5' THEN 'expense'
        WHEN LEFT(accountID, 1) = '6' THEN 'cost'
        ELSE NULL
    END
WHERE pucCode IS NULL AND deleted = 0;

-- ============================================================================
-- VERIFICACIÓN
-- ============================================================================

SELECT 'Actualización completada' as resultado;

SELECT
    accountType,
    COUNT(*) as cantidad
FROM subaccounts
WHERE deleted = 0
GROUP BY accountType
ORDER BY accountType;

SELECT
    'Subcuentas sin actualizar' as alerta,
    COUNT(*) as cantidad
FROM subaccounts
WHERE deleted = 0 AND pucCode IS NULL;

-- Mostrar algunas subcuentas actualizadas
SELECT
    id,
    accountName,
    pucCode,
    accountType
FROM subaccounts
WHERE deleted = 0 AND accountType IS NOT NULL
LIMIT 10;
