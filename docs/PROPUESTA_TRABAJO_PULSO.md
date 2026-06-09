# Pulso — Propuesta de trabajo y división de responsabilidades

**De:** Alex Alzate
**Para:** Jorge Cano
**Fecha:** Junio 2026
**Objetivo:** Acordar cómo dividimos el desarrollo de Pulso, las interfaces entre nuestros módulos y la forma de trabajo, para arrancar de inmediato.

---

## 1. La visión

Convertir el ERP actual (Ledxury) en **Pulso**: una plataforma multi-empresa tipo **Mastershop + Chatfuel integrado**. Cada empresa (tenant) que entra a la plataforma obtiene el negocio llave en mano:

- **Bot de WhatsApp que vende** (conversación IA, pedido automático)
- **ERP completo**: inventario, presupuestos, facturación, cartera
- **Logística Interrapidísimo** con la tarifa corporativa (remitente propio por empresa)
- **Contrapagos conciliados** y cuentas entre compañías automáticas
- **Contabilidad PUC** independiente por empresa

El diferencial frente a un Chatfuel genérico: nuestro bot está conectado al ERP — sabe el stock real, crea la factura, genera la guía, liquida comisiones. Nadie puede armar eso solo.

**Tenants iniciales:** Ledxury (datos actuales) y MAM-Online. Luego empresas externas.

---

## 2. Estado actual (qué ya está hecho)

### Multi-tenant (branch `alex/pulso-multitenant-fase1`, local, sin merge aún)

- Tabla `tenants` + columna `tenant_id` en ~129 tablas, histórico backfilled a Ledxury (tenant 1)
- `MY_Model` (application/core/): base class que filtra e inyecta `tenant_id` automáticamente — 42 modelos migrados
- Resolución de tenant por subdominio (`{slug}.pulso.test`) en `Backend_lib::resolveTenant()`
- JWT con claim `tid`; APIs y webhooks setean contexto con `set_tenant_context()`
- Platform admin (`users.is_platform_admin`): puede saltar entre empresas; CRUD de tenants en `/sisvent/admin/tenants` + switcher en navbar y en sidebar v2
- Numeración de documentos independiente por tenant (`tenant_invoice_counters`)
- Helpers globales en `mam_helper`: `current_tenant_id()`, `set_tenant_context()`, `apply_tenant()`, `is_platform_admin()`

### Bots (lo tuyo — ya operando en prod)

- Flujo completo: WhatsApp → BuilderBot Cloud → webhook → validación stock → presupuesto automático
- Cola de reintentos, aliases de productos, alternativas si agotado
- WhatsApp Web en el ERP, comisiones por bot, ROI Meta Ads

### Lo que falta (gaps identificados)

| Gap | Área |
|---|---|
| Tablas de bots sin `tenant_id` (`builderbot_configs`, `bot_sales_queue`, `bot_commission_*`, etc.) | Bots |
| `vendor_map`, `delivery_map`, `color_map` hardcoded en `BotImport.php` | Bots |
| Sin wizard self-service "crear bot" ni plantillas de prompt | Bots |
| ~23 modelos sin migrar a `MY_Model` + controllers con queries directas | Core |
| `Accounting_lib` no es tenant-aware internamente | Contabilidad |
| `Interrapidisimo_lib` usa sucursal fija de secrets (falta leer `tenants.inter_sucursal_id`) | Logística |
| Wizard de onboarding de empresa nueva (end-to-end) | Plataforma |
| DNS/SSL `pulso.app` + migration multi-tenant en prod | Infra |

---

## 3. División de responsabilidades propuesta

### Jorge — Bots & Conversacional

**Fase A — Bots multi-tenant (~1 semana)**
1. Migration: `tenant_id` en `builderbot_configs`, `builderbot_messages`, `builderbot_webhooks`, `bot_sales_queue`, `bot_product_aliases`, `bot_pending_alternatives`, `bot_commission_config/periods/details`, `bot_conversations`, `bot_messages`, `bot_conversation_tags`, `bot_appointments` (backfill a tenant 1)
2. Webhook resuelve tenant por `bot_config.tenant_id` (hoy lo deriva del vendor — ya hay un `set_tenant_context()` puesto como puente)
3. Panel de Bots filtrado por tenant: cada empresa ve solo sus bots/conversaciones/ventas/comisiones
4. Des-hardcodear `vendor_map` / `delivery_map` / `color_map` → moverlos a config por bot o tabla

**Fase B — Bot self-service (~1-2 semanas)**
5. Wizard "Crear bot" por tenant: canal (BuilderBot/Meta Direct) → credenciales → vendedor default → plantilla de prompt
6. Tabla `bot_templates` con prompts pre-escritos ("Venta", "Recuperación", "Soporte") con variables `{{empresa}}`, `{{catalogo}}`
7. Comisiones configurables por tenant (reemplazar el array fijo de 3 ciudades)

### Alex — Core ERP, Logística & Contabilidad

