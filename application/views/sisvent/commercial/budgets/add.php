<?php
defined('BASEPATH') OR exit('No direct script access allowed');

    //$permissions = $this->session->userdata('user_data')['permissions'];
    $role = $this->session->userdata('user_data')['role'];
    //$showAdmin = (!empty($permissions) && ($permissions['2']['read'] || $permissions['3']['read']));
?>
<!DOCTYPE html>
<html lang="en">
    <title>Presupuestos</title>
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
                        Nuevo Presupuesto
                    </h2>

                    <div class="flex flex-col flex-wrap mb-8 space-y-4 md:flex-row md:items-end md:space-x-4 text-center">
                        <h3 class="mx-auto text-gray-700"><span class="font-bold">Advertencia: </span>Si cambia el <span class="font-bold text-gray-600">Almacén</span>, los productos que haya seleccionado se eliminarán</h3>
                    </div>
                    
                    <form id="new-budget-form" action="<?php echo base_url();?>sisvent/commercial/budgets/store" method="POST">
                      <?php if($this->session->flashdata("error")):?>
                          <div class="flex items-center justify-between p-4 mb-8 text-sm font-semibold text-white bg-red-600 rounded-lg shadow-md">
                              <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                              <p><?php echo $this->session->flashdata("error"); ?></p>
                           </div>
                      <?php endif;?>
                      <div class="px-4 py-3 mb-8 bg-white rounded-lg shadow-md">
                        

                        <label class="block mt-4 text-sm">
                          <span class="text-gray-700">
                            Vendedor
                          </span>
                          <select id="budget-vendor" name="vendor" class="form-input form-select" <?php if($this->session->userdata('user_data')['role'] == 3): echo "disabled"; endif;?>>
                            <?php foreach($vendors as $vendor): ?>
                                <option value="<?php echo $vendor->idUser?>" <?php echo set_select("vendor",$vendor->idUser,$vendor->idUser==$this->session->userdata('user_data')['uname']);?>><?php echo $vendor->name;?></option>
                            <?php endforeach;?>
                          </select>
                        </label>

                        <div class="flex flex-row gap-4">
                          <div class="flex-1 mt-4 text-sm col-span-12 sm:col-span-6">
                            <span class="text-gray-700">
                              Cliente
                            </span>
                            <select id="budget-client" name="client" class="form-input form-select">
                              
                            </select>
                          </div>

                          <div class="flex-1 mt-4 text-sm col-span-12 sm:col-span-6">
                            <span class="text-gray-700">
                              Tarifa
                            </span>
                            <div class="flex flex-row gap-4">
                              <select id="budget-rate" name="rate" class="form-input form-select">
                                  <option value="1" <?php echo set_select("rate",1);?>>Precio</option>
                                  <option value="2" <?php echo set_select("rate",2);?>>Precio Base</option>
                                  <option value="3" <?php echo set_select("rate",3);?>>Precio Escala</option>
                                  <option value="4" <?php echo set_select("rate",4);?>>Precio Distribución</option>
                              </select>
                              <button id="change-price" class="flex items-center justify-between text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg focus:outline-none" type="button" value="Agregar" @click="changePrices()"/>
                                <span>Cambiar Tarifa</span>
                              </button>
                            </div>
                          </div>

                          <!--div class="flex-1 mt-4 text-sm col-span-12 sm:col-span-6">
                            <span class="text-gray-700">
                             
                            </span>
                            <button id="change-price" class="flex items-center justify-between text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-r-lg focus:outline-none" type="button" value="Agregar" onclick=""/>
                              <span>Cambiar Precio</span>
                            </button>
                          </div-->
                        </div>

                        <label class="block mt-4 text-sm">
                          <span class="text-gray-700">
                            Almacén
                          </span>
                          <select id="budget-store" name="store" class="form-input form-select">
                            <?php foreach($stores as $store):?>
                                <option value="<?php echo $store->idStore?>" <?php echo set_select("store",$store->idStore);?>><?php echo $store->name;?></option>
                            <?php endforeach;?>
                          </select>
                        </label>

                        <label class="flex items-center mt-4 dark:text-gray-400">
                          <input id="budget-tax" type="checkbox" name="hasIva" class="text-mam-blue-dark form-checkbox focus:border-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark"/>
                          <span class="ml-2">IVA</span>
                          <input id="budget-tax-value" class='form-input hidden ml-8 small w-16' type='number' min='1' max='100' name='iva' value='19'>
                        </label>

                        <label class="block mt-4 text-sm">
                          <span class="text-gray-700">Producto</span>
                          <div class="relative text-gray-500 focus-within:text-purple-600">
                            <input class="form-input" type="text" id="budgets-product"/>
                            <button id="btn-agregar-budget" class="flex items-center justify-between absolute inset-y-0 right-0 px-4 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-r-lg focus:outline-none" type="button" value="Agregar" onclick=""/>
                              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                              <span>Agregar</span>
                            </button>
                          </div>
                        </label>

                        <div class="w-full overflow-hidden rounded-lg shadow-xs">
                          <div class="w-full overflow-x-auto">
                            <table class="w-full whitespace-no-wrap">
                              <thead>
                                <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                                  <th class="px-4 py-3">Código</th>
                                  <th class="px-4 py-3">Descripción</th>
                                  <th class="px-4 py-3">Stock</th>
                                  <th class="px-4 py-3">Precio</th>
                                  <th class="px-4 py-3">Cantidad</th>
                                  <th class="px-4 py-3">Subtotal</th>
                                  <th class="px-4 py-3">Acciones</th>
                                </tr>
                              </thead>
                              <tbody id="tborders" class="bg-white divide-y">
                                
                              </tbody>
                            </table>
                          </div>
                        </div>

                        <div class="block text-sm mt-4">
                            <input type="submit" class="px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg active:bg-mam-blue-dark hover:bg-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark" value="Guardar">
                        </div>
                      </div>
                    </form>

    	 		    </div>
	        </main>
	      </div>
    </div>

          <!--Modal-->
    <div class="modal opacity-0 pointer-events-none fixed w-full h-full top-0 left-0 flex items-center justify-center">
      <div class="modal-overlay absolute w-full h-full bg-gray-900 opacity-50"></div>
      <div class="modal-container bg-white w-11/12 md:max-w-md mx-auto rounded shadow-lg z-50 overflow-y-auto" style="margin-top: -35%;">
        
        <div class="modal-close absolute top-0 right-0 cursor-pointer flex flex-col items-center mt-4 mr-4 text-white text-sm z-50">
          <svg class="fill-current text-white" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18">
            <path d="M14.53 4.53l-1.06-1.06L9 7.94 4.53 3.47 3.47 4.53 7.94 9l-4.47 4.47 1.06 1.06L9 10.06l4.47 4.47 1.06-1.06L10.06 9z"></path>
          </svg>
          <span class="text-sm">(Esc)</span>
        </div>

        <!-- Add margin if you want to see some of the overlay behind the modal-->
        <div class="modal-content py-4 text-left px-6">
          <!--Title-->
          <div class="flex justify-between items-center pb-3">
            <p class="text-2xl font-bold">Advertencia</p>
            <div class="modal-close cursor-pointer z-50">
              <svg class="fill-current text-black" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18">
                <path d="M14.53 4.53l-1.06-1.06L9 7.94 4.53 3.47 3.47 4.53 7.94 9l-4.47 4.47 1.06 1.06L9 10.06l4.47 4.47 1.06-1.06L10.06 9z"></path>
              </svg>
            </div>
          </div>

          <!--Body-->
          <div class="modal-body"></div>

          <!--Footer-->
          <div class="flex justify-end pt-2">
            <!--button class="px-4 bg-transparent p-3 rounded-lg text-indigo-500 hover:bg-gray-100 hover:text-indigo-400 mr-2">Action</button-->
            <button class="modal-close px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue border border-transparent rounded-lg active:bg-mam-blue hover:bg-mam-blue focus:outline-none focus:shadow-outline-mam-blue">Aceptar</button>
          </div>
          
        </div>
      </div>
    </div>
  </body>
</html>