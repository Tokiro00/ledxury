-- Migration 022: Actualizar precios desde Excel oficial (P.Venta PUBLICO)
-- Fuente: Excel de Ledxury entregado por Jorge Cano 2026-04-28
-- Solo precios al detal (= dropshipping en su caso)

-- ========================================================================
-- MÓDULOS LED (familias completas a precio unitario)
-- ========================================================================

-- 3LED 7CM (todas las variantes 12V/24V x 11 colores) — $1.250
UPDATE products SET price = 1250
WHERE idProduct REGEXP '^3LED-(12V|24V)-[A-K]$' AND deleted = 0;

-- 6LED 13CM (todas las variantes 12V/24V x 11 colores) — $2.000
UPDATE products SET price = 2000
WHERE idProduct REGEXP '^6LED-(12V|24V)-[A-K]$' AND deleted = 0;

-- 12LED 13CM (todas las variantes 12V/24V x 11 colores) — $3.250
UPDATE products SET price = 3250
WHERE idProduct REGEXP '^12LED-(12V|24V)-[A-K]$' AND deleted = 0;

-- 2835 ALTA POTENCIA 6.5CM (todas las variantes) — $1.750
UPDATE products SET price = 1750
WHERE idProduct LIKE '2835-%' AND deleted = 0;

-- JS-COB FIJO+FLASH 12V (variantes) — $4.500
UPDATE products SET price = 4500
WHERE idProduct LIKE 'JS-COB-%' AND deleted = 0;

-- MÓDULOS COB 7CM (M{color}-{12V|24V}) — $1.500
UPDATE products SET price = 1500
WHERE idProduct IN (
    'MB-12V','MB-24V','MBI-12V','MBI-24V','MG-12V','MG-24V',
    'MM-12V','MM-24V','MR-12V','MR-24V','MV-12V','MV-24V',
    'MW-12V','MW-24V','MY-12V','MY-24V'
) AND deleted = 0;

-- ========================================================================
-- EXPLORADORAS (precios individuales)
-- ========================================================================
UPDATE products SET price = 55000 WHERE idProduct = 'ACS-SD-38'    AND deleted = 0;
UPDATE products SET price = 70000 WHERE idProduct = 'ACS-SD-02'    AND deleted = 0;
UPDATE products SET price = 45000 WHERE idProduct = 'ACS-SD-21'    AND deleted = 0;
UPDATE products SET price = 75000 WHERE idProduct = 'ACS-M4-4'     AND deleted = 0;
UPDATE products SET price = 60000 WHERE idProduct = 'ACS-M4-4-3LED' AND deleted = 0;
UPDATE products SET price = 75000 WHERE idProduct = 'ACS-SD-112'   AND deleted = 0;
UPDATE products SET price = 45000 WHERE idProduct = 'ACS-SD-3'     AND deleted = 0;
UPDATE products SET price = 60000 WHERE idProduct = 'ACS-WL061-90W' AND deleted = 0;
UPDATE products SET price = 60000 WHERE idProduct = 'ACS-SD-113'   AND deleted = 0;
UPDATE products SET price = 85000 WHERE idProduct = 'ACS-SD-211-2' AND deleted = 0;
UPDATE products SET price = 35000 WHERE idProduct = 'ACS-SD-56'    AND deleted = 0;

-- ========================================================================
-- ACCESORIOS / OTROS
-- ========================================================================
UPDATE products SET price = 70000  WHERE idProduct = 'Y10-2X'        AND deleted = 0;
UPDATE products SET price = 90000  WHERE idProduct = 'Q58MAX'        AND deleted = 0;
UPDATE products SET price = 80000  WHERE idProduct = 'TP-012'        AND deleted = 0;
UPDATE products SET price = 15000  WHERE idProduct = 'MOTO-LOCK'     AND deleted = 0;
UPDATE products SET price = 55000  WHERE idProduct = 'DISC-ALARM'    AND deleted = 0;
UPDATE products SET price = 60000  WHERE idProduct = 'DISK-ALAR-PRO' AND deleted = 0;
UPDATE products SET price = 60000  WHERE idProduct = 'DISK-ALARM-U'  AND deleted = 0;
UPDATE products SET price = 399000 WHERE idProduct = 'CARPLAY-T100'  AND deleted = 0;

