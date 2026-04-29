-- ============================================================================
-- 029_bot_product_aliases_seed.sql
-- Población inicial de bot_product_aliases con descripciones que los clientes
-- usan en chat → código real en products. Usado por _resolveProductCode y
-- el nuevo fallback de _findProductCode (ver BotImport.php).
--
-- alias_norm: UPPER + trim + collapse whitespace (lo que hace _normalizeAlias)
-- ============================================================================

INSERT IGNORE INTO bot_product_aliases (alias_norm, alias_raw, product_code, created_by) VALUES

-- ── Candados / Seguridad ─────────────────────────────────────────────────
('CANDADO DE DISCO ALUMINIO',           'candado de disco aluminio',           'MOTO-LOCK',     'seed_v1'),
('CANDADO DISCO ALUMINIO',              'candado disco aluminio',              'MOTO-LOCK',     'seed_v1'),
('CANDADO MOTO',                        'candado moto',                        'MOTO-LOCK',     'seed_v1'),
('CANDADO DE MOTO',                     'candado de moto',                     'MOTO-LOCK',     'seed_v1'),
('CANDADO DE DISCO',                    'candado de disco',                    'MOTO-LOCK',     'seed_v1'),
('CANDADO ALUMINIO',                    'candado aluminio',                    'MOTO-LOCK',     'seed_v1'),
('CANDADO CON ALARMA',                  'candado con alarma',                  'DISC-ALARM',    'seed_v1'),
('CANDADO DE DISCO CON ALARMA',         'candado de disco con alarma',         'DISC-ALARM',    'seed_v1'),
('CANDADO DISCO ALARMA',                'candado disco alarma',                'DISC-ALARM',    'seed_v1'),
('CANDADO ALARMA SENSOR',               'candado alarma sensor',               'DISC-ALARM',    'seed_v1'),
('CANDADO CON SENSOR DE MOVIMIENTO',    'candado con sensor de movimiento',    'DISC-ALARM',    'seed_v1'),
('CANDADO ALARMA PREMIUM',              'candado alarma premium',              'DISK-ALAR-PRO', 'seed_v1'),
('CANDADO METALICO CON ALARMA',         'candado metalico con alarma',         'DISK-ALAR-PRO', 'seed_v1'),
('CANDADO METALICO ALARMA',             'candado metalico alarma',             'DISK-ALAR-PRO', 'seed_v1'),
('CANDADO PREMIUM',                     'candado premium',                     'DISK-ALAR-PRO', 'seed_v1'),
('CANDADO U ALARMA',                    'candado u alarma',                    'DISK-ALARM-U',  'seed_v1'),
('CANDADO ALARMA U',                    'candado alarma u',                    'DISK-ALARM-U',  'seed_v1'),
('CANDADO TIPO U',                      'candado tipo u',                      'DISK-ALARM-U',  'seed_v1'),

