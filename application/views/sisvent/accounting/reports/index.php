<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$role = $this->session->userdata('user_data')['role'];
?>
<!DOCTYPE html>
<html lang="en">
    <title>Reportes Contables</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<body>
    <div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
        <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => $_ci_view, 'role' => $role)); ?>

        <div class="flex flex-col flex-1 w-full">
            <?php $this->load->view('sisvent/layouts/navbar'); ?>
            <main class="h-full overflow-y-auto">
                <div class="px-6 mx-auto grid max-w-4xl">
                    <h2 class="my-6 text-2xl font-semibold text-gray-700">
                        Reportes Contables
                    </h2>

                    <div class="grid gap-6 mb-8 md:grid-cols-2">
                        <!-- Libro Mayor -->
                        <a href="<?php echo base_url(); ?>sisvent/accounting/mayor" class="flex items-center p-4 bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow">
                            <div class="p-3 mr-4 text-blue-500 bg-blue-100 rounded-full">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                            </div>
                            <div>
                                <p class="text-lg font-semibold text-gray-700">Libro Mayor</p>
                                <p class="text-sm text-gray-500">Movimientos por cuenta contable</p>
                            </div>
                        </a>

                        <!-- Balance de Comprobación -->
                        <a href="<?php echo base_url(); ?>sisvent/accounting/reports/comprobacion" class="flex items-center p-4 bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow">
                            <div class="p-3 mr-4 text-purple-500 bg-purple-100 rounded-full">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                            </div>
                            <div>
                                <p class="text-lg font-semibold text-gray-700">Balance de Comprobacion</p>
                                <p class="text-sm text-gray-500">Verificacion de debitos y creditos</p>
                            </div>
                        </a>

                        <!-- Balance General -->
                        <a href="<?php echo base_url(); ?>sisvent/accounting/reports/balance" class="flex items-center p-4 bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow">
                            <div class="p-3 mr-4 text-green-500 bg-green-100 rounded-full">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"></path></svg>
                            </div>
                            <div>
                                <p class="text-lg font-semibold text-gray-700">Balance General</p>
                                <p class="text-sm text-gray-500">Activos, Pasivos y Patrimonio</p>
                            </div>
                        </a>

                        <!-- Estado de Resultados -->
                        <a href="<?php echo base_url(); ?>sisvent/accounting/reports/resultados" class="flex items-center p-4 bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow">
                            <div class="p-3 mr-4 text-orange-500 bg-orange-100 rounded-full">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                            </div>
                            <div>
                                <p class="text-lg font-semibold text-gray-700">Estado de Resultados</p>
                                <p class="text-sm text-gray-500">Ingresos, Costos y Gastos</p>
                            </div>
                        </a>

                        <!-- Libro Diario -->
                        <a href="<?php echo base_url(); ?>sisvent/accounting/entries" class="flex items-center p-4 bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow">
                            <div class="p-3 mr-4 text-indigo-500 bg-indigo-100 rounded-full">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            </div>
                            <div>
                                <p class="text-lg font-semibold text-gray-700">Libro Diario</p>
                                <p class="text-sm text-gray-500">Asientos contables</p>
                            </div>
                        </a>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <?php $this->load->view('sisvent/layouts/footer'); ?>
</body>
</html>
