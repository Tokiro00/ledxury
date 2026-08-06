-- 070: etiqueta de empresa en los bots de WhatsApp.
--
-- Separa los bots por empresa dueña del canal: Ledxury (propios) vs empresas
-- externas que usan el canal (ej. Axonia, bot "Xonia" de plantas). El panel de
-- WhatsApp Web se divide en submenús por company. NULL/'ledxury' = Ledxury.

ALTER TABLE `builderbot_configs`
  ADD COLUMN `company` VARCHAR(50) NOT NULL DEFAULT 'ledxury'
    COMMENT 'Empresa dueña del bot: ledxury (propios) | axonia | ...'
    AFTER `name`;

-- Los bots existentes son de Ledxury.
UPDATE `builderbot_configs` SET `company` = 'ledxury' WHERE `company` IS NULL OR `company` = '';