-- ========================================================================
-- BOMBILLOS M1 (toda la serie) — $25.000
-- ========================================================================
UPDATE products SET price = 25000
WHERE idProduct IN (
    'M1-880','M1-9005','M1-9006','M1-H1','M1-H11','M1-H16','M1-H3','M1-H4','M1-H7'
) AND deleted = 0;

-- ========================================================================
-- BOMBILLOS K1 (toda la serie) — $75.000
-- ========================================================================
UPDATE products SET price = 75000
WHERE idProduct IN (
    'K1-880','K1-9005','K1-9006','K1-H1','K1-H11','K1-H11-Y',
    'K1-H3','K1-H4','K1-H4-Y','K1-H7','K1-H7-Y','k1-9005'
) AND deleted = 0;

-- BOMBILLOS L5 — $75.000
UPDATE products SET price = 75000
WHERE idProduct IN ('L5-9005','L5-H1','L5-H11','L5-H4','L5-H7') AND deleted = 0;

-- ========================================================================
-- BOMBILLOS T4 — $45.000
-- ========================================================================
UPDATE products SET price = 45000
WHERE idProduct IN ('T4-9005','T4-9006','T4-H1','T4-H11','T4-H4','T4-H7') AND deleted = 0;

-- ========================================================================
-- BOMBILLOS M9PRO — $85.000
-- ========================================================================
UPDATE products SET price = 85000
WHERE idProduct LIKE 'M9PRO-%' AND deleted = 0;

-- ========================================================================
-- BOMBILLOS 3SPRO — $140.000
-- ========================================================================
UPDATE products SET price = 140000
WHERE idProduct LIKE '3SPRO-%' AND deleted = 0;

-- ========================================================================
-- BOMBILLOS VX4 — $135.000
-- ========================================================================
UPDATE products SET price = 135000
WHERE idProduct LIKE 'VX4-%' AND deleted = 0;

-- ========================================================================
-- BOMBILLOS X8 — $160.000
-- ========================================================================
UPDATE products SET price = 160000
WHERE idProduct LIKE 'X8-%' AND deleted = 0;

-- ========================================================================
-- BOMBILLOS X9 — $175.000
-- ========================================================================
UPDATE products SET price = 175000
WHERE idProduct LIKE 'X9-%' AND deleted = 0;

-- ========================================================================
-- BOMBILLOS F8 — $145.000
-- ========================================================================
UPDATE products SET price = 145000
WHERE idProduct LIKE 'F8-%' AND deleted = 0;

-- ========================================================================
-- BOMBILLOS K150B — $170.000
-- ========================================================================
UPDATE products SET price = 170000
WHERE idProduct LIKE 'K150B-%' AND deleted = 0;

-- ========================================================================
-- BOMBILLOS FX5 — $195.000
-- ========================================================================
UPDATE products SET price = 195000
WHERE idProduct LIKE 'FX5-%' AND deleted = 0;

-- ========================================================================
-- BOMBILLOS MT4 / HDX (xenón / motorizados pequeños) — $16.500
-- ========================================================================
UPDATE products SET price = 16500
WHERE idProduct IN ('MT4-GI','MT4-GL','MT4-H4','HDX-M02-H4') AND deleted = 0;

-- ========================================================================
-- BOMBILLOS MOTO 30SMD LUPA — $35.000
-- ========================================================================
UPDATE products SET price = 35000
WHERE idProduct IN ('30-MOT-H6-W','30-MOT-H6-WY','30-MOT-P15-W','30-MOT-P15-WY') AND deleted = 0;

-- ========================================================================
-- UNIDAD KENWORTH H4 — $55.000
-- ========================================================================
UPDATE products SET price = 55000
WHERE idProduct LIKE 'ACS-F5-2-%' AND deleted = 0;
