-- ============================================================================
-- 009: Departamentos, KPIs por departamento, y sistema de bonos por niveles
-- Ejecutar despues de 008_expenses_module.sql
-- Idempotente: se puede ejecutar multiples veces sin duplicar datos
-- ============================================================================

-- 1. Columnas de bonificacion en departments
-- ============================================================================

ALTER TABLE departments ADD COLUMN IF NOT EXISTS bonus_base DECIMAL(15,2) DEFAULT 0;
ALTER TABLE departments ADD COLUMN IF NOT EXISTS bonus_cumpl DECIMAL(15,2) DEFAULT 0;
ALTER TABLE departments ADD COLUMN IF NOT EXISTS bonus_elite DECIMAL(15,2) DEFAULT 0;
ALTER TABLE departments ADD COLUMN IF NOT EXISTS bonus_max_annual DECIMAL(15,2) DEFAULT 0;
ALTER TABLE departments ADD COLUMN IF NOT EXISTS min_score DECIMAL(5,2) DEFAULT 60;
ALTER TABLE departments ADD COLUMN IF NOT EXISTS extra_condition TEXT NULL;

-- 2. Insertar los 7 departamentos base (INSERT IGNORE evita duplicados)
-- ============================================================================

INSERT IGNORE INTO departments (id, name, description, budget, sort_order, active, bonus_base, bonus_cumpl, bonus_elite, bonus_max_annual, min_score, extra_condition, created_at)
VALUES
(1, 'VENTAS',     'Departamento de ventas y comercializacion',           180000000, 1, 1, 500000, 1000000, 1500000, 18000000, 60, 'Minimo 3 KPIs con cumplimiento >= 70%', NOW()),
(2, 'CARTERA',    'Gestion de cobros y cartera',                          25000000, 2, 1, 400000,  800000, 1200000,  14400000, 60, 'Minimo 3 KPIs con cumplimiento >= 70%', NOW()),
(3, 'BODEGA',     'Almacen, inventario y logistica',                      30000000, 3, 1, 350000,  700000, 1050000,  12600000, 60, 'Minimo 3 KPIs con cumplimiento >= 70%', NOW()),
(4, 'GARANTIAS',  'Gestion de garantias y servicio post-venta',           20000000, 4, 1, 300000,  600000,  900000,  10800000, 60, 'Minimo 3 KPIs con cumplimiento >= 70%', NOW()),
(5, 'COMPRAS',    'Compras y gestion de proveedores',                     15000000, 5, 1, 400000,  800000, 1200000,  14400000, 60, 'Minimo 3 KPIs con cumplimiento >= 70%', NOW()),
(6, 'ADMIN',      'Administracion, contabilidad y nomina',                20000000, 6, 1, 350000,  700000, 1050000,  12600000, 60, 'Minimo 3 KPIs con cumplimiento >= 70%', NOW()),
(7, 'GERENCIA',   'Gerencia general y direccion estrategica',             60000000, 7, 1, 800000, 1500000, 2500000,  30000000, 60, 'Minimo 3 KPIs con cumplimiento >= 70%', NOW());

-- Actualizar bonos para departamentos que ya existan
UPDATE departments SET bonus_base = 500000,  bonus_cumpl = 1000000, bonus_elite = 1500000, bonus_max_annual = 18000000, min_score = 60, extra_condition = 'Minimo 3 KPIs con cumplimiento >= 70%' WHERE id = 1;
UPDATE departments SET bonus_base = 400000,  bonus_cumpl = 800000,  bonus_elite = 1200000, bonus_max_annual = 14400000, min_score = 60, extra_condition = 'Minimo 3 KPIs con cumplimiento >= 70%' WHERE id = 2;
UPDATE departments SET bonus_base = 350000,  bonus_cumpl = 700000,  bonus_elite = 1050000, bonus_max_annual = 12600000, min_score = 60, extra_condition = 'Minimo 3 KPIs con cumplimiento >= 70%' WHERE id = 3;
UPDATE departments SET bonus_base = 300000,  bonus_cumpl = 600000,  bonus_elite = 900000,  bonus_max_annual = 10800000, min_score = 60, extra_condition = 'Minimo 3 KPIs con cumplimiento >= 70%' WHERE id = 4;
UPDATE departments SET bonus_base = 400000,  bonus_cumpl = 800000,  bonus_elite = 1200000, bonus_max_annual = 14400000, min_score = 60, extra_condition = 'Minimo 3 KPIs con cumplimiento >= 70%' WHERE id = 5;
UPDATE departments SET bonus_base = 350000,  bonus_cumpl = 700000,  bonus_elite = 1050000, bonus_max_annual = 12600000, min_score = 60, extra_condition = 'Minimo 3 KPIs con cumplimiento >= 70%' WHERE id = 6;
UPDATE departments SET bonus_base = 800000,  bonus_cumpl = 1500000, bonus_elite = 2500000, bonus_max_annual = 30000000, min_score = 60, extra_condition = 'Minimo 3 KPIs con cumplimiento >= 70%' WHERE id = 7;

