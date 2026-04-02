# 🤖 Bot Import - Documentación

Sistema de importación automática de ventas desde Google Sheet (bot IA) hacia presupuestos de dropshipping.

## 📋 ¿Qué hace?

Este sistema:
1. ✅ Lee automáticamente el Google Sheet donde el bot registra las ventas
2. ✅ Crea o actualiza clientes en la base de datos
3. ✅ Identifica productos por cantidad de LEDs, voltaje y color
4. ✅ **NUEVO:** Soporta múltiples productos en una sola orden (ej: "20 blancos y 20 azules")
5. ✅ Crea presupuestos con toda la información y múltiples detalles
6. ✅ Omite filas ya procesadas (columna ENVIADO o PRESUPUESTO_ID)
7. ✅ **NUEVO:** Escribe el ID del presupuesto de vuelta al Sheet (requiere configuración opcional)

## 🚀 Uso Rápido

### Opción 1: Interfaz Web (Recomendado para pruebas)

Abre en tu navegador:
```
http://localhost/dropshipping/test_bot_import.html
```

### Opción 2: API Directa

```
GET /dropshipping/sisvent/rest/botimport/processSheet?sheet_id=xxx&gid=0&limit=10
```

## 📊 Estructura del Google Sheet

El bot debe tener estas columnas (en minúsculas, con o sin tildes):

| Columna | Ejemplo | Requerido |
|---------|---------|-----------|
| `nombre` | Jaime Álvaro Díaz Pinto | ✅ Sí |
| `documento` | 98385185 | ✅ Sí |
| `direccion` | Barrio X, Puerto Asís, Putumayo. Oficina Interrapidisimo | ❌ No |
| `modulos` | 40 módulos 6LED | ✅ Sí |
| `cantidad` | 40 | ✅ Sí |
| `voltaje` | 12V | ✅ Sí |
| `color` | Azul | ✅ Sí (soporta múltiples: "20 blancos y 20 azules") |
| `celular` | 3207820972 | ❌ No |
| `total` | $80.000 | ✅ Sí |
| `fecha` | 2025-01-15 | ❌ No |
| `tipo_envio` | Envío gratis | ❌ No |
| `ENVIADO` | ✓ | ❌ No (se marca después de procesar) |
| `PRESUPUESTO_ID` | 1234 | ❌ No (se llena automáticamente si Google Sheets API está configurado) |

## 🎨 Mapeo de Colores

El sistema mapea colores a letras de productos:

| Color (español/inglés) | Letra | Ejemplo Producto |
|------------------------|-------|------------------|
| Azul / Blue | E | 6LED-12V-E |
| Rojo / Red | C | 6LED-12V-C |
| Verde / Green | F | 6LED-12V-F |
| Amarillo / Yellow | K | 6LED-12V-K |
| Blanco / White | A | 6LED-12V-A |

## 🔄 Múltiples Productos por Orden (NUEVO)

El sistema ahora detecta y procesa automáticamente múltiples productos en una sola fila del Google Sheet.

### Formatos Soportados en el Campo "Color":

**Formato 1:** "X color1 y Y color2"
```
20 blancos y 20 azules
→ Crea: 20x 6LED-12V-A + 20x 6LED-12V-E
```

**Formato 2:** "X color1, Y color2"
```
30 azul, 10 blanco
→ Crea: 30x 6LED-12V-E + 10x 6LED-12V-A
```

**Formato 3:** Con "x" multiplicador
```
30x azul, 10x blanco
→ Crea: 30x 6LED-12V-E + 10x 6LED-12V-A
```

**Formato 4:** Saltos de línea o pipe "|"
```
30 azul
10 blanco
→ Crea: 30x 6LED-12V-E + 10x 6LED-12V-A
```

### Ejemplo Real:

Si el Google Sheet tiene:
- **Cantidad:** 40 módulos
- **Color:** "20 blancos y 20 azules"
- **Voltaje:** 12V

El sistema creará UN presupuesto con DOS detalles:
1. `6LED-12V-A` × 20 unidades
2. `6LED-12V-E` × 20 unidades

Los comentarios del presupuesto incluirán la lista completa de productos.

## 🔧 Configuración

### Vendedor por Defecto

