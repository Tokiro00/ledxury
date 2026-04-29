-- ============================================================================
-- 030_bot_commissions_seed.sql
-- Configuración inicial de comisiones por bot. Reemplaza la lógica hardcoded
-- en código por filas en bot_commission_config (creada en migration 029).
--
-- Regla del negocio acordada con Jorge Cano:
--   - 7% por bot a su dueño (operator)               → tipo 'operator'
--   - 1% sobre el total de TODOS los bots a Christina Morales → tipo 'ads_manager'
--   - 3% sobre el total de TODOS los bots a Jorge Cano → tipo 'admin_bots'
--     (Jorge Cano es además operador de GerMAM Medellín → recibe ambos)
--
-- Resolución de usuarios:
--   - Jorge Cano = default_vendor_id del bot 1 (GerMAM Medellín)
--   - Christina Morales = match por nombre en users (Christina o Cristina)
--
-- Idempotente: DELETE seguido de INSERT, se puede correr varias veces sin duplicar.
-- Después de correr, ejecuta para verificar:
--   SELECT bc.commission_type, bc.percentage, bc.applies_to, u.name
--   FROM bot_commission_config bc LEFT JOIN users u ON u.idUser = bc.user_id
--   WHERE bc.is_active = 1;
-- ============================================================================

-- Limpiar configs viejas (re-ejecutable)
DELETE FROM bot_commission_config WHERE is_active = 1;

-- 3% admin de TODOS los bots → Jorge Cano (= default_vendor_id del bot Medellín, id=1)
-- Usar el bot directamente es más confiable que matchear por nombre.
INSERT INTO bot_commission_config (user_id, commission_type, percentage, applies_to, is_active)
SELECT bcfg.default_vendor_id, 'admin_bots', 3.00, 'all', 1
FROM builderbot_configs bcfg
WHERE bcfg.id = 1
  AND bcfg.is_active = 1
  AND bcfg.default_vendor_id IS NOT NULL
  AND bcfg.default_vendor_id <> ''
LIMIT 1;

-- 1% ads manager → Christina Morales (match por nombre, varias variantes)
INSERT INTO bot_commission_config (user_id, commission_type, percentage, applies_to, is_active)
SELECT u.idUser, 'ads_manager', 1.00, 'all', 1
FROM users u
WHERE u.deleted = 0
  AND (
       u.name LIKE 'Christina Morales%'
    OR u.name LIKE 'Cristina Morales%'
    OR u.name = 'Christina Morales'
    OR u.name = 'Cristina Morales'
    OR u.name LIKE '%Christina%Morales%'
    OR u.name LIKE '%Cristina%Morales%'
  )
LIMIT 1;

-- 7% por bot a su dueño (operator) — un INSERT por cada bot activo cuyo
-- default_vendor_id corresponde al dueño/operador de ese bot.
-- applies_to = bot_id del bot al que aplica (UUID en builderbot_configs.bot_id)
-- Esto incluye automáticamente a Jorge Cano por el bot Medellín (recibe 7%
-- como operator + 3% como admin_bots = 10% sobre ventas de Medellín).
INSERT INTO bot_commission_config (user_id, commission_type, percentage, applies_to, is_active)
SELECT bcfg.default_vendor_id, 'operator', 7.00, bcfg.bot_id, 1
FROM builderbot_configs bcfg
WHERE bcfg.is_active = 1
  AND bcfg.default_vendor_id IS NOT NULL
  AND bcfg.default_vendor_id <> '';

-- Verificación esperada (3 bots activos): 1 admin_bots + 1 ads_manager + 3 operator = 5 filas
-- Si ads_manager NO aparece → ajustar manualmente el name de Christina en users
-- o ejecutar:
--   UPDATE bot_commission_config SET user_id = '<idUser_de_Christina>'
--   WHERE commission_type = 'ads_manager' AND is_active = 1;