-- 3. KPIs por departamento
-- ============================================================================
-- Usamos INSERT IGNORE con un truco: necesitamos unicidad por (department_id, name)
-- Primero aseguramos el indice unico

ALTER TABLE department_kpis ADD UNIQUE INDEX IF NOT EXISTS idx_dept_kpi_unique (department_id, name(191));

-- -------------------------------------------------------
-- VENTAS (department_id = 1)
-- -------------------------------------------------------
INSERT IGNORE INTO department_kpis (department_id, name, description, target_value, current_value, unit, direction, weight, sort_order, active, created_at)
VALUES
(1, 'Ventas mensuales vs meta ciudad',     'Total facturado del mes vs meta asignada por ciudad',          617000000, 0, '$', 'higher_better', 35, 1, 1, NOW()),
(1, 'Recaudo del mes / Ventas del mes',    'Porcentaje de recaudo sobre ventas del mismo periodo',               95, 0, '%', 'higher_better', 25, 2, 1, NOW()),
(1, 'Nuevos clientes activos',             'Clientes nuevos que realizaron al menos una compra en el mes',        5, 0, '#', 'higher_better', 15, 3, 1, NOW()),
(1, 'Reactivacion clientes dormidos >60d', 'Clientes sin compra en 60+ dias que compraron este mes',            10, 0, '#', 'higher_better', 15, 4, 1, NOW()),
(1, 'Descuento promedio / ventas',         'Porcentaje promedio de descuento otorgado sobre ventas',              2, 0, '%', 'lower_better',  10, 5, 1, NOW());

-- -------------------------------------------------------
-- CARTERA (department_id = 2)
-- -------------------------------------------------------
INSERT IGNORE INTO department_kpis (department_id, name, description, target_value, current_value, unit, direction, weight, sort_order, active, created_at)
VALUES
(2, 'Recaudo mensual / Ventas',       'Porcentaje de recaudo del mes sobre ventas totales',                      95, 0, '%', 'higher_better', 30, 1, 1, NOW()),
(2, 'Cartera >90d / cartera total',   'Porcentaje de cartera vencida a mas de 90 dias',                          8, 0, '%', 'lower_better',  30, 2, 1, NOW()),
(2, 'Recuperacion cartera >180d',     'Monto recuperado de cartera mayor a 180 dias',                      8000000, 0, '$', 'higher_better', 20, 3, 1, NOW()),
(2, 'DSO consolidado',                'Dias promedio de cobro (Days Sales Outstanding)',                         70, 0, '#', 'lower_better',  15, 4, 1, NOW()),
(2, 'Notas credito aplicadas',        'Porcentaje de notas credito procesadas correctamente',                   100, 0, '%', 'higher_better',  5, 5, 1, NOW());

-- -------------------------------------------------------
-- BODEGA (department_id = 3)
-- -------------------------------------------------------
INSERT IGNORE INTO department_kpis (department_id, name, description, target_value, current_value, unit, direction, weight, sort_order, active, created_at)
VALUES
(3, 'Exactitud inventario vs ERP',      'Coincidencia entre inventario fisico y sistema ERP',                   98, 0, '%', 'higher_better', 30, 1, 1, NOW()),
(3, 'Despachos completados <24h',       'Porcentaje de despachos completados en menos de 24 horas',             95, 0, '%', 'higher_better', 25, 2, 1, NOW()),
(3, 'Dias inventario',                  'Dias promedio que el inventario permanece en bodega',                  280, 0, '#', 'lower_better',  25, 3, 1, NOW()),
(3, 'Faltante inventario / ventas',     'Porcentaje de faltante de inventario respecto a ventas',               1.5, 0, '%', 'lower_better',  15, 4, 1, NOW()),
(3, 'Transferencias inter-ciudad <48h', 'Porcentaje de transferencias completadas en menos de 48 horas',       100, 0, '%', 'higher_better',  5, 5, 1, NOW());

