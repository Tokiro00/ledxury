# Restaurar producción tras la pérdida de la instancia (22/08/2026)

La instancia de `ledxury.com` se terminó por error desde la consola de AWS. Con
ella se fueron el disco, la base `mamdb` y los respaldos diarios, que vivían en
`/var/backups/ledxury` **en el mismo disco**.

El código estaba completo en git. La base se reconstruye desde el respaldo del
**18/06/2026** más las migraciones posteriores, y el hueco de **19/06 al 22/08**
se rehace con fuentes externas (ver el final).

Todo lo de aquí quedó **probado localmente** el 22/08 sobre una base de prueba
(`mamdb_restore`): 152 tablas, cero columnas `tenant_id`, partida doble en 0,00.

## 1. Respaldo de partida

    C:\Users\alexa\Backups\ledxury-recuperacion-20260822\mamdb_dump.sql.gz

5,2 MB comprimido / 31 MB en claro. Verificado con `gzip -t`, restaura sin un
solo error. Es dump de producción real (`Host: localhost  Database: mamdb`,
MariaDB 10.2.36 Linux).

Contenido: 140 tablas · 3.768 facturas (12/04/2024 → 18/06/2026) · 3.682
clientes · 1.611 productos · 3.084 pagos · 11.724 asientos · 1.144 guías ·
11 lotes de contrapago · 21 usuarios.

## 2. Crear la base y restaurar

    mysql -u <user> -p -e "CREATE DATABASE mamdb CHARACTER SET utf8mb4;"
    zcat mamdb_dump.sql.gz | mysql -u <user> -p mamdb

## 3. Migraciones a aplicar, EN ESTE ORDEN

    059_mam_returns.sql
    061_users_is_vendor.sql
    063_contrapagos_company_mam_online.sql
    064_shipping_guide_outcome.sql
    067_mam_remision_sync.sql
    068_widen_account_balances.sql
    069_modulo_compras_proveedores.sql
    071_libro_diario_asientos_compuestos.sql

Resultado: 152 tablas, `entries.entryGroupId` presente y
`subaccounts.accountBalance` en DECIMAL(18,2).

## 4. Migraciones que NO se aplican, y por qué

| Migración | Por qué no |
|---|---|
| `060_pulso_multitenant_foundation` y su rollback | Multi-tenant quedó archivado. Producción NUNCA debe tener tabla `tenants` ni columnas `tenant_id`: rompe todas las consultas. Ver la nota de deploy. |
| `065_tenant_id_bots_dropshipping_rules` | Igual, es tenant. |
| `061_mam_returns_accounting` | Ya viene aplicada en el dump (falla con "Duplicate column name 'total_cost'", que es la señal de que ya está). |
| `066_fix_contrapagos_julio_2026` | Corrige datos de julio que ya no existen. |
| `070_cerrar_brecha_banco_contable` | Igual: corrige datos que ya no existen. |

## 5. Antes de subir código: el puente MY_Model

Producción corre la línea single-tenant. Varios modelos del repo declaran
`extends MY_Model`, clase que allá no existe, y tumban la página entera. Hay que
desplegar PRIMERO:

    db/prod_variants/MY_Model.single-tenant.php  ->  application/core/MY_Model.php

Es una versión inerte: `applyTenantFilter()` y `withAllTenants()` no hacen nada,
`tenantInsert()` inserta sin `tenant_id`, `nextNumber()` usa MAX()+1.

## 6. El hueco 19/06 – 22/08 y de dónde se rehace

| Qué | Fuente que Alex conserva |
|---|---|
| Lotes y guías de contrapago | Los Excel de Interrapidísimo de cada pago |
| Movimientos del banco | El extracto bancario |
| Facturas y presupuestos del bot | Las Google Sheets de BuilderBot |
| Remisiones de MAM a MAM-Online | `channel_remisions` en accesoriosmam (34.207.188.31), vivo |
| Inventario | Conteo físico |

Y todo lo contable que se hizo esa semana está como scripts idempotentes en
`db/scripts/`: correcciones de contrapago, fechas, brecha banco/contabilidad,
flete de terceros, cuenta del bot, separación del vendedor MAM-Online, el 7% del
canal, el cierre y apertura del 20/08 y los ajustes del 22/08. Se vuelven a
correr sobre los datos rehechos.

## 7. Que no vuelva a pasar

- El respaldo diario (`scripts/backup_db.sh`) escribía al MISMO disco. Tiene que
  salir de la instancia: S3 cuesta centavos.
- Crear una regla de **Recycle Bin** para snapshots y AMIs.
- Snapshot EBS programado.
- El disco de 16 GB se llenaba cada pocos meses; la expansión a 30 GB seguía
  pendiente. Aprovechar el servidor nuevo para dejarlo en 30 GB.
