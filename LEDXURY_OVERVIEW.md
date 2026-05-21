# Ledxury — Arquitectura y Funcionalidades

> Documento de contexto para compartir con instancias nuevas de Claude u otros desarrolladores.
> Última actualización: 2026-05-17.

---

## 1. Qué es Ledxury

**Negocio:** empresa colombiana de venta de módulos LED y accesorios eléctricos vía **WhatsApp + envío contraentrega** (mayoritariamente Interrapidísimo). Opera bajo modelo dropshipping: **no maneja inventario propio** — le compra a su proveedor MAM cuando hay ventas.

**Stack técnico:** ERP web + 3 PWAs + flujo de bots WhatsApp.

| Capa | Tecnología |
|---|---|
| Backend | PHP 8.2 sobre **CodeIgniter 3** |
| BD | MySQL/InnoDB — base `mamdb` en producción |
| CSS | Tailwind 1.8.7 |
| JS | jQuery 3.5 + vanilla ES6 |
| Bundler | Webpack 4 |
| Servidor | Apache en EC2 (Amazon Linux), dominio `ledxury.com` |
| Frontend móvil ventas | `/ventas` (server-rendered) + PWA estática `/pwa` |

---

## 2. Estructura del repositorio

```
application/
├── controllers/
│   ├── api/                       # JWT REST APIs
│   │   ├── V1.php                 # Endpoints vendedores PWA
│   │   ├── Executive.php          # Dashboard ejecutivo (admin)
│   │   └── ClientPortal.php       # Portal cliente token-based
│   ├── sisvent/
│   │   ├── commercial/            # Budgets, Invoices, Clients, Creditnotes, Smartcatalog
│   │   ├── business/              # Clients (admin), Users, Vendors, Reports
│   │   ├── admin/                 # Comisiones, Cashboxes, Bankaccounts, Bots, Devoluciones, Garantias, etc.
│   │   ├── accounting/            # Apertura, Entries, reportes contables
│   │   ├── store/                 # Inventario y catálogo
│   │   └── rest/
│   │       ├── BotImport.php      # Webhooks BuilderBot, Sheet sync (entrada principal de ventas)
│   │       └── Meta_whatsapp.php  # Número de Garantías vía Meta directo
│   ├── ventas/Ventas.php          # Panel móvil vendedores (Mis Comisiones, presupuestos)
│   └── Tienda.php                 # Storefront público
├── libraries/
│   ├── Backend_lib.php            # Auth guard: control([roles]), controlModule, controlBotsAccess
│   ├── Accounting_lib.php         # Double-entry: recordPayment, recordRefund, recordInvoice
│   ├── Commissions_lib.php        # Comisiones (post-v2.2.0: stub, todo va por bots)
│   ├── Builderbot_lib.php         # Cliente BuilderBot Cloud API
│   ├── Interrapidisimo_lib.php    # Cliente API oficial Interrapidísimo
│   ├── Interrapidisimo_tracker.php# Scraper público fallback
│   ├── Tracking_service.php       # Wrapper 17TRACK (carrier-agnostic)
│   ├── JWT_lib.php                # HS256
│   └── Api_response.php           # JSON estándar + CORS
├── models/                        # ~40 modelos (1 por entidad mayor)
├── views/
│   ├── sisvent/                   # ERP web (la mayoría)
│   ├── ventas/                    # Móvil vendedores (server-rendered)
│   └── tienda/                    # E-commerce público
└── config/
    ├── database.php               # mamdb / admindbmam
    ├── routes.php                 # Rutas explícitas /api/v1/*, /webhook/*, /tienda/*, /ventas/*
    ├── secrets.php                # jwt_secret, builderbot, interrapidisimo, AI keys (gitignored)
    └── ai_models.php              # Anthropic/Groq/Gemini fallback chain

pwa/
├── index.html                     # PWA vendedores (legacy, separada de /ventas)
├── clientes/                      # PWA clientes (acceso por token, sin login)
└── exec/                          # Dashboard ejecutivo

db/migrations/                     # SQL numerados 001_*.sql, 002_*.sql, ... — aplicar en orden
docs/                              # apps_script_mam_sync.js (Apps Script para Sheets)
```

---

## 3. Roles y permisos (verificado contra tabla `roles`)

| ID | Nombre | Alcance |
|----|--------|---------|
| 1 | superadmin | Acceso total |
| 2 | admin (Gerente) | Acceso operativo amplio |
| 3 | vendor (Vendedor) | Solo sus clientes, presupuestos, facturas |
| 4 | storer (Almacenista) | Logística, despachos |
| 10 | super-bots | Como rol 1 pero focalizado en bots/comisiones |