-- ── Exploradoras ─────────────────────────────────────────────────────────
('EXPLORADORA ROBOT 2 LED',                  'exploradora robot 2 led',                  'ACS-SD-38',     'seed_v1'),
('EXPLORADORA ROBOT ALUMINIO 2 LED',         'exploradora robot aluminio 2 led',         'ACS-SD-38',     'seed_v1'),
('ROBOT ALUMINIO 2 LED',                     'robot aluminio 2 led',                     'ACS-SD-38',     'seed_v1'),
('EXPLORADORA ROBOT',                        'exploradora robot',                        'ACS-SD-38',     'seed_v1'),
('EXPLORADORA ROBOT 1 LED',                  'exploradora robot 1 led',                  'ACS-SD-02',     'seed_v1'),
('EXPLORADORA ROBOT DE ALUMINIO 1 LED',      'exploradora robot de aluminio 1 led',      'ACS-SD-02',     'seed_v1'),
('EXPLORADORA REDONDA 1 LED ALTA BAJA',      'exploradora redonda 1 led alta baja',      'ACS-SD-21',     'seed_v1'),
('REDONDA 1 LED LUPA',                       'redonda 1 led lupa',                       'ACS-SD-21',     'seed_v1'),
('EXPLORADORA INCRUSTRAR',                   'exploradora incrustrar',                   'ACS-M4-4',      'seed_v1'),
('EXPLORADORA INCRUSTAR',                    'exploradora incrustar',                    'ACS-M4-4',      'seed_v1'),
('EXPLORADORA REDONDA 2 LED INCRUSTRAR',     'exploradora redonda 2 led incrustrar',     'ACS-M4-4',      'seed_v1'),
('EXPLORADORA REDONDA 3 LED OJO DE ANGEL',   'exploradora redonda 3 led ojo de angel',   'ACS-M4-4',      'seed_v1'),
('EXPLORADORA OJO DE ANGEL',                 'exploradora ojo de angel',                 'ACS-M4-4',      'seed_v1'),
('EXPLORADORA REDONDA 1 LED 55 70',          'exploradora redonda 1 led 55 70',          'ACS-M4-4-3LED', 'seed_v1'),
('REDONDA 1 LED PEQUENA',                    'redonda 1 led pequena',                    'ACS-M4-4-3LED', 'seed_v1'),
('REDONDA PEQUENA 1 LED',                    'redonda pequena 1 led',                    'ACS-M4-4-3LED', 'seed_v1'),
('EXPLORADORA RECTANGULAR 3 LED',            'exploradora rectangular 3 led',            'ACS-SD-112',    'seed_v1'),
('RECTANGULAR 3 LED',                        'rectangular 3 led',                        'ACS-SD-112',    'seed_v1'),
('EXPLORADORA CUADRADA 1 LED',               'exploradora cuadrada 1 led',               'ACS-SD-3',      'seed_v1'),
('CUADRADA 1 LED',                           'cuadrada 1 led',                           'ACS-SD-3',      'seed_v1'),
('EXPLORADORA REDONDA 9 LED',                'exploradora redonda 9 led',                'ACS-WL061-90W', 'seed_v1'),
('REDONDA 9 LED 90W',                        'redonda 9 led 90w',                        'ACS-WL061-90W', 'seed_v1'),
('EXPLORADORA 9 LED',                        'exploradora 9 led',                        'ACS-WL061-90W', 'seed_v1'),
('EXPLORADORA 1 LED CUADRADA',               'exploradora 1 led cuadrada',               'ACS-SD-113',    'seed_v1'),
('EXPLORADORA CUADRADA GRANDE',              'exploradora cuadrada grande',              'ACS-SD-113',    'seed_v1'),
('BARRA 2 LED OJO DE ANGEL',                 'barra 2 led ojo de angel',                 'ACS-SD-211-2',  'seed_v1'),
('BARRA OJO DE ANGEL OJO DE DEMONIO',        'barra ojo de angel ojo de demonio',        'ACS-SD-211-2',  'seed_v1'),
('OJO DE ANGEL OJO DE DEMONIO',              'ojo de angel ojo de demonio',              'ACS-SD-211-2',  'seed_v1'),
('EXPLORADORA REDONDA 1 LED 120',            'exploradora redonda 1 led 120',            'ACS-SD-56',     'seed_v1'),
('REDONDA 1 LED FIJO FLASH',                 'redonda 1 led fijo flash',                 'ACS-SD-56',     'seed_v1'),

-- ── Intercomunicadores ───────────────────────────────────────────────────
('INTERCOMUNICADOR',                  'intercomunicador',                   'Y10-2X',  'seed_v1'),
('INTERCOMUNICADOR Y10',              'intercomunicador y10',               'Y10-2X',  'seed_v1'),
('INTERCOMUNICADORES',                'intercomunicadores',                 'Y10-2X',  'seed_v1'),
('INTERCOMUNICADOR CON PANTALLA',     'intercomunicador con pantalla',      'Q58MAX',  'seed_v1'),
('INTERCOMUNICADOR PANTALLA',         'intercomunicador pantalla',          'Q58MAX',  'seed_v1'),
('INTERCOMUNICADOR Q58',              'intercomunicador q58',               'Q58MAX',  'seed_v1'),

-- ── Aspiradora portátil ──────────────────────────────────────────────────
('ASPIRADORA PORTATIL',               'aspiradora portatil',                'TP-012',  'seed_v1'),
('ASPIRADORA 3R',                     'aspiradora 3r',                      'TP-012',  'seed_v1'),
('ASPIRADORA CON ESTUCHE',            'aspiradora con estuche',             'TP-012',  'seed_v1'),
('MINI ASPIRADORA',                   'mini aspiradora',                    'TP-012',  'seed_v1'),
('ASPIRADORA',                        'aspiradora',                         'TP-012',  'seed_v1'),

-- ── CarPlay / Multimedia ─────────────────────────────────────────────────
('CARPLAY',                           'carplay',                            'CARPLAY-T100', 'seed_v1'),
('CARPLAY MULTIMEDIA',                'carplay multimedia',                 'CARPLAY-T100', 'seed_v1'),
('PANTALLA CARPLAY',                  'pantalla carplay',                   'CARPLAY-T100', 'seed_v1'),
('PANTALLA AHD',                      'pantalla ahd',                       'CARPLAY-T100', 'seed_v1'),
('CARPLAY 9 PULGADAS',                'carplay 9 pulgadas',                 'CARPLAY-T100', 'seed_v1'),