El sistema usa **GerMam (ID: 1234567)** por defecto. Puedes cambiarlo con el parámetro `vendor`:

```
?vendor=12345678  (Julian Germam)
```

### Tienda por Defecto

**Medellín (ID: 1)** - No se puede cambiar actualmente.

### IVA

**Sin IVA (hasIva = 0)** - Los presupuestos del bot no incluyen IVA.

### Tipo de Envío por Defecto

**Interrapidisimo (ID: 5)** - "Envió a otra parte del país"

El sistema mapea automáticamente los textos del sheet al ID correspondiente:
- "envio gratis" o "envío gratis" → ID 5 (Interrapidisimo)
- "el cliente paga el envio" → ID 5 (Interrapidisimo)
- "domicilio en medellin" → ID 2 (Envío en Medellín)

## 📡 API Endpoints

### 1. Procesar Sheet

**URL:** `/sisvent/rest/botimport/processSheet`

**Método:** GET

**Parámetros:**
- `sheet_id` (requerido): ID del Google Sheet
- `gid` (opcional, default=0): Número de pestaña (0 para la primera)
- `limit` (opcional, default=50): Máximo de filas a procesar
- `vendor` (opcional): ID del vendedor a asignar

**Ejemplo:**
```
GET http://localhost/dropshipping/sisvent/rest/botimport/processSheet?sheet_id=16z1OxjyfduZITgIyYnsQ0g8HGRFP-ayXTXGqYP9zR0s&gid=0&limit=10
```

**Respuesta Exitosa:**
```json
{
  "ok": true,
  "summary": {
    "processed": 10,
    "created": 8,
    "errors": 1,
    "skipped": 1,
    "details": [
      {
        "row": 2,
        "status": "success",
        "budget_id": 1234,
        "client_id": 567,
        "data": {...}
      }
    ]
  },
  "total_rows": 10,
  "message": "Procesados: 10, Creados: 8, Errores: 1, Omitidos: 1"
}
```

**Respuesta con Error:**
```json
{
  "error": "No se pudo descargar el CSV. Verifica que el sheet sea público."
}
```

## 🔍 Lógica de Procesamiento

### 1. Cliente

- **Si existe** (por cédula/documento): Actualiza teléfono y dirección
- **Si NO existe**: Crea nuevo cliente con:
  - `vendor`: GerMam (1234567)
  - `store`: Medellín (1)
  - `retail`: 1 (cliente al detal)
  - `rate`: 0

### 2. Producto

El sistema construye el código del producto así:

**Formato:** `{NUM_LEDS}LED-{VOLTAJE}-{LETRA_COLOR}`

**Ejemplo de parseo:**
```
Entrada del bot:
- modulos: "40 módulos 6LED"
- color: "Azul 💙"
- voltaje: "12V"

Salida del sistema:
- Código producto: "6LED-12V-E"
- Precio: $1,200
```

### 3. Presupuesto

Se crea con:
- `state`: 0 (pendiente)
- `e_commerce`: 1 (venta online)
- `hasIva`: 0 (sin IVA)
- `list_price`: 0
- `deliverytypeId`: 5 (Interrapidisimo por defecto)
- `comments`: Incluye producto, color, voltaje, tipo de envío, dirección, cliente y teléfono

## ⚠️ Validaciones

El sistema **NO procesa** una fila si:
- ❌ Falta el nombre del cliente
- ❌ Falta el documento/cédula
- ❌ El producto no existe en la BD
- ❌ La columna ENVIADO ya tiene valor (ya fue procesada)

## 🐛 Manejo de Errores

Cada fila se procesa independientemente. Si una fila falla:
- ✅ Las demás continúan procesándose
- ✅ El error se registra en el resultado
- ✅ Se incluyen los datos de la fila problemática para debugging

## 🔐 Seguridad

⚠️ **IMPORTANTE:** El endpoint NO tiene autenticación actualmente.

**Recomendaciones para producción:**
1. Agregar API key o token de autenticación
2. Limitar el acceso por IP
3. Agregar rate limiting
4. Validar más estrictamente los datos de entrada

## 📝 Ejemplo Completo

### Paso 1: Preparar el Google Sheet

