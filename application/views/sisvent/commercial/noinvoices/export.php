<?php
defined('BASEPATH') OR exit('No direct script access allowed');

    //$permissions = $this->session->userdata('user_data')['permissions'];
    $role = $this->session->userdata('user_data')['role'];
    //$showAdmin = (!empty($permissions) && ($permissions['2']['read'] || $permissions['3']['read']));
?>
<!DOCTYPE html>
<html lang="en">
    <title>Usuarios</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<head>

</head>
  <body>
    <div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
    	<?php $this->load->view('sisvent/layouts/sidebar',array('thisFile' => $_ci_view,'role' => $role)); ?>

    	 <div class="flex flex-col flex-1 w-full">
    		<?php $this->load->view('sisvent/layouts/navbar'); ?>
    	 	<main class="h-full overflow-y-auto">
    	 		<div class="px-6 mx-auto grid">
                    <h2 class="mb-4 text-lg font-semibold text-gray-600 mt-2">
                        Crear Archivos de Facturas
                    </h2>
                    <div class="flex flex-col flex-wrap mb-8 space-y-4 md:flex-row md:items-end md:space-x-4">
                        <a href="<?php echo base_url();?>sisvent/commercial/noinvoices"  class="flex items-center justify-between px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-petroleo border border-transparent rounded-lg active:bg-mam-blue-petroleo hover:bg-mam-blue-petroleo focus:outline-none focus:shadow-outline-mam-blue-petroleo">
                          <span>Volver</span>
                        </a>
                        
                    </div>

                  <label class="block mt-4 text-sm">
                      <span class="text-gray-700">
                        Almacén
                      </span>
                      <select id="exportstore" name="store" class="form-input form-select">
                        <?php foreach($stores as $store):?>
                            <option value="<?php echo $store->idStore?>" <?php echo set_select("store",$store->idStore);?>><?php echo $store->name;?></option>
                        <?php endforeach;?>
                      </select>
                    </label>

                  <label class="block text-sm mt-4">
                    <span class="text-gray-700">Desde:</span>
                    <input id="exportfrom" class="form-input" type="number" name="from" value="" required />
                  </label>

                  <label class="block text-sm mt-4">
                    <span class="text-gray-700">Hasta:</span>
                    <input id="exportuntil" class="form-input" type="number" name="until" value="" />
                  </label>

                  <div class="block text-sm mt-4">
                    <button id="export-btn" class="mt-4 flex items-center justify-between px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-petroleo border border-transparent rounded-lg active:bg-mam-blue-petroleo hover:bg-mam-blue-petroleo focus:outline-none focus:shadow-outline-mam-blue-petroleo">
                      <span>Crear archivos Excel</span>
                      <!--svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg-->
                    </button>

                  </div>

                  <div id="export-btn-container" class="block text-sm mt-4">
                  </div>
    	 		    </div>
	        </main>
	      </div>
    </div>
    <?php $this->load->view('sisvent/layouts/footer'); ?>
  </body>
</html>