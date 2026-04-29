-- ============================================================================
-- 030_bot_commissions_seed.sql
-- Configuración inicial de comisiones por bot. Reemplaza la lógica hardcoded
-- en código por filas en bot_commission_config (creada en migration 029).
--
-- Regla del negocio acordada con Jorge Cano:
--   - 7% por bot a su dueño (operator)              → tipo 'operator'
--   - 1% sobre el total de TODOS los bots a Christina → tipo 'ads_manager'
--   - 3% sobre el total de TODOS los bots a Jorge Cano → tipo 'admin_bots'
--
-- Si el nombre del usuario en `users.name` no coincide exactamente, la fila NO
-- se insertará (IGNORE). Después de correr, ejecuta:
--   SELECT * FROM bot_commission_config WHERE is_active=1;
-- y ajusta manualmente los user_id que falten.
-- ============================================================================

-- Limpiar configs viejas (re-ejecutable)
DELETE FROM bot_commission_config WHERE is_active = 1;

-- 3% admin de todos los bots → Jorge Cano
INSERT INTO bot_commission_config (user_id, commission_type, percentage, applies_to, is_active)
SELECT u.idUser, 'admin_bots', 3.00, 'all', 1
FROM users u
WHERE u.deleted = 0
  AND (u.name LIKE 'Jorge Cano%' OR u.name = 'Jorge Cano' OR u.uname = 'jorgecano')
LIMIT 1;

-- 1% ads manager → Christina
INSERT INTO bot_commission_config (user_id, commission_type, percentage, applies_to, is_active)
SELECT u.idUser, 'ads_manager', 1.00, 'all', 1
FROM users u
WHERE u.deleted = 0
  AND (u.name LIKE 'Christina%' OR u.name LIKE 'Cristina%')
LIMIT 1;

-- 7% por bot a su dueño (operator) — un INSERT por cada bot activo cuyo
-- default_vendor_id corresponde al dueño/operador de ese bot.
-- applies_to = bot_id del bot al que aplica (UUID en builderbot_configs.bot_id)
INSERT INTO bot_commission_config (user_id, commission_type, percentage, applies_to, is_active)
SELECT bcfg.default_vendor_id, 'operator', 7.00, bcfg.bot_id, 1
FROM builderbot_configs bcfg
WHERE bcfg.is_active = 1
  AND bcfg.default_vendor_id IS NOT NULL
  AND bcfg.default_vendor_id <> '';

-- Verificación: deberías ver 3 + N filas (donde N = #bots activos)
-- SELECT bc.commission_type, bc.percentage, bc.applies_to, u.name
-- FROM bot_commission_config bc
-- LEFT JOIN users u ON u.idUser = bc.user_id
-- WHERE bc.is_active = 1;
