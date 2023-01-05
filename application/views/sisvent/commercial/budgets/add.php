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
<script>
  window.inBudgets = true;
  window.isadusr = <?php echo $role == 1; ?>;
</script>
</head>
  <body>
    <div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
    	<?php $this->load->view('sisvent/layouts/sidebar',array('thisFile' => $_ci_view,'role' => $role)); ?>

    	 <div class="flex flex-col flex-1 w-full">
    		<?php $this->load->view('sisvent/layouts/navbar'); ?>
    	 	<main class="h-full">
    	 		<div class="px-6 mx-auto grid">
                    <h2 class="mb-4 text-lg font-semibold text-gray-600 mt-2">
                        Nuevo Presupuesto
                    </h2>

                    <div class="flex flex-col flex-wrap mb-8 space-y-4 md:flex-row md:items-end md:space-x-4">
                        <?php //if(in_array($role, [1])): ?>
                            <a href="<?php echo base_url();?>sisvent/commercial/budgets"  class="flex items-center justify-between px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg active:bg-mam-blue-dark hover:bg-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark">
                              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                              <span>Volver</span>
                            </a>

                            <a id="reload-budget" onclick="window.loadBudget()" style="display: none; cursor: pointer;"  class="flex items-center justify-between px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg active:bg-mam-blue-dark hover:bg-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark">
                              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                              <span>Recargar Info</span>
                            </a>
                            <a id="clear-budget" onclick="window.clearBudget()" style="display: none; cursor: pointer;" class="flex items-center justify-between px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg active:bg-mam-blue-dark hover:bg-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark"><svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                              <span>Borrar Info</span>
                            </a>
                        <?php //endif; ?>
                    </div>

                    <div class="flex flex-col flex-wrap mb-8 space-y-4 md:flex-row md:items-end md:space-x-4 text-center">
                        <!--h3 class="mx-auto text-gray-700"><span class="font-bold">Advertencia: </span>Si cambia el <span class="font-bold text-gray-600">Almacén</span>, los productos que haya seleccionado se eliminarán</h3-->
                    </div>
                    
                    <form id="new-budget-form" action="<?php echo base_url();?>sisvent/commercial/budgets/store" method="POST">
                      <?php if($this->session->flashdata("error")):?>
                          <div class="flex items-center p-4 mb-8 text-sm font-semibold text-white bg-red-600 rounded-lg shadow-md">
                              <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                              <p><?php echo $this->session->flashdata("error"); ?></p>
                           </div>
                      <?php endif;?>
                      <div class="px-4 py-3 mb-8 bg-white rounded-lg shadow-md">
                        

                        <div class="grid grid-cols-12 gap-4">
                          <div class="flex-1 mt-4 text-sm col-span-12 sm:col-span-6">
                            <span class="text-gray-700">
                              Cliente
                            </span>
                            <input class="form-input" type="text" id="budget-client"/>
                            <input class="form-input" name="client" id="budget-client-id" type="hidden" readonly/>
                            <!--select id="budget-client" name="client" class="form-input form-select">
                              <?php foreach($clients as $key => $client): ?>
                                <option value="<?php echo $client->idClient?>" ><?php echo $client->name;?></option>
                              <?php endforeach;?>
                            </select-->
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
                            Vendedor
                          </span>
                          <select id="budget-vendor" name="vendor" class="form-input form-select" required>
                            <?php foreach($vendors as $vendor): ?>
                                <option value="<?php echo $vendor->idUser?>" <?php echo $this->session->userdata('user_data')['uname'] == $vendor->idUser ? 'selected' : '' ?>><?php echo $vendor->name;?></option>
                            <?php endforeach;?>
                          </select>
                        </label>

                        <label class="block mt-4 text-sm">
                          <span class="text-gray-700">
                            Almacén
                          </span>
                          <select id="budget-store" name="store" class="form-input form-select"><!---->
                            <?php foreach($stores as $store):?>
                                <option value="<?php echo $store->idStore?>" <?php echo set_select("store",$store->idStore);?>><?php echo $store->name;?></option>
                            <?php endforeach;?>
                          </select>
                        </label>

                        <label class="block mt-4 text-sm">
                          <span class="text-gray-700">
                            IVA
                          </span>
                          <select id="hasiva-field" name="hasIva" class="form-input form-select" required>
                            <option selected disabled>Selecciona Opción</option>
                            <option value="0">Remisión</option>
                            <option value="1">IVA</option>
                          </select>
                        </label>

                         <?php if(in_array($role, [1])): ?>
                        <label class="flex items-center mt-4 dark:text-gray-400">
                          <input id="e_commerce" type="checkbox" name="e_commerce" class="text-mam-blue-dark form-checkbox focus:border-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark"/>
                          <span class="ml-2">Venta por E-commerce</span>
                        </label>

                        <label class="flex items-center mt-4 dark:text-gray-400">
                          <input id="list_price" type="checkbox" name="list_price" class="text-mam-blue-dark form-checkbox focus:border-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark"/>
                          <span class="ml-2">Precio de lista</span>
                        </label>
                        <?php endif; ?>

                        <label class="block text-sm mt-4">
                          <span class="text-gray-700">Observaciones</span>
                          <textarea id="invoice-payment-comment" class="form-input" name="comments"><?php echo set_value('comments'); ?></textarea>
                        </label>
                        
                        <label class="flex flex-row text-xl mt-4">
                          <span class="form-input nb font-bold w-16">Total $</span>
                          <input id="budget-total-val" class="form-input nb font-bold" type="hidden" name="total" value="<?php echo set_value('total');?>" readonly/>
                          <input id="budget-total" class="form-input nb font-bold" type="text" value="<?php echo set_value('total');?>" disabled/>
                        </label>

                        <label class="flex flex-row text-xl mt-4">
                          <span class="form-input nb font-bold w-36">Total Productos:</span>
                          <input id="budget-total-products" class="form-input nb font-bold" type="text" value="0" disabled/>
                        </label>

                        <label class="block my-4 text-sm">
                          <span class="text-gray-700">Producto</span>
                          <div class="relative text-gray-500 focus-within:text-purple-600">
                            <input class="form-input-lg inline w-1/2" type="text" id="budgets-product"/>
                            <input id="budget-quantities-ele" class='form-input-lg inline' type='number' placeholder="Cantidad" min='1' value='1'>
                            <input id="budget-price-ele" class='form-input-lg inline' type='number' placeholder="Precio" min='1' value=''>
                            <button id="btn-agregar-budget" class="form-input-lg inline flex items-center justify-between inset-y-0 px-4 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg focus:outline-none" type="button" value="" onclick=""/>
                              <svg class="w-6 h-6 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                              <span class="inline pr-4">Agregar</span>
                            </button>
                          </div>
                        </label>

                        <div class="w-full overflow-hidden rounded-lg shadow-xs">
                          <div class="w-full overflow-x-auto">
                            <table class="stripped-table w-full whitespace-no-wrap mt-8 lg:mt-0">
                              <thead>
                                <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                                  <th class="px-4 py-3 hidden lg:table-cell">#</th>
                                  <th class="px-4 py-3 hidden lg:table-cell">Código</th>
                                  <th class="px-4 py-3 hidden lg:table-cell">Descripción</th>
                                  <th class="px-4 py-3 hidden lg:table-cell">Stock</th>
                                  <th class="px-4 py-3 hidden lg:table-cell">Cantidad</th>
                                  <th class="px-4 py-3 hidden lg:table-cell">Precio</th>
                                  <th class="px-4 py-3 hidden lg:table-cell">Subtotal</th>
                                  <th class="px-4 py-3 hidden lg:table-cell">Acciones</th>
                                </tr>
                              </thead>
                              <tbody id="tborders" class="bg-white divide-y">
                                
                                    <tr class='text-gray-700 flex sm:table-row flex-row sm:flex-row flex-wrap sm:flex-no-wrap mb-10 sm:mb-0'>
                                    <td class='px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static text-xs'><span class="sm:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">#</span>1</td>
                                    <td class='px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static'><span class="sm:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Código</span><input type='hidden' name='refs[]' value='<?php echo $default->idProduct; ?>'><?php echo $default->idProduct; ?><input class='price' type='hidden' name='price[]' value='<?php echo $default->price; ?>' readonly><input class='price_base' type='hidden' name='price_base[]' value='<?php echo $default->price_base; ?>' readonly><input class='price_scale' type='hidden' name='price_scale[]' value='<?php echo $default->price_scale; ?>' readonly><input class='price_dist' type='hidden' name='price_dist[]' value='<?php echo $default->price_dist; ?>' readonly></td>
                                    <td class='px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static text-xs'><span class="sm:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Descripción</span><?php echo $default->description; ?></td>
                                    <td class='px-4 py-3 w-full lg:w-auto block lg:table-cell relative lg:static'><span class='lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold'>Stock</span><input class='stock w-full' type='text' name='stock[]' value='<?php echo $default->stock ?? 0; ?>' readonly></td>
                                    <td class='px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static'><span class="sm:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Cantidad</span><input class='form-input budget-quantities' type='number' min='1' name='budget-quantities[]' value='1'></td>
                                    <td class='px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static'><span class="sm:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Precio</span><input class='form-input budget-rates' type='number' min='1' name='budget-rates[]' value='<?php echo $default->price; ?>'></td>
                                    <td class='px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static'><span class="sm:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Subtotal</span><input class='form-input budget-subtotal' type='text' name='budget-subtotal[]' value='<?php echo $default->price; ?>' readonly></td>
                                    <td class='px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static'><span class="sm:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Acciones</span><button type='button' class='button-main btn-base-price-product'><svg class='w-6 h-6' fill='none' stroke='currentColor' viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'></path></svg></button><button type='button' class='button-main btn-remove-budget-product'><svg class='w-6 h-6' fill='none' stroke='currentColor' viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M6 18L18 6M6 6l12 12'></path></svg></button></td>
                                    </tr>
                                
                              </tbody>
                            </table>
                          </div>
                        </div>

                        <div class="block text-sm mt-4">
                            <input id="create-budget" type="submit" class="px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg active:bg-mam-blue-dark hover:bg-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark disabled:opacity-50" value="Guardar">
                            <button id="btn-unblock-budget" type='button' class='button-main' style="display: none;"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M10 2a5 5 0 00-5 5v2a2 2 0 00-2 2v5a2 2 0 002 2h10a2 2 0 002-2v-5a2 2 0 00-2-2H7V7a3 3 0 015.905-.75 1 1 0 001.937-.5A5.002 5.002 0 0010 2z" /></svg></button>
                        </div>
                      </div>
                    </form>

    	 		    </div>
	        </main>
	      </div>
    </div>
    <?php $this->load->view('sisvent/layouts/footer'); ?>
  </body>
</html>