-- -------------------------------------------------------
-- GARANTIAS (department_id = 4)
-- -------------------------------------------------------
INSERT IGNORE INTO department_kpis (department_id, name, description, target_value, current_value, unit, direction, weight, sort_order, active, created_at)
VALUES
(4, 'Tiempo promedio resolucion',    'Dias promedio para resolver una garantia',                                  5, 0, '#', 'lower_better',  35, 1, 1, NOW()),
(4, 'Tasa de garantia / ventas',     'Porcentaje de garantias respecto al total de ventas',                     1.5, 0, '%', 'lower_better',  25, 2, 1, NOW()),
(4, 'Reincidencia de garantias',     'Porcentaje de garantias que se repiten sobre el mismo producto/cliente',    5, 0, '%', 'lower_better',  20, 3, 1, NOW()),
(4, 'Satisfaccion del cliente',      'Porcentaje de satisfaccion del cliente post-garantia',                     90, 0, '%', 'higher_better', 15, 4, 1, NOW()),
(4, 'Costo de garantias / ventas',   'Porcentaje del costo de garantias respecto a ventas totales',             0.8, 0, '%', 'lower_better',   5, 5, 1, NOW());

-- -------------------------------------------------------
-- COMPRAS (department_id = 5)
-- -------------------------------------------------------
INSERT IGNORE INTO department_kpis (department_id, name, description, target_value, current_value, unit, direction, weight, sort_order, active, created_at)
VALUES
(5, 'Margen bruto %',                'Margen bruto porcentual (ingresos - costo) / ingresos',                   44, 0, '%', 'higher_better', 30, 1, 1, NOW()),
(5, 'Dias inventario promedio',      'Dias promedio de rotacion de inventario',                                 200, 0, '#', 'lower_better',  25, 2, 1, NOW()),
(5, 'Quiebres de stock',            'Porcentaje de productos con stock agotado',                                  5, 0, '%', 'lower_better',  20, 3, 1, NOW()),
(5, 'Tiempo entrega proveedores',   'Dias promedio de entrega de proveedores',                                   30, 0, '#', 'lower_better',  15, 4, 1, NOW()),
(5, 'Costo logistico / ventas',     'Porcentaje de costos logisticos respecto a ventas',                          3, 0, '%', 'lower_better',  10, 5, 1, NOW());

-- -------------------------------------------------------
-- ADMIN (department_id = 6)
-- -------------------------------------------------------
INSERT IGNORE INTO department_kpis (department_id, name, description, target_value, current_value, unit, direction, weight, sort_order, active, created_at)
VALUES
(6, 'Cierre contable a tiempo',         'Porcentaje de cierres contables realizados dentro del plazo',         100, 0, '%', 'higher_better', 30, 1, 1, NOW()),
(6, 'Dashboard KPIs actualizado',       'Porcentaje de cumplimiento en actualizacion de dashboard de KPIs',    100, 0, '%', 'higher_better', 20, 2, 1, NOW()),
(6, 'Errores en nomina',                'Cantidad de errores detectados en el proceso de nomina',                0, 0, '#', 'lower_better',  20, 3, 1, NOW()),
(6, 'Gastos admin / ventas',            'Porcentaje de gastos administrativos respecto a ventas totales',        5, 0, '%', 'lower_better',  20, 4, 1, NOW()),
(6, 'Informes financieros a tiempo',    'Porcentaje de informes financieros entregados en fecha',              100, 0, '%', 'higher_better', 10, 5, 1, NOW());

-- -------------------------------------------------------
-- GERENCIA (department_id = 7)
-- -------------------------------------------------------
INSERT IGNORE INTO department_kpis (department_id, name, description, target_value, current_value, unit, direction, weight, sort_order, active, created_at)
VALUES
(7, 'Utilidad neta trimestral',     'Utilidad neta del trimestre (ingresos - costos - gastos)',            275000000, 0, '$', 'higher_better', 30, 1, 1, NOW()),
(7, 'Ventas consolidadas',          'Total de ventas consolidadas del trimestre',                         1850000000, 0, '$', 'higher_better', 25, 2, 1, NOW()),
(7, 'Margen neto',                  'Porcentaje de margen neto sobre ventas',                                    15, 0, '%', 'higher_better', 20, 3, 1, NOW()),
(7, 'Crecimiento vs ano anterior',  'Porcentaje de crecimiento respecto al mismo periodo del ano anterior',      20, 0, '%', 'higher_better', 15, 4, 1, NOW()),
(7, 'Satisfaccion empleados',       'Porcentaje de satisfaccion de empleados segun encuesta interna',            80, 0, '%', 'higher_better', 10, 5, 1, NOW());

-- Agregar columna bonus_tier a bonus_calculations si no existe
ALTER TABLE bonus_calculations ADD COLUMN IF NOT EXISTS bonus_tier VARCHAR(20) DEFAULT NULL;
ALTER TABLE bonus_calculations ADD COLUMN IF NOT EXISTS kpis_above_70 INT DEFAULT 0;
