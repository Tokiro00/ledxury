<?php
defined('BASEPATH') OR exit('No direct script access allowed');

    //$permissions = $this->session->userdata('user_data')['permissions'];
    $role = $this->session->userdata('user_data')['role'];
    //$showAdmin = (!empty($permissions) && ($permissions['2']['read'] || $permissions['3']['read']));
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
                        Camparar Conteos
                    </h2>
                    <div class="flex flex-col flex-wrap mb-8 space-y-4 md:flex-row md:items-end md:space-x-4">
                        <a href="<?php echo base_url();?>sisvent/store/inventory"  class="flex items-center justify-between px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg active:bg-mam-blue-dark hover:bg-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark">
                          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                          <span>Volver</span>
                        </a>
                    </div>

                    <div class="flex flex-col flex-wrap mb-8 space-y-4 md:flex-row md:items-end md:space-x-4 text-center">
                        <h3 class="mx-auto text-gray-700"><span class="font-bold">Estos productos presentan diferencia en los conteos</h3>
                    </div>
                    
                    <form id="new-inventory-form" action="<?php echo base_url();?>sisvent/store/inventory/storeCompare" method="POST">
                      <?php if($this->session->flashdata("error")):?>
                          <div class="flex items-center p-4 mb-8 text-sm font-semibold text-white bg-red-600 rounded-lg shadow-md">
                              <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                              <p><?php echo $this->session->flashdata("error"); ?></p>
                           </div>
                      <?php endif;?>
                      <div class="px-4 py-3 mb-8 bg-white rounded-lg shadow-md">
                        <input class="form-input" type="hidden" name="id" value="<?php echo $inventory->idInventory;?>" readonly/>
                        
                        <div id="cont" class="block flex flex-row gap-5 my-4 text-sm print:hidden">
                          <input type="submit" class="px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg active:bg-mam-blue-dark hover:bg-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark" value="Guardar">
                        </div>

                        <div class="w-full overflow-hidden rounded-lg shadow-xs">
                          <div class="w-full overflow-x-auto">
                            <table class="w-full whitespace-no-wrap">
                              <thead>
                                <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                                  <th class="px-4 py-3">#</th>
                                  <th class="px-4 py-3">Código</th>
                                  <th class="px-4 py-3">Descripción</th>
                                  <th class="px-4 py-3">Conteo 1</th>
                                  <th class="px-4 py-3">Conteo 2</th>
                                </tr>
                              </thead>
                              <tbody id="tborders" class="bg-white divide-y">
                                <?php foreach($compare as $key => $detail):?>
                                    <tr class="text-gray-700">
                                      <td class='px-2 py-1 print:py-0 text-sm'><?php echo ($key + 1); ?></td>
                                      <td class="px-4 py-3 print:py-0 text-xs whitespace-normal"><input type='hidden' name='refs[]' value='<?php echo $detail->idProduct;?>'><?php echo $detail->idProduct;?></td>
                                      <td class="px-4 py-3 print:py-0 text-xs whitespace-normal"><?php echo $detail->description;?></td>
                                      <td class="px-4 py-3 print:py-0 text-sm"><input class='form-input' type='number' name='quantities_1[]' min='0' value='<?php echo $detail->quantity_1;?>' readonly></td>
                                      <td class="px-4 py-3 print:py-0 text-sm"><input class='form-input' type='number' name='quantities_2[]' min='0' value='<?php echo $detail->quantity_2;?>' readonly></td>
                                      <td class="px-4 py-3 print:py-0 text-sm"><input class='form-input quantities' type='number' name='quantities[]' min='0' value='<?php echo $detail->quantity_1;?>'></td>
                                    </tr>
                                <?php endforeach;?>
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
    <?php $this->load->view('sisvent/layouts/footer'); ?>
  </body>
</html>