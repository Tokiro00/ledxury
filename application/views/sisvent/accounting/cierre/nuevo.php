<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$role = $this->session->userdata('user_data')['role'];
?>
<!DOCTYPE html>
<html lang="en">
    <title>Nuevo Cierre Contable</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<head>

</head>
  <body>
    <div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
    	<?php $this->load->view('sisvent/layouts/sidebar',array('thisFile' => 'sisvent/accounting/cierre/list.php','role' => $role)); ?>

    	 <div class="flex flex-col flex-1 w-full">
    		<?php $this->load->view('sisvent/layouts/navbar'); ?>
    	 	<main class="h-full overflow-y-auto pb-8">
    	 		<div class="px-6 mx-auto grid max-w-2xl">
            <!-- Header -->
            <div class="mt-4 mb-6">
                <a href="<?php echo base_url(); ?>sisvent/accounting/cierre" class="text-blue-600 hover:text-blue-800 text-sm flex items-center mb-2">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                    Volver a Períodos
                </a>
                <h2 class="text-2xl font-bold text-gray-800">Nuevo Cierre Contable</h2>
                <p class="text-gray-600 mt-1">Seleccione el período que desea cerrar</p>
            </div>

            <!-- Flash Messages -->
            <?php if($this->session->flashdata('error')): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo $this->session->flashdata('error'); ?>
            </div>
            <?php endif; ?>

            <!-- Form -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <form method="get" action="<?php echo base_url(); ?>sisvent/accounting/cierre/preview">
                    <div class="space-y-6">
                        <!-- Tipo de cierre -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Cierre</label>
                            <div class="flex gap-4">
                                <label class="flex items-center">
                                    <input type="radio" name="type" value="monthly" checked class="h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                                    <span class="ml-2 text-gray-700">Mensual</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" name="type" value="yearly" class="h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                                    <span class="ml-2 text-gray-700">Anual</span>
                                </label>
                            </div>
                        </div>

                        <!-- Año -->
                        <div>
                            <label for="year" class="block text-sm font-medium text-gray-700 mb-1">Año</label>
                            <select name="year" id="year" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <?php for($y = $currentYear; $y >= $currentYear - 5; $y--): ?>
                                <option value="<?php echo $y; ?>" <?php echo ($y == $currentYear) ? 'selected' : ''; ?>><?php echo $y; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <!-- Mes -->
                        <div id="monthContainer">
                            <label for="month" class="block text-sm font-medium text-gray-700 mb-1">Mes</label>
                            <select name="month" id="month" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <?php
                                $months = array(
                                    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo',
                                    4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
                                    7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre',
                                    10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
                                );
                                $prevMonth = $currentMonth - 1;
                                if ($prevMonth < 1) $prevMonth = 12;
                                foreach($months as $num => $name): ?>
                                <option value="<?php echo $num; ?>" <?php echo ($num == $prevMonth) ? 'selected' : ''; ?>><?php echo $name; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Bodega -->
                        <div>
                            <label for="store" class="block text-sm font-medium text-gray-700 mb-1">Bodega (opcional)</label>
                            <select name="store" id="store" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Todas las bodegas (consolidado)</option>
                                <?php foreach($stores as $store): ?>
                                <option value="<?php echo $store->idStore; ?>"><?php echo $store->name; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="mt-1 text-xs text-gray-500">Deje en blanco para un cierre consolidado de todas las bodegas.</p>
                        </div>

                        <!-- Warning -->
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                            <div class="flex">
                                <svg class="w-5 h-5 text-yellow-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                </svg>
                                <div>
                                    <h4 class="text-sm font-medium text-yellow-800">Importante</h4>
                                    <p class="text-sm text-yellow-700 mt-1">
                                        El cierre contable transferirá la utilidad o pérdida del período al patrimonio.
                                        Asegúrese de que todos los asientos del período estén registrados antes de proceder.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Submit -->
                        <div class="flex justify-end gap-3">
                            <a href="<?php echo base_url(); ?>sisvent/accounting/cierre"
                               class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">
                                Cancelar
                            </a>
                            <button type="submit"
                                    class="px-6 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                Ver Vista Previa
                            </button>
                        </div>
                    </div>
                </form>
            </div>
    	 		</div>
        </main>
      </div>
    </div>
    <?php $this->load->view('sisvent/layouts/footer'); ?>
    <script>
        // Show/hide month selector based on period type
        document.querySelectorAll('input[name="type"]').forEach(function(radio) {
            radio.addEventListener('change', function() {
                var monthContainer = document.getElementById('monthContainer');
                if (this.value === 'yearly') {
                    monthContainer.style.display = 'none';
                    document.getElementById('month').value = '12';
                } else {
                    monthContainer.style.display = 'block';
                }
            });
        });
    </script>
  </body>
</html>