**Importante:** el CLAUDE.md del repo dice "rol 4 = contador" — está **desactualizado**. La tabla real es la de arriba.

**`bots_access`** (columna en `users`): flag binario que junto a rol 1/10 da acceso al módulo Bots WhatsApp.

**`allowed_bot_ids`** (columna en `users`, mig 047): CSV de bot_config_ids permitidos. Si está set, el usuario actúa como "operador limitado" y puede acceder a WhatsApp Web + Garantías + Devoluciones aunque no sea rol 1/10. Caso real: Carlos bodeguero solo gestiona el bot Garantías (id=4).

**Permisos por módulo:** tabla `role_permissions(role_id, permission_key)`. Cargados a sesión en login. Backend_lib::controlModule('clave') verifica.

---

## 4. Módulos principales

### 4.1 Ventas (Comercial)
- **Presupuestos** (`budgets`) → aprobación → **Facturas** (`invoices`)
- Cuando un bodeguero aprueba un presupuesto, se crea automáticamente la factura, se decrementa stock (clamp a 0), se generan asientos contables.
- **Devoluciones de cliente** (`refunds` legacy + `credit_notes` nuevo) → reversan cartera + restauran inventario + crean asiento `refund` automático.

### 4.2 Bots WhatsApp (Jorge maintained)
**Entrada principal de ventas.** Bots de IA en BuilderBot Cloud conversan con clientes y disparan webhooks:
- `POST /webhook/builderbot` → `BotImport::receiveBuilderbot` → cola `bot_sales_queue` → `process_webhook_sale` → cliente + presupuesto + factura
- `POST /webhook/builderbot-message` → `BotImport::receiveMessage` → detecta "PEDIDO CONFIRMADO", auto-clasifica conversación, dispara `_processPedidoConfirmado`
- Cada bot vive como una fila en `builderbot_configs` (4 bots activos: Medellín, Barranquilla, Bogotá, Garantías)
- WhatsApp Web (`/sisvent/admin/bots/whatsapp`): vista chat para que humanos respondan conversaciones del bot

### 4.3 Comisiones (post-v2.2.0 refactor de Alex)
**Comisiones directas por factura ELIMINADAS.** Todo va por bots:
- `bot_commission_config(user_id, commission_type, applies_to, percentage)` con tipos `admin_bots`, `operator`, `ads_manager`
- Un usuario puede acumular múltiples filas (ej. operador del bot Medellín + admin_bots stacked)
- Liquidación corre 21 del mes anterior → 20 del mes actual
- Filtro por **fecha de cobro** (`updated_at` de la factura), no de creación
- Admin: `sisvent/admin/comisiones`
- Vendor: `/ventas/comisiones` (móvil)

### 4.4 Contabilidad (Alex maintained, rama `alex/accounting-foundation`)
- PUC Colombia: `accounts_class → accounts_group → accounts_accounts → subaccounts → auxiliary_subaccounts`
- Cada factura genera 2 asientos: ventas (DR Cartera / CR Ventas) + COGS (DR Costo / CR Inventario)
- Cierre mensual por bodega bloquea entradas
- `Accounting_lib::recordRefund` reversa el de ventas (NO el de COGS automáticamente — anulación manual debe marcar ambos deleted=1)
- Anticipos a vendedores con cruce FIFO (v1.2.0)

### 4.5 Logística
- **Interrapidísimo** API oficial: cotizar, crear guía, obtener PDF, solicitar recogida, consultar estados
- Tracking via 17TRACK (fallback `Interrapidisimo_tracker.php` con scraper)
- **Devoluciones de transportadora** (`Devoluciones.php` admin): workflow `detectada → recibida → nota_credito_emitida | reembarcada | perdida`
- Contrapagos: módulo aparte para cobros vía carrier

### 4.6 PWAs estáticas en `pwa/`
- **`pwa/index.html`** — vendedores legacy (poco usado)
- **`pwa/clientes/`** — clientes con link tokenizado (`?t=TOKEN`), sin login. API: `/api/client/*`
- **`pwa/exec/`** — dashboard KPIs, JWT admin only

### 4.7 Móvil vendedores `/ventas`
**Server-rendered**, distinto de `pwa/`. Vendedor accede vía login normal. Pantallas:
- Dashboard (KPIs personales)
- Mis Comisiones (período liquidable + desglose por bot)
- Presupuestos pendientes
- Guías (tracking masivo)
- Fallidos (cola de ventas que el bot no pudo procesar)
- Chat interno

### 4.8 Tienda pública `/tienda`
E-commerce sin login. Catalogo + carrito + OTP por SMS. Rate limiting. Genera presupuesto + notifica al bot del cliente.

