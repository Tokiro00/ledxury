-- 067: Nueva etiqueta de conversación "Cancelado" para el panel de chat de los bots.
--
-- Se usa cuando el cliente cancela un pedido. Manual (is_auto=0), como
-- Reclamo/Devolución: el asesor la marca desde el panel de conversaciones.
-- Política: un cliente puede cancelar hasta las 7:00 am del siguiente día
-- hábil; después el pedido ya salió y no se puede cancelar (ver prompt del bot).

INSERT INTO `bot_conversation_tags` (`name`, `color`, `is_auto`, `sort_order`)
VALUES ('Cancelado', '#78350F', 0, 18);