**Paralelo a Fase A:**
1. Migrar modelos restantes a `MY_Model` + controllers con queries directas (Salesboard, Tracking, Logistics)
2. `Accounting_lib` tenant-aware (asientos, auxiliares y periodos por empresa)
3. `Interrapidisimo_lib`: `CodigoConvenioRemitente` dinámico desde `tenants.inter_sucursal_id` (gestión comercial con Inter para registrar sucursales — en curso)
4. Test de aislamiento automatizado (CLI) que corre antes de cada deploy

**Después:**
5. Wizard onboarding de empresa nueva (datos → branding → bodega → usuario admin → sucursal Inter → bot inicial → catálogo)
6. Dashboard matriz (vista consolidada cross-tenant para platform admin)
7. Migration multi-tenant en prod + DNS/SSL `pulso.app`

---

## 4. Interfaces entre nuestros módulos (contratos)

Para no pisarnos, estos son los puntos de contacto y cómo los usamos:

### 4.1 Contexto de tenant (obligatorio en todo código nuevo)

```php
// En webhooks/APIs sin sesión, SIEMPRE después de autenticar:
set_tenant_context((int)$botConfig->tenant_id);

// En modelos: extender MY_Model y usar
$this->applyTenantFilter('alias');   // SELECT
$this->tenantInsert($table, $data);  // INSERT (inyecta tenant_id)

// En queries directas de controllers:
apply_tenant('alias');               // helper global
```

Regla: **ninguna query nueva a tablas transaccionales sin filtro de tenant.** El test de aislamiento (punto 3.4) validará esto en CI manual.

### 4.2 El webhook crea documentos — el core los procesa

- Tu lado termina cuando `bot_sales_queue` → `budgets` está creado con el `tenant_id` correcto.
- Mi lado garantiza que `Budgets_model::save()` (ya migrado) inyecta tenant y que todo lo downstream (factura, guía, contabilidad) respeta el tenant del presupuesto.
- Si necesitas crear clientes desde el bot: `Clients_model::save()` ya es tenant-aware.

### 4.3 Envío del bot por empresa

Cuando un pedido del bot de MAM-Online genere guía, `crearPreenvio()` usará el `inter_sucursal_id` del tenant (mi tarea 3). Tu lado no necesita tocar nada de Inter: solo asegurar el `tenant_id` correcto en el budget.

### 4.4 Esquema de DB

- Migrations numeradas secuenciales en `db/migrations/` — **antes de crear una, avisar al otro el número** para no chocar (la próxima libre es 062).
- Columna estándar: `tenant_id INT NOT NULL DEFAULT 1` + `INDEX idx_tenant (tenant_id)`.

---

## 5. Forma de trabajo propuesta

| Tema | Propuesta |
|---|---|
| **Branches** | Uno por persona/feature: `alex/...`, `jorge/...`. Master refleja prod. Merge solo tras validación local. |
| **Estado actual de branches** | Tengo `alex/pulso-multitenant-fase1` con 4 commits (foundation completa). Tus cambios recientes están sin integrar a master — propongo sincronizar primero: tú integras lo tuyo a master, yo hago rebase, y de ahí arrancamos limpios. |
| **Deploy a prod** | Por ahora sigue siendo SCP por archivo (no hay git en el server). **Regla: avisar antes de tocar prod** para no sobrescribirnos. Mediano plazo: instalar git en el server y deployar con `git pull`. |
| **Migrations en prod** | Solo después de validar en local con la DB clonada de prod (hay proceso de refresh documentado). La 060 multi-tenant aún NO está en prod — se sube cuando ambos lados estén listos. |
| **DB local** | Cada uno con copia fresca de prod (`mamdb` → local `ledxury`) + migration 060 aplicada encima. Te paso el script. |
| **Documentación** | `CLAUDE.md` en la raíz tiene la arquitectura completa actualizada (multi-tenant, gotchas de schema, deploy). Leerlo antes de arrancar. |
| **Sync** | Una llamada corta semanal + chat para bloqueos. Revisamos juntos cualquier cambio que toque las interfaces del punto 4. |

---

## 6. Cronograma tentativo

| Semana | Jorge | Alex |
|---|---|---|
| 1 | Migration tenant_id en bots + webhook tenant-aware | Modelos restantes + test aislamiento |
| 2 | Panel bots por tenant + des-hardcodear maps | Accounting_lib tenant-aware |
| 3 | Wizard crear bot + plantillas prompt | Inter sucursal por tenant + wizard empresa |
| 4 | Comisiones configurables | Dashboard matriz + preparar prod |
| 5 | — Integración conjunta, QA con MAM-Online como segundo tenant real — | |
| 6 | — Migration prod + DNS pulso.app + go-live MAM-Online — | |

---

## 7. Para acordar (responde sobre esto)

1. ¿Estás de acuerdo con la división Bots/Core o quieres ajustar algo?
2. ¿Cómo integramos tus cambios actuales que están fuera de master?
3. ¿Te sirve el contrato de interfaces del punto 4 o le falta algo de tu lado?
4. ¿El cronograma es realista con tu disponibilidad?
5. Número de migration: te reservo la **062** para tenant_id en bots, yo sigo desde 063. ¿OK?

Cualquier punto lo discutimos y ajustamos. La idea es arrancar esta semana.

— Alex
