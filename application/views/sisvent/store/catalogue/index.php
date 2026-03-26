<?php
defined('BASEPATH') OR exit('No direct script access allowed');

    //$permissions = $this->session->userdata('user_data')['permissions'];
    $user = $this->session->userdata('user_data');
    $logged = isset($user);
    if($logged)
        $role = $this->session->userdata('user_data')['role'];
    //$showAdmin = (!empty($permissions) && ($permissions['2']['read'] || $permissions['3']['read']));
?>
<!DOCTYPE html>
<html lang="en">
    <title>Catálogos</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<head>

</head>
  <body>
    <div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
    	<?php if($logged) $this->load->view('sisvent/layouts/sidebar',array('thisFile' => $_ci_view,'role' => $role)); ?>

    	 <div class="flex flex-col flex-1 w-full">
    		<?php if($logged) $this->load->view('sisvent/layouts/navbar'); ?>
    	 	<main class="h-full overflow-y-auto">
    	 		<div class="px-6 mx-auto grid">
            <h2 class="mb-4 text-2xl font-semibold text-gray-600 mt-2 text-center">
                Catálogos completos
            </h2>
            <div class="flex flex-col flex-wrap m-auto mb-8 space-y-4 md:flex-row md:items-end md:space-x-4">
                <a href="<?php echo base_url();?>sisvent/store/catalogue/view/1" class="flex flex-grow-0 items-center justify-between px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-petroleo border border-transparent rounded-lg active:bg-mam-blue-petroleo hover:bg-mam-blue-petroleo focus:outline-none focus:shadow-outline-mam-blue-petroleo m-0">
                    <span class="mr-0">Medellín</span>
                </a>
                <a href="<?php echo base_url();?>sisvent/store/catalogue/view/3" class="flex flex-grow-0 items-center justify-between px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-petroleo border border-transparent rounded-lg active:bg-mam-blue-petroleo hover:bg-mam-blue-petroleo focus:outline-none focus:shadow-outline-mam-blue-petroleo m-0">
                    <span class="mr-0">Bogotá</span>
                </a>
                <a href="<?php echo base_url();?>sisvent/store/catalogue/view/5" class="flex flex-grow-0 items-center justify-between px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-petroleo border border-transparent rounded-lg active:bg-mam-blue-petroleo hover:bg-mam-blue-petroleo focus:outline-none focus:shadow-outline-mam-blue-petroleo m-0">
                    <span class="mr-0">Cali</span>
                </a>
                <a href="<?php echo base_url();?>sisvent/store/catalogue/view/7" class="flex flex-grow-0 items-center justify-between px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-petroleo border border-transparent rounded-lg active:bg-mam-blue-petroleo hover:bg-mam-blue-petroleo focus:outline-none focus:shadow-outline-mam-blue-petroleo m-0">
                    <span class="mr-0">Barranquilla</span>
                </a>
            </div>
            <h2 class="mb-4 text-lg font-semibold text-gray-600 mt-2">
                        Catálogos personalizados
                    </h2>
                    <div class="flex flex-col flex-wrap mb-8 space-y-4 md:flex-row md:items-end md:space-x-4">
                        <a href="<?php echo base_url();?>sisvent/store/catalogue/add"  class="flex items-center justify-between px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-petroleo border border-transparent rounded-lg active:bg-mam-blue-petroleo hover:bg-mam-blue-petroleo focus:outline-none focus:shadow-outline-mam-blue-petroleo">
                          <span>Nuevo Catálogo</span>
                          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                        </a>
                    </div>
            
                    <div class="w-full overflow-hidden rounded-lg shadow-xs">
                      <div class="w-full overflow-x-auto overflow-y-hidden">
                        <table class="w-full whitespace-no-wrap mt-8 lg:mt-0">
                          <thead>
                            <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                              <th class="px-4 py-3 hidden lg:table-cell">Id</th>
                              <th class="px-4 py-3 hidden lg:table-cell">Nombre</th>
                              <th class="px-4 py-3 hidden lg:table-cell">Cliente</th>
                              <th class="px-4 py-3 hidden lg:table-cell">Observaciones</th>
                              <th class="px-4 py-3 hidden lg:table-cell">Acciones</th>
                            </tr>
                          </thead>
                          <tbody id="tborders" class="bg-white divide-y">
                            <?php if(!empty($catalogues)):?>
                                <?php foreach($catalogues as $key => $catalogue):?>
                                    <tr class="text-gray-700 <?php echo $key%2 ? 'bg-gray-300' : 'bg-gray' ?> flex lg:table-row flex-row lg:flex-row flex-wrap lg:flex-no-wrap mb-10 lg:mb-0">
                                      <td class="px-4 py-3 text-sm w-full lg:w-auto block lg:table-cell relative lg:static">
                                        <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Id</span>
                                        <?php echo $catalogue->idCatalogue;?>
                                      </td>
                                      <td class="px-4 py-3 text-xs whitespace-normal w-full lg:w-auto block lg:table-cell relative lg:static">
                                        <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Nombre</span>
                                        <?php echo $catalogue->name;?>
                                      </td>
                                      <td class="px-4 py-3 text-sm whitespace-normal w-full lg:w-auto block lg:table-cell relative lg:static">
                                        <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Cliente</span>
                                        <?php echo isset($catalogue->clientId) ? $catalogue->client_name : 'Todos';?>
                                      </td>
                                      <td class="px-4 py-3 text-xs max-w-2xl whitespace-normal w-full lg:w-auto block lg:table-cell relative lg:static">
                                        <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Observ.</span>
                                        <?php echo $catalogue->comments;?>
                                      </td>                         
                                      <td class="px-4 py-3 w-full lg:w-auto block lg:table-cell relative lg:static">
                                        <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Acciones</span>
                                        <div class="flex items-center space-x-4 text-sm">
                                          <a href="<?php echo base_url();?>sisvent/store/catalogue/viewcat/<?php echo $catalogue->idCatalogue;?>" class="btn-view-catalogue flex items-center justify-between px-2 py-2 text-sm font-medium leading-5 text-mam-blue-petroleo rounded-lg focus:outline-none focus:shadow-outline-gray" aria-label="View">
                                            <p class="tooltip"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg><span class="tooltip-text bg-blue-200 p-3 -mt-6 -ml-6 rounded">Ver</span></p>
                                          </a>
                                          <?php if(in_array($role, [1])): ?>
                                          <a href="<?php echo base_url()?>sisvent/store/catalogue/edit/<?php echo $catalogue->idCatalogue;?>" class="flex items-center justify-between px-2 py-2 text-sm font-medium leading-5 text-mam-blue-petroleo rounded-lg focus:outline-none focus:shadow-outline-gray" aria-label="Edit">
                                            <p class="tooltip"><svg class="w-5 h-5" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">
                                              <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"></path>
                                            </svg><span class="tooltip-text bg-blue-200 p-3 -mt-6 -ml-6 rounded">Editar</span></p>
                                          </a>
                                          <?php endif; ?>
                                          <?php if(in_array($role, [1])): ?>
                                          <a href="<?php echo base_url()?>sisvent/store/catalogue/delete/<?php echo $catalogue->idCatalogue;?>" class="flex items-center justify-between px-2 py-2 text-sm font-medium leading-5 text-mam-blue-petroleo rounded-lg focus:outline-none focus:shadow-outline-gray" onclick="showSureModal(event,this)" aria-label="Delete">
                                            <p class="tooltip"><svg class="w-5 h-5" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">
                                              <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                            </svg><span class="tooltip-text bg-blue-200 p-3 -mt-6 -ml-6 rounded">Eliminar</span></p>
                                          </a>
                                          <?php endif; ?>
                                        </div>
                                      </td>
                                    </tr>
                                <?php endforeach;?>
                            <?php endif;?>
                          </tbody>
                        </table>
                      </div>
                      <div class="grid px-4 py-3 text-xs font-semibold tracking-wide text-gray-500 uppercase border-t dark:border-gray-700 bg-gray-50 sm:grid-cols-9 dark:text-gray-400 dark:bg-gray-800">
                        <span class="flex items-center col-span-3">
                          <?php  $last       = ceil( $total / $limit ); ?>
                          Mostrando <?php echo ((($page-1) * $limit)+1).'-'.(($last == $page) ? ($total) : ((($page-1) * $limit)+$limit)).' de '.($total) ?>
                        </span>
                        <span class="col-span-2"></span>
                        <!-- Pagination -->
                        <span class="flex col-span-4 mt-2 sm:mt-auto sm:justify-end">
                          <nav aria-label="Table navigation">
                            <?php echo createLinks($page, $total, '', $limit) ?>
                          </nav>
                        </span>
                      </div>
                    </div>
            
    	 		</div>
	        </main>
	      </div>
    </div>
    <?php $this->load->view('sisvent/layouts/footer'); ?>
  </body>
</html>