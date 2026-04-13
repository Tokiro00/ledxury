<?php
defined('BASEPATH') OR exit('No direct script access allowed');

    //$permissions = $this->session->userdata('user_data')['permissions'];
    $role = $this->session->userdata('user_data')['role'];
    //$showAdmin = (!empty($permissions) && ($permissions['2']['read'] || $permissions['3']['read']));
    $editable = "";
    $show = "";
    if($inventory->state == 3)
    {
      $editable = " readonly";
      $show = " hidden";
    }
?>
<!DOCTYPE html>
<html lang="en">
    <title>Inventario</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<head>

</head>
  <body>
    <div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
    	<?php $this->load->view('sisvent/layouts/sidebar',array('thisFile' => $_ci_view,'role' => $role)); ?>

    	 <div class="flex flex-col flex-1 w-full">
    		<?php $this->load->view('sisvent/layouts/navbar'); ?>
    	 	<main class="h-full overflow-y-auto">
    	 		<div  id="inventory-print" class="px-6 mx-auto grid">
                    <h2 class="mb-4 text-lg font-semibold text-gray-600 mt-2">
                        Conteo 1
                    </h2>
                    <div class="flex flex-col flex-wrap mb-8 space-y-4 md:flex-row md:items-end md:space-x-4">
                        <a href="<?php echo base_url();?>sisvent/store/inventory"  class="flex items-center justify-between px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-petroleo border border-transparent rounded-lg active:bg-mam-blue-petroleo hover:bg-mam-blue-petroleo focus:outline-none focus:shadow-outline-mam-blue-petroleo">
                          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                          <span>Volver</span>
                        </a>
                    </div>
                    
                    <form id="new-inventory-form" action="<?php echo base_url();?>sisvent/store/inventory/storeCount1" method="POST">
                      <?php if($this->session->flashdata("error")):?>
                          <div class="flex items-center p-4 mb-8 text-sm font-semibold text-white bg-red-600 rounded-lg shadow-md">
                              <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                              <p><?php echo $this->session->flashdata("error"); ?></p>
                           </div>
                      <?php endif;?>
                      <div class="px-4 py-3 mb-8 bg-white rounded-lg shadow-md">
                        <input class="form-input" type="hidden" name="id" value="<?php echo $inventory->idInventory;?>" readonly/>
                        
                        <label class="block mt-4 text-sm">
                          <span class="text-gray-700">
                            Almacén
                          </span>
                          <input class="form-input" type="text" value="<?php echo $inventory->store_name;?>" readonly/>
                        </label>

                        <label class="block text-sm mt-4 <?php echo !empty(form_error('counted_count')) ? 'border-red-600':'';?>">
                          <span class="text-gray-700">Ingresó al sistema</span>
                          <input class="form-input" type="text" name="counted_count" value="<?php echo $inventory->counted_count_1;?>" required <?php echo $editable ?>/>
                          <?php echo form_error("counted_count","<span class='text-xs text-red-600'>","</span>");?>
                        </label>

                        <label class="block text-sm mt-4 <?php echo !empty(form_error('entry_count')) ? 'border-red-600':'';?>">
                          <span class="text-gray-700">Realizó conteo</span>
                          <input class="form-input" type="text" name="entry_count" value="<?php echo  $inventory->entry_count_1;?>" required <?php echo $editable ?>/>
                          <?php echo form_error("entry_count","<span class='text-xs text-red-600'>","</span>");?>
                        </label>

                        <div id="cont" class="block flex flex-row gap-5 my-4 text-sm print:hidden">
                          <button type="button" id="btn-all-inventory" class="flex items-center justify-between px-2 py-2 text-sm font-medium leading-5 bg-mam-blue-petroleo text-white rounded-lg focus:outline-none focus:shadow-outline-gray <?php echo $show ?>" aria-label="FillAll" onclick="">Agregar Todos</button>
                          <button type="button" onclick="printDiv('Conteo 1 Inventario <?= $inventory->idInventory; ?>','inventory-print')"  class="flex items-center justify-between px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-petroleo border border-transparent rounded-lg active:bg-mam-blue-petroleo hover:bg-mam-blue-petroleo focus:outline-none focus:shadow-outline-mam-blue-petroleo">
                            <span>Imprimir</span>
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                          </button>
                          <input type="submit" class="px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-petroleo border border-transparent rounded-lg active:bg-mam-blue-petroleo hover:bg-mam-blue-petroleo focus:outline-none focus:shadow-outline-mam-blue-petroleo <?php echo $show ?>" value="Guardar">
                        </div>

                        <label class="block mt-4 text-sm print:hidden <?php echo $show ?>">
                          <span class="text-gray-700">Producto</span>
                          <div class="relative text-gray-500 focus-within:text-purple-600">
                            <!--input class="form-input" type="text" id="producto"/-->
                            <input class="form-input-lg inline w-3/4" type="text" id="producto"/>
                            <input id="inv-quantities-ele" class='form-input-lg inline' type='number' placeholder="Cantidad" min='1' value='1'>
                            <button id="btn-agregar" class="flex items-center justify-between absolute inset-y-0 right-0 px-4 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-petroleo border border-transparent rounded-r-lg focus:outline-none" type="button" value="" onclick=""/>
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
                                  <th class="px-4 py-3">#</th>
                                  <th class="px-4 py-3">Código</th>
                                  <th class="px-4 py-3">Descripción</th>
                                  <th class="px-4 py-3">Cantidad</th>
                                  <th class="px-4 py-3">Acciones</th>
                                </tr>
                              </thead>
                              <tbody id="tborders" class="bg-white divide-y">
                                <?php foreach($details as $key => $detail):?>
                                    <tr class="text-gray-700">
                                      <td class='px-2 py-1 print:py-0 text-sm'><?php echo ($key + 1); ?></td>
                                      <td class="px-4 py-3 print:py-0 text-xs whitespace-normal"><input type='hidden' name='refs[]' value='<?php echo $detail->idProduct;?>'><?php echo $detail->idProduct;?></td>
                                      <td class="px-4 py-3 print:py-0 text-xs whitespace-normal"><?php echo $detail->description;?></td>
                                      <td class="px-4 py-3 print:py-0 text-sm"><input class='form-input quantities' type='number' name='quantities[]' min='0' value='<?php echo $detail->quantity;?>' <?php echo $editable ?>></td>
                                      <td class="px-4 py-3 print:py-0"><button type='button' class='button-main btn-remove-inv-product print:hidden <?php echo $show ?>'><p class='tooltip'><svg class='w-6 h-6' fill='none' stroke='currentColor' viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M6 18L18 6M6 6l12 12'></path></svg><span class='tooltip-text bg-blue-200 p-3 -mt-6 -ml-6 rounded text-mam-blue-petroleo'>Eliminar</span></p></button></td>
                                    </tr>
                                <?php endforeach;?>
                              </tbody>
                            </table>
                          </div>
                        </div>
                                           
                        <div class="block text-sm mt-4">
                            <input type="submit" class="px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-petroleo border border-transparent rounded-lg active:bg-mam-blue-petroleo hover:bg-mam-blue-petroleo focus:outline-none focus:shadow-outline-mam-blue-petroleo" value="Guardar">
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