### 4.9 IA / Agentes (`Aiassistant`, `Agents`)
- Provider stack: Anthropic Claude (default `claude-sonnet-4`, fast `claude-haiku-4-5`), fallback Groq → Gemini
- Agentes automáticos: cobranza (mensaje generado vía AI por cliente moroso), extractor de campos para BotImport (Groq fallback con JSON mode)

---

## 5. Integraciones externas

### BuilderBot (BSP WhatsApp principal)
**Regla dura: Meta directo está OFF para la línea principal.** La cuenta WABA está conectada a BuilderBot — un número WhatsApp solo puede tener un BSP.

- Mensajes salientes: `POST https://app.builderbot.cloud/api/v2/{bot_id}/messages`
- Plantillas (rompen ventana 24h): `POST .../{bot_id}/whatsapp-template`
- Header: `x-api-builderbot: <api_key>`
- Plantillas se crean en la UI de BuilderBot, no en Meta
- Excepción: número de **Garantías** sí usa Meta directo via `Meta_whatsapp.php` (línea separada, no la principal)

### Interrapidísimo
- API REST en `www3.interrapidisimo.com/ApiVentaCredito`
- Headers `x-app-signature`, `x-app-security_token` desde `secrets.php`
- Pre-envío, guía PDF, recogida, estados, manifest

### AI
- Anthropic Claude primary
- Groq Llama 3.3 fallback (rápido + barato, usado para parseo)
- Gemini Flash fallback
- Config: `application/config/ai_models.php`

### Google Sheets
- Apps Script (`docs/apps_script_mam_sync.js`) replica ventas a Sheet de Drive
- BotImport puede leer Sheets directamente (legacy)

---

## 6. Base de datos `mamdb` (~110 tablas)

Organizadas por dominio:

| Dominio | Tablas clave |
|---|---|
| Sales/Commercial | budgets, budget_detail, invoices, invoice_details, refunds, refund_details, credit_notes, payments, noinvoices, nopayments |
| Bots/WhatsApp | builderbot_configs, builderbot_messages, builderbot_webhooks, bot_sales_queue, bot_conversations, bot_product_aliases, bot_commission_config/details/periods, bot_pending_alternatives (mig 046) |
| Inventario | products, inventory (siempre 0 en Ledxury), counts, transfers, blocked_products, catalog_overrides |
| Logística | shipping_guides, shipping_tracking_events, delivery_type, contrapago_batches, contrapago_payments |
| Contabilidad | accounts_class/group/accounts/subaccounts, entries, account_statement, accounting_periods, cost_centers, cashboxes, cash_movements, bank_accounts, bank_reconciliations, expenses (vendor liquidations), expense_records |
| Auth/audit | users, roles, role_permissions, client_tokens, login_fails, logs, user_activity_log, notifications |
| HR/KPIs | departments, department_kpis, sales_goal, company_goals, tracking_weekly, cierre_mensual |
| IA | ai_conversations, ai_messages, collection_activities |

---

## 7. Despliegue / Infraestructura

**Servidor producción:** AWS EC2 `ec2-user@13.220.150.61` (dominio `ledxury.com`).

- **NO hay auto-pull desde git.** `/var/www/html/.git/` no existe. Los deploys son manuales: `scp`/`rsync` de archivos al servidor, o edición directa vía SSH.
- Llave SSH: `db/Amazon_MAM.pem` (committed en repo — security flag conocido).
- Cron `ec2-user` ejecuta tareas de aplicación (tracking carrier, bot imports, retries) — no de deploy.
- Backups SQL en `/home/ec2-user/backups/`.

**Branches en GitHub:**
```
master                       → estable (lo que en teoría va a prod, pero deploy es manual)
feature/ledxury-bots         → rama de integración
alex/<feature>               → ramas de Alex (contabilidad, devoluciones, comisiones)
jorge/<feature>              → ramas de Jorge (bots, móvil ventas)
jhon/<feature>               → ramas de Jhon (acceso, fixes)
hotfix/<descripcion>         → urgencias
```

**Convención de migraciones:** `db/migrations/0XX_descripcion.sql`, numeradas consecutivas. Aplicar en orden con `mysql ... < archivo.sql`.

**Convención de versiones:** SemVer `vMAJOR.MINOR.PATCH`. Tag desde master tras mergear. Última en master: `v1.4.0` (parser Inter dual-format).

---

## 8. Historial reciente de cambios significativos

