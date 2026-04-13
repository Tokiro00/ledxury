<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$role = $this->session->userdata('user_data')['role'];
$mainStore = isset($stores[0]) ? $stores[0] : null;
?>
<!DOCTYPE html>
<html lang="en">
    <title>Asistente de Configuracion</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<style>
    .wizard-step { display: none; }
    .wizard-step.active { display: block; }
    .step-indicator { transition: all 0.3s ease; }
    .step-indicator.completed { background-color: #8DC63F; color: white; cursor: pointer; }
    .step-indicator.current { background-color: #2E7D91; color: white; }
    .step-indicator.pending { background-color: #e2e8f0; color: #a0aec0; }
    .step-connector { height: 2px; transition: background-color 0.3s ease; }
    .step-connector.completed { background-color: #8DC63F; }
    .step-connector.pending { background-color: #e2e8f0; }
    .path-card { transition: all 0.2s ease; border: 2px solid transparent; }
    .path-card:hover { border-color: #2E7D91; transform: translateY(-2px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
    .fade-in { animation: fadeIn 0.3s ease-in; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    .drop-zone { border: 2px dashed #cbd5e0; transition: all 0.2s; }
    .drop-zone:hover, .drop-zone.dragover { border-color: #2E7D91; background-color: #f0fdfa; }
    .toast { position: fixed; top: 20px; right: 20px; z-index: 9999; transform: translateX(120%); transition: transform 0.3s ease; }
    .toast.show { transform: translateX(0); }
</style>
<body>
    <!-- Toast notification -->
    <div id="toast" class="toast p-4 rounded-lg shadow-lg text-white text-sm max-w-sm">
        <div class="flex items-center gap-2">
            <span id="toast-icon"></span>
            <span id="toast-message"></span>
        </div>
    </div>

    <div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
        <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => $_ci_view, 'role' => $role)); ?>

        <div class="flex flex-col flex-1 w-full">
            <?php $this->load->view('sisvent/layouts/navbar'); ?>
            <main class="h-full overflow-y-auto">
                <div class="px-6 mx-auto grid" style="max-width: 960px;">

                    <!-- Header -->
                    <div class="flex items-center justify-between mb-4 mt-2">
                        <div>
                            <h2 class="text-xl font-semibold text-gray-700">Asistente de Configuracion</h2>
                            <p class="text-xs text-gray-400">Configure su empresa paso a paso</p>
                        </div>
                        <button id="btn-back-to-welcome" class="text-sm text-gray-500 hover:text-gray-700 hidden">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                            Volver al inicio
                        </button>
                    </div>

                    <!-- ============================================================ -->
                    <!-- WELCOME SCREEN -->
                    <!-- ============================================================ -->
                    <div id="welcome-screen" class="fade-in">
                        <div class="text-center mb-8">
                            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full mb-4" style="background-color: #2E7D91;">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-800 mb-2">Bienvenido al Asistente de Configuracion</h3>
                            <p class="text-gray-500">Seleccione como desea configurar su sistema</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                            <!-- Path A: Import -->
                            <div class="path-card bg-white rounded-lg shadow-md p-8 cursor-pointer text-center" id="path-import">
                                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full mb-4" style="background-color: #1B365D;">
                                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2 1 3 3 3h10c2 0 3-1 3-3V7M16 3H8c-1.1 0-2 .9-2 2v0c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2v0c0-1.1-.9-2-2-2zM9 13l3 3 3-3M12 16V10"/></svg>
                                </div>
                                <h4 class="text-lg font-bold text-gray-800 mb-2">Importar Base de Datos</h4>
                                <p class="text-sm text-gray-500 mb-4">Suba un archivo SQL o Excel para migrar datos de otro sistema.</p>
                                <span class="inline-block px-3 py-1 text-xs font-semibold rounded-full" style="background-color: #EBF5FB; color: #1B365D;">Para migraciones</span>
                            </div>

                            <!-- Path B: Step by step -->
                            <div class="path-card bg-white rounded-lg shadow-md p-8 cursor-pointer text-center" id="path-wizard">
                                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full mb-4" style="background-color: #8DC63F;">
                                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                                </div>
                                <h4 class="text-lg font-bold text-gray-800 mb-2">Configurar Paso a Paso</h4>
                                <p class="text-sm text-gray-500 mb-4">Asistente interactivo para configurar todo desde cero.</p>
                                <span class="inline-block px-3 py-1 text-xs font-semibold rounded-full" style="background-color: #F0FFF4; color: #276749;">Para nuevos negocios</span>
                            </div>
                        </div>
                    </div>

                    <!-- ============================================================ -->
                    <!-- PATH A: IMPORT SCREEN -->
                    <!-- ============================================================ -->
                    <div id="import-screen" class="hidden fade-in">
                        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                            <h3 class="text-lg font-bold text-gray-800 mb-1">Importar Base de Datos SQL</h3>
                            <p class="text-sm text-gray-500 mb-4">Suba un archivo .sql para ejecutar contra la base de datos actual. Util para restaurar un respaldo o migrar datos.</p>

                            <div class="drop-zone rounded-lg p-8 text-center mb-4" id="sql-drop-zone">
                                <svg class="w-12 h-12 mx-auto text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                <p class="text-sm text-gray-600 mb-2">Arrastre un archivo .sql aqui o haga clic para seleccionar</p>
                                <input type="file" id="sql-file-input" accept=".sql" class="hidden">
                                <button type="button" class="px-4 py-2 text-sm font-medium text-white rounded-lg" style="background-color: #1B365D;" onclick="document.getElementById('sql-file-input').click()">Seleccionar archivo</button>
                                <p class="text-xs text-gray-400 mt-2" id="sql-file-name"></p>
                            </div>

                            <div class="flex items-center gap-3">
                                <button type="button" id="btn-import-sql" class="px-4 py-2 text-sm font-medium text-white rounded-lg disabled:opacity-50" style="background-color: #2E7D91;" disabled>
                                    <svg class="w-4 h-4 inline mr-1 animate-spin hidden" id="sql-spinner" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                    Ejecutar SQL
                                </button>
                                <div id="sql-result" class="text-sm hidden"></div>
                            </div>
                        </div>

                        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                            <h3 class="text-lg font-bold text-gray-800 mb-1">Importar desde Excel</h3>
                            <p class="text-sm text-gray-500 mb-4">Importe productos, clientes o proveedores desde un archivo Excel (.xlsx).</p>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <!-- Products -->
                                <div class="border rounded-lg p-4">
                                    <div class="flex items-center gap-2 mb-3">
                                        <svg class="w-5 h-5" style="color: #2E7D91;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                        <span class="font-semibold text-gray-700">Productos</span>
                                    </div>
                                    <input type="file" class="excel-file-input text-xs w-full mb-2" data-type="products" accept=".xlsx,.xls,.csv">
                                    <button type="button" class="btn-import-excel w-full px-3 py-2 text-xs font-medium text-white rounded-lg disabled:opacity-50" style="background-color: #8DC63F;" data-type="products" disabled>Importar Productos</button>
                                    <div class="excel-result text-xs mt-2 hidden" data-type="products"></div>
                                </div>

                                <!-- Clients -->
                                <div class="border rounded-lg p-4">
                                    <div class="flex items-center gap-2 mb-3">
                                        <svg class="w-5 h-5" style="color: #2E7D91;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        <span class="font-semibold text-gray-700">Clientes</span>
                                    </div>
                                    <input type="file" class="excel-file-input text-xs w-full mb-2" data-type="clients" accept=".xlsx,.xls,.csv">
                                    <button type="button" class="btn-import-excel w-full px-3 py-2 text-xs font-medium text-white rounded-lg disabled:opacity-50" style="background-color: #8DC63F;" data-type="clients" disabled>Importar Clientes</button>
                                    <div class="excel-result text-xs mt-2 hidden" data-type="clients"></div>
                                </div>

                                <!-- Providers -->
                                <div class="border rounded-lg p-4">
                                    <div class="flex items-center gap-2 mb-3">
                                        <svg class="w-5 h-5" style="color: #2E7D91;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                        <span class="font-semibold text-gray-700">Proveedores</span>
                                    </div>
                                    <input type="file" class="excel-file-input text-xs w-full mb-2" data-type="providers" accept=".xlsx,.xls,.csv">
                                    <button type="button" class="btn-import-excel w-full px-3 py-2 text-xs font-medium text-white rounded-lg disabled:opacity-50" style="background-color: #8DC63F;" data-type="providers" disabled>Importar Proveedores</button>
                                    <div class="excel-result text-xs mt-2 hidden" data-type="providers"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Import Summary -->
                        <div id="import-summary" class="bg-white rounded-lg shadow-md p-6 mb-6 hidden">
                            <h3 class="text-lg font-bold text-gray-800 mb-4">Resumen de Importacion</h3>
                            <div id="import-summary-content"></div>
                            <div class="mt-4 flex gap-3">
                                <a href="<?php echo base_url(); ?>sisvent/admin/welcome" class="px-4 py-2 text-sm font-medium text-white rounded-lg" style="background-color: #2E7D91;">Ir al Dashboard</a>
                                <button type="button" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800" id="btn-start-wizard-from-import">Continuar con el asistente</button>
                            </div>
                        </div>
                    </div>

                    <!-- ============================================================ -->
                    <!-- PATH B: STEP-BY-STEP WIZARD -->
                    <!-- ============================================================ -->
                    <div id="wizard-screen" class="hidden fade-in">

                        <!-- Progress Bar -->
                        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                            <div class="flex items-center justify-between">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <?php if ($i > 1): ?>
                                        <div class="flex-1 mx-2 step-connector pending" id="connector-<?php echo $i; ?>"></div>
                                    <?php endif; ?>
                                    <div class="flex flex-col items-center">
                                        <div class="step-indicator w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold pending" id="step-ind-<?php echo $i; ?>" data-step="<?php echo $i; ?>">
                                            <?php echo $i; ?>
                                        </div>
                                        <span class="text-xs text-gray-500 mt-1 hidden md:block">
                                            <?php
                                            $stepLabels = array('Empresa', 'Bodegas', 'Usuarios', 'Contabilidad', 'Resumen');
                                            echo $stepLabels[$i - 1];
                                            ?>
                                        </span>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>

                        <!-- ============================== -->
                        <!-- STEP 1: Datos de la Empresa -->
                        <!-- ============================== -->
                        <div class="wizard-step" id="step-1">
                            <div class="bg-white rounded-lg shadow-md p-6 mb-6 fade-in">
                                <h3 class="text-lg font-bold text-gray-800 mb-1">Datos de la Empresa</h3>
                                <p class="text-sm text-gray-500 mb-4">Ingrese la informacion basica de su empresa</p>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <label class="block text-sm">
                                        <span class="text-gray-700">Nombre de la empresa <span class="text-red-500">*</span></span>
                                        <input class="form-input" type="text" id="company-name" value="<?php echo $mainStore ? htmlspecialchars($mainStore->name) : ''; ?>" placeholder="Ej: Mi Empresa S.A.S.">
                                    </label>
                                    <label class="block text-sm">
                                        <span class="text-gray-700">NIT</span>
                                        <input class="form-input" type="text" id="company-nit" value="<?php echo $mainStore && isset($mainStore->nit) ? htmlspecialchars($mainStore->nit) : ''; ?>" placeholder="Ej: 900.123.456-7">
                                    </label>
                                    <label class="block text-sm">
                                        <span class="text-gray-700">Direccion</span>
                                        <input class="form-input" type="text" id="company-address" value="<?php echo $mainStore && isset($mainStore->address) ? htmlspecialchars($mainStore->address) : ''; ?>" placeholder="Ej: Calle 80 #45-12, Bogota">
                                    </label>
                                    <label class="block text-sm">
                                        <span class="text-gray-700">Telefono</span>
                                        <input class="form-input" type="text" id="company-phone" value="<?php echo $mainStore && isset($mainStore->phone) ? htmlspecialchars($mainStore->phone) : ''; ?>" placeholder="Ej: 301 234 5678">
                                    </label>
                                    <label class="block text-sm md:col-span-2">
                                        <span class="text-gray-700">Email</span>
                                        <input class="form-input" type="email" id="company-email" value="<?php echo $mainStore && isset($mainStore->email) ? htmlspecialchars($mainStore->email) : ''; ?>" placeholder="Ej: info@miempresa.com">
                                    </label>
                                    <label class="block text-sm md:col-span-2">
                                        <span class="text-gray-700">Datos de consignacion</span>
                                        <textarea class="form-input" id="company-invoice-account" rows="3" placeholder="Datos bancarios para consignaciones (aparecen en facturas)"><?php echo $mainStore && isset($mainStore->invoice_account) ? htmlspecialchars($mainStore->invoice_account) : ''; ?></textarea>
                                    </label>
                                    <label class="block text-sm md:col-span-2">
                                        <span class="text-gray-700">Datos de soporte</span>
                                        <textarea class="form-input" id="company-invoice-support" rows="2" placeholder="Informacion de soporte (aparece en documentos)"><?php echo $mainStore && isset($mainStore->invoice_support) ? htmlspecialchars($mainStore->invoice_support) : ''; ?></textarea>
                                    </label>
                                </div>

                                <div class="mt-4">
                                    <button type="button" id="btn-save-company" class="px-4 py-2 text-sm font-medium text-white rounded-lg" style="background-color: #2E7D91;">
                                        <svg class="w-4 h-4 inline mr-1 animate-spin hidden" id="company-spinner" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                        Guardar datos
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- ============================== -->
                        <!-- STEP 2: Bodegas / Almacenes -->
                        <!-- ============================== -->
                        <div class="wizard-step" id="step-2">
                            <div class="bg-white rounded-lg shadow-md p-6 mb-6 fade-in">
                                <h3 class="text-lg font-bold text-gray-800 mb-1">Bodegas / Almacenes</h3>
                                <p class="text-sm text-gray-500 mb-4">Cree las bodegas o puntos de venta de su empresa</p>

                                <!-- Existing stores list -->
                                <div class="mb-4">
                                    <h4 class="text-sm font-semibold text-gray-600 mb-2">Bodegas actuales</h4>
                                    <div id="stores-list" class="space-y-2">
                                        <?php if (isset($stores) && count($stores) > 0): ?>
                                            <?php foreach ($stores as $store): ?>
                                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg store-item" data-id="<?php echo $store->idStore; ?>">
                                                <div class="flex items-center gap-2">
                                                    <svg class="w-5 h-5" style="color: #2E7D91;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                                    <span class="text-sm font-medium text-gray-700"><?php echo htmlspecialchars($store->name); ?></span>
                                                    <?php if ($store->idStore == 1): ?>
                                                        <span class="px-2 py-0.5 text-xs font-semibold rounded-full" style="background-color: #E8F8F5; color: #2E7D91;">Principal</span>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if ($store->idStore != 1): ?>
                                                    <button type="button" class="btn-delete-store text-red-500 hover:text-red-700 text-sm" data-id="<?php echo $store->idStore; ?>">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Add new store -->
                                <div class="border-t pt-4">
                                    <h4 class="text-sm font-semibold text-gray-600 mb-2">Agregar nueva bodega</h4>
                                    <div class="flex gap-3">
                                        <input class="form-input flex-1" type="text" id="new-store-name" placeholder="Nombre de la bodega">
                                        <button type="button" id="btn-add-store" class="px-4 py-2 text-sm font-medium text-white rounded-lg whitespace-nowrap" style="background-color: #8DC63F;">
                                            + Agregar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ============================== -->
                        <!-- STEP 3: Usuarios -->
                        <!-- ============================== -->
                        <div class="wizard-step" id="step-3">
                            <div class="bg-white rounded-lg shadow-md p-6 mb-6 fade-in">
                                <h3 class="text-lg font-bold text-gray-800 mb-1">Usuarios</h3>
                                <p class="text-sm text-gray-500 mb-4">Cree los usuarios del sistema y asigne sus roles</p>

                                <!-- Existing users list -->
                                <div class="mb-4">
                                    <h4 class="text-sm font-semibold text-gray-600 mb-2">Usuarios actuales</h4>
                                    <div id="users-list" class="space-y-2">
                                        <?php if (isset($users) && count($users) > 0): ?>
                                            <?php foreach ($users as $user): ?>
                                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg user-item" data-id="<?php echo htmlspecialchars($user->idUser); ?>">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold" style="background-color: #1B365D;">
                                                        <?php echo strtoupper(substr($user->name, 0, 2)); ?>
                                                    </div>
                                                    <div>
                                                        <span class="text-sm font-medium text-gray-700 block"><?php echo htmlspecialchars($user->name); ?></span>
                                                        <span class="text-xs text-gray-500"><?php echo htmlspecialchars($user->idUser); ?> &middot; <?php echo htmlspecialchars($user->role_name); ?></span>
                                                    </div>
                                                </div>
                                                <?php if ($user->idUser !== $this->session->userdata('user_data')['uname']): ?>
                                                    <button type="button" class="btn-delete-user text-red-500 hover:text-red-700 text-sm" data-id="<?php echo htmlspecialchars($user->idUser); ?>">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Add new user -->
                                <div class="border-t pt-4">
                                    <h4 class="text-sm font-semibold text-gray-600 mb-3">Agregar nuevo usuario</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        <label class="block text-sm">
                                            <span class="text-gray-700">ID de usuario <span class="text-red-500">*</span></span>
                                            <input class="form-input" type="text" id="new-user-id" placeholder="Ej: jperez">
                                        </label>
                                        <label class="block text-sm">
                                            <span class="text-gray-700">Nombre completo <span class="text-red-500">*</span></span>
                                            <input class="form-input" type="text" id="new-user-name" placeholder="Ej: Juan Perez">
                                        </label>
                                        <label class="block text-sm">
                                            <span class="text-gray-700">Contrasena <span class="text-red-500">*</span></span>
                                            <input class="form-input" type="password" id="new-user-password" placeholder="Minimo 6 caracteres">
                                        </label>
                                        <label class="block text-sm">
                                            <span class="text-gray-700">Email</span>
                                            <input class="form-input" type="email" id="new-user-email" placeholder="Ej: jperez@empresa.com">
                                        </label>
                                        <label class="block text-sm">
                                            <span class="text-gray-700">Rol <span class="text-red-500">*</span></span>
                                            <select class="form-input form-select" id="new-user-role">
                                                <option value="">Seleccione un rol...</option>
                                                <?php if (isset($roles)): ?>
                                                    <?php foreach ($roles as $r): ?>
                                                        <option value="<?php echo $r->idRoles; ?>"><?php echo htmlspecialchars($r->description); ?></option>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </select>
                                        </label>
                                        <label class="block text-sm">
                                            <span class="text-gray-700">Bodega</span>
                                            <select class="form-input form-select" id="new-user-store">
                                                <?php if (isset($stores)): ?>
                                                    <?php foreach ($stores as $store): ?>
                                                        <option value="<?php echo $store->idStore; ?>"><?php echo htmlspecialchars($store->name); ?></option>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </select>
                                        </label>
                                    </div>

                                    <!-- Role descriptions -->
                                    <div class="mt-3 p-3 bg-blue-50 rounded-lg text-xs text-gray-600" id="role-descriptions">
                                        <p class="font-semibold mb-1" style="color: #1B365D;">Descripcion de roles:</p>
                                        <ul class="space-y-1">
                                            <li><strong>Admin:</strong> Acceso total al sistema</li>
                                            <li><strong>Gerente:</strong> Gestion comercial y reportes</li>
                                            <li><strong>Vendedor:</strong> Ventas y cotizaciones</li>
                                            <li><strong>Contador:</strong> Modulo contable y financiero</li>
                                            <li><strong>Administrador:</strong> Gestion operativa</li>
                                            <li><strong>Socio:</strong> Acceso a reportes y dashboard</li>
                                        </ul>
                                    </div>

                                    <div class="mt-3">
                                        <button type="button" id="btn-add-user" class="px-4 py-2 text-sm font-medium text-white rounded-lg" style="background-color: #8DC63F;">
                                            + Agregar usuario
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ============================== -->
                        <!-- STEP 4: Configuracion Contable -->
                        <!-- ============================== -->
                        <div class="wizard-step" id="step-4">
                            <div class="bg-white rounded-lg shadow-md p-6 mb-6 fade-in">
                                <h3 class="text-lg font-bold text-gray-800 mb-1">Configuracion Contable</h3>
                                <p class="text-sm text-gray-500 mb-4">Configure las cuentas PUC para la automatizacion contable</p>

                                <div class="p-4 rounded-lg mb-4" style="background-color: #EBF5FB;">
                                    <div class="flex items-start gap-3">
                                        <svg class="w-6 h-6 mt-0.5 flex-shrink-0" style="color: #2E7D91;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <div>
                                            <p class="text-sm font-semibold" style="color: #1B365D;">Estado de la configuracion contable</p>
                                            <p class="text-sm text-gray-600 mt-1" id="accounting-status-text">Cargando...</p>
                                        </div>
                                    </div>
                                </div>

                                <p class="text-sm text-gray-600 mb-4">
                                    La configuracion contable avanzada permite mapear las cuentas PUC colombianas
                                    para la generacion automatica de asientos contables al registrar ventas, compras,
                                    pagos y gastos.
                                </p>

                                <div class="flex gap-3">
                                    <a href="<?php echo base_url(); ?>sisvent/admin/accountingsettings" class="px-4 py-2 text-sm font-medium text-white rounded-lg inline-block" style="background-color: #2E7D91;">
                                        Ir a Configuracion Contable
                                    </a>
                                    <button type="button" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 btn-wizard-next">
                                        Omitir por ahora
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- ============================== -->
                        <!-- STEP 5: Resumen -->
                        <!-- ============================== -->
                        <div class="wizard-step" id="step-5">
                            <div class="bg-white rounded-lg shadow-md p-6 mb-6 fade-in">
                                <div class="text-center mb-6">
                                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full mb-3" style="background-color: #8DC63F;">
                                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    </div>
                                    <h3 class="text-xl font-bold text-gray-800 mb-1">Configuracion Completada</h3>
                                    <p class="text-sm text-gray-500">Su sistema esta listo para usar</p>
                                </div>

                                <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6" id="summary-cards">
                                    <!-- Filled dynamically -->
                                </div>

                                <div class="flex items-center justify-center gap-4 mt-6">
                                    <a href="<?php echo base_url(); ?>sisvent/admin/welcome" class="px-6 py-3 text-sm font-medium text-white rounded-lg" style="background-color: #2E7D91;">
                                        Ir al Dashboard
                                    </a>
                                    <a href="<?php echo base_url(); ?>sisvent/admin/import" class="px-6 py-3 text-sm font-medium border rounded-lg" style="color: #2E7D91; border-color: #2E7D91;">
                                        Importar Datos
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Navigation Buttons -->
                        <div class="flex items-center justify-between mb-8" id="wizard-nav">
                            <button type="button" id="btn-prev" class="px-4 py-2 text-sm font-medium text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 hidden">
                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                Anterior
                            </button>
                            <div></div>
                            <button type="button" id="btn-next" class="px-4 py-2 text-sm font-medium text-white rounded-lg" style="background-color: #2E7D91;">
                                Siguiente
                                <svg class="w-4 h-4 inline ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </button>
                        </div>

                    </div>

                </div>
            </main>
            <?php $this->load->view('sisvent/layouts/footer'); ?>
        </div>
    </div>

<script>
(function() {
    var currentStep = 1;
    var totalSteps  = 5;
    var completedSteps = {};

    // ================================================================
    // TOAST NOTIFICATIONS
    // ================================================================
    function showToast(message, type) {
        var $toast = $('#toast');
        var bg = type === 'success' ? '#8DC63F' : (type === 'error' ? '#e53e3e' : '#2E7D91');
        var icon = type === 'success'
            ? '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>'
            : '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
        $toast.css('background-color', bg);
        $('#toast-icon').html(icon);
        $('#toast-message').text(message);
        $toast.addClass('show');
        setTimeout(function() { $toast.removeClass('show'); }, 4000);
    }

    // ================================================================
    // WELCOME SCREEN NAVIGATION
    // ================================================================
    $(document).on('click', '#path-import', function() {
        $('#welcome-screen').addClass('hidden');
        $('#import-screen').removeClass('hidden');
        $('#btn-back-to-welcome').removeClass('hidden');
    });

    $(document).on('click', '#path-wizard', function() {
        startWizard();
    });

    $(document).on('click', '#btn-back-to-welcome', function() {
        $('#welcome-screen').removeClass('hidden');
        $('#import-screen').addClass('hidden');
        $('#wizard-screen').addClass('hidden');
        $(this).addClass('hidden');
    });

    $(document).on('click', '#btn-start-wizard-from-import', function() {
        $('#import-screen').addClass('hidden');
        startWizard();
    });

    function startWizard() {
        $('#welcome-screen').addClass('hidden');
        $('#import-screen').addClass('hidden');
        $('#wizard-screen').removeClass('hidden');
        $('#btn-back-to-welcome').removeClass('hidden');
        currentStep = 1;
        showStep(currentStep);
    }

    // ================================================================
    // WIZARD STEP NAVIGATION
    // ================================================================
    function showStep(step) {
        currentStep = step;
        $('.wizard-step').removeClass('active');
        $('#step-' + step).addClass('active');
        updateProgressBar();
        updateNavButtons();

        if (step === 4) loadAccountingStatus();
        if (step === 5) loadSummary();
    }

    function updateProgressBar() {
        for (var i = 1; i <= totalSteps; i++) {
            var $ind = $('#step-ind-' + i);
            $ind.removeClass('completed current pending');
            if (i < currentStep || completedSteps[i]) {
                $ind.addClass('completed');
                $ind.html('<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>');
            } else if (i === currentStep) {
                $ind.addClass('current');
                $ind.text(i);
            } else {
                $ind.addClass('pending');
                $ind.text(i);
            }
            if (i > 1) {
                var $conn = $('#connector-' + i);
                $conn.removeClass('completed pending');
                $conn.addClass(i <= currentStep ? 'completed' : 'pending');
            }
        }
    }

    function updateNavButtons() {
        if (currentStep === 1) {
            $('#btn-prev').addClass('hidden');
        } else {
            $('#btn-prev').removeClass('hidden');
        }
        if (currentStep === totalSteps) {
            $('#btn-next').addClass('hidden');
        } else {
            $('#btn-next').removeClass('hidden');
        }
        // Hide nav on final step
        if (currentStep === totalSteps) {
            $('#wizard-nav').addClass('hidden');
        } else {
            $('#wizard-nav').removeClass('hidden');
        }
    }

    $(document).on('click', '#btn-next', function() {
        if (currentStep < totalSteps) {
            completedSteps[currentStep] = true;
            showStep(currentStep + 1);
        }
    });

    $(document).on('click', '.btn-wizard-next', function() {
        if (currentStep < totalSteps) {
            completedSteps[currentStep] = true;
            showStep(currentStep + 1);
        }
    });

    $(document).on('click', '#btn-prev', function() {
        if (currentStep > 1) {
            showStep(currentStep - 1);
        }
    });

    // Clickable step indicators (only completed ones)
    $(document).on('click', '.step-indicator.completed', function() {
        var step = parseInt($(this).data('step'));
        if (step && step >= 1 && step <= totalSteps) {
            showStep(step);
        }
    });

    // ================================================================
    // STEP 1: SAVE COMPANY
    // ================================================================
    $(document).on('click', '#btn-save-company', function() {
        var name = $('#company-name').val().trim();
        if (!name) {
            showToast('El nombre de la empresa es obligatorio', 'error');
            return;
        }

        var $btn = $(this);
        $btn.prop('disabled', true);
        $('#company-spinner').removeClass('hidden');

        $.ajax({
            url: base_url + 'sisvent/admin/setup/save_company',
            type: 'POST',
            dataType: 'json',
            data: {
                name: name,
                nit: $('#company-nit').val().trim(),
                address: $('#company-address').val().trim(),
                phone: $('#company-phone').val().trim(),
                email: $('#company-email').val().trim(),
                invoice_account: $('#company-invoice-account').val().trim(),
                invoice_support: $('#company-invoice-support').val().trim()
            },
            success: function(resp) {
                if (resp.success) {
                    showToast(resp.message, 'success');
                    completedSteps[1] = true;
                    updateProgressBar();
                } else {
                    showToast(resp.message, 'error');
                }
            },
            error: function() {
                showToast('Error de conexion al servidor', 'error');
            },
            complete: function() {
                $btn.prop('disabled', false);
                $('#company-spinner').addClass('hidden');
            }
        });
    });

    // ================================================================
    // STEP 2: STORES
    // ================================================================
    $(document).on('click', '#btn-add-store', function() {
        var name = $('#new-store-name').val().trim();
        if (!name) {
            showToast('El nombre de la bodega es obligatorio', 'error');
            return;
        }

        var $btn = $(this);
        $btn.prop('disabled', true);

        $.ajax({
            url: base_url + 'sisvent/admin/setup/save_store',
            type: 'POST',
            dataType: 'json',
            data: { name: name },
            success: function(resp) {
                if (resp.success) {
                    showToast(resp.message, 'success');
                    var store = resp.store;
                    var html = '<div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg store-item" data-id="' + store.idStore + '">'
                        + '<div class="flex items-center gap-2">'
                        + '<svg class="w-5 h-5" style="color: #2E7D91;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>'
                        + '<span class="text-sm font-medium text-gray-700">' + $('<span>').text(store.name).html() + '</span>'
                        + '</div>'
                        + '<button type="button" class="btn-delete-store text-red-500 hover:text-red-700 text-sm" data-id="' + store.idStore + '">'
                        + '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>'
                        + '</button>'
                        + '</div>';
                    $('#stores-list').append(html);
                    $('#new-store-name').val('');

                    // Also update the user store dropdown
                    $('#new-user-store').append('<option value="' + store.idStore + '">' + $('<span>').text(store.name).html() + '</option>');
                } else {
                    showToast(resp.message, 'error');
                }
            },
            error: function() {
                showToast('Error de conexion al servidor', 'error');
            },
            complete: function() {
                $btn.prop('disabled', false);
            }
        });
    });

    $(document).on('click', '.btn-delete-store', function() {
        var $item = $(this).closest('.store-item');
        var id = $(this).data('id');

        if (!confirm('¿Esta seguro que desea eliminar esta bodega?')) return;

        $.ajax({
            url: base_url + 'sisvent/admin/setup/delete_store',
            type: 'POST',
            dataType: 'json',
            data: { id: id },
            success: function(resp) {
                if (resp.success) {
                    showToast(resp.message, 'success');
                    $item.fadeOut(300, function() { $(this).remove(); });
                    // Remove from user store dropdown
                    $('#new-user-store option[value="' + id + '"]').remove();
                } else {
                    showToast(resp.message, 'error');
                }
            },
            error: function() {
                showToast('Error de conexion al servidor', 'error');
            }
        });
    });

    // Allow adding store with Enter key
    $(document).on('keypress', '#new-store-name', function(e) {
        if (e.which === 13) {
            $('#btn-add-store').click();
        }
    });

    // ================================================================
    // STEP 3: USERS
    // ================================================================
    $(document).on('click', '#btn-add-user', function() {
        var idUser   = $('#new-user-id').val().trim();
        var name     = $('#new-user-name').val().trim();
        var password = $('#new-user-password').val();
        var role     = $('#new-user-role').val();
        var store    = $('#new-user-store').val();
        var email    = $('#new-user-email').val().trim();

        if (!idUser || !name || !password || !role) {
            showToast('Complete todos los campos obligatorios (ID, nombre, contrasena, rol)', 'error');
            return;
        }
        if (password.length < 6) {
            showToast('La contrasena debe tener al menos 6 caracteres', 'error');
            return;
        }

        var $btn = $(this);
        $btn.prop('disabled', true);

        $.ajax({
            url: base_url + 'sisvent/admin/setup/save_user',
            type: 'POST',
            dataType: 'json',
            data: {
                idUser: idUser,
                name: name,
                password: password,
                role: role,
                store: store,
                email: email
            },
            success: function(resp) {
                if (resp.success) {
                    showToast(resp.message, 'success');
                    var u = resp.user;
                    var initials = u.name.substring(0, 2).toUpperCase();
                    var html = '<div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg user-item" data-id="' + $('<span>').text(u.idUser).html() + '">'
                        + '<div class="flex items-center gap-3">'
                        + '<div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold" style="background-color: #1B365D;">' + initials + '</div>'
                        + '<div>'
                        + '<span class="text-sm font-medium text-gray-700 block">' + $('<span>').text(u.name).html() + '</span>'
                        + '<span class="text-xs text-gray-500">' + $('<span>').text(u.idUser).html() + ' &middot; ' + $('<span>').text(u.role_name).html() + '</span>'
                        + '</div></div>'
                        + '<button type="button" class="btn-delete-user text-red-500 hover:text-red-700 text-sm" data-id="' + $('<span>').text(u.idUser).html() + '">'
                        + '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>'
                        + '</button></div>';
                    $('#users-list').append(html);

                    // Clear form
                    $('#new-user-id').val('');
                    $('#new-user-name').val('');
                    $('#new-user-password').val('');
                    $('#new-user-email').val('');
                    $('#new-user-role').val('');
                } else {
                    showToast(resp.message, 'error');
                }
            },
            error: function() {
                showToast('Error de conexion al servidor', 'error');
            },
            complete: function() {
                $btn.prop('disabled', false);
            }
        });
    });

    $(document).on('click', '.btn-delete-user', function() {
        var $item = $(this).closest('.user-item');
        var id = $(this).data('id');

        if (!confirm('¿Esta seguro que desea eliminar este usuario?')) return;

        $.ajax({
            url: base_url + 'sisvent/admin/setup/delete_user',
            type: 'POST',
            dataType: 'json',
            data: { id: id },
            success: function(resp) {
                if (resp.success) {
                    showToast(resp.message, 'success');
                    $item.fadeOut(300, function() { $(this).remove(); });
                } else {
                    showToast(resp.message, 'error');
                }
            },
            error: function() {
                showToast('Error de conexion al servidor', 'error');
            }
        });
    });

    // ================================================================
    // STEP 4: ACCOUNTING STATUS
    // ================================================================
    function loadAccountingStatus() {
        $.ajax({
            url: base_url + 'sisvent/admin/setup/check_status',
            type: 'GET',
            dataType: 'json',
            success: function(resp) {
                if (resp.success) {
                    var s = resp.status;
                    if (s.accounting_total > 0) {
                        $('#accounting-status-text').html(s.accounting_configured + ' cuentas configuradas de ' + s.accounting_total + ' parametros disponibles');
                    } else {
                        $('#accounting-status-text').text('No hay parametros contables configurados aun. La tabla de configuracion contable no existe o esta vacia.');
                    }
                }
            }
        });
    }

    // ================================================================
    // STEP 5: SUMMARY
    // ================================================================
    function loadSummary() {
        $.ajax({
            url: base_url + 'sisvent/admin/setup/check_status',
            type: 'GET',
            dataType: 'json',
            success: function(resp) {
                if (resp.success) {
                    var s = resp.status;
                    var cards = '';

                    var items = [
                        { label: 'Empresa', value: s.company_configured ? s.company_name : 'Sin configurar', ok: s.company_configured, icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>' },
                        { label: 'Bodegas', value: s.stores, ok: s.stores > 0, icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/>' },
                        { label: 'Usuarios', value: s.users, ok: s.users > 0, icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>' },
                        { label: 'Productos', value: s.products, ok: s.products > 0, icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>' },
                        { label: 'Clientes', value: s.clients, ok: s.clients > 0, icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>' },
                        { label: 'Proveedores', value: s.providers, ok: s.providers > 0, icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>' }
                    ];

                    for (var i = 0; i < items.length; i++) {
                        var it = items[i];
                        var checkColor = it.ok ? '#8DC63F' : '#e2e8f0';
                        var checkIcon = it.ok
                            ? '<svg class="w-5 h-5" fill="none" stroke="' + checkColor + '" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'
                            : '<svg class="w-5 h-5" fill="none" stroke="' + checkColor + '" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';

                        cards += '<div class="p-4 bg-gray-50 rounded-lg">'
                            + '<div class="flex items-center justify-between mb-2">'
                            + '<svg class="w-6 h-6" style="color: #2E7D91;" fill="none" stroke="currentColor" viewBox="0 0 24 24">' + it.icon + '</svg>'
                            + checkIcon
                            + '</div>'
                            + '<p class="text-2xl font-bold text-gray-800">' + it.value + '</p>'
                            + '<p class="text-xs text-gray-500">' + it.label + '</p>'
                            + '</div>';
                    }

                    $('#summary-cards').html(cards);
                }
            }
        });
    }

    // ================================================================
    // PATH A: SQL IMPORT
    // ================================================================
    // Drag and drop for SQL
    $(document).on('dragover', '#sql-drop-zone', function(e) {
        e.preventDefault();
        $(this).addClass('dragover');
    });
    $(document).on('dragleave', '#sql-drop-zone', function(e) {
        e.preventDefault();
        $(this).removeClass('dragover');
    });
    $(document).on('drop', '#sql-drop-zone', function(e) {
        e.preventDefault();
        $(this).removeClass('dragover');
        var files = e.originalEvent.dataTransfer.files;
        if (files.length > 0) {
            document.getElementById('sql-file-input').files = files;
            $('#sql-file-name').text(files[0].name + ' (' + (files[0].size / 1024).toFixed(1) + ' KB)');
            $('#btn-import-sql').prop('disabled', false);
        }
    });

    $(document).on('change', '#sql-file-input', function() {
        if (this.files.length > 0) {
            $('#sql-file-name').text(this.files[0].name + ' (' + (this.files[0].size / 1024).toFixed(1) + ' KB)');
            $('#btn-import-sql').prop('disabled', false);
        }
    });

    $(document).on('click', '#btn-import-sql', function() {
        var fileInput = document.getElementById('sql-file-input');
        if (!fileInput.files.length) {
            showToast('Seleccione un archivo SQL', 'error');
            return;
        }

        var $btn = $(this);
        $btn.prop('disabled', true);
        $('#sql-spinner').removeClass('hidden');
        $('#sql-result').addClass('hidden');

        var formData = new FormData();
        formData.append('sql_file', fileInput.files[0]);

        $.ajax({
            url: base_url + 'sisvent/admin/setup/import_sql',
            type: 'POST',
            dataType: 'json',
            data: formData,
            processData: false,
            contentType: false,
            success: function(resp) {
                var $result = $('#sql-result');
                $result.removeClass('hidden');
                if (resp.success) {
                    $result.html('<span class="text-green-700">' + resp.message + '</span>');
                    showToast('Importacion SQL completada', 'success');
                    showImportSummary();
                } else {
                    $result.html('<span class="text-red-600">' + resp.message + '</span>');
                    showToast(resp.message, 'error');
                }
            },
            error: function() {
                showToast('Error de conexion al servidor', 'error');
            },
            complete: function() {
                $btn.prop('disabled', false);
                $('#sql-spinner').addClass('hidden');
            }
        });
    });

    // ================================================================
    // PATH A: EXCEL IMPORT
    // ================================================================
    $(document).on('change', '.excel-file-input', function() {
        var type = $(this).data('type');
        if (this.files.length > 0) {
            $('.btn-import-excel[data-type="' + type + '"]').prop('disabled', false);
        }
    });

    $(document).on('click', '.btn-import-excel', function() {
        var type = $(this).data('type');
        var fileInput = $('.excel-file-input[data-type="' + type + '"]')[0];
        if (!fileInput.files.length) {
            showToast('Seleccione un archivo Excel', 'error');
            return;
        }

        var $btn = $(this);
        $btn.prop('disabled', true).text('Importando...');

        var formData = new FormData();
        formData.append('excel_file', fileInput.files[0]);
        formData.append('type', type);

        $.ajax({
            url: base_url + 'sisvent/admin/setup/import_excel',
            type: 'POST',
            dataType: 'json',
            data: formData,
            processData: false,
            contentType: false,
            success: function(resp) {
                var $result = $('.excel-result[data-type="' + type + '"]');
                $result.removeClass('hidden');
                if (resp.success) {
                    $result.html('<span class="text-green-700">' + resp.message + '</span>');
                    showToast('Importacion de ' + type + ' completada', 'success');
                    showImportSummary();
                } else {
                    $result.html('<span class="text-red-600">' + resp.message + '</span>');
                    showToast(resp.message, 'error');
                }
            },
            error: function() {
                showToast('Error de conexion al servidor', 'error');
            },
            complete: function() {
                var labels = { products: 'Importar Productos', clients: 'Importar Clientes', providers: 'Importar Proveedores' };
                $btn.prop('disabled', false).text(labels[type]);
            }
        });
    });

    function showImportSummary() {
        $.ajax({
            url: base_url + 'sisvent/admin/setup/check_status',
            type: 'GET',
            dataType: 'json',
            success: function(resp) {
                if (resp.success) {
                    var s = resp.status;
                    var html = '<div class="grid grid-cols-2 md:grid-cols-4 gap-3">';
                    var items = [
                        { label: 'Bodegas', value: s.stores },
                        { label: 'Usuarios', value: s.users },
                        { label: 'Productos', value: s.products },
                        { label: 'Clientes', value: s.clients },
                        { label: 'Proveedores', value: s.providers }
                    ];
                    for (var i = 0; i < items.length; i++) {
                        html += '<div class="p-3 bg-gray-50 rounded text-center">'
                            + '<p class="text-xl font-bold" style="color: #2E7D91;">' + items[i].value + '</p>'
                            + '<p class="text-xs text-gray-500">' + items[i].label + '</p>'
                            + '</div>';
                    }
                    html += '</div>';
                    $('#import-summary-content').html(html);
                    $('#import-summary').removeClass('hidden');
                }
            }
        });
    }

})();
</script>

</body>
</html>
