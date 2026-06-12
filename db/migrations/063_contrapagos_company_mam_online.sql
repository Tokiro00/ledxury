-- 063: Agregar mam_online (y buckets administrativos) a los enums de company
-- en contrapagos. El endpoint markCompany ya validaba estos valores pero la BD
-- los rechazaba. Ejecutar en mamdb (prod) / ledxury (local).

ALTER TABLE contrapago_payments
    MODIFY company ENUM('ledxury','mam','mam_online') DEFAULT 'ledxury';

ALTER TABLE contrapago_invoice_items
    MODIFY company ENUM('ledxury','mam','mam_online','no_invoice','disputa','sin_revisar') DEFAULT NULL;