-- ── Patrulleras (luces tipo policía) ─────────────────────────────────────
('PATRULLERA 6 LED AZUL',             'patrullera 6 led azul',              '6LED-B',     'seed_v1'),
('PATRULLERA AZUL',                   'patrullera azul',                    '6LED-B',     'seed_v1'),
('PATRULLERA 6 LED ROJO',             'patrullera 6 led rojo',              '6LED-R',     'seed_v1'),
('PATRULLERA ROJA',                   'patrullera roja',                    '6LED-R',     'seed_v1'),
('PATRULLERA 6 LED ROJO AZUL',        'patrullera 6 led rojo azul',         '6LED-R.B',   'seed_v1'),
('PATRULLERA ROJO Y AZUL',            'patrullera rojo y azul',             '6LED-R.B',   'seed_v1'),
('PATRULLERA 6 LED BLANCA',           'patrullera 6 led blanca',            '6LED-W',     'seed_v1'),
('PATRULLERA BLANCA',                 'patrullera blanca',                  '6LED-W',     'seed_v1'),
('PATRULLERA 6 LED AMARILLA',         'patrullera 6 led amarilla',          '6LED-Y',     'seed_v1'),
('PATRULLERA AMARILLA',               'patrullera amarilla',                '6LED-Y',     'seed_v1'),

-- ── Unidades Kenworth ────────────────────────────────────────────────────
('UNIDAD KENWORTH H4 BLANCO',         'unidad kenworth h4 blanco',          'ACS-F5-2-B', 'seed_v1'),
('KENWORTH BORDE BLANCO',             'kenworth borde blanco',              'ACS-F5-2-B', 'seed_v1'),
('UNIDAD KENWORTH BORDE AZUL',        'unidad kenworth borde azul',         'ACS-F5-2-D', 'seed_v1'),
('KENWORTH BORDE AZUL',               'kenworth borde azul',                'ACS-F5-2-D', 'seed_v1'),
('UNIDAD KENWORTH BORDE AZUL ICE',    'unidad kenworth borde azul ice',     'ACS-F5-2-I', 'seed_v1'),
('KENWORTH BORDE AMARILLO',           'kenworth borde amarillo',            'ACS-F5-2-A', 'seed_v1'),
('KENWORTH BORDE VERDE',              'kenworth borde verde',               'ACS-F5-2-E', 'seed_v1'),

-- ── Bombillos serie M1 (más vendidos H4/H7/H11) ──────────────────────────
('BOMBILLO M1 H4',           'bombillo m1 h4',         'M1-H4',  'seed_v1'),
('M1 H4',                    'm1 h4',                  'M1-H4',  'seed_v1'),
('BOMBILLO M1 H7',           'bombillo m1 h7',         'M1-H7',  'seed_v1'),
('M1 H7',                    'm1 h7',                  'M1-H7',  'seed_v1'),
('BOMBILLO M1 H11',          'bombillo m1 h11',        'M1-H11', 'seed_v1'),
('M1 H11',                   'm1 h11',                 'M1-H11', 'seed_v1'),
('BOMBILLO M1 9005',         'bombillo m1 9005',       'M1-9005', 'seed_v1'),
('BOMBILLO M1 9006',         'bombillo m1 9006',       'M1-9006', 'seed_v1'),
('BOMBILLO M1 H1',           'bombillo m1 h1',         'M1-H1',  'seed_v1'),
('BOMBILLO M1 H3',           'bombillo m1 h3',         'M1-H3',  'seed_v1'),

-- ── Bombillos serie K1 ───────────────────────────────────────────────────
('BOMBILLO K1 H4',           'bombillo k1 h4',         'K1-H4',  'seed_v1'),
('K1 H4',                    'k1 h4',                  'K1-H4',  'seed_v1'),
('BOMBILLO K1 H7',           'bombillo k1 h7',         'K1-H7',  'seed_v1'),
('K1 H7',                    'k1 h7',                  'K1-H7',  'seed_v1'),
('BOMBILLO K1 H11',          'bombillo k1 h11',        'K1-H11', 'seed_v1'),

-- ── Bombillos serie M9 PRO ──────────────────────────────────────────────
('BOMBILLO M9 PRO H4',       'bombillo m9 pro h4',     'M9PRO-H4',  'seed_v1'),
('M9 PRO H4',                'm9 pro h4',              'M9PRO-H4',  'seed_v1'),
('BOMBILLO M9 PRO H7',       'bombillo m9 pro h7',     'M9PRO-H7',  'seed_v1'),
('M9 PRO H7',                'm9 pro h7',              'M9PRO-H7',  'seed_v1'),
('BOMBILLO M9 PRO H11',      'bombillo m9 pro h11',    'M9PRO-H11', 'seed_v1'),

