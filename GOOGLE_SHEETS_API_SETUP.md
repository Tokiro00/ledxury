# 🔧 Configuración de Google Sheets API para Write-Back

Esta guía te ayudará a configurar la escritura automática del `budget_id` de vuelta al Google Sheet (para marcar filas como procesadas).

## ¿Por qué es necesario?

El sistema puede **leer** Google Sheets sin autenticación (vía CSV export), pero para **escribir** necesita credenciales de Google Sheets API. Esto permite:

- ✅ Marcar automáticamente las filas procesadas con el ID del presupuesto creado
- ✅ Evitar procesar la misma fila dos veces
- ✅ Tener trazabilidad completa (qué presupuesto corresponde a cada fila del bot)

## 📋 Pasos de Configuración

### 1. Instalar Dependencias de Composer

```bash
cd /xampp/htdocs/dropshipping
composer install
```

Esto instalará `google/apiclient` que ya está incluido en `composer.json`.

### 2. Crear Proyecto en Google Cloud Console

1. Ve a [Google Cloud Console](https://console.cloud.google.com/)
2. Crea un nuevo proyecto o selecciona uno existente
3. Nombre sugerido: "Dropshipping Bot Import"

### 3. Habilitar Google Sheets API

1. En el menú lateral, ve a **APIs & Services > Library**
2. Busca "Google Sheets API"
3. Click en **Enable**

### 4. Crear Service Account

1. Ve a **APIs & Services > Credentials**
2. Click en **Create Credentials > Service Account**
3. Nombre: `dropshipping-bot-importer`
4. Role: **Editor** (o **Owner** si necesitas permisos completos)
5. Click **Done**

### 5. Generar Clave JSON

1. En la lista de Service Accounts, click en el que acabas de crear
2. Ve a la pestaña **Keys**
3. Click **Add Key > Create New Key**
4. Selecciona **JSON**
5. Click **Create** - se descargará un archivo JSON

### 6. Configurar Credenciales en el Proyecto

1. Renombra el archivo descargado a `google_sheets_credentials.json`
2. Cópialo a: `application/config/google_sheets_credentials.json`

```bash
# Ejemplo en Windows
copy C:\Users\TuUsuario\Downloads\proyecto-xxx-xxx.json C:\xampp\htdocs\dropshipping\application\config\google_sheets_credentials.json
```

### 7. Compartir el Google Sheet con el Service Account

**IMPORTANTE:** El Service Account necesita permisos de **Editor** en el Sheet.

1. Abre tu Google Sheet
2. Click en **Compartir** (botón azul arriba a la derecha)
3. Copia el email del Service Account (ejemplo: `dropshipping-bot-importer@proyecto-xxx.iam.gserviceaccount.com`)
   - Lo encuentras en el archivo JSON: `"client_email"`
4. Pégalo en el campo "Añadir personas y grupos"
5. Selecciona **Editor** como rol
6. **Desactiva** "Notificar a las personas"
7. Click **Compartir**

### 8. Agregar Columna PRESUPUESTO_ID al Sheet

El sistema escribirá el `budget_id` en la **columna J** (décima columna).

Agrega este encabezado en tu Google Sheet:

| A | B | C | D | E | F | G | H | I | J |
|---|---|---|---|---|---|---|---|---|---|
| Nombre | Documento | Dirección | ... | Fecha | Tipo_envio | ENVIADO | PRESUPUESTO_ID |

**Nota:** Si tu sheet tiene más/menos columnas, ajusta la columna en el código (línea 665 de `BotImport.php`).

## ✅ Verificar Configuración

Para verificar que todo está correcto:

1. Ejecuta una importación de prueba con 1-2 filas:
```
http://localhost/dropshipping/sisvent/rest/botimport/processSheet?sheet_id=TU_SHEET_ID&limit=2
```

2. Verifica en el Google Sheet que aparezca el número de presupuesto en la columna PRESUPUESTO_ID

3. Si no aparece, revisa los logs de PHP para ver el error:
```bash
tail -f C:\xampp\apache\logs\error.log
```

## 🔄 Funcionamiento Sin Google Sheets API

**El sistema funciona sin esta configuración.** Si no configuras las credenciales:

- ✅ Seguirá importando y creando presupuestos normalmente
- ❌ NO escribirá el `budget_id` de vuelta al sheet
- ⚠️ Debes marcar manualmente las filas como procesadas (columna ENVIADO)

Para trabajar sin API:
1. Marca la columna ENVIADO con "✓" después de importar
2. El sistema omitirá esas filas en futuras importaciones

## 📁 Estructura de Archivos

```
dropshipping/
├── application/
│   └── config/
│       ├── google_sheets_credentials.json      ← Tu archivo de credenciales
│       └── google_sheets_credentials.example.json  ← Plantilla
├── composer.json                               ← Ya incluye google/apiclient
└── vendor/                                     ← Se crea con composer install
    └── google/
        └── apiclient/
```

## 🐛 Solución de Problemas

### Error: "Class 'Google_Client' not found"
**Solución:** Ejecuta `composer install` en la raíz del proyecto.

### Error: "The caller does not have permission"
**Solución:** Verifica que compartiste el Sheet con el email del Service Account como **Editor**.

### No se escribe nada en el Sheet
**Solución:**
1. Verifica que el archivo `google_sheets_credentials.json` existe en `application/config/`
2. Verifica que el email del Service Account tiene permisos de Editor en el Sheet
3. Revisa los logs: `error_log('...')` en el método `write_budget_to_sheet()`

### Error: "Invalid credentials"
**Solución:**
1. Descarga de nuevo las credenciales desde Google Cloud Console
2. Verifica que copiaste el archivo completo (no solo una parte)
3. Asegúrate de que el archivo es JSON válido

## 🔐 Seguridad

**Recomendaciones:**

1. ✅ NO subas `google_sheets_credentials.json` a Git
2. ✅ Agrega a `.gitignore`:
   ```
   application/config/google_sheets_credentials.json
   ```
3. ✅ Usa permisos restrictivos en el archivo:
   ```bash
   chmod 600 application/config/google_sheets_credentials.json
   ```
4. ✅ El Service Account solo debe tener acceso a los Sheets necesarios

## 📖 Referencias

- [Google Sheets API - PHP Quickstart](https://developers.google.com/sheets/api/quickstart/php)
- [Service Account Authentication](https://cloud.google.com/docs/authentication/getting-started)
- [Google API PHP Client](https://github.com/googleapis/google-api-php-client)

---

**Fecha:** 2026-02-03
**Versión:** 1.0