| Versión | Cambio |
|---|---|
| v1.1.0 | Contabilidad PUC retail + liquidaciones estructuradas + módulo gastos workflow |
| v1.2.0 | Anticipos a vendedores con cruce FIFO |
| v1.3.x | Estado de cuenta cronológico del vendedor + simplificación saldo neto |
| v1.4.0 | Parser Inter dual-format (CORTE + SOPORTE DETALLADO) |
| v2.0.x | `Commissions_lib` unifica 3 implementaciones de las 7 reglas históricas |
| v2.1.0 | **BREAKING:** simplificación comisiones 7 reglas → 2 |
| v2.2.0 | **Eliminación** total de comisiones directas por factura — todo vía `bot_commission_config` |
| mig 046 (2026-05-15) | `bot_pending_alternatives`: cuando bot detecta agotado, envía sugerencia de alternativa al cliente vía WhatsApp |
| mig 047 (2026-05-15) | `users.allowed_bot_ids` para operadores limitados (Carlos bodeguero → bot Garantías) |

---

## 9. Cosas no obvias / gotchas

1. **CLAUDE.md del repo dice rol 4 = contador. Está desactualizado.** Rol 4 = storer (almacenista). Verificar siempre contra `SELECT * FROM roles`.

2. **Inventario siempre debería estar en 0** porque Ledxury compra a MAM cuando hay ventas. La tabla `inventory` se decrementa al facturar (clamp a 0, no negativo) pero se descuadra silenciosamente porque MAM repone después. No es bug, es modelo de negocio.

3. **Validación de stock en `Budgets::approve` está desactivada por política** — comentario explícito en línea 1043: "stock puede quedar negativo y luego corregirse con la compra semanal". No re-activar sin alinear con el negocio.

4. **`process_webhook_sale` y `_processPedidoConfirmado` son dos vías de entrada distintas** del mismo flujo (venta del bot). La primera espera payload estructurado del webhook, la segunda parsea la conversación cuando el bot dice "PEDIDO CONFIRMADO". Ambas terminan creando budget/factura.

5. **Después de mergear a master, hay que hacer el deploy manual.** Producción no jala automático. Más de una vez se ha asumido "ya está en prod" cuando no.

6. **Los archivos `.bak-YYYY-MM-DD-*` en `/var/www/html/...`** son backups previos a edits SSH-directos. Sirven para rollback rápido. Limpiar de vez en cuando.

7. **Christina Morales** (rol 2, `idUser=52107500`) y **Carlos Alberto Henao** (rol 2, idUser `71218078` Y `712180788` — cuenta duplicada) son los admins operativos no-superadmin más activos. Sus permisos requieren atención fina por feature.

8. **El móvil `/ventas` no es lo mismo que `pwa/index.html`.** El primero es server-rendered, integrado con sesión; el segundo es PWA static con JWT. Diferentes audiencias.

---

## 10. Mapa rápido "dónde está X"

| Necesito... | Lugar |
|---|---|
| Crear factura desde presupuesto | `application/controllers/sisvent/commercial/Budgets.php::approve` |
| Anular factura (sin afectar contabilidad) | SQL: marcar `invoices.deleted=1` + entries deleted=1. Ver `memory/reference_anulacion_factura.md` |
| Enviar WhatsApp programáticamente | `Builderbot_lib::sendMessage($botConfig, $phone, $content)` |
| Bloquear producto por agotado | INSERT en `blocked_products` (también UI en `/sisvent/admin/bots/agotados`) |
| Crear cliente desde el flujo del bot | `Clients::quickCreate` (AJAX, JSON) |
| Editar cliente "ligero" desde presupuesto | `Clients::quickUpdate` (whitelist de campos, JSON) |
| Ver conversaciones del bot Garantías | `/sisvent/admin/bots/whatsapp/4` |
| Lista de pendientes alternativa agotado | `SELECT * FROM bot_pending_alternatives ORDER BY id DESC` |
| Logs de webhooks | `/var/www/html/application/logs/webhook_debug.log` |
| Reporte de comisiones liquidables | `/sisvent/admin/comisiones` (admin) / `/ventas/comisiones` (vendor) |

---

## 11. Contactos del equipo

- **Alex** — contabilidad, tesorería, cartera, módulo de devoluciones. Áreas críticas. Avisar antes de tocar `Devoluciones.php`, `Comisiones.php`, `Accounting_lib.php`, módulos en `application/controllers/sisvent/accounting/`.
- **Jorge** — bots WhatsApp, BotImport, móvil `/ventas`. Avisar antes de tocar `BotImport.php`, `Bots.php`, controllers en `application/controllers/ventas/`.
- **Jhon** — fixes transversales, accesos, hotfixes.

Reglas de coordinación en `WORKFLOW.md` del repo.

---

**Fin del documento.** Si lo abres en una sesión nueva de Claude para trabajar Ledxury, este overview cubre el 80% del contexto necesario para empezar. El detalle implementacional vive en CLAUDE.md (cuidado: outdated en algunos detalles de roles) y en el código mismo.
