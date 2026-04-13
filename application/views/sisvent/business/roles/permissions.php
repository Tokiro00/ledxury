<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$userRole = $this->session->userdata('user_data')['role'];
?>
<!DOCTYPE html>
<html lang="en">
    <title>Permisos - <?php echo htmlspecialchars($role->name);?></title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<head></head>
  <body>
    <div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
    	<?php $this->load->view('sisvent/layouts/sidebar',array('thisFile' => $_ci_view,'role' => $userRole)); ?>

    	 <div class="flex flex-col flex-1 w-full">
    		<?php $this->load->view('sisvent/layouts/navbar'); ?>
    	 	<main class="h-full">
    	 		<div class="px-4 py-4 w-full">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-600 mt-2">
                            Permisos del Rol: <span class="font-bold" style="color:#1B365D"><?php echo htmlspecialchars($role->name);?></span>
                        </h2>
                        <a href="<?php echo base_url();?>sisvent/business/roles" class="px-4 py-2 text-sm font-medium leading-5 text-gray-700 transition-colors duration-150 border border-gray-300 rounded-lg hover:bg-gray-100 focus:outline-none">
                            Volver a Roles
                        </a>
                    </div>

                    <?php if(!empty($role->description)):?>
                    <p class="text-sm text-gray-500 mb-4"><?php echo htmlspecialchars($role->description);?></p>
                    <?php endif;?>

                    <form action="<?php echo base_url();?>sisvent/business/roles/permissions/<?php echo $role->idRoles;?>" method="POST">

                      <!-- Botones seleccionar/deseleccionar -->
                      <div class="flex items-center space-x-4 mb-6">
                          <button type="button" id="btn-select-all" class="px-3 py-1 text-sm font-medium text-white rounded-lg focus:outline-none" style="background:#2E7D91">
                              Seleccionar Todos
                          </button>
                          <button type="button" id="btn-deselect-all" class="px-3 py-1 text-sm font-medium text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-100 focus:outline-none">
                              Deseleccionar Todos
                          </button>
                          <span id="permission-counter" class="text-sm text-gray-500"></span>
                      </div>

                      <!-- Grid de permisos por seccion -->
                      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 mb-6">
                        <?php foreach($allModuleKeys as $section => $modules): ?>
                        <div class="bg-white rounded-lg shadow-md overflow-hidden">
                            <div class="px-4 py-3 font-semibold text-white text-sm" style="background:#1B365D">
                                <?php echo $section;?>
                            </div>
                            <div class="px-4 py-3 space-y-3">
                                <?php foreach($modules as $key => $label): ?>
                                <label class="flex items-start space-x-3 cursor-pointer">
                                    <input type="checkbox"
                                           name="permissions[]"
                                           value="<?php echo $key;?>"
                                           class="permission-checkbox mt-1 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                           <?php echo in_array($key, $currentPermissions) ? 'checked' : '';?>
                                    />
                                    <div>
                                        <span class="text-sm font-medium text-gray-700"><?php echo $label;?></span>
                                        <span class="block text-xs text-gray-400"><?php echo $key;?></span>
                                    </div>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                      </div>

                      <div class="flex items-center space-x-4">
                          <button type="submit" class="px-6 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 border border-transparent rounded-lg focus:outline-none" style="background:#2E7D91">
                              Guardar Permisos
                          </button>
                          <a href="<?php echo base_url();?>sisvent/business/roles" class="px-4 py-2 text-sm font-medium leading-5 text-gray-700 transition-colors duration-150 border border-gray-300 rounded-lg hover:bg-gray-100 focus:outline-none">
                              Cancelar
                          </a>
                      </div>

                      <input type="hidden" name="csrf_token" value="<?php echo $this->session->userdata('csrf_token');?>" />
                    </form>
    	 		</div>
	        </main>
	      </div>
    </div>
    <?php $this->load->view('sisvent/layouts/footer'); ?>

    <script>
    (function(){
        function updateCounter(){
            var total = document.querySelectorAll('.permission-checkbox').length;
            var checked = document.querySelectorAll('.permission-checkbox:checked').length;
            var counter = document.getElementById('permission-counter');
            if(counter) counter.textContent = checked + ' de ' + total + ' seleccionados';
        }

        $(document).on('click', '#btn-select-all', function(){
            $('.permission-checkbox').prop('checked', true);
            updateCounter();
        });

        $(document).on('click', '#btn-deselect-all', function(){
            $('.permission-checkbox').prop('checked', false);
            updateCounter();
        });

        $(document).on('change', '.permission-checkbox', function(){
            updateCounter();
        });

        // Inicializar contador
        updateCounter();
    })();
    </script>
  </body>
</html>
