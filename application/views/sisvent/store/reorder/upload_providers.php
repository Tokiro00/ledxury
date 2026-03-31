<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$role = $this->session->userdata('user_data')['role'];
?>
<!DOCTYPE html>
<html lang="en">
    <title>Carga Masiva de Proveedores</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<head></head>
<body>
    <div class="flex h-screen bg-gray-50">
        <?php $thisFile = 'sisvent/store/reorder/upload_providers'; ?>
        <?php $this->load->view('sisvent/layouts/sidebar'); ?>
        <div class="flex flex-col flex-1 w-full">
            <?php $this->load->view('sisvent/layouts/navbar'); ?>
            <main class="h-full overflow-y-auto" style="position:relative; z-index:1;">
                <div class="container px-6 mx-auto grid py-6">

                    <div class="flex justify-between items-center mb-4">
                        <div>
                            <h2 class="text-xl font-bold text-gray-800">Carga Masiva de Proveedores</h2>
                            <p class="text-sm text-gray-500">Actualiza el proveedor de cada producto mediante un archivo Excel</p>
                        </div>
                        <a href="<?= base_url() ?>sisvent/store/reorder" class="text-sm" style="color:#2E7D91;">← Clasificacion ABC</a>
                    </div>

                    <?php if (!empty($error)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        <?= $error ?>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($result)): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                        <p class="font-bold"><?= $result['message'] ?></p>
                        <?php if (!empty($result['errors'])): ?>
                        <ul class="mt-2 text-sm list-disc pl-5">
                            <?php foreach ($result['errors'] as $err): ?>
                            <li><?= $err ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Instructions -->
                    <div class="bg-white rounded-lg shadow-sm border p-6 mb-4">
                        <h3 class="text-sm font-bold text-gray-800 mb-3">Instrucciones</h3>
                        <ol class="text-sm text-gray-600 space-y-2 list-decimal pl-5">
                            <li>Descarga la <strong>plantilla Excel</strong> — incluye una hoja con la lista de proveedores para referencia</li>
                            <li>Llena la columna <strong>Codigo</strong> con el codigo del producto (ej: FW-12V)</li>
                            <li>Llena la columna <strong>Proveedor</strong> con el <strong>ID</strong> o el <strong>nombre exacto</strong> del proveedor</li>
                            <li>Sube el archivo y haz clic en <strong>Procesar</strong></li>
                        </ol>
                        <div class="mt-4">
                            <a href="<?= base_url() ?>sisvent/store/reorder/downloadTemplate" class="inline-flex items-center px-4 py-2 text-sm font-bold text-white bg-green-600 rounded-lg hover:bg-green-700">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                Descargar Plantilla
                            </a>
                        </div>
                    </div>

                    <!-- Upload Form -->
                    <div class="bg-white rounded-lg shadow-sm border p-6">
                        <h3 class="text-sm font-bold text-gray-800 mb-3">Subir archivo</h3>
                        <form action="<?= base_url() ?>sisvent/store/reorder/processProviders" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                            <div class="flex items-center gap-4">
                                <input type="file" name="file" accept=".xlsx,.xls,.csv" class="text-sm border border-gray-300 rounded-lg px-3 py-2" required>
                                <button type="submit" class="px-6 py-2 text-sm font-bold text-white rounded-lg" style="background:#2E7D91;">
                                    Procesar
                                </button>
                            </div>
                            <p class="text-xs text-gray-400 mt-2">Formatos aceptados: .xlsx, .xls, .csv</p>
                        </form>
                    </div>

                </div>
            </main>
        </div>
    </div>
    <?php $this->load->view('sisvent/layouts/footer'); ?>
</body>
</html>
