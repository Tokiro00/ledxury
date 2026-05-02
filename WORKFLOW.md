# Workflow de colaboración — Ledxury

Este documento define cómo trabajamos en el código y la base de datos para evitar pisarnos y mantener producción estable.

**Aplica para:** Alex, Jorge, y sus respectivas instancias de Claude Code.

---

## 1. Estructura de ramas

```
master                       → versión productiva estable (solo deploy desde aquí)
feature/ledxury-bots         → rama de integración (todos los cambios pasan por aquí)
alex/<feature>               → ramas de Alex
jorge/<feature>              → ramas de Jorge
hotfix/<descripcion>         → fixes urgentes
```

**Regla:** nadie hace commits directos a `master` ni a `feature/ledxury-bots`. Todo cambio entra vía Pull Request.

## 2. Workflow día a día

### Al empezar el día
```bash
git checkout feature/ledxury-bots
git pull origin feature/ledxury-bots
git checkout -b alex/<descripcion-del-trabajo>
```

Ejemplo: `alex/contabilidad-cierres`, `jorge/whatsapp-templates`

### Mientras trabajas
- Commits pequeños y frecuentes
- Mensajes claros: `Modulo: que cambia`. Ej: `Contabilidad: validación cierre mensual`
- Cada par de horas, sincroniza con la rama de integración:
  ```bash
  git fetch origin
  git rebase origin/feature/ledxury-bots
  ```

### Al terminar una feature
```bash
git push origin alex/<descripcion-del-trabajo>
```
Crea un **Pull Request** en GitHub apuntando a `feature/ledxury-bots`.

El otro revisa, comenta, y mergea cuando esté OK.

## 3. Probar en servidor (staging y producción)

**Importante:** ambos servidores reciben código **solo desde una rama versionada**, nunca archivos sueltos.

### Para probar tus cambios en servidor antes de mergear

**Opción A — Server de pruebas (recomendado):**
- Usa la BD local + XAMPP para probar la mayoría de cambios
- Si necesitas probar en server real, levanta una instancia separada (no producción)

**Opción B — Probar en producción (con cuidado):**
1. Asegúrate que tu rama está pusheada
2. Avisa al otro: "Voy a desplegar `alex/contabilidad-cierres` a producción para probar"
3. Conéctate al server: `ssh -i db/Amazon_MAM.pem ec2-user@<ip>`
4. **NO uses SCP de archivos sueltos.** En su lugar:
   ```bash
   cd /var/www/html
   git fetch origin
   git checkout alex/<descripcion-del-trabajo>
   ```
5. Prueba lo que necesites
6. Cuando termines, vuelve a la rama productiva:
   ```bash
   git checkout feature/ledxury-bots
   git pull
   ```
7. Avisa al otro: "Listo, server volvió a `feature/ledxury-bots`"

### Deploy formal a producción
Solo se hace desde `master` (después de mergear `feature/ledxury-bots` → `master`):
1. Crear PR de `feature/ledxury-bots` a `master`
2. Tag de versión: `git tag -a vX.Y.Z -m "mensaje" && git push --tags`
3. En el servidor: `git pull origin master`
4. Aplicar migraciones SQL pendientes
5. Reiniciar servicios si aplica

**Solo una persona despliega a producción a la vez.** Avisar antes y después.

## 4. Versionado (SemVer)

Usamos **versionado semántico**: `vMAJOR.MINOR.PATCH` (ej: `v1.2.3`)

### Cuándo subir cada parte

| Cambio | Ejemplo | Sube |
|--------|---------|------|
| **PATCH** (`v1.0.1` → `v1.0.2`) | Bug fix, ajuste menor, hotfix | Tercera cifra |
| **MINOR** (`v1.0.5` → `v1.1.0`) | Feature nuevo compatible (módulo nuevo, endpoint nuevo) | Segunda cifra, resetea PATCH |
| **MAJOR** (`v1.5.3` → `v2.0.0`) | Cambio que rompe compatibilidad (renombrar tabla, eliminar endpoint, cambio de schema mayor) | Primera cifra, resetea MINOR y PATCH |

### Versiones actuales
Última versión: revisar con `git tag -l --sort=-v:refname | head -5`

Ejemplos de tags ya creados:
- `v1.0.0` — release inicial estable
- `v1.0.1` — cart recovery, DB backup, bot errors page
- `v1.0.2` — tracking proactivo, stock bajo, health check bots, auto-reply

### Cómo crear una versión nueva

**1. Asegurarse que `master` tiene los cambios mergeados:**
```bash
git checkout master
git pull origin master
git log --oneline v1.0.2..HEAD  # ver commits desde último tag
```

**2. Crear el tag con mensaje descriptivo:**
```bash
git tag -a v1.0.3 -m "v1.0.3: descripción corta de lo que incluye"
git push origin v1.0.3
```

**3. (Opcional) Crear release en GitHub:**
- Ir a `github.com/Tokiro00/ledxury/releases/new`
- Seleccionar el tag
- Pegar changelog detallado (commits desde el último tag)

**4. Anotar en CHANGELOG.md** (si existe) qué cambió.

### Reglas de tagging

- **Solo se taguea desde `master`**, nunca desde ramas de feature
- **Siempre tags anotados** (`-a`), nunca livianos. Quedan firmados con autor + fecha + mensaje.
- **Una versión = un deploy.** Si subes a producción, taguear primero.
- **Si rompes producción**, hacer hotfix:
  ```bash
  git checkout -b hotfix/descripcion master
  # arreglar
  git checkout master
  git merge hotfix/descripcion
  git tag -a v1.0.4 -m "v1.0.4: hotfix de X"
  git push origin master --tags
  ```