1. Asegúrate de que el Sheet sea **público** (Cualquiera con el enlace puede ver)
2. Verifica que tenga todas las columnas necesarias
3. Copia el ID del Sheet de la URL:
   ```
   https://docs.google.com/spreadsheets/d/16z1OxjyfduZITgIyYnsQ0g8HGRFP-ayXTXGqYP9zR0s/edit
                                            ↑ Este es el sheet_id
   ```

### Paso 2: Ejecutar la importación

**Opción A - Interfaz Web:**
```
1. Abrir: http://localhost/dropshipping/test_bot_import.html
2. Pegar el Sheet ID
3. Click en "Importar Ventas"
4. Ver resultados
```

**Opción B - CURL:**
```bash
curl "http://localhost/dropshipping/sisvent/rest/botimport/processSheet?sheet_id=16z1OxjyfduZITgIyYnsQ0g8HGRFP-ayXTXGqYP9zR0s&limit=5"
```

### Paso 3: Verificar resultados

1. **Clientes creados:** `/sisvent/business/clients`
2. **Presupuestos creados:** `/sisvent/commercial/budgets`

## 📝 Escribir Budget ID de Vuelta al Sheet (NUEVO)

El sistema ahora puede escribir automáticamente el ID del presupuesto creado de vuelta al Google Sheet en la columna `PRESUPUESTO_ID`.

### Beneficios:
- ✅ Trazabilidad completa: sabes exactamente qué presupuesto corresponde a cada fila del bot
- ✅ Evita duplicados: las filas con PRESUPUESTO_ID se omiten automáticamente
- ✅ Auditoría: puedes verificar qué filas se procesaron y cuándo

### Configuración:

Esta funcionalidad es **OPCIONAL**. El sistema funciona perfectamente sin ella.

Para habilitarla:
1. Ejecuta `composer install` en la raíz del proyecto
2. Sigue las instrucciones en [GOOGLE_SHEETS_API_SETUP.md](GOOGLE_SHEETS_API_SETUP.md)
3. Crea credenciales de Service Account en Google Cloud Console
4. Comparte el Google Sheet con el Service Account
5. Guarda las credenciales en `application/config/google_sheets_credentials.json`

Si no configuras esto, el sistema seguirá funcionando normalmente, pero NO escribirá el `budget_id` de vuelta al sheet.

## 🔄 Funcionalidades Completadas

- [x] ✅ Marcar automáticamente filas con PRESUPUESTO_ID (configuración opcional)
- [x] ✅ Soporte para múltiples productos por presupuesto
- [ ] ⏳ Validación de stock antes de crear presupuesto
- [ ] ⏳ Notificaciones por email cuando se crean presupuestos
- [ ] ⏳ Dashboard de estadísticas de importación
- [ ] ⏳ Autenticación con API key

## 🆘 Troubleshooting

### Error: "No se pudo descargar el CSV"
**Solución:** Verifica que el Google Sheet sea público (Compartir → Cualquiera con el enlace puede ver)

### Error: "Producto no encontrado: 6LED-12V-X"
**Solución:** Verifica que el producto exista en la BD o ajusta el mapeo de colores en el código

### Error: "No se pudo crear el cliente"
**Solución:** Verifica que la cédula no esté duplicada o que los campos obligatorios estén presentes

### Las filas se procesan múltiples veces
**Solución:** Agrega la columna ENVIADO y marca las filas procesadas, o configura Google Sheets API para que el sistema escriba automáticamente el PRESUPUESTO_ID

### No se detectan múltiples productos
**Solución:** Verifica el formato del campo "color". Formatos soportados:
- "20 blancos y 20 azules"
- "30 azul, 10 blanco"
- "30x azul, 10x blanco"

### El PRESUPUESTO_ID no se escribe en el Sheet
**Solución:**
1. Verifica que ejecutaste `composer install`
2. Verifica que el archivo `application/config/google_sheets_credentials.json` existe
3. Verifica que compartiste el Sheet con el Service Account como Editor
4. Revisa los logs de PHP: `C:\xampp\apache\logs\error.log`

## 📞 Soporte

Para dudas o problemas:
1. Revisa los logs en el resultado del endpoint
2. Verifica la estructura del Google Sheet
3. Prueba con un límite pequeño (5-10 filas) primero

---

**Versión:** 1.0
**Fecha:** 2026-02-02
**Autor:** Claude Code + Alex Alzate
