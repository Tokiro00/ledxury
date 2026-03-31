# INTER RAPIDÍSIMO - API REST Integraciones B2B
## Documentación Técnica Completa para Implementación

**Proveedor:** Inter Rapidísimo S.A.
**Código documento:** GCV-COR-N-0
**Vigente desde:** 22/09/2023
**Versión:** 1
**Fecha documento:** Marzo 2025, Bogotá D.C.

---

## TABLA DE CONTENIDOS

1. [Información General](#1-información-general)
2. [Autenticación](#2-autenticación)
3. [Identificadores Básicos de Consumo](#3-identificadores-básicos-de-consumo)
4. [Servicio de Cotización de Envíos](#4-servicio-de-cotización-de-envíos)
5. [Servicio de Admisión de Preenvíos](#5-servicio-de-admisión-de-preenvíos)
6. [Servicios de Formatos de Impresión](#6-servicios-de-formatos-de-impresión)
7. [Servicio de Recogidas Esporádicas](#7-servicio-de-recogidas-esporádicas)
8. [Servicios de Información](#8-servicios-de-información)
9. [Notificación de Estados tipo PUSH](#9-notificación-de-estados-tipo-push)
10. [Reglas de Negocio](#10-reglas-de-negocio)
11. [Matriz de Estados Logísticos](#11-matriz-de-estados-logísticos)
12. [Errores Comunes](#12-errores-comunes)
13. [Ejemplos cURL](#13-ejemplos-curl)
14. [Causales de Devolución](#14-causales-de-devolución)

---

## 1. INFORMACIÓN GENERAL

### Objetivo
Permitir la integración de diversos clientes y plataformas a los servicios disponibles en INTER RAPIDÍSIMO.

### Alcance
Este manual aplica para la funcionalidad de la integración de los clientes interesados. El proceso inicia con la solicitud del cliente y finaliza con la integración de la API en la web de INTER RAPIDÍSIMO.

### Vocabulario
- **API:** Interfaces de Programación de Aplicaciones - conjuntos de definiciones y protocolos que permiten la integración y comunicación entre dos aplicaciones de software.
- **Integración tecnológica:** Proceso de conectar diversas aplicaciones entre sí para intercambiar información operativa o financiera.
- **Preenvío:** Modelo de solicitud en el cual el cliente completa los datos del envío de manera anticipada, asignándole un número consecutivo. Se registra como admitido cuando el envío es recogido por un recurso, recibido en un punto de venta o ingresado en el centro logístico de origen.

### Ambientes

| Ambiente | Base URL |
|----------|----------|
| QA (Pruebas) | `https://qawww3.interrapidisimo.co/` |
| Producción | *(Se proporciona al pasar a producción)* |

---

## 2. AUTENTICACIÓN

Todos los servicios requieren dos headers de autenticación:

| Header | Tipo | Descripción |
|--------|------|-------------|
| `x-app-signature` | String(250) | Firma digital (APIKEY) generada por el sistema Inter Rapidísimo para la aplicación de integración en consumo (3rd-Party) |
| `x-app-security_token` | String(250) | Campo `Access_token`, retorno de servicio de autenticación. Formato: `Bearer {token}` |

---

## 3. IDENTIFICADORES BÁSICOS DE CONSUMO

### 3.1 Forma de Pago (Integer)

| Valor | Nombre | Descripción |
|-------|--------|-------------|
| 2 | CRÉDITO | Por defecto |

### 3.2 Tipo de Identificación (Integer)

| Valor | Nombre |
|-------|--------|
| CC | CEDULA DE CIUDADANÍA |
| CE | CEDULA DE EXTRANJERÍA |
| NI | NIT |
| TI | TARJETA DE IDENTIDAD |

### 3.3 Estado de los Preenvíos

| Valor | Nombre |
|-------|--------|
| 11 | CREADO |
| 12 | ASOCIADO |
| 15 | ANULADO |

### 3.4 Estado de las Recogidas Esporádicas

| Valor | Nombre |
|-------|--------|
| 1 | RESERVADO |
| 3 | PARA FORZAR |
| 4 | CANCELADO POR EL CLIENTE |
| 5 | REALIZADA |
| 8 | TELEMERCADEO |

### 3.5 Tipo de Envío (Integer)

| Valor | Nombre | Descripción | Peso Máximo (Kg) |
|-------|--------|-------------|------------------|
| 1 | SOBRE CARTA | 0.0000 | 10.000 |
| 2 | SOBRE MANILA | 0.0000 | 20.000 |
| 3 | PAQUETE PEQUEÑO | 0.0000 | 20.000 |
| 4 | TULA | 0.0000 | 1,000.000 |
| 5 | CAJA PEQUEÑA | 40.000 | 50.000 |
| 6 | OTROS | 60.000 | 7,000.000 |
| 7 | BULTO | 60.000 | 7,000.000 |
| 9 | PAQUETE | 30.000 | 3,000.000 |
| 10 | CAJA | 60.000 | 1,200.000 |

### 3.6 Estados de la Guía (Integer)

| Valor | Nombre | Descripción |
|-------|--------|-------------|
| 1 | ADMITIDA | Se origina al momento de capturar los datos de la guía para entrega |
| 2 | CENTRO ACOPIO | Se origina al momento de ingresar el paquete a las bodegas de INTERRAPIDÍSIMO |
| 3 | TRÁNSITO NACIONAL | Paquete transportado en interconexiones departamentales |
| 4 | TRÁNSITO REGIONAL | Paquete transportado al interior de un departamento |
| 8 | TELEMERCADEO | Envío no se entrega de manera exitosa y se intenta contactar al cliente |
| 10 | DEVOLUCIÓN RATIFICADA | Se debe devolver el envío al remitente de forma exclusiva |
| 11 | ENTREGADA | El envío se entrega de forma exitosa |
| 15 | ANULADA | Se cancela un envío |
| 18 | TRÁNSITO URBANO | Envío se encuentra en ruta al interior de una ciudad |
| 21 | INCAUTADO | Aduana, militares o cuerpos armados del estado retienen el paquete |
| 31 | DISTRIBUCIÓN | Paquete está en ruta para entrega al cliente |

### 3.7 Tipos de Entrega (Integer)

| Valor | Nombre | Descripción |
|-------|--------|-------------|
| 1 | ENTREGA EN DIRECCIÓN | Entrega en la dirección solicitada (ciudad o municipio) |
| 2 | RECLAME EN OFICINA | Entrega a cliente el paquete en centro de servicio (tipo reclame oficina) |
| 3 | VEREDAS | Entrega en corregimientos, inspecciones, caseríos y veredas |
| 4 | KILÓMETROS (Inactivo) | Entrega cuando la dirección hace referencia a un kilómetro de una vía |
| 5 | CENTROS PENITENCIARIOS | Entrega en cárceles y centros de reclusión |
| 6 | GUARNICIÓN MILITAR | Entrega en bases y alojamientos militares |

### 3.8 Identificación Servicio (Integer)

| Valor | Nombre | Descripción |
|-------|--------|-------------|
| 1 | Rapi Hoy | Rapidísimo Hoy mensajería |
| 2 | Rapi AM | Rapidísimo AM mensajería |
| 3 | Mensajería | Mensajería Puerta a Puerta |
| 4 | Rapi Masivos | Rapi Masivos |
| 5 | Rapi Promocional | Rapi Promocional |
| 6 | Rapi Carga Terrestre | Carga Puerta a Puerta |
| 7 | Rapi Carga Contrapago | Rapi Carga Contrapago |
| 8 | Giros | Giros |
| 9 | Inter Viajes | Inter Viajes |
| 10 | Trámites | Trámites |
| 11 | Internacional | Servicio Internacional |
| 12 | Centros de Correspon. | Centros de Correspondencia |
| 13 | Rapi Personalizado | Rapi Personalizado |
| 14 | Rapi Envíos Contrapago | Rapi Envíos Contrapago |
| 15 | Notificaciones | Notificaciones Judiciales |
| 16 | Rapi Radicado | Carta Porte / Radicado |
| 17 | Rapi Carga | Carga Express Puerta a Puerta |
| 18 | Komprech | Komprech |
| 19 | Rapi Tula | Rapi Tula |
| 20 | Rapi Valores Msj | Rapi Valores Mensajería |
| 21 | Rapi Valores Carga | Rapi Valores Carga |
| 22 | Rapi Carga Consolidada | Rapi Carga Consolidada |
| 23 | Rapi Valijas | Rapi Valijas |
| 24 | Otros Ingresos | Otros Ingresos |

---

## 4. SERVICIO DE COTIZACIÓN DE ENVÍOS

Indicando un trayecto, datos de envío, y en función del contrato del cliente en el sistema de INTER RAPIDÍSIMO, se proporcionan valores de servicios, fechas y disponibilidad.

### Endpoint

```
Método: GET
URL: {baseUrl}/ApiServInterQA/api/CotizadorCliente/ResultadoListaCotizarValidaContrapago/{idCliente}/{idCiudadOrigen}/{idCiudadDestino}/{peso}/{valorDeclarado}/{idTipoEntrega}/{fecha}/AplicaContrapago
```

### Headers

| Header | Tipo | Descripción |
|--------|------|-------------|
| x-app-signature | String(250) | APIKEY |
| x-app-security_token | String(250) | Bearer {access_token} |

### Request (Path Parameters)

| Parámetro | Tipo | Descripción | Ejemplo |
|-----------|------|-------------|---------|
| IdCliente | integer | Identificador de cliente registrado en sistema InterRapidísimo | 3772 |
| IdCiudadOrigen | String(8) | Identificador de localidad establecido por INTER RAPIDÍSIMO para ciudad origen | 11001000 (Bogotá/Cundinamarca) |
| IdCiudadDestino | String(8) | Identificador de localidad establecido por INTER RAPIDÍSIMO para ciudad destino | 05001000 (Medellín/Antioquia) |
| Peso | decimal | Peso a liquidar del envío (peso tarifario) en Kilogramos | 1 |
| ValorDeclarado | decimal | Valor comercial del envío expresado en COP ($) | 250000 |
| IdTipoEntrega | String(3) | Identificador de Tipo de Entrega | 1 |
| Fecha | string | Fecha de Cotización | 02-10-2023 |
| AplicaContrapago | bool | Indicador de envío Contrapago (TRUE para sí, FALSE para no) | TRUE |

### Ejemplo Request

```
GET {baseUrl}/ApiServInterQA/api/CotizadorCliente/ResultadoListaCotizarValidaContrapago/3772/11001000/05001000/1/200000/1/02-10-2023/TRUE
```

### Response

| Parámetro | Tipo | Descripción | Ejemplo |
|-----------|------|-------------|---------|
| IdServicio | integer | Identificador del servicio para la cotización | 3 |
| Precio.Impuestos | decimal | Valor del impuesto | 0.0 |
| Precio.ValorKiloInicial | decimal | Valor del kilo inicial de la entrega | 10400.0000 |
| Precio.ValorKiloAdicional | decimal | Valor del kilo adicional de la entrega | 2640.0000 |
| Precio.Valor | decimal | Valor subtotal del servicio | 10400.0000 |
| Precio.ValorContraPago | decimal | Valor contrapago (si aplica) | 0.0 |
| Precio.ValorPrimaSeguro | decimal | Valor prima seguro relacionado con el servicio | 4000.0000 |
| PrecioCarga | decimal | Precio de carga | 0.0 |
| Mensaje | String(250) | Mensaje | null |
| NombreServicio | String(25) | Nombre del servicio | Mensajería |
| TiempoEntrega | integer | Tiempo estimado en días de la entrega | 1 |
| FormaPagoServicio.IdServicio | integer | Identificador del servicio para la cotización | 3 |
| FormaPagoServicio.IdFormaPago | integer | No aplica para cliente crédito integrado | 0 |
| FormaPagoServicio.Descripcion | String(250) | No aplica para cliente crédito integrado | Contado |
| FechaEntrega | String(250) | Fecha y tiempo estimado de la entrega | 2023-10-03T18:00:00 |

### Ejemplo Response

```json
[
  {
    "IdServicio": 3,
    "Precio": {
      "Impuestos": [],
      "ValorKiloInicial": 10400.0000,
      "ValorKiloAdicional": 2640.0000,
      "Valor": 10400.0000,
      "ValorContraPago": 0.0,
      "ValorPrimaSeguro": 4000.0000
    },
    "PrecioCarga": null,
    "Mensaje": null,
    "NombreServicio": "Mensajería",
    "TiempoEntrega": "1",
    "FormaPagoServicio": {
      "IdServicio": 3,
      "FormaPago": [
        { "IdFormaPago": 0, "Descripcion": "Contado" },
        { "IdFormaPago": 0, "Descripcion": "AlCobro" }
      ]
    },
    "fechaEntrega": "2023-10-03T18:00:00"
  }
]
```

---

## 5. SERVICIO DE ADMISIÓN DE PREENVÍOS

Servicio que genera números de preenvío en respuesta a solicitudes enviadas por el cliente.

### Endpoint

```
Método: POST
URL: {baseUrl}/ApiVentaCreditoQA/api/Admision/InsertarAdmision
Content-Type: application/json
```

### Headers

| Header | Tipo | Descripción |
|--------|------|-------------|
| x-app-signature | String(250) | APIKEY |
| x-app-security_token | String(250) | Bearer {access_token} |

### Request Body

| Parámetro | Tipo | Descripción | Requerido | Ejemplo |
|-----------|------|-------------|-----------|---------|
| IdClienteCredito | integer | Identificador del cliente | Sí | 2538 |
| CodigoConvenioRemitente | integer | Identificador de la sucursal del cliente en sistema (remitente del envío) | Sí | 11991 |
| IdTipoEntrega | Integer | Identificador tipos de Entrega | Sí | 1 |
| AplicaContrapago | bool | Indicador de envío con Contrapago (True/False) | Sí | "False" |
| IdServicio | integer | Identificador del Servicio | Sí | 3 |
| Peso | decimal | Peso bruto del envío (peso en báscula) en Kg | Sí | 55 |
| Largo | decimal | Medida del largo del envío (cm) | Sí | 10.3 |
| Ancho | decimal | Medida del ancho del envío (cm) | Sí | 11.5 |
| Alto | decimal | Medida del alto del envío (cm) | Sí | 45.3 |
| DiceContener | String(50) | Descripción del contenido de envío | Sí | "No. Orden 123456 - Ropa deportiva" |
| ValorDeclarado | decimal | Valor declarado por el remitente | Sí | 250000 |
| IdTipoEnvio | integer | Identificador del tipo de envío | Sí | 1 |
| IdFormaPago | integer | Identificador Forma de pago del envío | Sí | 2 |
| NumeroPieza | integer | Número de piezas del envío (por defecto 1) | Sí | 1 |
| **Destinatario** | **Object** | **Objeto con la información del destinatario** | **Sí** | |
| Destinatario.tipoDocumento | String(2) | Identificador tipo de identificación del destinatario | Sí | "CC" |
| Destinatario.numeroDocumento | String(25) | Número de identificación del destinatario | Sí | "1020753895" |
| Destinatario.nombre | String(50) | Nombre del destinatario | Sí | "Juan Carlos" |
| Destinatario.primerApellido | String(50) | Primer apellido del destinatario (si es tipo documento NIT, enviar null) | Condicional | "Solano" |
| Destinatario.segundoApellido | String(50) | Segundo Apellido del destinatario (opcional, enviar null) | No | "Martínez" |
| Destinatario.Telefono | String(25) | Número telefónico del destinatario | Sí | "12939648" |
| Destinatario.Direccion | String(250) | Dirección de entrega del envío | Sí | "Avenida Calle 3 # 41a-57B Edificio 123 Torre 3 APTO 43" |
| Destinatario.idDestinatario | integer | Campos requeridos en un proceso posterior (dejar en 0) | Sí | 0 |
| Destinatario.idRemitente | integer | Campos requeridos en un proceso posterior (dejar en 0) | Sí | 0 |
| Destinatario.idLocalidad | String(8) | Identificador de localidad INTER RAPIDÍSIMO para ciudad destino | Sí | "11001000" |
| Destinatario.CodigoConvenio | integer | Código del convenio relacionado con el cliente destino (por defecto 0) | Sí | 0 |
| Destinatario.ConvenioDestinatario | integer | ID de la sucursal del cliente cuando el destinatario es cliente convenio (por defecto 0) | Sí | 0 |
| Destinatario.correo | String(50) | Correo electrónico del destinatario | Sí | "juan.c.solano.v@mail.com" |
| DescripcionTipoEntrega | String(50) | Descripción del tipo de entrega seleccionado (opcional, se envía en blanco) | No | "" |
| NombreTipoEnvio | String(50) | Descripción del tipo de envío seleccionado | No | "" |
| CodigoConvenio | integer | Código del convenio relacionado con el cliente destino (por defecto 0) | Sí | 0 |
| IdSucursal | integer | Id de la sucursal del convenio (por defecto 0) | Sí | 0 |
| IdCliente | integer | Id del cliente convenio (por defecto 0) | Sí | 0 |
| **RapiRadicado** | **Object** | **Objeto solo para servicio id 16 RapiRadicado (puede descartarse si no se usa)** | **Condicional** | |
| RapiRadicado.numerodeFolios | integer | Número de folios de la radicación (solo para servicio 16, en los demás casos 0) | Condicional | 7 |
| RapiRadicado.CodigoRapiRadicado | integer | Código generado del RapiRadicado (solo para servicio 16, en los demás casos 0) | Condicional | 25541645 |
| Observaciones | String(250) | Descripción adicional del remitente sobre el envío | No | "Al frente del centro comercial xxxx" |

### Ejemplo Request

```json
{
  "IdClienteCredito": "7227",
  "CodigoConvenioRemitente": "55729",
  "IdTipoEntrega": "1",
  "AplicaContrapago": false,
  "IdServicio": "3",
  "Peso": 2,
  "Largo": 10,
  "Ancho": 10,
  "Alto": 10,
  "DiceContener": "prueba contenido",
  "ValorDeclarado": 25000,
  "IdTipoEnvio": "3",
  "IdFormaPago": "2",
  "NumeroPieza": 1,
  "Destinatario": {
    "tipoDocumento": "CC",
    "numeroDocumento": "123456789",
    "nombre": "prueba nombre",
    "primerApellido": "prueba apellido",
    "segundoApellido": null,
    "telefono": "3003000000",
    "direccion": "prueba direccion",
    "idRemitente": 0,
    "idDestinatario": 0,
    "idLocalidad": "41791000",
    "CodigoConvenio": 0,
    "ConvenioDestinatario": 0,
    "correo": "prueba correo"
  },
  "DescripcionTipoEntrega": "",
  "NombreTipoEnvio": "",
  "CodigoConvenio": "0",
  "IdSucursal": "0",
  "IdCliente": "0",
  "RapiRadicado": {
    "numerodeFolios": 0,
    "CodigoRapiRadicado": 0
  },
  "Observaciones": "Prueba"
}
```

### Response

| Parámetro | Tipo | Descripción | Ejemplo |
|-----------|------|-------------|---------|
| IdPreenvio | integer | Consecutivo interno del Preenvío generado | 206075 |
| numeroPreenvio | long | Número de guía del Preenvío generado | 240000000406 |
| fechaVencimiento | date | Fecha y hora del vencimiento, según parametrización del sistema | 2021-07-10T15:59:09.343 |
| fechaCreacion | date | Fecha y hora de generación del preenvío | 2021-07-07T15:59:09.343 |
| valorFlete | decimal | Valor del flete de transporte según parametrización interna | 300.00 |
| valorSobreFlete | decimal | Valor prima seguro del transporte, según parametrización interna | 1000.00 |
| valorServicioContrapago | decimal | Valor del servicio de contrapago a ser facturado al cliente corporativo (si no aplica, valor viaja en 0) | 0.0 |

### Ejemplo Response

```json
{
  "idPreenvio": 1160596,
  "numeroPreenvio": 240000037974,
  "fechaVencimiento": "2024-01-03T09:30:49.08",
  "fechaCreacion": "2023-10-03T09:30:48.4908712-05:00",
  "valorFlete": 11900.0000,
  "valorSobreFlete": 1000.0000,
  "valorServicioContraPago": 0.0
}
```

---

## 6. SERVICIOS DE FORMATOS DE IMPRESIÓN

### 6.1 Etiqueta Simplificada (Media Carta)

Funcionalidad para consultar preenvíos ya generados y obtener el formato de la guía correspondiente para su impresión.

```
Método: GET
URL: {baseUrl}/ApiVentaCreditoQA/api/Admision/ObtenerBase64PdfPreGuia/{numeroguia}
```

#### Request

| Parámetro | Tipo | Descripción | Ejemplo |
|-----------|------|-------------|---------|
| NumeroGuia | long | Número de guía generado | 2400057735 |

#### Response

| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| Bytes | Arreglo de bytes | Archivo codificado en Base64 de los formatos de guía solicitados. El modelo generado es formato Statement sobre tamaño Carta. |

**Nota:** Pueden convertir el código generado (arreglo de bytes codificado en Base64) decodificándolo a PDF.

### 6.2 Etiqueta Pequeña (Cuarto de Página)

Consulta de preenvío generado, que devuelve la guía en formato de un cuarto de página.

```
Método: GET
URL: {baseUrl}/ApiVentaCreditoQA/Api/Admision/ObtenerBase64PdfPreGuiaFormatoPeq/{numeroguia}
```

#### Request

| Parámetro | Tipo | Descripción | Ejemplo |
|-----------|------|-------------|---------|
| NumeroGuia | long | Número de guía generado | 2400057735 |

#### Response

| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| Bytes | Arreglo de bytes | Archivo codificado en Base64 de los formatos de guía solicitados |

### 6.3 Etiquetas por Lote

Funcionalidad para consultar uno o varios preenvíos ya generados, ya sea mediante un listado específico o en un rango de fechas. Devuelve un arreglo de bytes correspondiente al archivo PDF con los formatos de las etiquetas. Permite seleccionar formato media carta o cuarto de página.

```
Método: POST
URL: {baseUrl}/ApiVentaCreditostg/api/Admision/ObtenerBase64PdfPreGuias/
Content-Type: application/json
```

#### Request

| Parámetro | Tipo | Descripción | Ejemplo |
|-----------|------|-------------|---------|
| IdCliente | long | Identificador del cliente crédito en sistema de INTER RAPIDÍSIMO | 3931 |
| IdSucursal | long | Identificador de la sucursal del cliente (remitente del envío) | 17880 |
| PorRangoFecha | Boolean | true = guías por rango de fecha y hora; false = por listado de guías. Default: false | true |
| LTSPREGUIAS | Array | Listado de guías separadas por coma (máx 150 guías por solicitud). Si PorRangoFecha=true, se ignora | [240000000001, 240000000002, ...] |
| FechaInicio | DateTime | Fecha inicio del rango (solo si PorRangoFecha=true). No puede ser mayor a FechaFinal | 2021-08-10T00:01 |
| FechaFinal | DateTime | Fecha final del rango (solo si PorRangoFecha=true). No puede ser menor a FechaInicio | 2021-08-12T23:59 |
| Formato | Integer | Formato a generar: 1 = media carta, 2 = pequeña. Default: 1 | 2 |

#### Ejemplo Request

```json
{
  "IdCliente": 1057,
  "IdSucursal": 2980,
  "PorRangoFecha": false,
  "LtsPreGuias": [240000002748, 240000002745, 240000002741],
  "FechaInicio": null,
  "FechaFinal": null,
  "Formato": 2
}
```

#### Response

| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| fechaEjcucion | DateTime | Fecha y hora de la ejecución del servicio (API) |
| pdfGuias | Bytes | Arreglo de bytes que representa el archivo codificado en Base64 |
| msjError | String(250) | Mensaje de error o excepción en el resultado del servicio |
| ltsPreenviosNoIncluidos | Array | Listado de guías con excepción (errores o no corresponden al cliente) |

#### Ejemplo Response

```json
{
  "fechaEjcucion": "2022-02-24T09:54:56.4947638-05:00",
  "pdfGuias": "JVBERi0xLjUNCiWDkvr+DQo4OCA...",
  "msjError": "Algunos de estos preenvíos no corresponden al cliente y sucursal ingresados",
  "ltsPreenviosNoIncluidos": [240000004852, 240000004851]
}
```

### 6.4 Planilla de Preenvíos

Funcionalidad para generar un informe en PDF como control operativo donde se pueden relacionar los preenvíos que van a ser entregados al recurso que realiza la recogida de los envíos.

```
Método: POST
URL: {baseUrl}/ApiVentaCreditoQA/api/Planilla/GenerarPlanillaRecoleccionPreenvios
Content-Type: application/json
```

#### Request

| Parámetro | Tipo | Descripción | Ejemplo |
|-----------|------|-------------|---------|
| idCliente | Integer | Identificador del cliente crédito en sistema | 6486 |
| idSucursal | Integer | Identificador de la sucursal del cliente (remitente del envío) | 27185 |
| listaNumPreenvios | Array | Guías preadmitidas (preenvíos) a ser entregados al mensajero. Solo preenvíos con prefijo 24 generados vía API | [240000031973, 240000031974, 240000031975, 240000031976] |

#### Ejemplo Request

```json
{
  "idCliente": 6486,
  "idSucursal": 27185,
  "listaNumPreenvios": [240000031973, 240000031974, 240000031975, 240000031976]
}
```

#### Response

| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| numeroPlanilla | String(8) | Consecutivo generado de forma automática del número de planilla |
| fechaCreacion | String(250) | Fecha y hora de generación de la planilla |
| mensajeCantidadMaximaPreenvios | String(250) | Mensaje de advertencia cuando se sobrepasa el número máximo parametrizado de preenvíos por planilla |
| numerosPreenviosNoIncluidos | Array | Arreglo de preenvíos que sobrepasan el número máximo de preenvíos aceptados en un único consumo |
| mensajePreenviosInvalidos | String(250) | Mensaje de advertencia que indica que algunos números de guías no son válidos o no corresponden al cliente |
| numerosPreenviosInvalidos | Array | Arreglo de preenvíos no válidos incluidos en la entrada del servicio (sin prefijo 24 o no corresponden al cliente) |
| arregloBytesPlanilla | Base64 | Planilla codificada en Base64 (formato de planilla en PDF) |

---

## 7. SERVICIO DE RECOGIDAS ESPORÁDICAS

Funcionalidad utilizada para programar, basado en una preselección de preenvíos, una orden de recogida por demanda. Estas recogidas esporádicas no afectan la programación de las recogidas fijas configuradas para el cliente.

```
Método: POST
URL: {baseUrl}/ApiVentaCreditoQA/api/Recogida/InsertarRecogidaCliente/
Content-Type: application/json
```

### Headers

| Header | Tipo | Descripción |
|--------|------|-------------|
| x-app-signature | String(250) | APIKEY |
| x-app-security_token | String(250) | Bearer {access_token} |

### Request

| Parámetro | Tipo | Descripción | Ejemplo |
|-----------|------|-------------|---------|
| IdClienteCredito | Long | Identificador del cliente crédito en sistema de INTER RAPIDÍSIMO | 6486 |
| IdSucursalCliente | Long | Identificador de la sucursal del cliente (remitente del envío) | 27185 |
| listaNumPreenvios | Array | Arreglo de 1 o varias guías que estarán relacionadas con la Recogida Esporádica solicitada | [240000031973, 240000031974, 240000031975] |
| fechaRecogida | DateTime | Fecha y hora de la recogida esporádica según el horario operativo. Si se solicita fuera de horario, se mostrará un mensaje de error | "2021-08-26T14:00:01.3312" |

### Ejemplo Request

```json
{
  "IdClienteCredito": "7232",
  "IdSucursalCliente": "57385",
  "listaNumPreenvios": [240000037975, 240000037976],
  "fechaRecogida": "2023-10-17 14:00"
}
```

### Response

| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| IdRecogida | Integer | Número de recogida esporádica, generado por nuestros sistemas |
| cantidadPreenvios | Integer | Cantidad de guías preadmitidas relacionados en la Recogida generada |
| fechaSolicitud | Date | Fecha y hora en la que se requiere la Recogida |
| pesoTotal | Float | Sumatoria del peso de todos los envíos individuales incluidos |
| mensajePreenviosAsociados | String(100) | Mensaje cuando alguno de los preenvíos están relacionados a otra Recogida previa |
| preenviosAsociados | Array | Guías preadmitidas no relacionados en la solicitud (solo aparece cuando se intentan generar con guías relacionadas a alguna Recogida previa) |
| mensajeCantidadMaximaPreenvios | String(100) | Mensaje si se sobrepasa el límite máximo de guías relacionadas a una misma Recogida |
| preenviosNoIncluidos | Array | Guías no relacionados en la solicitud (solo aparece cuando se sobrepasa el límite máximo de guías permitidas) |

### Ejemplo Response

```json
{
  "idRecogida": 3722568,
  "cantidadPreenvios": 2,
  "fechaSolicitud": "2023-10-17T14:00:00",
  "pesoTotal": 2.0,
  "mensajePreenviosAsociados": "La recogida se generó Exitosamente.",
  "preenviosAsociados": [240000037975, 240000037976],
  "mensajePreenviosNoIncluidos": "",
  "preenviosNoIncluidos": [],
  "mensajeCantidadMaximaPreenvios": ""
}
```

---

## 8. SERVICIOS DE INFORMACIÓN

### 8.1 Consulta de Estados de los Envíos

Funcionalidad para consultar uno o varios envíos y obtener los respectivos estados.

```
Método: POST
URL: {baseUrl}/ApiVentaCreditoQA/api/ClientesCredito/ConsultarEstadosGuiasCliente
Content-Type: application/json
```

#### Request

| Parámetro | Tipo | Descripción | Ejemplo |
|-----------|------|-------------|---------|
| IdCliente | Integer | Identificador del cliente crédito en sistema de INTER RAPIDÍSIMO | 4288 |
| numeroGuias | Array | Arreglo de uno o varios envíos admitidos (máximo 15, separados por coma) | [240000005422, 240000005423, 240000005424] |

#### Ejemplo Request

```json
{
  "idCliente": 4288,
  "numeroGuias": [240000005422]
}
```

#### Response

La respuesta por cada número de guía muestra los estados con los que han contado, ordenados desde el reciente al hasta el inicial:

- **Estados Logísticos:** `estadosGuia`
- **Estados de los Preenvíos:** `estadosPreenvio`
- **Estados de las Recogidas Esporádicas:** `estadosRecogida`

| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| numeroGuia | Integer | Número de guía consultada |
| detalleMotivoDevolucion | n/a | Campo en construcción |
| idEstadoGuia | Integer | Código del estado logístico, del preenvío o de la recogida esporádica |
| nombreEstado | String(50) | Nombre del estado logístico, del preenvío o de la recogida esporádica |
| idLocalidadOrigen | String(8) | Identificador DANE de la ciudad de origen |
| idLocalidadDestino | String(8) | Identificador DANE de la ciudad de destino |
| nombreCiudadOrigen | String(50) | Ciudad con iniciales de departamento y país del origen |
| nombreCiudadDestino | String(50) | Ciudad con iniciales de departamento y país del destino |
| idClienteCredito | Integer | Id del cliente que realiza la consulta |
| fechaConsulta | DateTime | Fecha y hora en la que se realiza la consulta del estado |
| fechaEstado | DateTime | Fecha y hora del estado logístico reportado |
| mensajeGuiasNoCliente | String(255) | Mensaje de excepción cuando alguna guía consultada no pertenece al cliente o no se encuentra admitida |
| listadoGuiasNoCliente | Array | Guías que no pertenezcan al cliente |

#### Ejemplo Response

```json
{
  "listadoGuias": [
    {
      "numeroGuia": 240000016622,
      "detalleMotivoDevolucion": null,
      "estadosGuia": [
        {
          "idEstadoGuia": 1,
          "nombreEstado": "Admitida",
          "idLocalidadOrigen": "11001000",
          "idLocalidadDestino": "19698000",
          "nombreCiudadOrigen": "BOGOTA\\CUND\\COL",
          "nombreCiudadDestino": "SANTANDER DE QUILICHAO\\CAUC\\COL",
          "idClienteCredito": 1057,
          "fechaConsulta": "2022-11-25T15:05:35.13",
          "fechaEstado": "2022-11-25T15:05:35.17"
        }
      ],
      "estadosPreenvio": [
        {
          "idEstadoGuia": 11,
          "nombreEstado": "Creado",
          "idLocalidadOrigen": "11001000",
          "idLocalidadDestino": "19698000",
          "nombreCiudadOrigen": "BOGOTA\\CUND\\COL",
          "nombreCiudadDestino": "SANTANDER DE QUILICHAO\\CAUC\\COL",
          "idClienteCredito": 1057,
          "fechaConsulta": "0001-01-01T00:00:00",
          "fechaEstado": "2022-11-25T13:26:32.403"
        }
      ],
      "estadosRecogida": [
        {
          "idEstadoGuia": 5,
          "nombreEstado": "Realizada",
          "idLocalidadOrigen": "11001000",
          "idLocalidadDestino": "19698000",
          "nombreCiudadOrigen": "BOGOTA",
          "nombreCiudadDestino": "SANTANDER DE QUILICHAO\\CAUC\\COL",
          "idClienteCredito": 1057,
          "fechaConsulta": "0001-01-01T00:00:00",
          "fechaEstado": "2022-11-25T15:06:32.573"
        }
      ]
    }
  ],
  "mensajeGuiasNoCliente": null,
  "listadoGuiasNoCliente": null
}
```

### 8.2 Consulta de Localidades

Funcionalidad utilizada para consultar información general de localidades.

```
Método: GET
URL: {baseUrl}/ApicontrollerQA/api/ParametrosFramework/ObtenerLocalidadesNoPaisNoDepartamentoColombia
```

#### Response

| Parámetro | Tipo | Descripción | Ejemplo |
|-----------|------|-------------|---------|
| IdLocalidad | string | Identificador de la ciudad de destino (Código DANE) | 5001000 |
| IdTipoLocalidad | string | Tipo de localidad | 3 |
| IdAncestroPGrado | string | Ancestro P Grado | 5 |
| IdAncestroSGrado | string | Ancestro S Grado | 57 |
| Nombre | string | Nombre, departamento y país de localidad | MEDELLIN\\ANT\\COL |
| NombreCorto | string | Nombre de localidad | MEDELLIN |
| NombreAncestroPGrado | string | Departamento de localidad | ANTIOQUIA |
| NombreCompleto | string | Nombre, departamento y país | MEDELLIN\\ANT\\COL |
| AsignadoEnZona | boolean | Asignado en zona | FALSE |
| AsignadoEnZonaOrig | boolean | Asignado en zona origen | FALSE |
| DispoLocalidad | boolean | Disponibilidad localidad | FALSE |
| CodigoPostal | string | Código Postal | 500 |
| Indicativo | string | Indicativo | 4 |
| IdCentroServicio | number | Centro de servicio | 2339 |
| EstadoRegistro | number | Estado de registro | 0 |
| PermiteRecogida | boolean | Permite recogida | TRUE |
| HoraMaxRecogida | number | Hora máxima de recogida | 0 |
| SeGeorreferencia | boolean | Georreferenciación | TRUE |
| PermitePreEnviosPunto | boolean | Permite admisión preenvíos | FALSE |
| EtiquetaEntrega | boolean | Etiqueta de entrega | TRUE |
| HoraMinRecogida | number | Hora mínima recogida | 0 |
| AbreviacionCiudad | string | Abreviatura nombre localidad | MDE |

### 8.3 Consulta de Sucursales

Funcionalidad utilizada para consulta de sucursales creadas del cliente.

```
Método: GET
URL: {baseUrl}/ApiVentaCreditoQA/api/ClientesCredito/ObtenerSucursalesActivasPorCliente?idCliente={idCliente}
```

#### Request

| Parámetro | Tipo | Descripción | Ejemplo |
|-----------|------|-------------|---------|
| IdCliente | integer | Identificador de cliente registrado en el sistema Inter Rapidísimo | 7232 |

#### Response

| Parámetro | Tipo | Descripción | Ejemplo |
|-----------|------|-------------|---------|
| suC_IdSucursal | integer | Identificador de la sucursal del cliente, remitente del envío | 57385 |
| suC_Nombre | String() | Nombre de la sucursal, remitente del envío | SUCURSAL DE PRUEBA |

### 8.4 Consulta de Centros de Servicio

Funcionalidad utilizada para consulta de los centros de servicio de INTER RAPIDÍSIMO.

```
Método: GET
URL: {baseUrl}/ApiControllerQA/api/CentrosServicio/ObtenerCentrosServicioNacional/{idCiudad}/{idZona}/{idDia}
```

#### Request

| Parámetro | Tipo | Descripción | Ejemplo |
|-----------|------|-------------|---------|
| IdCiudad | String(8) | Identificador de ciudad DANE de ciudad de origen | 11001000 (Bogotá) |
| IdZona | String(2) | IdZona | A1 |
| IdDia | String(2) | Días de la semana | 1 |

#### Response

| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| IdCentroServicio | Integer | Número identificador del centro de servicio de INTER RAPIDÍSIMO |
| NombreCentroServicio | String | Corresponde a ciudad junto con dirección |
| TipoCentroServicio | String | RACOL, Agencia o Punto de INTER RAPIDÍSIMO |
| IdZona | String | Identificador de Zona |
| Estado | String | Estado Activo o Inactivo |
| Direccion | String | Dirección física |
| Telefono | String | Número de teléfono |
| Latitud | Float | Coordenada de ubicación en Latitud |
| Longitud | Float | Coordenada de ubicación en Longitud |
| AplicaPagoEnCasa | String | Validador de localidad con contrapago |
| AplicaReclameOficina | String | Validador de localidad con tipo de entrega con Reclame en Oficina |
| ZonaDificilAcceso | String | Validador de localidad con restricción de entrega en dirección |
| IdLocalidad | String | Número identificador de código DANE |
| NombreLocalidad | String | Nombre de localidad |
| IdDia | Integer | Número identificador de día de la semana |
| NombreDia | String | Nombre del día de la semana |
| InicioApertura | String | Hora apertura de establecimiento |
| FinCierre | String | Hora de cierre de establecimiento |
| InicioRecogidas | String | Hora de inicio de recogidas |
| FinRecogidas | String | Hora de término de recogidas |

---

## 9. NOTIFICACIÓN DE ESTADOS TIPO PUSH

Funcionalidad alternativa utilizada para la notificación de estados logísticos de una guía y causales de devolución.

### Flujo de implementación:

**Paso 1 - 1ra API (Autenticación del cliente):**

El cliente deberá construir y proveer la dirección de una 1ra API REST (tipo POST), usuario y contraseña para obtener el TOKEN de seguridad del API que, posteriormente, se le enviará al PUSH.

#### Header 1ra API

| Header | Tipo | Descripción | Ejemplo |
|--------|------|-------------|---------|
| Authorization | String(250) | Header de autorización Básica (Basic+espacio+usuario:contraseña codificada en base64) | Basic Zm9vOmJhcg== |

#### Request 1ra API

| Parámetro | Tipo | Descripción | Ejemplo |
|-----------|------|-------------|---------|
| grant_type | String(250) | Variable de validación de la conexión. Valor fijo. | "client_credentials" |
| validity_period | String(250) | Tiempo de validez del token generado | "3600" |

El response de este servicio DEBE devolver el token en una variable llamada `access_token` (formato JSON):

```json
{
  "access_token": "2f8ce5d154b2efc55ef83e414fed266b80bcf31889f6cf5727aa8331eca00904..."
}
```

**Paso 2 - 2da API (Recepción de notificaciones):**

El cliente deberá construir y proveer la dirección de una 2da API REST (tipo POST), habilitada para recibir PUSH. El proceso PUSH usará el token generado por el API anterior.

#### Header 2da API

| Header | Tipo | Descripción | Ejemplo |
|--------|------|-------------|---------|
| Authorization | String(250) | Header de autorización (Bearer+espacio+token generado por la 1ra API) | Bearer 24Zm9vOmJhcg... |

#### Estructura JSON del PUSH

```json
{
  "NotificacionEstados": {
    "DetalleNotificacion": {
      "FechaNotificacion": "2024-05-17T23:20:55.263",
      "FechaEstado": "2024-05-17T20:52:58.067",
      "NumeroGuia": 240010443834,
      "DescripcionEstado": "Centro acopio",
      "CodigoEstado": 2,
      "DescripcionMotivoEst": null,
      "CodigoMotivoEst": 0,
      "CodigoCiudad": "05001000"
    }
  }
}
```

#### Campos del PUSH

| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| FechaNotificacion | Fecha | Fecha y hora de la notificación del estado |
| FechaEstado | Datetime | Fecha y hora del estado notificado |
| NumeroGuia | long | Número de guía de la que se genera el estado |
| DescripcionEstado | String(25) | Descripción del estado logístico. Ver tabla de Estados |
| CodigoEstado | integer | Código del estado logístico. Ver tabla de Estados |
| DescripcionMotivoEst | String(25) | Motivo del estado logístico en caso de excepción |
| CodigoMotivoEst | integer | Código del motivo en caso de excepción |
| CodigoCiudad | integer | Código de ciudad DANE |

---

## 10. REGLAS DE NEGOCIO

**Código:** GCV-COR-F-08 | **Vigente desde:** 01/09/2023 | **Versión:** 1

### 10.1 Características de Servicios

| Valor | Nombre | Descripción | Peso Máximo | Tiempo de entrega |
|-------|--------|-------------|-------------|-------------------|
| 1 | Rapi Hoy | Rapidísimo Hoy mensajería Puerta a Puerta | 3 kg | Envíos recibidos antes de las 11:00 a.m. se entregan el mismo día |
| 2 | Rapi AM | Rapidísimo AM mensajería Puerta a Puerta | 10 kg | Entregas al siguiente día hábil antes de las 12 del mediodía |
| 3 | Mensajería | Mensajería Puerta a Puerta | 5 kg | 24, 48 y 72 horas, a partir de las 6:00 p.m. del día en que se admite |
| 6 | Carga Terrestre | Carga Terrestre Puerta a Puerta | 6 a 60 kg | 24, 48 y 72 horas, a partir de las 6:00 p.m. del día en que se admite |
| 15 | Notificaciones Judiciales | Notificaciones Judiciales | 5 kg | 24, 48 y 72 horas |
| 16 | Rapi Radicado | Carta Porte/Rapi Radicado | Establecido por Origen/Destino | 24, 48 y 72 horas |
| 17 | Rapi Carga | Carga Puerta a Puerta | Peso superior a 5.1 kg hasta el límite establecido por origen/destino | 24, 48 y 72 horas |

### 10.2 Destinos NO RECLAME EN OFICINA

Para los destinos que de acuerdo a cobertura vigente no cuenten con agencia por estado de inexistencia, inactivo o en liquidación, no podrán crearse preenvíos con tipo de entrega **RECLAME EN OFICINA**.

### 10.3 Destinos SOLO RECLAME EN OFICINA

Para los destinos que de acuerdo a la cobertura vigente sean de subtipo **Corresponsal Postal** (agencia ubicada en veredas, corregimientos o agrupaciones de más de 500 habitantes que cuenta con servicios de venta de mensajería y carga) sólo podrán crearse preenvíos con tipo de entrega **RECLAME EN OFICINA**.

### 10.4 Cálculo de Peso Tarifario

Para liquidar un preenvío a través de la API, el sistema calculará automáticamente el **peso tarifario** seleccionando el valor mayor entre el **peso bruto** y el **peso volumétrico**.

**Fórmula peso volumétrico:**
```
Peso Volumétrico = (Alto × Ancho × Largo) / 6000
```
- Dimensiones en centímetros
- Se aplica redondeo al número entero superior en caso de obtener decimales
- Se cobra el mayor entre peso bruto y peso volumétrico

---

## 11. MATRIZ DE ESTADOS LOGÍSTICOS

**Código:** GCV-COR-F-07 | **Vigente desde:** 01/09/2023 | **Versión:** 1

### 11.1 Estados de los Preenvíos

| Id | Nombre | Observación |
|----|--------|-------------|
| 11 | Creado | Confirmación que petición de cliente se procesa con la creación de número de preenvío |
| 12 | Asociado | Número de preenvío se le asoció una programación de recogida esporádica |
| 13 | Verificado | No aplica en estados presentados por consulta API |
| 14 | Facturado | No aplica en estados presentados por consulta API |
| 15 | Anulado | Número de preenvío que ha sido anulado en sistema |
| 16 | Admitido | No aplica en estados presentados por consulta API |

### 11.2 Estados de las Recogidas Esporádicas

| Id | Nombre | Observación |
|----|--------|-------------|
| 1 | Creado | No aplica en estados presentados por consulta API |
| 2 | Reservado | Cuando la PAMI o el recurso de la zona toma el servicio |
| 3 | Para Forzar | Después de pasar un tiempo sin que el recurso (PAMI o mensajero) tomara el servicio se le asigna al recurso de la zona |
| 4 | Cancelado Por El Cliente | Se recibe información por parte del cliente que cancela recogida |
| 5 | Realizada | El recurso realiza la recogida sin novedad |
| 6 | Cancelada | No aplica en estados presentados por consulta API |
| 7 | Vencida | No aplica en estados presentados por consulta API |
| 8 | Telemercadeo | La recogida no fue exitosa por x o y motivo y se entra a verificación |
| 9 | Forzada | No aplica en estados presentados por consulta API |

### 11.3 Estados Logísticos

| Id | Nombre | Estado Actual | Observación |
|----|--------|---------------|-------------|
| 1 | Admitida | Envío admitido | El envío se encuentra creado en sistema sin la recepción en centro logístico |
| 2 | Centro acopio | Ingresado a bodega | El envío ingresa a centro logístico bien sea de origen o de destino |
| 3 | Tránsito nacional | Viajando en ruta nacional | El envío es despachado a destino dentro de un operativo nacional |
| 4 | Tránsito regional | Viajando en ruta regional | El envío es despachado a un destino aledaño o al municipio de la misma RACOL |
| 5 | Reclame en oficina | Para Reclamar en Oficina | El envío se encuentra listo para ser reclamado en un punto de venta autorizado |
| 6 | Reparto | En distribución urbana | El envío se encuentra en estado de reparto dentro de la zona asignada |
| 7 | Intento de entrega | En Proceso de Devolución | Cuando el intento de entrega es fallido, y el envío se encuentra en retorno a centro logístico |
| 8 | Telemercadeo | En confirmación telefónica | El envío se encuentra en telemercadeo para confirmación de información |
| 9 | Custodia | En bodega final/custodia | Envíos se encuentra en bodega de custodia en estado de espera de reclamación o confirmación de datos |
| 10 | Devolución ratificada | Devuelto al remitente | La entrega no es efectiva y el envío se encuentra en trayecto de devolución a su origen |
| 11 | Entregada | Entrega exitosa | El envío es entregado |
| 12 | Reenvio | Para nuevo intento entrega | El envío es enviado nuevamente a distribución por intento fallido |
| 13 | Digitalizada | Prueba de Entrega Digitalizada | Indica que el soporte de la entrega se encuentra habilitado en el sistema |
| 14 | Indemnización | En investigación | El envío presenta un siniestro y se escala a nivel de investigación operacional |
| 15 | Anulada | Documento anulado | La guía generada es anulada del sistema |
| 16 | Archivada | Prueba de Entrega Archivada | La prueba de entrega está dentro del archivo central de operaciones como expediente de consulta |
| 17 | Disposición final | Disposición final | El estado del envío que después de un tiempo no es reclamado por remitente y destinatario y procede a proceso de destrucción o donación |
| 18 | Transito Urbano | Despachado para bodega | El envío se encuentra despachado del centro logístico al punto de venta solicitado |
| 21 | Incautado | Incautado por autoridades | El envío ha sido retenido por las autoridades y se encuentra en proceso de inspección |
| 22 | Pend Ing Custodia | Para bodega final/custodia | El envío que no se puede entregar en diferentes intentos de entrega o es rehusado por el destinatario debe pasar al área de custodia para su disposición |
| 23 | Físico Faltante | No Llegó el Envío Físico | Estado del envío que impone el cliente corporativo cuando existe una discrepancia entre las guías generadas y el envío físico |
| 24 | Caso Fortuito | Caso fortuito | Evento imprevisto que por su naturaleza no se puede resistir y puede generar avería parcial o total del envío |
| 25 | Facturado | Facturado | El Estado aplica para aquellos envíos que, sin gestión de origen o destino pasado el corte de facturación es incluido dentro de la liquidación |
| 26 | Nota crédito | Nota crédito | Estado que genera el proceso de control de cuentas cuando se debe realizar una reposición financiera al cliente por una desviación en la liquidación de su factura |
| 29 | Auditoría | En Auditoría en Terreno | Estado que indica que el envío está asignado a un auditor en terreno que verifica la información errada por la cual no se entregó |
| 30 | Devolución en espera confirmación cliente | Devolución por Confirmación del Cliente | Estado que indica que el cliente de origen confirma por telemercadeo que se debe hacer efectiva la devolución del envío |
| 31 | Distribución | En distribución urbana agencia | El envío se encuentra asignado para reparto en municipio o ciudad aledaña |
| 32 | Devolución Regional | Para devolver al Remitente | Estado del envío cuando se encuentra en centro logístico de destino y será próximo a despachar a origen |
| 34 | PreAnulado | Preanulado | El envío se encuentra notificado por el punto o cliente para ser anulado, y se encuentra en espera de la autorización del estado definitivo de anulación |
| 35 | En Inspección | En inspección | Envío en revisión por parte de las autoridades que por sospecha en su contenido no se puede movilizar |
| 39 | Recertificar | Recertificar | La prueba de entrega no cargó bien en sistema y se encuentra en proceso de nuevo cargue del comprobante |
| 40 | EnvioTrocado | Envío trocado | El envío no corresponde al destino relacionado |

---

## 12. ERRORES COMUNES

**Código:** GCV-COR-F-05 | **Vigente desde:** 01/09/2023 | **Versión:** 1

### 12.1 Admisión/InsertarAdmision (POST)

| Error | Posibles Causas y Acciones |
|-------|---------------------------|
| Cliente no válido | Posible diferencia en el código de la variable IdClienteCredito o CodigoConvenioRemitente (sucursal) respecto a lo parametrizado para el cliente en el sistema. **Acción:** Reportar a Inter Rapidísimo (área Comercial) para revisar la parametrización del cliente |
| Falla en servicio de georreferenciación | Falla en el método interno de georreferenciación usado por el servicio internamente. **Acción:** Reportar a Inter Rapidísimo para revisar técnicamente la falla |
| No es posible admitir el pre envío, no existe un centro de servicio a la ciudad de origen | Falla en el método interno de identificación de ciudad origen. **Acción:** Reportar a Inter Rapidísimo para revisar técnicamente la falla |
| Excede el peso máximo para la ciudad. Favor rectificar los datos ingresados | Falla por el peso informado en la Variable "Peso" o por el calculado como peso volumétrico de los campos Largo, Ancho y Alto. **Acción:** Reportar a Inter Rapidísimo para validar los datos del campo Peso y la parametrización del peso permitido para el centro de servicio asociado a la ciudad de origen |
| El valor declarado no se encuentra dentro del rango permitido "valorMinimo" - "valorMaximo" | ValorDeclarado por debajo o por encima de los valores permitidos, según parametrización para el cliente. **Acción:** Reportar a Inter Rapidísimo para validar que el ValorDeclarado esté entre el rango parametrizado |
| No hay servicios asociados que cumplan con los datos ingresados / El servicio seleccionado no se encuentra asociado | Falla por errores en las variables de entrada involucradas con la cotización interna (IdClienteCredito, IdCiudadOrigen, IdCiudadDestino, Peso, ValorDeclarado, IdTipoEntrega, Fecha, IdServicio). **Acción:** Revisar que los parámetros enviados sean válidos |
| Sin presupuesto asignado. Favor validar con la Transportadora | Falla por parametrización del presupuesto asignado. **Acción:** Reportar a Inter Rapidísimo para validar el presupuesto del cliente parametrizado |
| El parámetro llave es obligatorio | Validar método: **InsertarAdmision** |
| CO.Servidor.Servicios.WebApi.Comun.FabricaServicios | Intermitencia debido al consumo masivo del servicio. **Acción:** Revisar tiempos entre peticiones enviadas |
| Estado de error: 412 Precondition Failed | Falla cuando el ID centro de servicios asociado al destino con el subtipo corresponsal postal se encuentra Inactivo. **Acción:** Confirmar cubrimiento de servicios sobre destino indicado |
| Error desconocido: Los preenvios creados no cuentan con la totalidad de los campos requeridos | Los preenvíos creados no cuentan con la totalidad de los campos requeridos en la estructura de la petición |
| Error desconocido. Favor contáctese con soporte técnico | Los campos de la estructura de la petición no están diligenciados o lo están, pero no con los tipos de datos requeridos |

### 12.2 Admision/ObtenerBase64PdfPreGuia/{numeroGuia} (GET)

| Error | Posibles Causas y Acciones |
|-------|---------------------------|
| Url servidor admisión no encontrado en configuración | Revisar que en el web config se encuentre parametrizada la key: **urlApiImpresionesMS** |
| No es posible conectarse con el servicio de impresión | Revisar el servicio de impresión: **MsImpresionesapi/Preenvios/ObtenerBase64Impresion/** |

### 12.3 Admision/ObtenerBase64PdfPreguias (POST)

| Error | Posibles Causas y Acciones |
|-------|---------------------------|
| Algunos campos obligatorios no fueron diligenciados | Revisar el método **ValidarCampos** |
| El parámetro DiasImpresionPreenvios no se encuentra configurado | Revisar que en el web.config se encuentre parametrizada la Key: **DiasImpresionPreenvios** |
| La fecha y hora inicial debe ser menor a la final | Verificar los datos e intente nuevamente |
| La fecha fin no puede ser mayor a la fecha actual | Validar el método **ValidarFechas** |
| El rango de fecha sobrepasa la cantidad máxima de días | Revisar que las fechas enviadas cumplan con las validaciones. Máximo de **diasImpresionPreevios** días |
| Cliente o sucursal inválida, por favor valide | Validar parametrización del cliente en controller. Revisar método **ObtenerClienteCreditoActivo** |
| No se encontraron guías con los datos ingresados | Revisar método **ConsultarPreGuiasPorFechas** |
| No es posible conectarse con el servicio de impresión | Revisar el servicio de impresión: **MsImpresionesapi/Preenvios/ObtenerBase64Impresion/** |
| No fue posible completar la impresión | Eventual error en la conexión con el servicio API en la generación del code para consulta de formato de etiqueta de preenvíos |

### 12.4 Recogida/InsertarRecogidaCliente (POST)

| Error | Posibles Causas y Acciones |
|-------|---------------------------|
| Cliente no válido | Revisar parametrización del cliente. Validar que la recogida cuando le hace falta datos en el Request (IdClienteCredito, IdSucursalCliente, listaNumPreenvios o fechaRecogida) |
| El día seleccionado se encuentra fuera de la operación | Validar método **ConsultaOperRecogidaLocalidadDia** |
| No se encuentran habilitadas estas recogidas para la ciudad | Validar método **ConsultaOperRecogidaLocalidadDia** |
| El horario se encuentra fuera de la operación | Corresponde a la parametrización de la localidad de la recogida (Administrador Logístico) |
| Faltan parámetros para realizar la consultar | Revisar servicio: **ApiRecogidas/api/AdminZona/ConsultaOperRecogidaLocalidadDia** |
| No hay preenvío asociado | Se presenta cuando el objeto recogido no lleva una lista de preenvío |
| Fecha corresponde a día festivo | Se presenta cuando la fecha de solicitud de recogida es un día festivo. Debe enviarse en una fecha hábil |
| Todos los preenvios incluidos no pertenecen a la sucursal | No generación de ID de recogida cuando el pre envío tiene una sucursal diferente a la que se creó |
| Algunos preenvios no pertenecen a la sucursal o están incluidos en una recogida previa | No generación de ID de recogida para los pre envíos ya asociados de iguales o diferentes sucursales |
| Algunos envíos ya tienen una orden de recogida | No generación de ID de recogida para los pre envíos ya asociados |
| Los preenvios incluido en la lista no existen o han sido procesados | Validar la recogida de un pre-envío que no existe (mismo cliente) |
| Las credenciales no coinciden con el cliente crédito | Validar que no se genere orden de recogida cuando el IdClienteCredito del pre-envío es diferente al de la recogida, o cuando el valor del Header y Token del Usuario son diferentes al idCliente |
| No hay preenvios asociados | Validar la recogida cuando le hace falta datos en el Request |
| La fecha de la recogida no puede ser anterior a la fecha actual | Validar la recogida con pre-envío que tiene fecha de recogida anterior o vacía o incorrecta |
| Algunos envíos no pertenecen a la sucursal | Validar pre-envíos de otro cliente incluidos en la recogida |
| Cantidad de pre envíos sobrepasa el máximo parametrizado | Validar la recogida cuando la cantidad máxima de pre-envíos admitidos en la recogida es superada |

### 12.5 Planilla/GenerarPlanillaRecoleccionPreenvios (POST)

| Error | Posibles Causas y Acciones |
|-------|---------------------------|
| La identificación del cliente o sucursal es errada | Validar método **ObtenerClienteCreditoActivo**. Revisar parametrización del cliente (Controller) |
| Verifique los datos de entrada | Se presenta cuando el objeto **PlanillaRecoleccionImpresionRequest** no tiene una lista de preenvíos asociado. Es obligatorio enviar una lista de preenvíos |
| No se ha Encontrado el Parámetro Total Preenvios Planilla | Revisar servicio: **ApiPreenvio/api/Planilla/InsertarPlanillaPreenvio**. Revisar método: **ObtenerTotalPreenviosPlanillaCL** |
| Url servidor impresión no encontrado en configuración | Revisar método: **ObtenerPdfPlanilla**. Validar que en el web.config esté agregado el parámetro **urlApiImpresionesMS** |
| No es posible conectarse con el servicio de impresión | Revisar el servicio: **MsImpresionesapi/Preenvios/GenerarPlanillaPreenvios** |
| Template planilla preenvios no encontrado en Base de datos | Validar respuesta del método: **ObtenerTemplateFactura** |
| El cliente no se encuentra autorizado para consumir el servicio solicitado | Validar que no se genere orden de recogida cuando el valor del Header del Usuario es diferente al idCliente |
| Error desconocido: Favor contacte a soporte técnico | Los datos en el campo Contacto de ingreso de información de configuración de sucursales de cliente crédito en Controller, sobrepasa los 42 caracteres. **Acción:** Reportar a Inter Rapidísimo para revisar la parametrización del cliente |

---

## 13. EJEMPLOS cURL

**Código:** GCV-COR-F-04 | **Vigente desde:** 01/09/2023 | **Versión:** 1

### 13.1 COTIZADOR

```bash
curl --location --request GET 'https://qawww3.interrapidisimo.co/ApiServInterQA/api/CotizadorCliente/ResultadoListaCotizar/4261/11001000/52835000/1/59900/1/09-08-2021' \
--header 'x-app-signature: userPruebaQA' \
--header 'x-app-security_token: bearer I1hnZBkEvVd_Xocb_IT-Af7xAWtNFIARHwBapcrA3sJ-gE3AO2MN8qPkLh26Xvm0EhCkiClg3ihCNmf5aTfTb9VdxLMyt1LL7zyPoMVOqXervGHSK8Ir9ynFJlkDatC6298vnOudi0djjWHE2By7sXXiX1C5uftwc6UbewEia0IfFpAN1FzNT5j9oWIpQFurKrMIpwh5JXdIU5RYsyvGBUyU__7WNa-iQjHrRP3VUJgPlRMSRAtLCwvr2OF5KHeS'
```

### 13.2 ADMISIÓN PRE ENVÍOS

```bash
curl --location --request POST 'https://qawww3.interrapidisimo.co/ApiVentaCreditoQA/api/Admision/InsertarAdmision' \
--header 'x-app-signature: userPruebaQA' \
--header 'x-app-security_token: bearer {TOKEN}' \
--header 'Content-Type: application/json' \
--data-raw '{
  "IdClienteCredito":1234,
  "CodigoConvenioRemitente": 123456,
  "IdTipoEntrega":"1",
  "AplicaContrapago": "False",
  "IdServicio":3,
  "Peso":1,
  "Largo":1,
  "Ancho":1,
  "Alto":1,
  "DiceContener":"Prueba",
  "ValorDeclarado":59000,
  "IdTipoEnvio":1,
  "IdFormaPago":2,
  "NumeroPieza":1,
  "Destinatario":{
    "tipoDocumento":"CC",
    "numeroDocumento":"123456789",
    "nombre":"Juan",
    "primerApellido":"Perez",
    "segundoApellido":null,
    "telefono":"3012884943",
    "direccion":"CARRERA 79 # 19 - 88",
    "idDestinatario":0,
    "idRemitente":0,
    "idLocalidad":"11001000",
    "CodigoConvenio":0,
    "ConvenioDestinatario":0,
    "correo":"nada@gmail.com"
  },
  "DescripcionTipoEntrega":"",
  "NombreTipoEnvio":"CAJA",
  "CodigoConvenio":0,
  "IdSucursal":0,
  "IdCliente":0,
  "Notificacion":null,
  "RapiRadicado":null,
  "Observaciones":"Prueba"
}'
```

### 13.3 GENERACIÓN FORMATO GUÍA

```bash
curl --location --request GET 'https://qawww3.interrapidisimo.co/ApiVentaCreditoQA/api/Admision/ObtenerBase64PdfPreGuia/240000000001' \
--header 'x-app-signature: userPruebaQA' \
--header 'x-app-security_token: Bearer {TOKEN}'
```

### 13.4 GENERACIÓN GUÍAS POR LOTE

```bash
curl --location --request POST 'https://qawww3.interrapidisimo.com/ApiVentaCreditostg/api/Admision/ObtenerBase64PdfPreGuias/' \
--header 'x-app-signature: userPruebaQA' \
--header 'x-app-security_token: Bearer {TOKEN}' \
--header 'Content-Type: application/json' \
--data-raw '{
  "IdCliente": 1234,
  "IdSucursal": 12345,
  "PorRangoFecha":true,
  "LTSPREGUIAS":[240000000001, 240000000002, 240000000003, 240000000004],
  "FechaInicio":"2021-08-10T00:01",
  "FechaFinal":"2021-08-12T23:59"
}'
```

### 13.5 CONSULTA ESTADO GUÍAS (tracking)

```bash
curl --location --request POST 'https://qawww3.interrapidisimo.co/ApiVentaCreditoQA/api/ClientesCredito/ConsultarEstadosGuiasCliente' \
--header 'x-app-signature: userPruebaQA' \
--header 'x-app-security_token: bearer {TOKEN}' \
--header 'Content-Type: application/json' \
--data-raw '{
  "idCliente": 1234,
  "numeroGuias": [240000000001]
}'
```

### 13.6 PLANILLA PRE ENVÍOS

```bash
curl --location --request POST 'http://qawww3.interrapidisimo.co/ApiVentaCreditoQA/api/Planilla/GenerarPlanillaRecoleccionPreenvios' \
--header 'x-app-signature: userPruebaQA' \
--header 'x-app-security_token: bearer {TOKEN}' \
--header 'Content-Type: application/json' \
--data-raw '{
  "idCliente": 1234,
  "idSucursal": 12345,
  "listaNumPreenvios": [240000031973, 240000031974, 240000031975, 240000031976]
}'
```

### 13.7 RECOGIDAS

```bash
curl --location --request POST 'http://qawww3.interrapidisimo.co/ApiVentaCreditoQA/api/Recogida/InsertarRecogidaCliente/' \
--header 'x-app-signature: userPruebaQA' \
--header 'x-app-security_token: Bearer {TOKEN}' \
--header 'Content-Type: application/json' \
--data-raw '{
  "IdClienteCredito": 1234,
  "IdSucursalCliente": 12345,
  "listaNumPreenvios": [240000004408],
  "fechaRecogida": "2021-09-29T15:55:14.885Z"
}'
```

### 13.8 LOCALIDADES

```bash
curl --location --request GET 'https://qawww4.interrapidisimo.co/ApicontrollerQA/api/ParametrosFramework/ObtenerLocalidadesNoPaisNoDepartamentoColombia' \
--header 'x-app-signature: userPruebaQA' \
--header 'x-app-security_token: bearer {TOKEN}'
```

---

## 14. CAUSALES DE DEVOLUCIÓN

**Código:** GCV-COR-F-03 | **Vigente desde:** 01/09/2023 | **Versión:** 1

Códigos y descripción del motivo del estado logístico generado, cuando se presente uno de los estados de excepción.

| ID | Descripción |
|----|-------------|
| 1 | OTROS / ACCIDENTE |
| 2 | REHUSADO / AVERIADO |
| 3 | NO RESIDE / CAMBIO DE DOMICILIO |
| 4 | DESCONOCIDO / DESTINATARIO DESCONOCIDO |
| 5 | DIRECCIÓN ERRADA / DIRECCIÓN INCOMPLETA |
| 6 | DIRECCIÓN ERRADA / DIRECCIÓN NO EXISTE |
| 7 | OTROS / VACACIONES |
| 8 | NO RESIDE / INMUEBLE DESHABITADO |
| 9 | REHUSADO / SE NEGÓ A RECIBIR |
| 10 | DIFICULTAD DE PAGO AL COBRO |
| 11 | OTROS / NO PAGARON EL ALCOBRO |
| 12 | NO RESIDE / FALLECIDO |
| 13 | OTROS / HUELGA |
| 14 | OTROS / CERRADO ANTES DE LAS 6PM |
| 15 | OTROS / RESIDENTE AUSENTE / NO ALCANZÓ EL MENSAJERO |
| 16 | OTROS / VISITA FUERA DE HORARIO |
| 17 | OTROS / RETENCIÓN EN DEVOLUCIÓN |
| 18 | OTROS / TROCADO |
| 19 | OTROS / NO LABORAN LOS SÁBADOS |
| 20 | OTROS / PETICIÓN DEL REMITENTE |
| 21 | REHUSADO / HURTO |
| 22 | OTROS / INCAUTADO |
| 23 | OTROS / PARA RECLAMO EN OFICINA |
| 24 | OTROS / RESIDENTE AUSENTE 1 AVISO |
| 25 | OTROS / RESIDENTE AUSENTE 2 AVISO |
| 26 | REHUSADO / CONTENIDO INCOMPLETO |
| 27 | NO RESIDE / CAMBIO DE DOMICILIO |
| 28 | DESCONOCIDO / DESTINATARIO DESCONOCIDO |
| 29 | DIRECCIÓN ERRADA / DIRECCIÓN NO EXISTE |
| 30 | NO RESIDE / INMUEBLE DESHABITADO |
| 31 | REHUSADO / SE NEGÓ A RECIBIR |
| 32 | OTROS / RESIDENTE AUSENTE 2 AVISO |
| 33 | OTROS / ACCIDENTE |
| 34 | REHUSADO / AVERIADO |
| 35 | NO RESIDE / CAMBIO DE DOMICILIO |
| 36 | DESCONOCIDO / DESTINATARIO DESCONOCIDO |
| 37 | DIRECCIÓN ERRADA / DIRECCIÓN INCOMPLETA |
| 38 | DIRECCIÓN ERRADA / DIRECCIÓN NO EXISTE |
| 39 | DEV. EN VACACIONES |
| 40 | NO RESIDE / INMUEBLE DESHABITADO |
| 41 | REHUSADO / SE NEGÓ A RECIBIR |
| 42 | OTROS / NO PAGARON EL ALCOBRO |
| 43 | NO RESIDE / FALLECIDO |
| 44 | OTROS / HUELGA |
| 45 | OTROS / TROCADO |
| 46 | OTROS / PETICIÓN DEL REMITENTE |
| 47 | REHUSADO / HURTO |
| 48 | OTROS / INCAUTADO |
| 49 | OTROS / NO RECLAMO EN OFICINA |
| 50 | OTROS / RESIDENTE AUSENTE 2 AVISO |
| 51 | REHUSADO / CONTENIDO INCOMPLETO |
| 52 | DESTRUCCIÓN |
| 53 | DONACIÓN |
| 54 | VENTA INTERNA |
| 55 | REHUSADO / HURTO |
| 56 | DIFICULTAD DE PAGO AL COBRO |
| 57 | OTROS / RESIDENTE AUSENTE 1 AVISO |
| 58 | OTROS / RESIDENTE AUSENTE 1 AVISO |
| 63 | OTROS / RESIDENTE AUSENTE TELEMERCADEO |
| 100 | NO RESIDE / CAMBIO DE DOMICILIO |
| 101 | DESCONOCIDO / DESTINATARIO DESCONOCIDO |
| 102 | DIRECCIÓN ERRADA / DIRECCIÓN INCOMPLETA |
| 103 | DIRECCIÓN ERRADA / DIRECCIÓN NO EXISTE |
| 104 | NO RESIDE / INMUEBLE DESHABITADO |
| 105 | REHUSADO / SE NEGÓ A RECIBIR |
| 106 | OTROS / NO PAGARON EL ALCOBRO |
| 107 | NO RESIDE / FALLECIDO |
| 108 | OTROS / RETENCIÓN EN DEVOLUCIÓN |
| 109 | OTROS / PETICIÓN DEL REMITENTE |
| 110 | OTROS / NO RECLAMO EN OFICINA |
| 111 | OTROS / RESIDENTE AUSENTE |
| 112 | OTROS / NO RECLAMO EN OFICINA |
| 113 | OTROS / BLOQUEO DE CALLES |
| 114 | OTROS / FUERA DE ZONA |
| 115 | OTROS / NO PAGARON PRODUCTO CONTRA PAGO |
| 116 | OTROS / FUERA DE ZONA |
| 117 | OTROS / ACCIDENTE |
| 118 | OTROS / BLOQUEO DE CALLES |
| 119 | OTROS / HUELGA |
| 120 | OTROS / VACACIONES |
| 121 | OTROS / RESIDENTE AUSENTE |
| 122 | OTROS / RESIDENTE AUSENTE / NO ALCANZÓ EL MENSAJERO |
| 123 | NO RESIDE / INMUEBLE DESHABITADO |
| 124 | NO RESIDE / CAMBIO DE DOMICILIO |
| 125 | NO RESIDE / FALLECIDO |
| 126 | DIRECCIÓN ERRADA / DIRECCIÓN INCOMPLETA |
| 127 | DIRECCIÓN ERRADA / DIRECCIÓN NO EXISTE |
| 128 | DIRECCIÓN ERRADA / NO CORRESPONDE LA CIUDAD DESTINO |
| 129 | DESCONOCIDO / DESTINATARIO DESCONOCIDO |
| 130 | REHUSADO / SE NEGÓ A RECIBIR |
| 131 | REHUSADO / AVERIADO |
| 132 | REHUSADO / CONTENIDO INCOMPLETO (HURTO) |
| 133 | REHUSADO / NO PAGARON EL ALCOBRO |
| 134 | REHUSADO / NO PAGARON PRODUCTO CONTRA PAGO |
| 136 | OTROS / CAMBIAR NUEVA DIRECCIÓN DE ENTREGA |
| 137 | OTROS / A PETICIÓN DEL REMITENTE |
| 138 | OTROS / INCAUTADO |
| 139 | OTROS / HURTO |
| 140 | OTROS / ENVÍO CRUZADO (TROCADO) |
| 141 | OTROS / NO MARCADO NO IDENTIFICADO (NN) |
| 142 | OTROS / A PETICIÓN DEL REMITENTE |
| 143 | OTROS / NO RECLAMADO EN OFICINA |
| 144 | OTROS / RESIDENTE AUSENTE |
| 145 | NO RESIDE / INMUEBLE DESHABITADO |
| 146 | NO RESIDE / CAMBIO DE DOMICILIO |
| 147 | NO RESIDE / FALLECIDO |
| 148 | DIRECCIÓN ERRADA / DIRECCIÓN INCOMPLETA |
| 149 | DIRECCIÓN ERRADA / DIRECCIÓN NO EXISTE |
| 150 | DIRECCIÓN ERRADA / NO CORRESPONDE LA CIUDAD DESTINO |
| 151 | DESCONOCIDO / DESTINATARIO DESCONOCIDO |
| 152 | REHUSADO / SE NEGÓ A RECIBIR |
| 153 | REHUSADO / NO PAGARON EL ALCOBRO |
| 154 | OTROS / CAMBIAR NUEVA DIRECCIÓN DE ENTREGA |
| 155 | REHUSADO / AVERIADO |
| 156 | REHUSADO / CONTENIDO INCOMPLETO (HURTO) |
| 157 | REHUSADO / NO PAGARON PRODUCTO CONTRA PAGO |
| 159 | OTROS / INCAUTADO |
| 160 | OTROS / HURTO |
| 162 | OTROS / BLOQUEO DE CALLES |
| 163 | OTROS / HUELGA |
| 164 | OTROS / VACACIONES |
| 165 | OTROS / ENVÍO CRUZADO (TROCADO) |
| 167 | INGRESO A CUSTODIA |
| 168 | OTROS / DEVOLUCIÓN EN ESPERA CONFIRMACIÓN CLIENTE |
| 169 | ACCIDENTE |
| 170 | BLOQUEO DE CALLES / CRUDO INVIERNO / MANIFESTACIÓN / ORDEN |
| 171 | HUELGA |
| 172 | VACACIONES |
| 173 | RESIDENTE AUSENTE |
| 175 | NO RESIDE / INMUEBLE DESHABITADO |
| 176 | NO RESIDE / CAMBIO DE DOMICILIO |
| 177 | NO RESIDE / FALLECIDO |
| 178 | DIRECCIÓN ERRADA / DIRECCIÓN INCOMPLETA |
| 179 | DIRECCIÓN ERRADA / DIRECCIÓN NO EXISTE |
| 180 | DIRECCIÓN ERRADA / NO CORRESPONDE LA CIUDAD DESTINO |
| 181 | DESCONOCIDO / DESTINATARIO DESCONOCIDO |
| 182 | REHUSADO / SE NEGÓ A RECIBIR |
| 183 | REHUSADO / AVERIADO |
| 184 | REHUSADO / CONTENIDO INCOMPLETO (HURTO) |
| 185 | REHUSADO / NO PAGARON AL COBRO |
| 186 | REHUSADO / NO PAGARON PRODUCTO CONTRA PAGO |
| 187 | NO RECLAMADO EN OFICINA |
| 188 | CAMBIAR NUEVA DIRECCIÓN DE ENTREGA |
| 189 | A PETICIÓN DEL REMITENTE |
| 190 | INCAUTADO |
| 191 | HURTO |
| 192 | ENVÍO CRUZADO / TROCADO |
| 193 | ENTREGA MAESTRA |
| 194 | OTROS / CONTENIDO NO CORRESPONDE |
| 195 | OTROS / CONTENIDO NO CORRESPONDE |
| 196 | OTROS / REVISIÓN POR AUTORIDADES |
| 197 | OTROS / REVISIÓN POR AUTORIDADES |
| 198 | EL CLIENTE YA NO REALIZA EL ENVÍO |
| 199 | MALA CAPTURA DE INFORMACIÓN EN SISTEMA |
| 200 | CONFIRMACIÓN DE AUDITORÍA |
| 201 | CONFIRMACIÓN DE CIERRE |
| 202 | MALA IMPORTACIÓN BASE DE DATOS |
| 203 | ENMENDADURAS |
| 204 | DOBLE MARCA EN LA FORMA DE PAGO |
| 205 | REPORTADA ANULAR ESTA EN LIMPIO |
| 206 | OTROS / CRUDO INVIERNO |
| 207 | OTROS / MANIFESTACIÓN ORDEN PÚBLICO |
| 208 | OTROS / CRUDO INVIERNO |
| 209 | OTROS / MANIFESTACIÓN ORDEN PÚBLICO |

---

## RESUMEN DE ENDPOINTS

| # | Servicio | Método | URL (QA) |
|---|----------|--------|----------|
| 1 | Cotización | GET | `/ApiServInterQA/api/CotizadorCliente/ResultadoListaCotizarValidaContrapago/{params}` |
| 2 | Admisión Preenvío | POST | `/ApiVentaCreditoQA/api/Admision/InsertarAdmision` |
| 3 | Etiqueta Simplificada | GET | `/ApiVentaCreditoQA/api/Admision/ObtenerBase64PdfPreGuia/{numeroguia}` |
| 4 | Etiqueta Pequeña | GET | `/ApiVentaCreditoQA/Api/Admision/ObtenerBase64PdfPreGuiaFormatoPeq/{numeroguia}` |
| 5 | Etiquetas por Lote | POST | `/ApiVentaCreditostg/api/Admision/ObtenerBase64PdfPreGuias/` |
| 6 | Planilla Preenvíos | POST | `/ApiVentaCreditoQA/api/Planilla/GenerarPlanillaRecoleccionPreenvios` |
| 7 | Recogidas Esporádicas | POST | `/ApiVentaCreditoQA/api/Recogida/InsertarRecogidaCliente/` |
| 8 | Consulta Estados | POST | `/ApiVentaCreditoQA/api/ClientesCredito/ConsultarEstadosGuiasCliente` |
| 9 | Localidades | GET | `/ApicontrollerQA/api/ParametrosFramework/ObtenerLocalidadesNoPaisNoDepartamentoColombia` |
| 10 | Sucursales | GET | `/ApiVentaCreditoQA/api/ClientesCredito/ObtenerSucursalesActivasPorCliente?idCliente={id}` |
| 11 | Centros de Servicio | GET | `/ApiControllerQA/api/CentrosServicio/ObtenerCentrosServicioNacional/{idCiudad}/{idZona}/{idDia}` |

---

*Documento generado a partir de la documentación técnica oficial de Inter Rapidísimo S.A. para uso en implementación de integración API REST.*
