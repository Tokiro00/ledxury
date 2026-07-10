-- 065: Encuesta de motivo de devolución vía bot WhatsApp.
--
-- Cuando Devoluciones::_autoDetect() crea una fila en shipping_returns,
-- el cron /cron/returnSurveys le escribe al cliente por el bot BuilderBot
-- del vendedor preguntando por qué se devolvió el pedido. La respuesta
-- entra por el webhook de chat (BotImport) y se guarda acá.
--
-- Categorías (survey_reason):
--   no_estaba     → nadie recibió cuando llegó el domiciliario
--   sin_dinero    → no tenía el dinero en ese momento (contrapago)
--   se_arrepintio → ya no lo quería / compró en otro lado
--   demora        → la entrega tardó demasiado
--   direccion     → dirección o teléfono errado, no lo ubicaron
--   error_pedido  → le llegó algo distinto a lo pedido
--   carrier       → falla de la transportadora (no llamó / nunca pasó)
--   otro          → texto libre en survey_response

ALTER TABLE `shipping_returns`
  ADD COLUMN `survey_sent_at` DATETIME NULL COMMENT 'Cuándo el bot envió la encuesta de motivo' AFTER `notes`,
  ADD COLUMN `survey_bot_id` INT NULL COMMENT 'builderbot_configs.id usado para enviar' AFTER `survey_sent_at`,
  ADD COLUMN `survey_status` VARCHAR(20) NULL COMMENT 'enviada|respondida|error|sin_telefono' AFTER `survey_bot_id`,
  ADD COLUMN `survey_error` VARCHAR(255) NULL COMMENT 'Detalle del último error de envío' AFTER `survey_status`,
  ADD COLUMN `survey_response` TEXT NULL COMMENT 'Respuesta cruda del cliente vía WhatsApp' AFTER `survey_error`,
  ADD COLUMN `survey_responded_at` DATETIME NULL AFTER `survey_response`,
  ADD COLUMN `survey_reason` VARCHAR(30) NULL COMMENT 'no_estaba|sin_dinero|se_arrepintio|demora|direccion|error_pedido|carrier|otro' AFTER `survey_responded_at`;

-- Para el cron (pendientes de envío) y los KPIs por motivo
ALTER TABLE `shipping_returns`
  ADD KEY `idx_survey` (`survey_status`, `survey_reason`);
