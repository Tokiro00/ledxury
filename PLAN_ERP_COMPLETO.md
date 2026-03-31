# Plan de Transformación: Sistema MAM a ERP Completo

**Versión:** 1.0
**Fecha:** 23 de Enero de 2026
**Autor:** Equipo de Desarrollo MAM

---

## Tabla de Contenidos

1. [Resumen Ejecutivo](#resumen-ejecutivo)
2. [Estado Actual del Sistema](#estado-actual-del-sistema)
3. [Arquitectura Propuesta del ERP](#arquitectura-propuesta-del-erp)
4. [Módulo Prioritario: Caja y Bancos](#módulo-prioritario-caja-y-bancos)
5. [Base de Datos](#base-de-datos)
6. [Arquitectura de Software](#arquitectura-de-software)
7. [Cronograma de Implementación](#cronograma-de-implementación)
8. [Anexos](#anexos)

---

## Resumen Ejecutivo

El sistema MAM actualmente es un sistema de gestión empresarial operativo enfocado en ventas, inventario y contabilidad básica. Este documento presenta un plan integral para transformarlo en un **ERP (Enterprise Resource Planning) completo** que cubra todas las áreas de negocio de una empresa.

### Objetivos Principales

- ✅ Completar el módulo financiero con gestión de Caja y Bancos
- ✅ Implementar Cuentas por Cobrar y por Pagar avanzadas
- ✅ Agregar módulos de Compras, CRM y Recursos Humanos
- ✅ Desarrollar Business Intelligence y reportes avanzados
- ✅ Integrar con sistemas externos (e-commerce, facturación electrónica)

### Prioridad Inmediata

**Módulo de Caja y Bancos** - Crítico para el control financiero y base para otros módulos.

---

## Estado Actual del Sistema

### Tecnologías Utilizadas

| Aspecto | Tecnología |
|---------|-----------|
| **Framework Backend** | CodeIgniter 3.x |
| **Lenguaje Backend** | PHP 5.3.7+ |
| **Base de Datos** | MySQL |
| **Framework Frontend** | Tailwind CSS |
| **Build Tool** | Webpack 4 |
| **Lenguaje Frontend** | JavaScript (Babel/ES6+) |
| **Librerías JS** | jQuery, Lodash, Glide.js |
| **Exportación** | Excel (PHPSpreadsheet), PDF (mPDF) |

### Módulos Existentes

| Módulo | Estado | Completitud | Observaciones |
|--------|--------|-------------|---------------|
| **Ventas** | ✓ Operativo | 70% | Falta gestión completa de devoluciones |
| **Inventario** | ✓ Operativo | 75% | Falta trazabilidad, lotes, series |
| **Contabilidad** | ✓ Básico | 60% | Falta cierre contable, reportes fiscales |
| **Clientes** | ✓ Operativo | 65% | Falta CRM, historial, seguimiento |
| **Productos** | ✓ Operativo | 70% | Falta costos, kits, variantes |
| **Facturación** | ✓ Operativo | 80% | Operativo |
| **Pagos** | ✓ Básico | 50% | Falta cajas, bancos, conciliación |

### Estructura de Directorios

```
mam/
├── application/              # Código principal (MVC)
│   ├── controllers/         # Controladores por módulos
│   │   └── sisvent/
│   │       ├── admin/       # Administración
│   │       ├── business/    # Negocios
│   │       ├── commercial/  # Comercial
│   │       ├── store/       # Almacén
│   │       └── accounting/  # Contabilidad
│   ├── models/              # 29 modelos de datos
│   ├── views/               # Vistas HTML/PHP
│   ├── config/              # Configuración
│   └── libraries/           # Librerías personalizadas
├── public/                  # Assets (CSS, JS, imágenes)
├── db/                      # Scripts SQL
├── system/                  # Framework CodeIgniter
└── vendor/                  # Dependencias Composer
```

### Jerarquía Contable Actual

```
accounts_class (Clase)
    → accounts_group (Grupo)
        → accounts_accounts (Cuenta)
            → subaccounts (Subcuenta)
                → auxiliary_subaccounts (Auxiliares)
```

**Ejemplo:**
- Clase 1: Activos
  - Grupo 11: Activo Corriente
    - Cuenta 1105: Caja
      - Subcuenta: Caja Principal Bodega 1
        - Auxiliar: Usuario Cajero001

---

## Arquitectura Propuesta del ERP

### Visión General de Módulos

```
┌─────────────────────────────────────────────────────────────┐
│                    DASHBOARD EJECUTIVO (BI)                  │
└─────────────────────────────────────────────────────────────┘
         ↓              ↓              ↓              ↓
┌────────────────┐ ┌───────────┐ ┌────────────┐ ┌──────────┐
│   FINANCIERO   │ │ OPERATIVO │ │  GESTIÓN   │ │ SOPORTE  │
├────────────────┤ ├───────────┤ ├────────────┤ ├──────────┤
│ Tesorería      │ │ Compras   │ │ CRM        │ │ Usuarios │
│ Ctas x Cobrar  │ │ Ventas    │ │ RRHH       │ │ Permisos │
│ Ctas x Pagar   │ │ Inventario│ │ Proyectos  │ │ Auditoría│
│ Contabilidad   │ │ Producción│ │ Activos    │ │ Docs     │
└────────────────┘ └───────────┘ └────────────┘ └──────────┘
                            ↓
                    ┌───────────────┐
                    │  INTEGRACIONES│
                    ├───────────────┤
                    │ API REST      │
                    │ E-commerce    │
                    │ Facturación E.│
                    │ Pagos Online  │
                    └───────────────┘
```

### Niveles de Implementación

#### NIVEL 1: Módulos Financieros (Prioridad Alta) 🔴

**1.1 Tesorería (Caja y Bancos)** - CRÍTICO
- Gestión de cajas físicas y virtuales
- Cuentas bancarias
- Apertura/cierre de caja
- Arqueo de caja
- Conciliación bancaria
- Flujo de caja proyectado
- Transferencias entre cuentas

**1.2 Cuentas por Cobrar** - CRÍTICO
- Estado de cuenta por cliente
- Aging de cartera (30, 60, 90, 120+ días)
- Recordatorios automáticos
- Notas de crédito y débito
- Anticipos y prepagos
- Proyección de cobros

**1.3 Cuentas por Pagar** - NUEVO
- Registro de cuentas por pagar
- Programación de pagos
- Pagos a proveedores
- Control de anticipos
- Proyección de pagos

**1.4 Contabilidad Avanzada** - MEJORAR
- Cierre contable mensual/anual
- Estados financieros (Balance, P&G, Flujo)
- Centros de costo
- Presupuestos vs Real
- Reportes fiscales (IVA, Retenciones)

#### NIVEL 2: Módulos Operativos (Prioridad Alta) 🟡

**2.1 Compras Completo** - NUEVO
- Requisiciones de compra
- Órdenes de compra
- Recepción de mercancía
- Facturas de proveedores
- Devoluciones a proveedores

**2.2 Inventario Avanzado** - MEJORAR
- Lotes y fechas de vencimiento
- Números de serie
- Ubicaciones (racks, estantes)
- Inventario cíclico
- Costos (FIFO, LIFO, Promedio)
- Kits y paquetes
- Variantes (tallas, colores)
- Stock mínimo y punto de reorden

**2.3 Ventas Avanzado** - MEJORAR
- Devoluciones completas
- Comisiones de vendedores
- Descuentos y promociones
- Lista de precios por cliente
- E-commerce básico

#### NIVEL 3: Módulos de Gestión (Prioridad Media) 🟢

**3.1 CRM (Customer Relationship Management)**
- Gestión de contactos y leads
- Pipeline de ventas
- Tareas y seguimientos
- Historial de interacciones
- Campañas de marketing
- Segmentación de clientes

**3.2 Recursos Humanos**
- Expediente de empleados
- Control de asistencia
- Nómina
- Vacaciones y permisos
- Evaluaciones de desempeño

**3.3 Activos Fijos**
- Registro de activos
- Depreciación automática
- Mantenimiento programado
- Asignación a empleados/departamentos

**3.4 Proyectos**
- Gestión de proyectos
- Tareas y subtareas
- Control de tiempo
- Presupuesto vs gastado

#### NIVEL 4: Módulos de Producción (Prioridad Baja)

**4.1 Producción/Manufactura** (si aplica)
- BOM (Bill of Materials)
- Órdenes de producción
- Control de calidad
- Seguimiento de lotes

#### NIVEL 5: Módulos de Inteligencia (Prioridad Media)

**5.1 Business Intelligence (BI)**
- Dashboard ejecutivo con KPIs
- Gráficos interactivos
- Análisis de tendencias
- Predicciones con IA básica

**5.2 Reportes Personalizados**
- Constructor de reportes drag & drop
- Filtros dinámicos
- Exportación (PDF, Excel, CSV)
- Reportes programados

#### NIVEL 6: Integraciones (Prioridad Media)

**6.1 API REST**
- Endpoints para todos los módulos
- Autenticación OAuth2/JWT
- Documentación Swagger

**6.2 Integraciones Externas**
- WooCommerce/Shopify
- Mercado Libre
- Facturación electrónica
- Pasarelas de pago
- WhatsApp Business

---

## Módulo Prioritario: Caja y Bancos

### Justificación

El módulo de Caja y Bancos es **CRÍTICO** porque:
- Sin control de caja no hay control financiero real
- Es la base para Cuentas por Cobrar y por Pagar
- Permite flujo de caja en tiempo real
- Mejora la toma de decisiones financieras
- Reduce faltantes y sobrantes de caja
- Facilita conciliación bancaria

### Características Principales

#### Para Cajas
- ✅ Múltiples cajas (por sucursal, por usuario)
- ✅ Apertura y cierre diario con arqueo
- ✅ Control de efectivo, tarjetas, cheques
- ✅ Registro automático de ingresos/egresos
- ✅ Alertas de faltantes/sobrantes
- ✅ Integración con ventas y pagos

#### Para Bancos
- ✅ Múltiples cuentas bancarias
- ✅ Registro de movimientos bancarios
- ✅ Conciliación bancaria
- ✅ Transferencias entre cuentas
- ✅ Control de cheques emitidos
- ✅ Integración con contabilidad

#### Flujo de Caja
- ✅ Dashboard con saldos en tiempo real
- ✅ Proyección de ingresos y egresos
- ✅ Reportes históricos
- ✅ Gráficos de tendencias
- ✅ Alertas de bajo efectivo

---

## Base de Datos

### Nuevas Tablas para Caja y Bancos

#### Tabla: cashboxes (Cajas)

```sql
CREATE TABLE `cashboxes` (
  `idCashbox` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT 'Nombre de la caja',
  `code` varchar(20) NOT NULL COMMENT 'Código único',
  `type` enum('principal','secundaria','chica') DEFAULT 'principal',
  `storeId` int(11) NOT NULL COMMENT 'Bodega asignada',
  `subaccountId` int(11) NULL COMMENT 'Vincula con contabilidad',
  `initialBalance` decimal(15,2) DEFAULT 0.00,
  `currentBalance` decimal(15,2) DEFAULT 0.00 COMMENT 'Calculado',
  `responsibleUserId` varchar(100) NULL,
  `status` enum('abierta','cerrada','arqueo','bloqueada') DEFAULT 'cerrada',
  `openedAt` datetime NULL,
  `closedAt` datetime NULL,
  `openedBy` varchar(100) NULL,
  `closedBy` varchar(100) NULL,
  `notes` text NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted` tinyint(4) DEFAULT 0,
  PRIMARY KEY (`idCashbox`),
  UNIQUE KEY `code` (`code`),
  KEY `storeId` (`storeId`),
  KEY `subaccountId` (`subaccountId`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Cajas físicas y virtuales';
```

#### Tabla: bank_accounts (Cuentas Bancarias)

```sql
CREATE TABLE `bank_accounts` (
  `idBankAccount` int(11) NOT NULL AUTO_INCREMENT,
  `bankName` varchar(100) NOT NULL,
  `accountNumber` varchar(50) NOT NULL,
  `accountType` enum('ahorros','corriente','credito','otro') DEFAULT 'corriente',
  `currency` varchar(10) DEFAULT 'COP',
  `subaccountId` int(11) NULL COMMENT 'Vincula con contabilidad',
  `initialBalance` decimal(15,2) DEFAULT 0.00,
  `currentBalance` decimal(15,2) DEFAULT 0.00,
  `ownerName` varchar(150) NULL,
  `ownerIdNumber` varchar(50) NULL,
  `branchOffice` varchar(100) NULL,
  `contactEmail` varchar(100) NULL,
  `contactPhone` varchar(50) NULL,
  `status` enum('activa','inactiva','bloqueada') DEFAULT 'activa',
  `notes` text NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted` tinyint(4) DEFAULT 0,
  PRIMARY KEY (`idBankAccount`),
  UNIQUE KEY `accountNumber` (`accountNumber`),
  KEY `subaccountId` (`subaccountId`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

#### Tabla: cash_movements (Movimientos)

```sql
CREATE TABLE `cash_movements` (
  `idMovement` int(11) NOT NULL AUTO_INCREMENT,
  `movementType` enum('ingreso','egreso','transferencia','ajuste','apertura','cierre') NOT NULL,
  `sourceType` enum('caja','banco') NOT NULL,
  `sourceId` int(11) NOT NULL,
  `destinationType` enum('caja','banco') NULL,
  `destinationId` int(11) NULL,
  `amount` decimal(15,2) NOT NULL,
  `concept` varchar(255) NOT NULL,
  `category` enum('venta','pago_proveedor','gasto','pago_cliente','nomina','impuestos','prestamo','otro') DEFAULT 'otro',
  `referenceType` varchar(50) NULL COMMENT 'invoice, payment, expense',
  `referenceId` int(11) NULL,
  `paymentMethodId` int(11) NULL,
  `documentNumber` varchar(100) NULL COMMENT 'Cheque, transferencia',
  `entryId` int(11) NULL COMMENT 'Asiento contable',
  `executedBy` varchar(100) NOT NULL,
  `authorizedBy` varchar(100) NULL,
  `movementDate` datetime NOT NULL,
  `notes` text NULL,
  `status` enum('pendiente','autorizado','ejecutado','rechazado','anulado') DEFAULT 'ejecutado',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted` tinyint(4) DEFAULT 0,
  PRIMARY KEY (`idMovement`),
  KEY `sourceType_sourceId` (`sourceType`,`sourceId`),
  KEY `movementType` (`movementType`),
  KEY `movementDate` (`movementDate`),
  KEY `referenceType_referenceId` (`referenceType`,`referenceId`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

#### Tabla: cashbox_closures (Cierres de Caja)

```sql
CREATE TABLE `cashbox_closures` (
  `idClosure` int(11) NOT NULL AUTO_INCREMENT,
  `cashboxId` int(11) NOT NULL,
  `closureDate` datetime NOT NULL,
  `openingBalance` decimal(15,2) DEFAULT 0.00,
  `totalIngress` decimal(15,2) DEFAULT 0.00,
  `totalEgress` decimal(15,2) DEFAULT 0.00,
  `expectedBalance` decimal(15,2) DEFAULT 0.00 COMMENT 'Calculado',
  `actualBalance` decimal(15,2) DEFAULT 0.00 COMMENT 'Contado real',
  `difference` decimal(15,2) DEFAULT 0.00 COMMENT 'Sobrante/Faltante',
  `billCount` text NULL COMMENT 'JSON billetes y monedas',
  `notes` text NULL,
  `closedBy` varchar(100) NOT NULL,
  `authorizedBy` varchar(100) NULL,
  `status` enum('borrador','cerrada','autorizada') DEFAULT 'borrador',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted` tinyint(4) DEFAULT 0,
  PRIMARY KEY (`idClosure`),
  KEY `cashboxId` (`cashboxId`),
  KEY `closureDate` (`closureDate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

#### Tabla: bank_reconciliations (Conciliaciones)

```sql
CREATE TABLE `bank_reconciliations` (
  `idReconciliation` int(11) NOT NULL AUTO_INCREMENT,
  `bankAccountId` int(11) NOT NULL,
  `reconciliationDate` date NOT NULL,
  `statementDate` date NOT NULL,
  `bookBalance` decimal(15,2) DEFAULT 0.00,
  `bankBalance` decimal(15,2) DEFAULT 0.00,
  `reconciledBalance` decimal(15,2) DEFAULT 0.00,
  `difference` decimal(15,2) DEFAULT 0.00,
  `notes` text NULL,
  `reconciledBy` varchar(100) NOT NULL,
  `status` enum('borrador','conciliada','autorizada') DEFAULT 'borrador',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted` tinyint(4) DEFAULT 0,
  PRIMARY KEY (`idReconciliation`),
  KEY `bankAccountId` (`bankAccountId`),
  KEY `reconciliationDate` (`reconciliationDate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

#### Modificación: Tabla payments

```sql
ALTER TABLE `payments`
ADD COLUMN `originType` enum('caja','banco') NULL AFTER `comments`,
ADD COLUMN `originId` int(11) NULL AFTER `originType`,
ADD COLUMN `cashMovementId` int(11) NULL AFTER `originId`,
ADD KEY `originType_originId` (`originType`,`originId`);
```

### Diagrama de Relaciones

```
┌─────────────┐         ┌──────────────┐         ┌──────────┐
│  cashboxes  │────────>│ subaccounts  │<────────│  stores  │
└─────────────┘         └──────────────┘         └──────────┘
       │                        │
       │                        │
       ▼                        ▼
┌──────────────────┐    ┌────────────────┐
│ cash_movements   │───>│    entries     │
└──────────────────┘    └────────────────┘
       │                 (contabilidad)
       │
       ▼
┌──────────────────┐
│   payments       │
└──────────────────┘
       │
       ▼
┌──────────────────┐
│   invoices       │
└──────────────────┘

┌─────────────────┐       ┌──────────────┐
│ bank_accounts   │──────>│ subaccounts  │
└─────────────────┘       └──────────────┘
       │
       ▼
┌─────────────────────────┐
│ bank_reconciliations    │
└─────────────────────────┘
```

---

## Arquitectura de Software

### Modelos a Crear (PHP)

#### 1. Cashboxes_model.php
**Ubicación:** `application/models/Cashboxes_model.php`

**Métodos principales:**
```php
// CRUD básico
getCashboxes()              // Listar todas
getCashbox($id)             // Obtener una
getCashboxesByStore($storeId) // Por bodega
save($data)                 // Crear
update($id, $data)          // Actualizar
remove($id)                 // Eliminar (soft)

// Operaciones de caja
openCashbox($id, $userId, $initialBalance)
closeCashbox($id, $userId)
getActiveCashboxes()        // Cajas abiertas
getCashboxByUser($userId)   // Caja del usuario

// Saldos
getCurrentBalance($id)      // Saldo actual
updateBalance($id, $amount, $operation) // +/-
getBalanceHistory($id, $from, $to)
```

#### 2. Bankaccounts_model.php
**Ubicación:** `application/models/Bankaccounts_model.php`

**Métodos principales:**
```php
getBankAccounts()           // Listar todas
getBankAccount($id)         // Obtener una
getActiveBankAccounts()     // Activas
save($data)
update($id, $data)
remove($id)
getCurrentBalance($id)
updateBalance($id, $amount, $operation)
```

#### 3. Cashmovements_model.php
**Ubicación:** `application/models/Cashmovements_model.php`

**Métodos principales:**
```php
getMovements($filters)      // Con filtros
getMovement($id)
getMovementsBySource($type, $id)
getMovementsByDate($date)
getMovementsByPeriod($from, $to)
save($data)
update($id, $data)
remove($id)                 // Anular

// Agregaciones
getTotalsBySource($type, $id, $from, $to)
getBalanceByDate($type, $id, $date)
getDailyMovements($date)
getMonthlyMovements($year, $month)
```

#### 4. Cashboxclosures_model.php
**Ubicación:** `application/models/Cashboxclosures_model.php`

**Métodos principales:**
```php
getClosures($cashboxId)
getClosure($id)
getLastClosure($cashboxId)
save($data)
update($id, $data)
calculateExpectedBalance($cashboxId, $from, $to)
authorizeClosure($id, $userId)
```

#### 5. Bankreconciliations_model.php
**Ubicación:** `application/models/Bankreconciliations_model.php`

**Métodos principales:**
```php
getReconciliations($bankAccountId)
getReconciliation($id)
getLastReconciliation($bankAccountId)
save($data)
update($id, $data)
authorize($id, $userId)
```

### Controladores a Crear (PHP)

#### 1. Cashboxes.php
**Ubicación:** `application/controllers/sisvent/admin/Cashboxes.php`

**Métodos:**
```php
index()                     // Listar
search($term)               // Buscar
add()                       // Vista crear
store()                     // Guardar
edit($id)                   // Vista editar
update()                    // Actualizar
delete($id)                 // Eliminar
view($id)                   // Ver detalle
open($id)                   // Abrir caja
processOpen()               // Procesar apertura
close($id)                  // Cerrar caja
processClose()              // Procesar cierre
movements($id)              // Movimientos
arqueo($id)                 // Arqueo
```

#### 2. Bankaccounts.php
**Ubicación:** `application/controllers/sisvent/admin/Bankaccounts.php`

```php
index()
add()
store()
edit($id)
update()
delete($id)
view($id)
movements($id)
reconcile($id)
processReconciliation()
```

#### 3. Cashmovements.php
**Ubicación:** `application/controllers/sisvent/admin/Cashmovements.php`

```php
index()
add()
store()
edit($id)
update()
delete($id)
view($id)
transfer()                  // Transferencias
processTransfer()
getMovementsByFilters()     // AJAX
```

### Vistas a Crear (PHP/HTML)

#### Estructura de carpetas:
```
application/views/sisvent/admin/
├── cashboxes/
│   ├── list.php            # Listado de cajas
│   ├── add.php             # Crear caja
│   ├── edit.php            # Editar caja
│   ├── view.php            # Detalle + movimientos
│   ├── open_modal.php      # Modal apertura
│   ├── close_modal.php     # Modal cierre
│   └── arqueo.php          # Arqueo de caja
├── bankaccounts/
│   ├── list.php
│   ├── add.php
│   ├── edit.php
│   ├── view.php
│   └── reconcile.php       # Conciliación
├── cashmovements/
│   ├── list.php
│   ├── add.php
│   ├── view.php
│   └── transfer.php
└── reports/
    ├── cashflow.php        # Dashboard flujo de caja
    ├── cashbox_report.php  # Reporte por caja
    ├── bank_report.php     # Reporte por banco
    └── daily_cash.php      # Reporte diario
```

### Modificaciones a Archivos Existentes

#### Payments.php (Controller)
```php
// Modificar método store()
// Agregar selección de caja/banco
// Crear movimiento en cash_movements
// Vincular payment con movement

// Modificar método delete()
// Reversar movimiento de caja/banco

// Nuevos métodos AJAX
getCashboxesByStore()
getBankAccounts()
```

#### Payments_model.php (Model)
```php
// Nuevos métodos
getPaymentsByOrigin($type, $id)
linkCashMovement($paymentId, $movementId)
```

#### Reports.php (Controller)
```php
// Nuevos métodos
cashflow()
cashboxReport($id)
bankReport($id)
dailyCashReport()
getCashflowData()       // AJAX
exportCashReport()      // Excel/PDF
```

---

## Flujos de Trabajo

### 1. Apertura de Caja

```
Usuario → [Clic "Abrir Caja"] → Sistema verifica estado
    ↓
Modal solicita:
    - Saldo inicial
    - Observaciones
    ↓
Sistema:
    - Actualiza status = "abierta"
    - Registra openedAt, openedBy
    - Crea movimiento tipo "apertura"
    - Actualiza currentBalance
    ↓
Caja lista para operar
```

### 2. Registro de Pago con Caja

```
Módulo Pagos → Usuario selecciona:
    - Factura a pagar
    - Monto
    - Método de pago
    - Origen: [Caja Principal] ← NUEVO
    ↓
Sistema:
    ├─> Crea registro en payments
    ├─> Crea movimiento en cash_movements (tipo: ingreso)
    ├─> Actualiza saldo de caja
    ├─> Genera asiento contable automático
    └─> Vincula payment.cashMovementId
    ↓
Pago registrado y reflejado en caja
```

### 3. Cierre de Caja

```
Usuario → [Clic "Cerrar Caja"]
    ↓
Sistema muestra:
    - Saldo apertura: $100,000
    - Ingresos del día: $500,000
    - Egresos del día: $50,000
    - Saldo esperado: $550,000 ← Calculado
    ↓
Usuario ingresa:
    - Saldo real contado: $548,000
    - Conteo de billetes (opcional)
    ↓
Sistema calcula:
    - Diferencia: -$2,000 (faltante)
    ↓
Si diferencia > 5% → Requiere autorización
    ↓
Sistema:
    ├─> Crea registro en cashbox_closures
    ├─> Actualiza status = "cerrada"
    ├─> Si hay diferencia: crea movimiento "ajuste"
    └─> Registra closedAt, closedBy
    ↓
Caja cerrada
```

### 4. Transferencia entre Cajas/Bancos

```
Usuario selecciona:
    - Origen: Caja Principal ($100,000)
    - Destino: Banco Davivienda
    - Monto: $50,000
    - Concepto: "Depósito diario"
    ↓
Sistema valida:
    - Saldo suficiente en origen ✓
    - Destino activo ✓
    ↓
Sistema crea 2 movimientos vinculados:
    ├─> Egreso en Caja Principal: -$50,000
    └─> Ingreso en Banco Davivienda: +$50,000
    ↓
Actualiza saldos:
    - Caja Principal: $50,000
    - Banco Davivienda: +$50,000
    ↓
Genera asiento contable:
    Débito: Banco (1110) $50,000
    Crédito: Caja (1105) $50,000
```

### 5. Conciliación Bancaria

```
Usuario → Selecciona cuenta bancaria
    ↓
Sistema muestra:
    - Movimientos del período
    - Saldo en libros: $5,000,000
    ↓
Usuario ingresa:
    - Saldo extracto bancario: $4,950,000
    ↓
Usuario marca movimientos:
    [✓] Depósito $100,000
    [✓] Cheque #123 $50,000
    [ ] Cheque #124 $50,000 ← No cobrado aún
    ↓
Sistema calcula:
    - Saldo libros: $5,000,000
    - Cheques no cobrados: -$50,000
    - Saldo conciliado: $4,950,000 ✓
    - Diferencia: $0
    ↓
Genera reporte de conciliación
```

---

## Integración con Contabilidad

### Vinculación de Cajas con Plan de Cuentas

**Al crear una caja:**
1. Sistema crea automáticamente subcuenta en `subaccounts`
2. Bajo la cuenta principal **1105 (Caja)**
3. Guarda `subaccountId` en tabla `cashboxes`
4. Todos los movimientos actualizan esta subcuenta

**Ejemplo:**
```
Cuenta: 1105 - Caja
    └─ Subcuenta: 1105001 - Caja Principal Bodega 1
    └─ Subcuenta: 1105002 - Caja Sucursal Norte
    └─ Subcuenta: 1105003 - Caja Chica Administración
```

### Vinculación de Bancos con Plan de Cuentas

**Al crear cuenta bancaria:**
1. Sistema crea subcuenta en `subaccounts`
2. Bajo la cuenta principal **1110 (Bancos)**
3. Guarda `subaccountId` en tabla `bank_accounts`

**Ejemplo:**
```
Cuenta: 1110 - Bancos
    └─ Subcuenta: 1110001 - Banco Davivienda Cta 123456
    └─ Subcuenta: 1110002 - Banco Bancolombia Cta 789012
    └─ Subcuenta: 1110003 - Banco BBVA USD Cta 345678
```

### Asientos Contables Automáticos

Cada movimiento en `cash_movements` genera asiento en `entries`:

**Ejemplo 1: Pago de cliente (ingreso a caja)**
```
Concepto: Pago Factura 1001 de Cliente ABC
Débito:  Caja Principal (1105-001)        $100,000
Crédito: Clientes (1305-005-ABC)          $100,000
```

**Ejemplo 2: Pago a proveedor (egreso de banco)**
```
Concepto: Pago Factura 2050 Proveedor XYZ
Débito:  Proveedores (2205-010-XYZ)       $50,000
Crédito: Banco Davivienda (1110-001)      $50,000
```

**Ejemplo 3: Gasto operativo (egreso de caja)**
```
Concepto: Pago servicios públicos
Débito:  Gastos Servicios (5135)          $30,000
Crédito: Caja Principal (1105-001)        $30,000
```

**Ejemplo 4: Transferencia**
```
Concepto: Depósito de caja a banco
Débito:  Banco Davivienda (1110-001)      $200,000
Crédito: Caja Principal (1105-001)        $200,000
```

---

## Reportes Clave

### 1. Dashboard de Flujo de Caja

**Vista principal con:**
```
┌─────────────────────────────────────────────────┐
│  SALDOS ACTUALES                                │
├─────────────────────────────────────────────────┤
│  💵 Total en Cajas:        $  500,000.00       │
│  🏦 Total en Bancos:       $5,000,000.00       │
│  💰 Total Disponible:      $5,500,000.00       │
└─────────────────────────────────────────────────┘

┌──────────────┬──────────────┬──────────────────┐
│   HOY        │    MES       │   PROYECCIÓN     │
├──────────────┼──────────────┼──────────────────┤
│ Ingresos     │ Ingresos     │ Próximos 7 días  │
│ $300,000     │ $8,500,000   │ + $1,200,000     │
│              │              │                  │
│ Egresos      │ Egresos      │ Próximos 7 días  │
│ $150,000     │ $7,200,000   │ - $900,000       │
│              │              │                  │
│ Neto         │ Neto         │ Saldo proyectado │
│ +$150,000    │ +$1,300,000  │ $5,800,000       │
└──────────────┴──────────────┴──────────────────┘

[Gráfico de líneas: Flujo de caja últimos 30 días]

┌─────────────────────────────────────────────────┐
│  TOP 5 CONCEPTOS DE INGRESO (Este mes)         │
├─────────────────────────────────────────────────┤
│  1. Pagos de clientes        $5,200,000        │
│  2. Ventas contado          $2,800,000        │
│  3. Otros ingresos          $  500,000        │
└─────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────┐
│  TOP 5 CONCEPTOS DE EGRESO (Este mes)          │
├─────────────────────────────────────────────────┤
│  1. Pago a proveedores       $4,500,000        │
│  2. Nómina                  $1,800,000        │
│  3. Servicios públicos      $  400,000        │
│  4. Arriendos               $  300,000        │
│  5. Otros gastos            $  200,000        │
└─────────────────────────────────────────────────┘
```

### 2. Reporte de Caja Diario

**Por cada caja:**
```
CAJA PRINCIPAL - BODEGA CENTRO
Fecha: 23/01/2026
Responsable: Juan Pérez

═══════════════════════════════════════
APERTURA: 08:00 AM
Saldo inicial:              $  100,000
───────────────────────────────────────
INGRESOS:
  Ventas contado           $  250,000
  Pagos de clientes        $  300,000
  Otros ingresos          $   50,000
                          ───────────
  Total ingresos:         $  600,000
───────────────────────────────────────
EGRESOS:
  Gastos varios           $   80,000
  Devoluciones            $   20,000
  Depósito a banco        $  200,000
                          ───────────
  Total egresos:          $  300,000
───────────────────────────────────────
SALDO ESPERADO:            $  400,000
SALDO REAL CONTADO:        $  398,500
DIFERENCIA:                $   -1,500 ⚠️
═══════════════════════════════════════
Observaciones: Faltante menor, justificado
Cerrada por: Juan Pérez - 06:00 PM
Autorizada por: María López
```

### 3. Libro de Bancos

**Por cuenta bancaria:**
```
BANCO DAVIVIENDA - CTA CORRIENTE 123456789
Período: Enero 2026

Fecha      | Concepto                | Débito    | Crédito   | Saldo
-----------|-------------------------|-----------|-----------|------------
01/01/2026 | Saldo anterior          |           |           | 3,000,000
02/01/2026 | Depósito efectivo      | 200,000   |           | 3,200,000
03/01/2026 | Pago proveedor XYZ     |           | 150,000   | 3,050,000
05/01/2026 | Transferencia cliente  | 500,000   |           | 3,550,000
07/01/2026 | Cheque #1001           |           | 80,000    | 3,470,000
10/01/2026 | Comisión bancaria      |           | 5,000     | 3,465,000
...
31/01/2026 | Saldo final            |           |           | 4,200,000
           |                         |           |           |
           | TOTALES:                | 5,800,000 | 1,600,000 |

[Botón: Exportar a Excel] [Botón: Imprimir PDF]
```

### 4. Análisis de Cartera vs Caja

```
CARTERA Y DISPONIBLE
Fecha: 23/01/2026

┌─────────────────────────────────────┐
│  CUENTAS POR COBRAR                 │
├─────────────────────────────────────┤
│  Total cartera:        $15,000,000  │
│                                      │
│  Por vencer:           $ 8,000,000  │
│  Vencidas 1-30 días:   $ 4,000,000  │
│  Vencidas 31-60 días:  $ 2,000,000  │
│  Vencidas +60 días:    $ 1,000,000  │
└─────────────────────────────────────┘

┌─────────────────────────────────────┐
│  DISPONIBLE INMEDIATO               │
├─────────────────────────────────────┤
│  Cajas:                $   500,000  │
│  Bancos:               $ 5,000,000  │
│  Total:                $ 5,500,000  │
└─────────────────────────────────────┘

┌─────────────────────────────────────┐
│  PROYECCIÓN PRÓXIMOS 7 DÍAS         │
├─────────────────────────────────────┤
│  Cobros estimados:     $ 2,500,000  │
│  Pagos programados:    $ 1,800,000  │
│  Flujo neto:           $   700,000  │
│  Disponible proyectado:$ 6,200,000  │
└─────────────────────────────────────┘

[Gráfico circular: Composición de cartera por aging]
```

---

## Validaciones y Reglas de Negocio

### Validaciones Críticas

✅ **Validaciones obligatorias:**
1. No permitir egresos si saldo es insuficiente
2. No permitir cerrar caja con saldo negativo
3. Solo un usuario puede tener una caja abierta a la vez
4. Diferencia en cierre > 5% requiere autorización
5. No eliminar movimientos contabilizados (solo anular)
6. En transferencias: origen ≠ destino
7. Montos siempre > 0
8. Fecha de movimiento no puede ser futura

⚠️ **Advertencias:**
- Cierre de caja sin movimientos
- Saldo muy bajo en caja (< saldo mínimo)
- Cuenta bancaria sin conciliar > 30 días
- Diferencia en cierre entre 2% y 5%

### Permisos por Rol

#### Rol: Administrador
- ✅ CRUD de cajas y bancos
- ✅ Apertura/cierre de cualquier caja
- ✅ Ver todos los movimientos
- ✅ Autorizar cierres con diferencia
- ✅ Conciliación bancaria
- ✅ Anular movimientos
- ✅ Todos los reportes

#### Rol: Cajero
- ✅ Ver cajas asignadas
- ✅ Apertura/cierre de caja propia
- ✅ Registrar movimientos de su caja
- ✅ Ver movimientos de su caja
- ✅ Arqueo de caja
- ❌ No puede ver otras cajas
- ❌ No puede autorizar cierres

#### Rol: Vendedor
- ✅ Registrar pagos (se reflejan en caja automáticamente)
- ✅ Ver saldo de caja (solo consulta)
- ❌ No puede abrir/cerrar caja
- ❌ No puede hacer movimientos manuales

#### Rol: Contador
- ✅ Ver todos los movimientos
- ✅ Generar todos los reportes
- ✅ Conciliación bancaria
- ✅ Ver asientos contables
- ❌ No puede crear/modificar cajas
- ❌ No puede hacer movimientos

### Estados y Transiciones

#### Estados de Caja:
```
cerrada → abierta → arqueo → cerrada
                      ↓
                   bloqueada (requiere autorización)
```

#### Estados de Movimiento:
```
pendiente → autorizado → ejecutado
                ↓
            rechazado

ejecutado → anulado (solo con autorización)
```

#### Estados de Cierre:
```
borrador → cerrada → autorizada
```

---

## Cronograma de Implementación

### Sprint 1: Base de Datos y Modelos (Semana 1-2)

**Objetivos:**
- ✅ Crear todas las tablas
- ✅ Crear todos los modelos
- ✅ Probar métodos CRUD básicos

**Tareas:**
1. Crear tabla `cashboxes`
2. Crear tabla `bank_accounts`
3. Crear tabla `cash_movements`
4. Crear tabla `cashbox_closures`
5. Crear tabla `bank_reconciliations`
6. Modificar tabla `payments`
7. Crear modelo `Cashboxes_model.php`
8. Crear modelo `Bankaccounts_model.php`
9. Crear modelo `Cashmovements_model.php`
10. Crear modelo `Cashboxclosures_model.php`
11. Crear modelo `Bankreconciliations_model.php`
12. Testing de modelos

**Entregables:**
- Script SQL completo
- 5 modelos funcionales
- Pruebas unitarias básicas

---

### Sprint 2: Controladores y Vistas de Cajas (Semana 3-4)

**Objetivos:**
- ✅ CRUD completo de cajas
- ✅ Apertura/cierre básico
- ✅ Vistas funcionales

**Tareas:**
1. Crear controlador `Cashboxes.php`
2. Implementar métodos index, add, store, edit, update, delete
3. Crear vista `list.php` (listado de cajas)
4. Crear vista `add.php` (formulario crear)
5. Crear vista `edit.php` (formulario editar)
6. Crear vista `view.php` (detalle de caja)
7. Implementar métodos open, processOpen
8. Implementar métodos close, processClose
9. Crear modal `open_modal.php`
10. Crear modal `close_modal.php`
11. Integrar con sistema de permisos
12. Testing funcional

**Entregables:**
- Controlador completo
- 6 vistas funcionales
- Flujo de apertura/cierre operativo

---

### Sprint 3: Movimientos y Bancos (Semana 5-6)

**Objetivos:**
- ✅ Gestión de movimientos
- ✅ CRUD de bancos
- ✅ Transferencias

**Tareas:**
1. Crear controlador `Cashmovements.php`
2. Implementar listado de movimientos con filtros
3. Crear vista `list.php` de movimientos
4. Implementar registro manual de movimientos
5. Crear vista `add.php` y `view.php` de movimientos
6. Implementar transferencias entre cajas/bancos
7. Crear vista `transfer.php`
8. Crear controlador `Bankaccounts.php`
9. Implementar CRUD completo de bancos
10. Crear vistas de bancos (list, add, edit, view)
11. Testing de flujos
12. Validaciones de negocio

**Entregables:**
- 2 controladores completos
- 8 vistas funcionales
- Flujo de transferencias operativo

---

### Sprint 4: Integración con Pagos y Contabilidad (Semana 7-8)

**Objetivos:**
- ✅ Vincular pagos con cajas/bancos
- ✅ Generar asientos contables automáticos
- ✅ Actualización de saldos en tiempo real

**Tareas:**
1. Modificar controlador `Payments.php`
2. Agregar selección de caja/banco en formulario de pago
3. Implementar creación automática de movimiento al pagar
4. Modificar método delete para reversar movimiento
5. Crear método `linkCashMovement()` en modelo
6. Implementar generación de asiento contable automático
7. Vincular movimientos con tabla `entries`
8. Actualizar saldos de subcuentas contables
9. Implementar vinculación de caja con plan de cuentas
10. Implementar vinculación de banco con plan de cuentas
11. Testing de integración contable
12. Validación de cuadre contable

**Entregables:**
- Integración completa Pagos → Caja/Banco
- Asientos contables automáticos
- Cuadre contable verificado

---

### Sprint 5: Reportes y Funcionalidades Avanzadas (Semana 9-10)

**Objetivos:**
- ✅ Dashboard de flujo de caja
- ✅ Reportes específicos
- ✅ Arqueo y conciliación

**Tareas:**
1. Modificar controlador `Reports.php`
2. Implementar método `cashflow()`
3. Crear vista `cashflow.php` (dashboard)
4. Implementar gráficos de flujo de caja
5. Crear reporte de caja diario
6. Crear reporte de libro de bancos
7. Implementar exportación a Excel/PDF
8. Crear vista `arqueo.php`
9. Implementar conteo de billetes y monedas
10. Crear vista `reconcile.php` (conciliación bancaria)
11. Implementar lógica de conciliación
12. Testing completo del módulo

**Entregables:**
- Dashboard operativo
- 4 reportes funcionales
- Arqueo de caja completo
- Conciliación bancaria operativa

---

### Sprint 6: Testing, Ajustes y Documentación (Semana 11)

**Objetivos:**
- ✅ Testing integral
- ✅ Corrección de bugs
- ✅ Documentación
- ✅ Capacitación

**Tareas:**
1. Testing de todos los flujos principales
2. Testing de validaciones y permisos
3. Testing de integración contable
4. Corrección de bugs encontrados
5. Optimización de consultas SQL
6. Crear manual de usuario
7. Crear manual técnico
8. Documentar API (si aplica)
9. Capacitación a usuarios clave
10. Preparar ambiente de producción
11. Migración de datos (si aplica)
12. Deploy a producción

**Entregables:**
- Sistema completamente testeado
- Documentación completa
- Usuarios capacitados
- Sistema en producción

---

## Cronograma Visual

```
SEMANA 1-2: Base de Datos y Modelos
████████████████████ 100%

SEMANA 3-4: Cajas (Controladores y Vistas)
████████████████████ 100%

SEMANA 5-6: Movimientos y Bancos
████████████████████ 100%

SEMANA 7-8: Integración Pagos/Contabilidad
████████████████████ 100%

SEMANA 9-10: Reportes y Avanzado
████████████████████ 100%

SEMANA 11: Testing y Deploy
████████████████████ 100%

TOTAL: 11 semanas (2.5 meses aproximadamente)
```

---

## Plan de Trabajo para Múltiples Programadores

### Equipo Sugerido

**Equipo de 3 programadores:**

#### Programador 1: Backend & BD
**Responsabilidades:**
- Sprint 1 completo (BD + Modelos)
- Lógica de negocio en controladores
- Integración contable (Sprint 4)
- Optimización de queries

**Skills requeridos:**
- PHP / CodeIgniter
- MySQL
- Diseño de BD
- Contabilidad básica

#### Programador 2: Frontend & UX
**Responsabilidades:**
- Todas las vistas (Sprints 2, 3, 5)
- Modales y componentes
- JavaScript/jQuery para interactividad
- Gráficos y reportes visuales

**Skills requeridos:**
- HTML/CSS
- JavaScript/jQuery
- Tailwind CSS
- UX/UI

#### Programador 3: Integración & Testing
**Responsabilidades:**
- Integración entre módulos
- Testing de flujos
- Validaciones
- Corrección de bugs
- Documentación

**Skills requeridos:**
- PHP / CodeIgniter
- Testing
- Debugging
- Documentación técnica

### Distribución de Tareas por Sprint

**Sprint 1:**
- Programador 1: 100% (BD y modelos)
- Programador 2: Diseño de mockups de vistas
- Programador 3: Plan de testing

**Sprint 2:**
- Programador 1: Controlador Cashboxes (lógica)
- Programador 2: Vistas de Cashboxes
- Programador 3: Validaciones y permisos

**Sprint 3:**
- Programador 1: Controladores Cashmovements y Bankaccounts
- Programador 2: Vistas de movimientos y bancos
- Programador 3: Testing de flujos

**Sprint 4:**
- Programador 1: Integración contable (80%)
- Programador 2: Ajustes en vistas de pagos (20%)
- Programador 3: Testing de integración

**Sprint 5:**
- Programador 1: Lógica de reportes
- Programador 2: Dashboard y gráficos (100%)
- Programador 3: Testing y validaciones

**Sprint 6:**
- Todos: Testing, corrección de bugs, documentación

---

## Consideraciones Adicionales

### Performance

**Optimizaciones recomendadas:**
1. **Índices en BD:**
   - Todos los campos de búsqueda frecuente
   - Campos de JOIN
   - Campos de fecha

2. **Cache:**
   - Saldos actuales de cajas/bancos
   - Dashboard (actualizar cada 5 minutos)
   - Reportes históricos

3. **Paginación:**
   - Movimientos (50 por página)
   - Listados largos

4. **Queries optimizados:**
   - Evitar subconsultas complejas
   - Usar índices compuestos
   - EXPLAIN para queries lentos

### Seguridad

**Medidas críticas:**
1. **Autenticación:**
   - Validar sesión en cada request
   - Timeout de sesión (30 min)

2. **Autorización:**
   - Validar permisos por rol
   - No confiar en datos del cliente

3. **Validación:**
   - CSRF tokens en formularios
   - Sanitización de inputs
   - Validación server-side

4. **Auditoría:**
   - Log de todas las operaciones críticas
   - IP y usuario en logs
   - Timestamps en todo

5. **Datos sensibles:**
   - Cifrar números de cuenta (opcional)
   - No exponer saldos sin autorización

### UX/UI

**Mejores prácticas:**
1. **Colores:**
   - Verde: Ingresos ✅
   - Rojo: Egresos ❌
   - Azul: Transferencias ↔️
   - Amarillo: Advertencias ⚠️

2. **Indicadores visuales:**
   - Badge "Abierta" en verde
   - Badge "Cerrada" en gris
   - Badge "Diferencia" en rojo

3. **Confirmaciones:**
   - Confirmar antes de eliminar
   - Confirmar antes de cerrar caja con diferencia
   - Confirmar transferencias grandes

4. **Autocompletado:**
   - Búsqueda de cajas
   - Búsqueda de conceptos
   - Filtros inteligentes

5. **Mensajes:**
   - Claros y descriptivos
   - Ubicados cerca de la acción
   - Con opciones de acción

### Datos de Prueba

**Script para insertar datos de prueba:**
```sql
-- Insertar cajas de ejemplo
INSERT INTO cashboxes (name, code, type, storeId, initialBalance, currentBalance, status)
VALUES
('Caja Principal Bodega Centro', 'CAJ001', 'principal', 1, 100000, 100000, 'cerrada'),
('Caja Sucursal Norte', 'CAJ002', 'principal', 2, 50000, 50000, 'cerrada'),
('Caja Chica Administración', 'CAJ003', 'chica', 1, 20000, 20000, 'cerrada');

-- Insertar bancos de ejemplo
INSERT INTO bank_accounts (bankName, accountNumber, accountType, initialBalance, currentBalance, status)
VALUES
('Banco Davivienda', '1234567890', 'corriente', 5000000, 5000000, 'activa'),
('Banco Bancolombia', '0987654321', 'ahorros', 3000000, 3000000, 'activa'),
('Banco BBVA', '5555555555', 'corriente', 2000000, 2000000, 'activa');

-- Insertar movimientos de ejemplo
INSERT INTO cash_movements (movementType, sourceType, sourceId, amount, concept, category, executedBy, movementDate)
VALUES
('ingreso', 'caja', 1, 50000, 'Venta contado', 'venta', 'admin', NOW()),
('egreso', 'caja', 1, 20000, 'Compra de insumos', 'gasto', 'admin', NOW()),
('transferencia', 'caja', 1, 100000, 'Depósito diario', 'otro', 'admin', NOW());
```

---

## Anexos

### Anexo A: Scripts SQL Completos

Ver sección "Base de Datos" para scripts completos de creación de tablas.

### Anexo B: Estructura de JSON para billCount

```json
{
  "billetes": {
    "100000": 5,
    "50000": 10,
    "20000": 15,
    "10000": 20,
    "5000": 30,
    "2000": 40,
    "1000": 50
  },
  "monedas": {
    "1000": 20,
    "500": 40,
    "200": 60,
    "100": 80,
    "50": 100
  },
  "total": 548500
}
```

### Anexo C: Endpoints API REST (Futuro)

```
GET    /api/v1/cashboxes              # Listar cajas
POST   /api/v1/cashboxes              # Crear caja
GET    /api/v1/cashboxes/{id}         # Obtener caja
PUT    /api/v1/cashboxes/{id}         # Actualizar caja
DELETE /api/v1/cashboxes/{id}         # Eliminar caja
POST   /api/v1/cashboxes/{id}/open    # Abrir caja
POST   /api/v1/cashboxes/{id}/close   # Cerrar caja

GET    /api/v1/bank-accounts          # Listar bancos
POST   /api/v1/bank-accounts          # Crear banco
GET    /api/v1/bank-accounts/{id}     # Obtener banco
PUT    /api/v1/bank-accounts/{id}     # Actualizar banco
DELETE /api/v1/bank-accounts/{id}     # Eliminar banco

GET    /api/v1/movements              # Listar movimientos
POST   /api/v1/movements              # Crear movimiento
GET    /api/v1/movements/{id}         # Obtener movimiento
POST   /api/v1/movements/transfer     # Transferencia

GET    /api/v1/reports/cashflow       # Reporte flujo de caja
GET    /api/v1/reports/daily          # Reporte diario
```

### Anexo D: Glosario de Términos

| Término | Definición |
|---------|------------|
| **Arqueo** | Conteo físico del efectivo en caja para verificar coincidencia con registros |
| **Conciliación bancaria** | Proceso de comparar registros internos con extracto bancario |
| **Asiento contable** | Registro en libros de contabilidad de una transacción |
| **Subcuenta** | Subdivisión de una cuenta contable para mayor detalle |
| **Auxiliar** | Nivel más detallado de la contabilidad (ej: por cliente) |
| **Flujo de caja** | Movimiento de entradas y salidas de efectivo |
| **Aging** | Clasificación de cartera por días de vencimiento |
| **FIFO** | First In, First Out - Método de valoración de inventario |
| **LIFO** | Last In, First Out - Método de valoración de inventario |
| **BOM** | Bill of Materials - Lista de materiales para producción |
| **KPI** | Key Performance Indicator - Indicador clave de desempeño |
| **ERP** | Enterprise Resource Planning - Sistema de planificación empresarial |

### Anexo E: Contactos y Recursos

**Equipo de Desarrollo:**
- Coordinador del proyecto: [Nombre]
- Líder técnico: [Nombre]
- Programador 1: [Nombre]
- Programador 2: [Nombre]
- Programador 3: [Nombre]

**Recursos:**
- Repositorio Git: [URL]
- Documentación CodeIgniter: https://codeigniter.com/userguide3/
- Documentación Tailwind CSS: https://tailwindcss.com/docs
- Trello/Jira del proyecto: [URL]

---

## Conclusión

Este documento presenta un plan completo y estructurado para transformar el sistema MAM en un ERP empresarial completo, comenzando con el módulo crítico de **Caja y Bancos**.

### Próximos Pasos Inmediatos

1. ✅ Revisar y aprobar este documento
2. ✅ Asignar equipo de desarrollo
3. ✅ Crear repositorio Git y estructura de proyecto
4. ✅ Ejecutar Sprint 1: Base de datos y modelos
5. ✅ Reuniones de seguimiento semanales

### Beneficios Esperados

**A corto plazo (3 meses):**
- Control financiero en tiempo real
- Reducción de faltantes de caja
- Mejor flujo de caja
- Reportes financieros confiables

**A mediano plazo (6 meses):**
- ERP completo operativo
- Reducción de errores manuales
- Mejor toma de decisiones
- Aumento de productividad

**A largo plazo (12 meses):**
- Sistema escalable y robusto
- Integración con sistemas externos
- Ventaja competitiva
- Base para crecimiento empresarial

---

**Versión del documento:** 1.0
**Fecha de última actualización:** 23 de Enero de 2026
**Estado:** Propuesta pendiente de aprobación

---

*Este documento es confidencial y propiedad del equipo de desarrollo MAM.*
