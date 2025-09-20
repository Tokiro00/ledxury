<?php
defined('BASEPATH') OR exit('No direct script access allowed');

    //$permissions = $this->session->userdata('user_data')['permissions'];
    $role = $this->session->userdata('user_data')['role'];
    //$showAdmin = (!empty($permissions) && ($permissions['2']['read'] || $permissions['3']['read']));
?>
<!DOCTYPE html>
<html lang="en">
    <title>Vendedores</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<head>

</head>
  <body>
    <div id="bars" ref="foo" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
    	<?php $this->load->view('sisvent/layouts/sidebar',array('thisFile' => $_ci_view,'role' => $role)); ?>

    	 <div class="flex flex-col flex-1 w-full">
    		<?php $this->load->view('sisvent/layouts/navbar'); ?>
    	 	<main class="h-full">
    	 		  <div class="px-6 mx-auto grid">
              <h2 class="mb-4 text-lg font-semibold text-gray-600 mt-2">
                  Vendedores Archivados
              </h2>
              <div class="flex flex-col flex-wrap mb-8 space-y-4 md:flex-row md:items-end md:space-x-4">
                  <a href="<?php echo base_url();?>sisvent/business/vendors"  class="flex items-center justify-between px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg active:bg-mam-blue-dark hover:bg-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark">
                    <span>Vendedores</span>
                  </a>
              </div>
              <div class="w-full overflow-hidden rounded-lg shadow-xs">
                <div class="w-full overflow-x-auto overflow-y-hidden">
                  <table id="myTable" class="w-full whitespace-no-wrap">
                    <thead>
                      <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                        <th class="px-4 py-3">Id</th>
                        <th class="px-4 py-3">Vendedor</th>
                        <th class="px-4 py-3">Dirección</th>
                        <th class="px-4 py-3">Teléfono</th>
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">Acciones</th>
                      </tr>
                    </thead>
                    <tbody class="bg-white divide-y">
                      <?php if(!empty($vendors)):?>
                          <?php foreach($vendors as $user):?>
                              <tr class="text-gray-700">
                                <td class="px-4 py-3 text-sm">
                                  <?php echo $user->idUser;?>
                                </td>
                                <td class="px-4 py-3">
                                  <div class="flex items-center text-sm whitespace-normal">
                                    <!-- Avatar with inset shadow -->
                                    <div @click="otro2()" class="relative hidden w-8 h-8 mr-3 rounded-full md:block">
                                      <img  class="object-cover w-full h-full rounded-full" src="<?php echo get_images_path($user->picture_url) ?>" alt="" loading="lazy"/>
                                      <div class="absolute inset-0 rounded-full shadow-inner" aria-hidden="true"></div>
                                    </div>
                                    <div>
                                      <p class="font-semibold whitespace-normal"><?php echo $user->name;?></p>
                                      <p class="text-xs text-gray-600">
                                        <?php echo $user->store_name;?>
                                      </p>
                                    </div>
                                  </div>
                                </td>
                                <td class="px-4 py-3 text-xs whitespace-normal">
                                  <?php echo $user->address;?>
                                </td>
                                <td class="px-4 py-3 text-xs">
                                  <?php echo $user->phone;?>
                                </td>
                                <td class="px-4 py-3 text-xs">
                                  <?php echo $user->email;?>
                                </td>
                                <td class="px-4 py-3">
                                  <div class="flex items-center space-x-4 text-sm">
                                    <a href="<?php echo base_url()?>sisvent/business/vendors/edit/<?php echo $user->idUser;?>" class="flex items-center justify-between px-2 py-2 text-sm font-medium leading-5 text-mam-blue-dark rounded-lg focus:outline-none focus:shadow-outline-gray" aria-label="Edit">
                                      <p class="tooltip"><svg class="w-5 h-5" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"></path>
                                      </svg><span class="tooltip-text bg-blue-200 p-3 -mt-6 -ml-6 rounded">Editar</span></p>
                                    </a>

                                    <?php if($user->idUser !="00000"): ?>
                                      <a href="<?php echo base_url()?>sisvent/business/vendors/unarchive/<?php echo $user->idUser;?>" class="flex items-center justify-between px-2 py-2 text-sm font-medium leading-5 text-mam-blue-dark rounded-lg focus:outline-none focus:shadow-outline-gray" aria-label="Archive">
                                            <p class="tooltip"><svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z" /></svg><span class="tooltip-text bg-blue-200 p-3 -mt-6 -ml-6 rounded">Desarchivar</span></p>
                                          </a>
                                    <a href="<?php echo base_url()?>sisvent/business/vendors/delete/<?php echo $user->idUser;?>" class="flex items-center justify-between px-2 py-2 text-sm font-medium leading-5 text-mam-blue-dark rounded-lg focus:outline-none focus:shadow-outline-gray del-btn" onclick="showSureModal(event,this)" aria-label="Delete">
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
                
              </div>
    	 		  </div>
	        </main>
	      </div>
    </div>
    <?php $this->load->view('sisvent/layouts/footer'); ?>

  </body>
</html>