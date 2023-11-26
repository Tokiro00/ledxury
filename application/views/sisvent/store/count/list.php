<?php
defined('BASEPATH') OR exit('No direct script access allowed');

    $role = $this->session->userdata('user_data')['role'];
?>
<!DOCTYPE html>
<html lang="en">
    <title>Conteo diario</title>
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
                  Conteo diario
              </h2>
              <div class="flex flex-col flex-wrap m-auto mb-8 space-y-4 md:flex-row md:items-end md:space-x-4">
                  <?php if(!empty($inventories)):?>
                      <?php foreach($inventories as $key => $inventory):?>
                      <a href="<?php echo base_url();?>sisvent/store/count/addCount/<?php echo $inventory->idStore ?>"  class="flex items-center justify-between px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg active:bg-mam-blue-dark hover:bg-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark">
                        <span>Nuevo Conteo <?php echo $inventory->name ?></span>
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                      </a>
                      <?php endforeach;?>
                    <?php endif;?>
              </div>
              <div class="w-full overflow-hidden rounded-lg shadow-xs">
                <div class="w-full overflow-x-auto overflow-y-hidden">
                  <table class="w-full whitespace-no-wrap mt-8 lg:mt-0">
                    <thead>
                      <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                        <th class="px-4 py-3 hidden lg:table-cell">Id</th>
                        <th class="px-4 py-3 hidden lg:table-cell">Usuario</th>
                        <th class="px-4 py-3 hidden lg:table-cell">Almacén</th>
                        <th class="px-4 py-3 hidden lg:table-cell">Fecha</th>
                        <th class="px-4 py-3 hidden lg:table-cell">Observaciones</th>
                        <th class="px-4 py-3 hidden lg:table-cell">Acciones</th>
                      </tr>
                    </thead>
                    <tbody id="tborders" class="bg-white divide-y">
                      <?php if(!empty($counts)):?>
                          <?php foreach($counts as $key => $count):?>
                              <tr class="text-gray-700 <?php echo $key%2 ? 'bg-gray-300' : 'bg-gray' ?> flex lg:table-row flex-row lg:flex-row flex-wrap lg:flex-no-wrap mb-10 lg:mb-0">
                                <td class="px-4 py-3 text-sm w-full lg:w-auto block lg:table-cell relative lg:static">
                                  <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Id</span>
                                  <?php echo $count->idCount;?>
                                </td>
                                <td class="px-4 py-3 text-sm whitespace-normal w-full lg:w-auto block lg:table-cell relative lg:static">
                                  <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Usuario</span>
                                  <?php echo $count->user_name;?>
                                </td>
                                <td class="px-4 py-3 text-xs whitespace-normal w-full lg:w-auto block lg:table-cell relative lg:static">
                                  <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Almacén</span>
                                  <?php echo $count->store_name;?>
                                </td>
                                <td class="px-4 py-3 text-xs whitespace-normal w-full lg:w-auto block lg:table-cell relative lg:static">
                                  <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Fecha</span>
                                  <?php echo date("d-m-Y H:m:s", strtotime($count->date));// $count->date;?>
                                </td>
                                <td class="px-4 py-3 text-xs max-w-2xl whitespace-normal w-full lg:w-auto block lg:table-cell relative lg:static">
                                  <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Observ.</span>
                                  <?php echo $count->comments;?>
                                </td>
                                <td class="px-4 py-3 w-full lg:w-auto block lg:table-cell relative lg:static">
                                  <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Acciones</span>
                                  <div class="flex items-center space-x-4 text-sm">
                                   <?php if(in_array($role, [1])): ?>
                                    <button value="<?php echo $count->idCount;?>" class="btn-view-count flex items-center justify-between px-2 py-2 text-sm font-medium leading-5 text-mam-blue-dark rounded-lg focus:outline-none focus:shadow-outline-gray" aria-label="View">
                                      <p class="tooltip"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg><span class="tooltip-text bg-blue-200 p-3 -mt-6 -ml-6 rounded">Ver</span></p>
                                    </button>
                                  <?php endif;?>
                                    <a href="<?php echo base_url()?>sisvent/store/count/edit/<?php echo $count->idCount;?>" class="flex items-center justify-between px-2 py-2 text-sm font-medium leading-5 text-mam-blue-dark rounded-lg focus:outline-none focus:shadow-outline-gray" aria-label="Edit">
                                            <p class="tooltip"><svg class="w-5 h-5" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">
                                              <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"></path>
                                            </svg><span class="tooltip-text bg-blue-200 p-3 -mt-6 -ml-6 rounded">Editar</span></p>
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