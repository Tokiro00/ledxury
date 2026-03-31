-- ============================================================================
-- MIGRACIÓN 003a: Corrección de Índices UNIQUE
-- Fecha: 2026-01-25
-- Descripción: Elimina índices UNIQUE que impiden duplicados válidos
--              en esquema multi-bodega
-- ============================================================================

-- El problema: accounts_group tiene índice UNIQUE en pucCode,
-- pero el mismo grupo (ej: Disponible=11) existe para varias bodegas

-- ============================================================================
-- 1. Eliminar índices UNIQUE problemáticos
-- ============================================================================

ALTER TABLE `accounts_group`
  DROP INDEX `idx_puc`;

ALTER TABLE `accounts_accounts`
  DROP INDEX `idx_puc`;

-- ============================================================================
-- 2. Crear índices normales (no únicos)
-- ============================================================================

ALTER TABLE `accounts_group`
  ADD INDEX `idx_puc` (`pucCode`);

ALTER TABLE `accounts_accounts`
  ADD INDEX `idx_puc` (`pucCode`);

-- NOTA: accounts_class mantiene su índice UNIQUE compuesto (pucCode, store)
-- porque SÍ tiene el campo store y la combinación debe ser única

SELECT 'Índices corregidos exitosamente' as resultado;
