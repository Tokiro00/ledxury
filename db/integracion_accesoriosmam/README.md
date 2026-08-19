# Puente accesoriosmam → Ledxury (remisiones del canal MAM-Online)

Ledxury es el cliente **3377 "MAM-Online"** en accesoriosmam.com (el ERP de MAM,
EC2 34.207.188.31, misma llave SSH `db/Amazon_MAM.pem`). Cada envío de mercancía
de MAM a Ledxury queda allá como una **remisión de canal** (`channel_remisions`,
con costo + margen ≈10% + total por cobrar). Este puente las convierte en
facturas de proveedor MAM **pendientes por recibir** en Ledxury.

## Piezas

| Pieza | Dónde vive | Dónde corre |
|---|---|---|
| `Channelsync.php` (endpoint solo lectura, con llave) | este folder (versión maestra) | `accesoriosmam.com:/var/www/html/application/controllers/api/Channelsync.php` |
| `Cronmamsync.php` (importador) | `application/controllers/` | ledxury.com |
| `application/config/mamsync.php` (URL + llaves) | repo ledxury | ledxury.com |
| Migración `067_mam_remision_sync.sql` (tabla de control) | `db/migrations/` | BD `mamdb` de ledxury |

La llave del API vive en `config/mamsync.php` y en la constante `API_KEY` del
`Channelsync.php` desplegado — **al desplegar, reemplazar `__MAMSYNC_KEY__` por
la llave real** (el deploy de abajo lo hace con `sed`).

## Flujo

1. MAM digita la remisión en accesoriosmam (como siempre — nada cambia allá).
2. El cron de Ledxury (cada 15 min) pide las remisiones nuevas del canal.
3. Cada una se vuelve `supplier_invoice` `REM-MAM-000NN` del proveedor MAM (12):
   detalles con los mismos códigos de producto (catálogos compartidos, verificado
   8/8), asiento DR mercancía en tránsito / CR proveedores + aux MAM, estado
   **"En Tránsito"** en Cuentas por Pagar.
4. Bodega revisa contra lo que llegó físicamente y da **"Recibir Mercancía"**:
   entra el stock y el asiento pasa de tránsito a inventario (flujo `receive()`
   que ya existía).
5. Idempotencia: `mam_remision_sync` (UNIQUE por remisión). Las remisiones
   anteriores al 01/08/2026 se marcan `omitida_saldo_inicial` — están dentro de
   la factura `SALDO-INICIAL-MAMONLINE-20260801` ($129.308.187).
6. Si una remisión trae códigos que no existen en Ledxury, la factura se importa
   igual y las notas lo advierten; crear el producto con ese código reconecta el
   stock (inventory usa el código como llave).

## Deploy del endpoint a accesoriosmam

```bash
KEY=$(php -r "include 'application/config/mamsync.php'; echo \$config['mamsync']['api_key'];")
sed "s/__MAMSYNC_KEY__/$KEY/" db/integracion_accesoriosmam/Channelsync.php > /tmp/Channelsync.deploy.php
scp -i db/Amazon_MAM.pem /tmp/Channelsync.deploy.php ec2-user@34.207.188.31:/tmp/
ssh -i db/Amazon_MAM.pem ec2-user@34.207.188.31 "sudo mkdir -p /var/www/html/application/controllers/api && sudo cp /tmp/Channelsync.deploy.php /var/www/html/application/controllers/api/Channelsync.php && sudo chown apache:apache /var/www/html/application/controllers/api/Channelsync.php && php -l /var/www/html/application/controllers/api/Channelsync.php"
# prueba:
curl -s "https://accesoriosmam.com/api/channelsync/ping?key=$KEY"
```

## Crontab en ledxury (agregar al root crontab)

```
*/15 * * * * curl -s "https://ledxury.com/cronmamsync/run?key=mamsync_cron_2026" >> /var/www/html/application/logs/cron_mamsync.log 2>&1
```

## Pendiente / fase 2

- **Devoluciones del canal** (`channel_returns`) → NC de proveedor automática.
- **Dirección inversa** (pedido de compra en Ledxury → borrador de remisión en
  accesoriosmam) — requiere un endpoint de escritura allá; hoy MAM digita la
  remisión directamente, que en la práctica es el mismo paso.
- Conciliación mensual: `SUM(total_ar)` de remisiones del mes allá vs
  `SUM(total)` de `REM-MAM-*` del mes acá.
