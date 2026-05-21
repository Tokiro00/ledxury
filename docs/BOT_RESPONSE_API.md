# BotResponse API — Contrato para BuilderBot

Endpoints en MAM para que BuilderBot **delegue la decisión de respuesta** al backend (patrón estilo "JSON API" de Chatfuel).

**Despliegue inicial:** 2026-05-20. Producción `https://ledxury.com`.

**Estado:** Fase 1 funcional. **NO está conectado a BuilderBot todavía** — el flow del bot sigue usando el webhook clásico `/webhook/builderbot`. Estos endpoints están disponibles cuando Jorge reconfigure los flows en `builderbot.cloud` para llamarlos.

---

## Autenticación

Header obligatorio: `X-Bot-Key: <bot_response_key>`

La key vive en `application/config/secrets.php` en producción (entrada `$config['bot_response_key']`). Pedírsela a Jhon si no la tienes. Sin esa key todos los endpoints devuelven `401 Unauthorized`.

---

## Endpoints

### 1. `POST /api/v1/bot/quote` — preview del pedido

**Propósito:** dado lo que el cliente pidió, **calcular** producto + precio + envío + verificar agotado. **NO crea budget** — es preview puro. Sirve para que el bot le muestre al cliente el resumen ANTES de confirmar.

**Request:**

```json
{
  "action": "preview_quote",
  "phone": "573114567890",
  "attributes": {
    "producto_solicitado": "5 modulos 6LED verdes 12v",
    "cantidad": 5,
    "ciudad": "medellin"
  }
}
```

**Respuestas posibles:**

#### A. Producto OK
```json
{
  "success": true,
  "messages": [
    "✅ Listo, este es tu pedido:",
    "5x MODULO 6LED 13CM 6 WATTS 12V DC VERDE",
    "Subtotal: $10.000",
    "Envío *gratis* 🚚",
    "*Total: $10.000*",
    "¿Confirmas el pedido? Responde *SI* para confirmar."
  ],
  "set_attributes": {
    "sku": "6LED-12V-F",
    "cantidad": 5,
    "precio_unit": 2000,
    "subtotal": 10000,
    "envio": 0,
    "total": 10000,
    "estado": "esperando_confirmacion"
  }
}
```

#### B. Producto agotado (con alternativas)
```json
{
  "success": true,
  "messages": [
    "😕 Ese color está agotado por ahora.",
    "Pero tengo disponible al mismo precio: Blanco, Verde, Rosado, Azul hielo",
    "¿Quieres cambiar a alguno? Respóndeme con el color o escribe NO para esperar al original."
  ],
  "set_attributes": {
    "estado": "agotado_con_alternativas",
    "sku_agotado": "3LED-12V-C",
    "alternativas": ["3LED-12V-A", "3LED-12V-F", "3LED-12V-G", "3LED-12V-I"],
    "alternativas_colores": ["Blanco", "Verde", "Rosado", "Azul hielo"]
  }
}
```

#### C. Producto no reconocido
```json
{
  "success": true,
  "messages": [
    "No reconocí el producto \"una lampara grande\" 🤔",
    "¿Me confirmas voltaje (12V o 24V), cantidad de LEDs (3, 6 o 12) y color?"
  ],
  "set_attributes": {
    "estado": "aclarar_producto"
  }
}
```

#### D. Agotado sin alternativas
```json
{
  "success": true,
  "messages": [
    "Lo siento, ese producto está agotado y no tengo alternativas similares 😕",
    "Un asesor te va a contactar para ayudarte."
  ],
  "set_attributes": {
    "estado": "agotado_sin_alternativa",
    "sku_agotado": "12LED-24V-C"
  }
}
```

---

### 2. `POST /api/v1/bot/confirm` — crear cliente y budget

**Propósito:** una vez que el cliente confirmó, **crear el cliente** (si no existe) y el **budget** en BD.

**Request:** debe incluir los atributos calculados por `/quote` más los datos del cliente:

```json
{
  "action": "confirm_quote",
  "phone": "573114567890",
  "vendor_id": "1234567",
  "store_id": 1,
  "attributes": {
    "nombre": "Juan Perez",
    "cedula": "71234567",
    "direccion": "Cra 50 # 30-20",
    "ciudad": "Medellín",
    "departamento": "Antioquia",
    "sku": "6LED-12V-F",
    "cantidad": 5,
    "precio_unit": 2000,
    "subtotal": 10000,
    "envio": 0,
    "total": 10000
  }
}
```

**Atributos requeridos:** `nombre`, `sku`, `cantidad`, `subtotal`, `total`. Adicionalmente `phone` válido (≥10 dígitos).

**Opcionales:** `cedula`, `direccion`, `ciudad`, `departamento`, `envio`, `vendor_id`, `store_id`.

**Defaults:** si no se pasa `vendor_id` usa `1234567` (GerMam Medellín). Si no se pasa `store_id` usa `1`.

**Respuesta exitosa:**

```json
{
  "success": true,
  "messages": [
    "✅ Pedido confirmado! Tu número de pedido es *#004492*",
    "Te llega entre 2 y 4 días hábiles 📦",
    "Si necesitas consultar tu guía, escribe *guía* en cualquier momento."
  ],
  "set_attributes": {
    "budget_id": 4492,
    "client_id": 5867,
    "estado": "confirmado"
  }
}
```

---

## Códigos de error

| HTTP | Causa |
|------|-------|
| `401` | Falta header `X-Bot-Key` o no coincide |
| `400` | Body JSON inválido / falta atributo requerido |
| `405` | Método HTTP distinto a POST |
| `500` | Error en BD al guardar |
| `503` | `bot_response_key` no está configurado en `secrets.php` |

