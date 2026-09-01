-- 047_users_allowed_bot_ids.sql
-- Permite limitar a qué bots WhatsApp Web tiene acceso un usuario.
--
-- NULL = sin restricción (admin típico). Cuando tiene CSV de bot_config_id
-- (ej. '4' o '1,3'), el usuario:
--   1. Solo puede gestionar esos bots desde /sisvent/admin/bots/whatsapp
--   2. Los AJAX whatsappConversations/Messages/Send/SetTag rechazan bots fuera de la lista
--   3. El sidemenu le muestra "WhatsApp Web", "Garantías" y "Devoluciones"
--      aunque no sea rol 1/10, porque actúa como operador limitado del bot.
--
-- Caso de uso inicial: Carlos Alberto Henao (bodeguero) → solo bot 4 (Garantías).
--
-- IMPORTANTE: para que el cambio tome efecto, el usuario debe cerrar sesión
-- y volver a entrar (allowed_bot_ids se carga al user_data en Login_model).

ALTER TABLE `users`
  ADD COLUMN `allowed_bot_ids` VARCHAR(100) NULL
  COMMENT 'CSV de bot_config_id permitidos para WhatsApp Web. NULL=sin restricción.'
  AFTER `bots_access`;

-- Carlos Alberto Henao: ambos idUser apuntan a la misma persona (duplicado
-- de cuenta que conviene limpiar después). Aplicamos a los dos por seguridad.
UPDATE `users`
   SET `allowed_bot_ids` = '4',
       `bots_access` = 1
 WHERE `idUser` IN ('71218078', '712180788');