-- ── Bombillos serie 3SPRO ────────────────────────────────────────────────
('BOMBILLO 3SPRO H4',        'bombillo 3spro h4',      '3SPRO-H4',  'seed_v1'),
('BOMBILLO 3SPRO H7',        'bombillo 3spro h7',      '3SPRO-H7',  'seed_v1'),

-- ── Bombillos serie X8 / X9 / FX5 (top end) ──────────────────────────────
('BOMBILLO X8 H4',           'bombillo x8 h4',         'X8-H4',     'seed_v1'),
('BOMBILLO X8 H7',           'bombillo x8 h7',         'X8-H7',     'seed_v1'),
('BOMBILLO X9 H4',           'bombillo x9 h4',         'X9-H4',     'seed_v1'),
('BOMBILLO FX5 H4',          'bombillo fx5 h4',        'FX5-H4',    'seed_v1'),
('BOMBILLO FX5 H7',          'bombillo fx5 h7',        'FX5-H7',    'seed_v1'),

-- ── Bombillos T4 ─────────────────────────────────────────────────────────
('BOMBILLO T4 H4',           'bombillo t4 h4',         'T4-H4',     'seed_v1'),
('BOMBILLO T4 H7',           'bombillo t4 h7',         'T4-H7',     'seed_v1'),

-- ── Bombillos moto ───────────────────────────────────────────────────────
('BOMBILLO MOTO BA20D',      'bombillo moto ba20d',    '30-MOT-H6-W',   'seed_v1'),
('BOMBILLO LUPA MOTO',       'bombillo lupa moto',     '30-MOT-H6-W',   'seed_v1'),
('BOMBILLO MOTO P15D',       'bombillo moto p15d',     '30-MOT-P15-W',  'seed_v1'),
('BOMBILLO TRICETA',         'bombillo triceta',       'MT4-H4',        'seed_v1'),
('BOMBILLO H4 TRICETA',      'bombillo h4 triceta',    'MT4-H4',        'seed_v1'),

-- ── COB cintas LED 5m ────────────────────────────────────────────────────
('CINTA COB BLANCA 12V',     'cinta cob blanca 12v',   'ACS-COB-12V-A', 'seed_v1'),
('CINTA LED COB BLANCO',     'cinta led cob blanco',   'ACS-COB-12V-A', 'seed_v1'),
('CINTA COB ROJA 12V',       'cinta cob roja 12v',     'ACS-COB-12V-B', 'seed_v1'),
('CINTA COB VERDE 12V',      'cinta cob verde 12v',    'ACS-COB-12V-C', 'seed_v1'),
('CINTA COB AZUL ICE 12V',   'cinta cob azul ice 12v', 'ACS-COB-12V-D', 'seed_v1'),
('CINTA COB AMARILLA 12V',   'cinta cob amarilla 12v', 'ACS-COB-12V-E', 'seed_v1'),
('CINTA COB AZUL 12V',       'cinta cob azul 12v',     'ACS-COB-12V-F', 'seed_v1'),

-- ── COB JS-COB-4 (módulos fijo+flash blancos disponibles) ────────────────
('MODULO COB FIJO FLASH BLANCO',          'modulo cob fijo flash blanco',          'JS-COB-4-A', 'seed_v1'),
('MODULO LED COB BLANCO 12V',             'modulo led cob blanco 12v',             'JS-COB-4-A', 'seed_v1'),
('MODULO COB FIJO FLASH BLANCO CALIDO',   'modulo cob fijo flash blanco calido',   'JS-COB-4-B', 'seed_v1'),
('MODULO LED COB BLANCO CALIDO',          'modulo led cob blanco calido',          'JS-COB-4-B', 'seed_v1'),

-- ── COB 7CM (M-color) ────────────────────────────────────────────────────
('MODULO COB BLANCO 12V',     'modulo cob blanco 12v',     'MW-12V',  'seed_v1'),
('MODULO COB BLANCO 24V',     'modulo cob blanco 24v',     'MW-24V',  'seed_v1'),
('MODULO COB AMARILLO 12V',   'modulo cob amarillo 12v',   'MY-12V',  'seed_v1'),
('MODULO COB AZUL 12V',       'modulo cob azul 12v',       'MB-12V',  'seed_v1'),
('MODULO COB AZUL ICE 12V',   'modulo cob azul ice 12v',   'MBI-12V', 'seed_v1'),
('MODULO COB ROJO 12V',       'modulo cob rojo 12v',       'MR-12V',  'seed_v1'),
('MODULO COB VERDE 12V',      'modulo cob verde 12v',      'MG-12V',  'seed_v1'),
('MODULO COB MORADO 12V',     'modulo cob morado 12v',     'MM-12V',  'seed_v1');
