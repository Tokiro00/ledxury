-- =====================================================================
-- Migration 064: shipping_guides.outcome (desenlace resuelto de la guía)
-- =====================================================================
-- Fecha: 2026-06-19
-- Autor: Alex + Claude
--
-- Problema: estadoGuia=16 "Archivada" es un estado terminal ambiguo de
-- Interrapidísimo — no dice si la operación terminó ENTREGADA o DEVUELTA.
-- El desenlace real está en el historial (estadosGuia[]) de consultarEstados.
--
-- Esta columna guarda el desenlace resuelto, que el sync oficial (que solo
-- escribe estadoGuia/estadoNombre con el último estado) NO pisa:
--   'entregado' | 'devuelto' | 'archivada' (resuelto sin señal clara) | NULL (sin resolver)
--
-- La llena Cron::resolveArchived(). Devoluciones::_autoDetect() crea
-- shipping_returns para outcome='devuelto'.
-- =====================================================================

ALTER TABLE shipping_guides
    ADD COLUMN IF NOT EXISTS outcome VARCHAR(20) DEFAULT NULL
    COMMENT 'Desenlace resuelto: entregado|devuelto|archivada|NULL' AFTER estadoNombre;
