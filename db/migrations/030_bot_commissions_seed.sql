-- ============================================================================
-- 030_bot_commissions_seed.sql
-- Seed inicial de bot_commission_config (creada en 029).
--
-- Reemplaza la lógica hardcoded en código por filas declarativas.
-- Idempotente: DELETE seguido de INSERT, se puede correr varias veces.
--
-- Estructura del modelo (independiente de la empresa):
--   - admin_bots:  % sobre el TOTAL de ventas de TODOS los bots
--   - ads_manager: % sobre el TOTAL de ventas de TODOS los bots
--   - operator:    % sobre las ventas de UN bot específico (applies_to = bot_id)
--
-- Una persona puede tener varios roles → varias filas. Ej: Jorge Cano es
-- operator de Medellín (7%) Y admin_bots de los 3 (3%) = 10% en Medellín, 3% en
-- Barranquilla/Bogotá.
--
-- ╔══════════════════════════════════════════════════════════════════════════╗
-- ║  PARA MIGRAR A OTRA EMPRESA: editar SOLO los SET @vars de abajo.        ║
-- ║  El resto de la migración funciona genérico contra builderbot_configs.  ║
-- ╚══════════════════════════════════════════════════════════════════════════╝
-- ============================================================================

-- ── PERSONAS Y PORCENTAJES (editar por empresa) ─────────────────────────
-- Resolver idUser del admin_bots: por defecto buscamos por nombre.
-- Para Ledxury: Jorge Cano. Si tu DB usa otro name, ajustar.
SET @admin_bots_name_pattern    = 'GerMam';        -- Jorge Cano figura como 'GerMam' en users.name
SET @admin_bots_alt_pattern     = 'Jorge Cano';    -- fallback si name fue actualizado
SET @admin_bots_percentage      = 3.00;

-- Ads manager: Christina Morales (varias variantes de spelling)
SET @ads_manager_name_pattern   = 'Christina Morales';
SET @ads_manager_alt_pattern    = 'Cristina Morales';
SET @ads_manager_percentage     = 1.00;

-- Operadores de cada bot: porcentaje uniforme. Para distintos % por bot,
-- modificar el INSERT de operadores abajo y agregar más SET @op_*_pct.
SET @operator_percentage        = 7.00;

-- ── EJECUCIÓN (genérica, no editar para nuevas empresas) ────────────────

-- Limpiar configs activas viejas (re-ejecutable)
DELETE FROM bot_commission_config WHERE is_active = 1;

-- 1) Admin de bots (3% Ledxury) — sobre TODOS los bots
INSERT INTO bot_commission_config (user_id, commission_type, percentage, applies_to, is_active)
SELECT u.idUser, 'admin_bots', @admin_bots_percentage, 'all', 1
FROM users u
WHERE u.deleted = 0
  AND (u.name = @admin_bots_name_pattern
    OR u.name = @admin_bots_alt_pattern
    OR u.name LIKE CONCAT('%', @admin_bots_name_pattern, '%')
    OR u.name LIKE CONCAT('%', @admin_bots_alt_pattern, '%'))
ORDER BY u.idUser ASC
LIMIT 1;

-- 2) Ads manager (1% Ledxury) — sobre TODOS los bots
INSERT INTO bot_commission_config (user_id, commission_type, percentage, applies_to, is_active)
SELECT u.idUser, 'ads_manager', @ads_manager_percentage, 'all', 1
FROM users u
WHERE u.deleted = 0
  AND (u.name = @ads_manager_name_pattern
    OR u.name = @ads_manager_alt_pattern
    OR u.name LIKE CONCAT('%', @ads_manager_name_pattern, '%')
    OR u.name LIKE CONCAT('%', @ads_manager_alt_pattern, '%'))
ORDER BY u.idUser ASC
LIMIT 1;

-- 3) Operadores: una fila por cada bot activo, asignada a su default_vendor_id.
-- Si el operador es la misma persona que el admin (caso Jorge en Medellín),
-- recibe AMBAS filas (operator + admin_bots) y se le suman los porcentajes.
INSERT INTO bot_commission_config (user_id, commission_type, percentage, applies_to, is_active)
SELECT bcfg.default_vendor_id, 'operator', @operator_percentage, bcfg.bot_id, 1
FROM builderbot_configs bcfg
WHERE bcfg.is_active = 1
  AND bcfg.default_vendor_id IS NOT NULL
  AND bcfg.default_vendor_id <> '';

-- ── VERIFICACIÓN ────────────────────────────────────────────────────────
-- Ejecutar esto manualmente después de la migration:
--
-- SELECT bc.commission_type, bc.percentage, bc.applies_to,
--        u.name AS person, u.idUser
-- FROM bot_commission_config bc
-- LEFT JOIN users u ON u.idUser = bc.user_id
-- WHERE bc.is_active = 1
-- ORDER BY bc.commission_type, bc.applies_to;
--
-- Esperado para Ledxury (3 bots activos):
--   admin_bots   3.00  all                                    GerMam (Jorge Cano)
--   ads_manager  1.00  all                                    Christina Morales
--   operator     7.00  1cafcdaf-... (Medellín bot_id)         GerMam (Jorge Cano)
--   operator     7.00  2d0c6da8-... (Barranquilla bot_id)     <operador BAQ>
--   operator     7.00  6616c833-... (Bogotá bot_id)           <operador BOG>
--
-- Si admin_bots o ads_manager NO aparecen → ajustar @vars al inicio del archivo.
