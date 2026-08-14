# Pendiente: 17 archivos donde producción y la línea de trabajo tienen trabajo distinto

**Estado al 14/08/2026.** No se resolvieron a dedo a propósito: en estos archivos
**los dos lados tienen código real y distinto**, y elegir uno revierte el otro.

## Cómo se llegó a esta lista

1. Se comparó cada uno de los 601 archivos PHP de `/var/www/html` contra **toda**
   la historia del repo (hash de blob de git por archivo, normalizando fin de
   línea). 555 eran versiones ya commiteadas; **46 tenían contenido que no
   existía en ningún commit** — unas 2.900 líneas de código vivo sin respaldo.
2. Esos 43 archivos de código quedaron respaldados en la rama
   **`prod/snapshot-20260814`** (los otros 3 eran `secrets.php` y config de
   entorno).
3. De los 43, descartando diferencias que son solo acentos de "Interrapidísimo"
   y plomería de tenant (inerte, ver tag `pulso-archivado-20260814`):
   - **13** ya estaban completos en la línea de trabajo.
   - **10** se mezclaron automáticamente con un merge de tres vías contra la
     base común `21d14fa` (05/05/2026), sin un solo conflicto.
   - **3** se tomaron de producción (no existían en la base: `api/BotResponse.php`,
     `admin/Devoluciones.php`, `Mamdispatches_model.php`).
   - **17** dieron conflicto → son los de esta lista.

## Los 17

| Archivo | Producción tiene | La rama tiene |
|---|---|---|
| `controllers/sisvent/rest/BotImport.php` | +1.446 líneas | `parse_address()` y el contexto de tenant |
| `controllers/ventas/Ventas.php` | +364 | 12 líneas |
| `controllers/sisvent/admin/Bots.php` | +235 | permisos `allowed_bot_ids` (mig 047) y de Meta Ads |
| `controllers/Cron.php` | +151 | — |
| `views/sisvent/layouts/sidemenu.php` | +16 | +10 |
| `views/ventas/pendientes.php` | +14 | +28 |
| `models/Bankaccounts_model.php` | `realBalanceExpr()` duplicado en el modelo | el mismo cálculo refactorizado a `MY_Model` |
| `models/Cashboxes_model.php` | ídem | ídem |
| `controllers/sisvent/business/Users.php` | +8 | +36 (selector de comisiones) |
| `controllers/sisvent/admin/Salesboard.php` | +1 | +22 |
| `models/Cashmovements_model.php` | +2 | +1 |
| `models/Contrapago_model.php` | +4 | — |
| `models/Contrapago_invoice_model.php` | +3 | — |
| `models/Vendors_model.php` | +3 | +4 |
| `libraries/Interrapidisimo_lib.php` | +1 | +1 |
| `views/sisvent/admin/salesboard/index.php` | +2 | +2 |
| `views/sisvent/commercial/invoices/list.php` | +3 | — |

En estos archivos la rama conserva **su** versión: nada de producción se perdió
(está en `prod/snapshot-20260814`), pero master todavía no reproduce producción
para ellos.

## Qué NO va a pasar mientras estén pendientes

`db/deploy.sh` los marca **AJENO** y se niega a subirlos, porque el servidor
tiene trabajo que la rama no contiene. Producción está protegida sin que nadie
tenga que recordarlo.

## Cómo resolver cada uno

```bash
BASE=21d14fa
f=application/controllers/sisvent/admin/Bots.php     # el que toque

git show $BASE:$f                        > /tmp/base
git show alex/consolidacion-20260814:$f  > /tmp/ours
git show prod/snapshot-20260814:$f       > /tmp/prod

# merge con marcadores para resolver a mano
git merge-file -p --diff3 /tmp/ours /tmp/base /tmp/prod > $f
# ...resolver los <<<<<<< , validar con php -l, y commitear
```

**Los de bots (BotImport, Ventas, Bots, Cron, sidemenu, pendientes) los debería
revisar Jorge**: son su área y el lado de producción es trabajo suyo que nunca
se commiteó.

**Los de tesorería (Bankaccounts_model, Cashboxes_model, Cashmovements_model)
son de Alex** y el conflicto es solo por el refactor: producción tiene
`realBalanceExpr()` duplicado dentro de cada modelo, la rama lo tiene una vez en
`MY_Model`. Verificado que **las dos versiones calculan el mismo saldo**, así que
aquí la de la rama es la buena; solo hay que confirmar que ningún método que
producción agregó al modelo se quede afuera.

`Users.php` es el inverso: la rama tiene el selector de comisiones (+36) que
nunca se desplegó, así que la buena es la de la rama.