### Ver historial de versiones

```bash
# Lista de tags ordenados por versión
git tag -l --sort=-v:refname

# Ver qué incluye un tag
git show v1.0.2

# Comparar dos versiones
git log --oneline v1.0.1..v1.0.2

# Volver a una versión específica (lectura)
git checkout v1.0.1
git checkout master  # volver a la rama
```

### Convención de mensaje del tag

```
vX.Y.Z: feature1, feature2, fix3
```

Mantener corto pero descriptivo. Para detalles: usa el body del release en GitHub.

**No taguear sin probar.** El tag implica que esa versión está estable y desplegada.

## 5. Migraciones de base de datos

### Reglas
- Cada migración tiene número consecutivo: `db/migrations/0XX_descripcion.sql`
- **Nunca repetir número** entre desarrolladores. Si Jorge tomó 034, Alex toma 035.
- Antes de crear una migración, revisa la última: `ls db/migrations/ | tail -5`
- Las migraciones son **idempotentes** cuando es posible (`CREATE TABLE IF NOT EXISTS`, `ADD COLUMN IF NOT EXISTS`)
- Documentar en el archivo SQL: qué hace, por qué, cuándo se aplica

### Aplicar migraciones
- En local: `mysql -u root ledxury < db/migrations/0XX_*.sql`
- En producción: solo se aplican durante el deploy formal, no antes

## 6. Archivos compartidos peligrosos

Estos archivos los tocan ambos. Antes de modificarlos, **avisar al otro**:

- `application/views/sisvent/layouts/sidemenu.php` (menú lateral)
- `application/views/sisvent/layouts/meta_header.php`
- `application/config/routes.php`
- `application/helpers/mam_helper.php`
- `application/controllers/sisvent/Login.php`
- `CLAUDE.md` y este `WORKFLOW.md`

Si dos personas tocan el mismo archivo, hacer rebase y resolver conflictos antes del PR.

## 7. División de áreas (orientativa)

| Área | Responsable principal | Archivos clave |
|------|----------------------|----------------|
| Contabilidad, Tesorería, Cartera | Alex | `controllers/sisvent/accounting/`, `admin/cashboxes/`, `admin/payments/` |
| Bots WhatsApp, Hub, Cola | Jorge | `controllers/sisvent/admin/Bots*.php`, `BotImport.php`, `views/bots/` |
| Envíos, Contrapagos | Compartido (avisar) | `admin/Envios.php`, `admin/Contrapagos.php` |
| Frontend móvil `/ventas` | Jorge | `controllers/ventas/`, `views/ventas/` |
| Inventario, Productos | Compartido | `admin/Inventory.php`, `admin/Products.php` |

Si necesitas tocar área del otro, **avisar primero**.

## 8. Reglas para Claude Code

Tanto la instancia de Claude de Alex como la de Jorge deben seguir estas reglas:

### Antes de cualquier cambio
- Verificar `git status` y `git branch --show-current`
- Si la rama actual es `master` o `feature/ledxury-bots`, **detenerse** y preguntar al usuario qué rama crear

### Al terminar cambios
- **Nunca** hacer `git push --force` ni `git reset --hard` sin avisar al usuario
- **Nunca** subir archivos sueltos al servidor con SCP/SSH cuando se puede usar git
- Hacer commits con mensaje descriptivo: `Modulo: que cambia`
- Push solo a la rama personal (`alex/...` o `jorge/...`)

### Para deploy a producción
- **No desplegar sin que el usuario lo pida explícitamente**
- Si el usuario pide deploy, recordarle: "¿avisaste al compañero?"
- Preferir `git pull` en el servidor sobre SCP de archivos individuales

### Migraciones DB
- Siempre crear archivo en `db/migrations/0XX_*.sql`
- Antes, verificar el número con `ls db/migrations/ | tail -3`
- Hacer la migración idempotente cuando sea posible

### Ramas y PRs
- Crear ramas con prefijo del usuario (`alex/...` o `jorge/...`)
- Sugerir crear PR cuando termine la feature, no mergear directamente
- Si detecta conflicto al rebase, parar y mostrar el conflicto al usuario antes de resolver

## 9. Cuando algo sale mal

### Conflicto de merge
1. **No hacer `git reset --hard`** sin avisar
2. Resolver el conflicto local, probar, commit, push
3. Si no sabes resolver: hacer commit del estado actual en una rama de respaldo y pedir ayuda

### Producción rota
1. Avisar inmediatamente por canal acordado (WhatsApp/teléfono)
2. Identificar el commit que rompió: `git log --oneline -10`
3. Revertir: `git revert <commit>` y push (NO `reset --hard` en producción)
4. Después analizar tranquilo en local

### Pérdida de cambios
- `git reflog` muestra todos los commits recientes incluso si "desaparecieron"
- Antes de pánico, revisar reflog

## 10. Comunicación

### Canal de coordinación
- WhatsApp/Slack/Discord (acordar uno) para avisos rápidos
- Avisar:
  - "Voy a desplegar X a producción"
  - "Estoy tocando archivo X (compartido)"
  - "Acabo de mergear PR #N"
  - "Producción tiene problema"

### Daily sync
- Un mensaje al final del día: "Hoy mergeé X, mañana sigo con Y"
- Push diario aunque sea trabajo a medias (en tu rama personal)

---

**Última actualización:** 2026-04-21
**Autores:** Alex + Jorge
