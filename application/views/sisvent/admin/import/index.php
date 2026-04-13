<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$role = $this->session->userdata('user_data')['role'];
?>
<!DOCTYPE html>
<html lang="en">
    <title>Importar Datos</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<body>
    <div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
        <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => $_ci_view, 'role' => $role)); ?>

        <div class="flex flex-col flex-1 w-full">
            <?php $this->load->view('sisvent/layouts/navbar'); ?>
            <main class="h-full overflow-y-auto">
                <div class="px-6 mx-auto grid">
                    <div class="flex items-center justify-between mb-4 mt-2">
                        <div>
                            <h2 class="text-xl font-semibold text-gray-700">Importar Datos</h2>
                            <p class="text-xs text-gray-400">Importe datos masivos desde archivos Excel o CSV</p>
                        </div>
                    </div>

                    <?php if(!empty($import_error)): ?>
                    <div class="p-3 mb-4 text-sm text-red-700 bg-red-100 rounded-lg"><?php echo $import_error; ?></div>
                    <?php endif; ?>
                    <?php if(!empty($import_success)): ?>
                    <div class="p-3 mb-4 text-sm text-green-700 bg-green-100 rounded-lg"><?php echo $import_success; ?></div>
                    <?php endif; ?>

                    <!-- Opciones de importacion -->
                    <div class="grid gap-6 mb-8 md:grid-cols-3">
                        <!-- Productos -->
                        <div class="min-w-0 p-6 bg-white rounded-lg shadow-xs">
                            <h4 class="mb-2 font-semibold text-gray-600">Productos</h4>
                            <p class="text-sm text-gray-400 mb-4">Importe productos desde un archivo Excel (.xlsx)</p>
                            <form action="<?php echo base_url(); ?>sisvent/admin/import/products" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
                                <input type="file" name="file" accept=".xlsx,.xls,.csv" class="block w-full text-sm text-gray-500 mb-3 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                <button type="submit" class="w-full px-4 py-2 text-sm font-medium text-white bg-mam-green rounded-lg hover:bg-mam-green-dark">Importar Productos</button>
                            </form>
                        </div>

                        <!-- Clientes -->
                        <div class="min-w-0 p-6 bg-white rounded-lg shadow-xs">
                            <h4 class="mb-2 font-semibold text-gray-600">Clientes</h4>
                            <p class="text-sm text-gray-400 mb-4">Importe clientes desde un archivo Excel (.xlsx)</p>
                            <form action="<?php echo base_url(); ?>sisvent/admin/import/clients" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
                                <input type="file" name="file" accept=".xlsx,.xls,.csv" class="block w-full text-sm text-gray-500 mb-3 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                <button type="submit" class="w-full px-4 py-2 text-sm font-medium text-white bg-mam-green rounded-lg hover:bg-mam-green-dark">Importar Clientes</button>
                            </form>
                        </div>

                        <!-- Proveedores -->
                        <div class="min-w-0 p-6 bg-white rounded-lg shadow-xs">
                            <h4 class="mb-2 font-semibold text-gray-600">Proveedores</h4>
                            <p class="text-sm text-gray-400 mb-4">Importe proveedores desde un archivo Excel (.xlsx)</p>
                            <form action="<?php echo base_url(); ?>sisvent/admin/import/providers" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
                                <input type="file" name="file" accept=".xlsx,.xls,.csv" class="block w-full text-sm text-gray-500 mb-3 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                <button type="submit" class="w-full px-4 py-2 text-sm font-medium text-white bg-mam-green rounded-lg hover:bg-mam-green-dark">Importar Proveedores</button>
                            </form>
                        </div>
                    </div>

                    <!-- Asignar Proveedor a Productos -->
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold text-gray-700 mb-3">Asignar Proveedor por Producto</h3>
                        <div class="grid gap-6 md:grid-cols-2">
                            <div class="min-w-0 p-6 bg-white rounded-lg shadow-xs">
                                <h4 class="mb-2 font-semibold text-gray-600">Subir archivo Excel</h4>
                                <p class="text-sm text-gray-400 mb-2">Excel con 2 columnas: <strong>Codigo</strong> del producto y <strong>Proveedor</strong> (nombre o ID)</p>
                                <p class="text-xs text-gray-400 mb-4">El archivo puede tener o no encabezado. Si la primera fila contiene un codigo de producto valido, se procesa como dato.</p>
                                <form action="<?php echo base_url(); ?>sisvent/admin/import/productProviders" method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
                                    <input type="file" name="file" accept=".xlsx,.xls,.csv" class="block w-full text-sm text-gray-500 mb-3 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-orange-50 file:text-orange-700 hover:file:bg-orange-100">
                                    <button type="submit" class="w-full px-4 py-2 text-sm font-medium text-white rounded-lg" style="background:#2E7D91;">Actualizar Proveedores</button>
                                </form>
                            </div>
                            <div class="min-w-0 p-6 bg-white rounded-lg shadow-xs">
                                <h4 class="mb-2 font-semibold text-gray-600">Formato del archivo</h4>
                                <table class="w-full text-sm text-left mt-2">
                                    <thead class="text-xs text-gray-500 uppercase bg-gray-50"><tr><th class="px-3 py-2">Columna A</th><th class="px-3 py-2">Columna B</th></tr></thead>
                                    <tbody>
                                        <tr class="border-t"><td class="px-3 py-2 font-mono">FW-12V</td><td class="px-3 py-2">3R</td></tr>
                                        <tr class="border-t"><td class="px-3 py-2 font-mono">6LED-12V-A</td><td class="px-3 py-2">IMPORTADOS</td></tr>
                                        <tr class="border-t"><td class="px-3 py-2 font-mono">T5-6SMD-W</td><td class="px-3 py-2">5</td></tr>
                                    </tbody>
                                </table>
                                <p class="text-xs text-gray-400 mt-3">Columna B acepta el <strong>nombre</strong> o el <strong>ID</strong> del proveedor. Se ignoran filas con proveedor vacio o "0".</p>
                                <a href="<?php echo base_url(); ?>sisvent/store/reorder/downloadTemplate" class="inline-flex items-center mt-3 text-sm text-blue-600 hover:underline">
                                    Descargar plantilla con lista de proveedores
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Fotos de Productos -->
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold text-gray-700 mb-3">Fotos de Productos</h3>
                        <div class="grid gap-6 md:grid-cols-2">
                            <div class="min-w-0 p-6 bg-white rounded-lg shadow-xs">
                                <h4 class="mb-2 font-semibold text-gray-600">Importar Fotos desde ZIP</h4>
                                <p class="text-sm text-gray-400 mb-2">Suba un archivo .zip con las fotos de productos. El nombre de cada imagen debe ser el codigo del producto.</p>
                                <p class="text-xs text-gray-400 mb-4">Ejemplo: <code class="bg-gray-100 px-1 rounded">H100A.jpg</code>, <code class="bg-gray-100 px-1 rounded">L5-H11.png</code>. Formatos: jpg, jpeg, png, gif, webp. Si la foto ya existe, se reemplaza.</p>
                                <form action="<?php echo base_url(); ?>sisvent/admin/import/photos" method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
                                    <input type="file" name="file" accept=".zip" class="block w-full text-sm text-gray-500 mb-3 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100">
                                    <button type="submit" class="w-full px-4 py-2 text-sm font-medium text-white rounded-lg" style="background:#2E7D91;">Importar Fotos</button>
                                </form>
                            </div>
                            <div class="min-w-0 p-6 bg-white rounded-lg shadow-xs">
                                <h4 class="mb-2 font-semibold text-gray-600">Instrucciones</h4>
                                <ul class="text-sm text-gray-500 space-y-2">
                                    <li class="flex items-start"><span class="text-green-500 mr-2 mt-0.5">&#10003;</span> El nombre del archivo = codigo del producto en el ERP</li>
                                    <li class="flex items-start"><span class="text-green-500 mr-2 mt-0.5">&#10003;</span> Si la foto ya existe, se reemplaza por la nueva</li>
                                    <li class="flex items-start"><span class="text-green-500 mr-2 mt-0.5">&#10003;</span> Solo se procesan imagenes (jpg, png, gif, webp)</li>
                                    <li class="flex items-start"><span class="text-green-500 mr-2 mt-0.5">&#10003;</span> Se ignoran carpetas internas del ZIP</li>
                                    <li class="flex items-start"><span class="text-yellow-500 mr-2 mt-0.5">&#9888;</span> Productos no encontrados se reportan pero no generan error</li>
                                    <li class="flex items-start"><span class="text-blue-500 mr-2 mt-0.5">&#8505;</span> Tamano maximo: 100MB</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <?php if(isset($results)): ?>
                    <div class="min-w-0 p-6 bg-white rounded-lg shadow-xs mb-8">
                        <h4 class="mb-2 font-semibold text-gray-600">Resultado de la importacion</h4>
                        <p class="text-sm text-gray-600">Registros procesados: <strong><?php echo $results['processed']; ?></strong></p>
                        <p class="text-sm text-green-600">Importados exitosamente: <strong><?php echo $results['success']; ?></strong></p>
                        <?php if($results['errors'] > 0): ?>
                        <p class="text-sm text-red-600">Errores: <strong><?php echo $results['errors']; ?></strong></p>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </main>
            <?php $this->load->view('sisvent/layouts/footer'); ?>
        </div>
    </div>
</body>
</html>