Formato uniforme de error:
```json
{ "success": false, "error": "mensaje descriptivo" }
```

---

## Flow esperado en BuilderBot

```
[Cliente: "quiero modulos verdes"]
   ↓
[Bot pide datos: nombre, cedula, dirección, producto, cantidad]
   ↓
[Bot llama POST /api/v1/bot/quote]
   ↓
[Si estado == aclarar_producto → bot re-pregunta y reintenta]
[Si estado == agotado_con_alternativas → bot ofrece colores]
[Si estado == esperando_confirmacion → bot muestra mensajes y espera "SI"]
   ↓
[Cliente: "SI"]
   ↓
[Bot llama POST /api/v1/bot/confirm con atributos guardados]
   ↓
[Bot muestra mensajes de confirmación]
```

---

## Patrón de respuesta

Inspirado en Chatfuel:

- **`messages[]`** — array de strings. El bot las envía como mensajes separados (idealmente con `delay` corto entre cada uno).
- **`set_attributes{}`** — atributos a guardar en el perfil del usuario en BuilderBot. Quedan disponibles como `{{atributo}}` en el resto del flow.
- **`success`** — `true` indica que el procesamiento fue OK (no que la venta se cerró). Si `false`, hay un `error` adjunto.

---

## Lógica de matching de producto

El endpoint `/quote` resuelve `producto_solicitado` (texto libre) a un SKU usando:

1. **Match directo** del SKU si lo trae embebido: `"quiero 3LED-12V-F"` → `3LED-12V-F`
2. **Parseo estructurado**: extrae LEDs (`/\d+\s*led/i`), voltaje (`/\d+\s*v/i`) y color del `color_map` (azul=E, verde=F, rojo=C, blanco=A, blanco cálido=B, amarillo=D, rosado/fucsia=G, morado=H, azul hielo=I, verde limón=J, verde turquesa=K, etc.). Si tiene los 3, arma SKU `{N}LED-{V}V-{C}`.
3. **Alias en `bot_product_aliases`**: si el texto matchea algún alias normalizado, devuelve el SKU del alias.
4. Si nada coincide → estado `aclarar_producto`.

**Limitación:** si el cliente solo dice "módulos verdes" sin LED count, no resuelve. El bot debe pedir aclaración. Esto es intencional — mejor pedir info que adivinar mal.

---

## Política de envío (hardcoded simple)

- Ciudad contiene "medel" → `envío = 0`
- Cualquier otra ciudad → `envío = 15000`

Si esto debe ser más sofisticado (por departamento, por peso, etc.), ajustar `_previewQuote` en `BotResponse.php`.

---

## Cosas que NO hace este endpoint (todavía)

- ❌ No envía WhatsApp al cliente directamente — solo devuelve los textos para que BuilderBot los envíe.
- ❌ No verifica si el cliente tiene cartera vencida (oportunidad de mejora).
- ❌ No descuenta inventario (consistente con política Ledxury: stock siempre 0, se compra a MAM).
- ❌ No genera guía de envío.
- ❌ No notifica al vendedor.
- ❌ No reemplaza `/webhook/builderbot` — son flujos paralelos. El webhook clásico sigue procesando ventas como hoy.

---

## Para Jorge: configuración en BuilderBot

Cuando vayas a conectar BuilderBot a estos endpoints:

1. **API Request Block** apuntando a `https://ledxury.com/api/v1/bot/quote`
   - Method: `POST`
   - Headers: `Content-Type: application/json`, `X-Bot-Key: <key>`
   - Body: JSON con `action`, `phone`, `attributes`
2. Parsear la respuesta:
   - Iterar `messages[]` y enviar cada uno como texto
   - Guardar `set_attributes` en variables del flow
   - Branch lógico sobre `attributes.estado`:
     - `esperando_confirmacion` → mostrar y esperar SI/NO
     - `agotado_con_alternativas` → mostrar alternativas y esperar respuesta del cliente
     - `aclarar_producto` / `falta_producto` → volver al bloque de preguntar producto
3. Cuando cliente diga "SI", llamar `/api/v1/bot/confirm` con todos los atributos acumulados.

---

## Archivos del código (producción)

- **Controller:** `/var/www/html/application/controllers/api/BotResponse.php` (creado 2026-05-20)
- **Routes:** entradas agregadas al final de `/var/www/html/application/config/routes.php`
- **Auth key:** `/var/www/html/application/config/secrets.php` → `$config['bot_response_key']`
- **Backups previos a cambios:** `routes.php.bak-2026-05-20-botresponse`, `secrets.php.bak-2026-05-20-botresponse`

---

## Tests realizados (2026-05-20)

| # | Escenario | Resultado |
|---|-----------|-----------|
| 1 | POST sin auth | 401 ✓ |
| 2 | POST con key incorrecta | 401 ✓ |
| 3 | quote: 5 verdes 12V en Medellín, sin LED count | `aclarar_producto` ✓ |
| 4 | quote: 10 rojos 12V (`3LED-12V-C` está agotado) | `agotado_con_alternativas` con 8 colores ✓ |
| 5 | quote: "una lampara grande" | `aclarar_producto` ✓ |
| 6 | quote: 3 azules 24V 6LED en Cali (cobra envío) | `esperando_confirmacion`, total $21.000 ✓ |
| 7 | confirm con atributos válidos | Cliente + budget creados, response OK ✓ |
| 8 | Limpieza de test data | OK ✓ |
