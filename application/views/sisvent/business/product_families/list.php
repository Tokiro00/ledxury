<?php
defined('BASEPATH') OR exit('No direct script access allowed');

    //$permissions = $this->session->userdata('user_data')['permissions'];
    $role = $this->session->userdata('user_data')['role'];
    //$showAdmin = (!empty($permissions) && ($permissions['2']['read'] || $permissions['3']['read']));
?>
<!DOCTYPE html>
<html lang="en">
    <title>Familias</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<head>

</head>
  <body>
    <div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
    	<?php $this->load->view('sisvent/layouts/sidebar',array('thisFile' => $_ci_view,'role' => $role)); ?>

    	 <div class="flex flex-col flex-1 w-full">
    		<?php $this->load->view('sisvent/layouts/navbar'); ?>
    	 	<main class="h-full">
    	 		<div class="px-6 mx-auto grid">
            <h2 class="mb-4 text-lg font-semibold text-gray-600 mt-2">
                Familias
            </h2>
            <div class="flex flex-col flex-wrap mb-8 space-y-4 md:flex-row md:items-end md:space-x-4">
                <?php if(in_array($role, [1])): ?>
                    <a href="<?php echo base_url();?>sisvent/business/products/addfamily"  class="flex items-center justify-between px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg active:bg-mam-blue-dark hover:bg-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark">
                      <span>Agregar Familia</span>
                      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                    </a>
                    <div class="flex-1"></div>
                    <a href="<?php echo base_url();?>sisvent/business/products"  class="flex items-center justify-between px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue border border-transparent rounded-lg active:bg-mam-blue hover:bg-mam-blue focus:outline-none focus:shadow-outline-mam-blue">
                      <span>Ver Productos </span>
                      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                    </a>
                <?php endif; ?>
            </div>
            <div class="w-full overflow-hidden rounded-lg shadow-xs">
              <div class="w-full overflow-x-auto overflow-y-hidden">
                <table class="w-full whitespace-no-wrap">
                  <thead>
                    <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                      <th class="px-4 py-3">Id</th>
                      <th class="px-4 py-3">Nombre</th>
                      <th class="px-4 py-3">Acciones</th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y">
                    <?php if(!empty($families)):?>
                        <?php foreach($families as $family):?>
                            <tr class="text-gray-700">
                              <td class="px-4 py-3 text-sm">
                                <?php echo $family->idFamily;?>
                              </td>
                              <td class="px-4 py-3">
                                <p class="font-semibold"><?php echo $family->name;?></p>
                              </td>
                              <td class="px-4 py-3">
                                <div class="flex items-center space-x-4 text-sm">
                                  <a href="<?php echo base_url()?>sisvent/business/products/editfamily/<?php echo $family->idFamily;?>" class="flex items-center justify-between px-2 py-2 text-sm font-medium leading-5 text-mam-blue-dark rounded-lg focus:outline-none focus:shadow-outline-gray" aria-label="Edit">
                                    <p class="tooltip"><svg class="w-5 h-5" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">
                                      <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"></path>
                                    </svg><span class="tooltip-text bg-blue-200 p-3 -mt-6 -ml-6 rounded">Editar</span></p>
                                  </a>
                                  <a href="<?php echo base_url()?>sisvent/business/products/deletefamily/<?php echo $family->idFamily;?>"  class="flex items-center justify-between px-2 py-2 text-sm font-medium leading-5 text-mam-blue-dark rounded-lg focus:outline-none focus:shadow-outline-gray" onclick="showSureModal(event,this)" aria-label="Delete">
                                    <p class="tooltip"><svg class="w-5 h-5" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">
                                      <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                    </svg><span class="tooltip-text bg-blue-200 p-3 -mt-6 -ml-6 rounded">Eliminar</span></p>
                                  </a>
                                </div>
                              </td>
                            </tr>
                        <?php endforeach;?>
                    <?php endif;?>
                  </tbody>
                </table>
              </div>
            </div>
    	 		</div>
        </main>
      </div>
    </div>
    <?php $this->load->view('sisvent/layouts/footer'); ?>
  </body>
</html>