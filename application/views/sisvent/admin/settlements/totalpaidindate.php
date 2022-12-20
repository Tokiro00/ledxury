<?php
defined('BASEPATH') OR exit('No direct script access allowed');

    //$permissions = $this->session->userdata('user_data')['permissions'];
    $role = $this->session->userdata('user_data')['role'];
    //$showAdmin = (!empty($permissions) && ($permissions['2']['read'] || $permissions['3']['read']));
?>
<!DOCTYPE html>
<html lang="en">
    <title>Total Cobrado</title>
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
                        Total Cobrado
                    </h2>
                    
                  <label class="block text-sm mt-4">
                    <span class="text-gray-700">Desde:</span>
                    <input id="datepicker-since" class="form-input font-bold" type="text" name="since" required/>
                  </label>

                  <label class="block text-sm mt-4">
                    <span class="text-gray-700">Hasta:</span>
                    <input id="datepicker-until" class="form-input font-bold" type="text" name="until" required/>
                  </label>

                  <div class="block text-sm mt-4">
                  <button id="get-total-paid" class="px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg active:bg-mam-blue-dark hover:bg-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark disabled:opacity-50">Actualizar</button>
                  </div>

                  <div class="w-full overflow-hidden rounded-lg shadow-xs my-4">
                    <div class="w-full overflow-x-auto overflow-y-hidden">
                      <table class="w-full whitespace-no-wrap table2excel" data-tableName="Vales">
                        <thead>
                          <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                            <th class="px-4 py-3">Id</th>
                            <th class="px-4 py-3">Vendedor</th>
                            <th class="px-4 py-3">Valor</th>
                          </tr>
                        </thead>
                        <tbody id="total-paid-tb" class="bg-white divide-y">
                          
                        </tbody>
                      </table>
                    </div>
                  
                  </div>

    	 		    </div>

              
	        </main>
	      </div>
    </div>
  </body>
</html>