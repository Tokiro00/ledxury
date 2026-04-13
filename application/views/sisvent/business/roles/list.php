<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$role = $this->session->userdata('user_data')['role'];
?>
<!DOCTYPE html>
<html lang="en">
    <title>Permisos y Roles</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<head></head>
  <body>
    <div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
    	<?php $this->load->view('sisvent/layouts/sidebar',array('thisFile' => $_ci_view,'role' => $role)); ?>

    	 <div class="flex flex-col flex-1 w-full">
    		<?php $this->load->view('sisvent/layouts/navbar'); ?>
    	 	<main class="h-full">
    	 		<div class="px-4 py-4 w-full">
                    <h2 class="mb-4 text-lg font-semibold text-gray-600 mt-2">
                        Permisos y Roles
                    </h2>

                    <?php if($this->session->flashdata("success")):?>
                        <div class="flex items-center p-4 mb-4 text-sm font-semibold text-white bg-green-600 rounded-lg shadow-md">
                            <p><?php echo $this->session->flashdata("success"); ?></p>
                        </div>
                    <?php endif;?>
                    <?php if($this->session->flashdata("error")):?>
                        <div class="flex items-center p-4 mb-4 text-sm font-semibold text-white bg-red-600 rounded-lg shadow-md">
                            <p><?php echo $this->session->flashdata("error"); ?></p>
                        </div>
                    <?php endif;?>

                    <div class="flex flex-col flex-wrap mb-8 space-y-4 md:flex-row md:items-end md:space-x-4">
                        <a href="<?php echo base_url();?>sisvent/business/roles/add" class="flex items-center justify-between px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 border border-transparent rounded-lg focus:outline-none" style="background:#2E7D91">
                          <span>Nuevo Rol</span>
                          <svg class="w-6 h-6 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                        </a>
                        <a href="<?php echo base_url();?>sisvent/business/roles/matrix" class="flex items-center justify-between px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 border border-transparent rounded-lg focus:outline-none" style="background:#1B365D">
                          <span>Matriz de Permisos</span>
                        </a>
                    </div>

                    <div class="w-full overflow-hidden rounded-lg shadow-xs">
                      <div class="w-full overflow-x-auto overflow-y-hidden">
                        <table class="w-full whitespace-no-wrap">
                          <thead>
                            <tr class="text-xs font-semibold tracking-wide text-left uppercase border-b" style="background:#1B365D; color:white">
                              <th class="px-4 py-3">ID</th>
                              <th class="px-4 py-3">Nombre</th>
                              <th class="px-4 py-3">Descripcion</th>
                              <th class="px-4 py-3">Codigo PUC</th>
                              <th class="px-4 py-3">Permisos</th>
                              <th class="px-4 py-3">Acciones</th>
                            </tr>
                          </thead>
                          <tbody class="bg-white divide-y">
                            <?php if(!empty($roles)):?>
                                <?php foreach($roles as $r):?>
                                    <tr class="text-gray-700">
                                      <td class="px-4 py-3 text-sm">
                                        <?php echo $r->idRoles;?>
                                      </td>
                                      <td class="px-4 py-3 text-sm font-semibold">
                                        <?php echo htmlspecialchars($r->name);?>
                                      </td>
                                      <td class="px-4 py-3 text-sm">
                                        <?php echo htmlspecialchars($r->description);?>
                                      </td>
                                      <td class="px-4 py-3 text-sm">
                                        <?php echo !empty($r->puc_code) ? htmlspecialchars($r->puc_code) : '<span class="text-gray-400">N/A</span>';?>
                                      </td>
                                      <td class="px-4 py-3 text-sm">
                                        <span class="px-2 py-1 font-semibold leading-tight rounded-full <?php echo $r->permission_count > 0 ? 'text-green-700 bg-green-100' : 'text-gray-600 bg-gray-100';?>">
                                            <?php echo $r->permission_count;?> permisos
                                        </span>
                                      </td>
                                      <td class="px-4 py-3">
                                        <div class="flex items-center space-x-2 text-sm">
                                          <a href="<?php echo base_url()?>sisvent/business/roles/edit/<?php echo $r->idRoles;?>" class="flex items-center justify-between px-2 py-2 text-sm font-medium leading-5 rounded-lg focus:outline-none" style="color:#2E7D91" title="Editar">
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                              <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"></path>
                                            </svg>
                                          </a>
                                          <a href="<?php echo base_url()?>sisvent/business/roles/permissions/<?php echo $r->idRoles;?>" class="flex items-center justify-between px-2 py-2 text-sm font-medium leading-5 rounded-lg focus:outline-none" style="color:#2E7D91" title="Permisos">
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                              <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path>
                                            </svg>
                                          </a>
                                          <?php if($r->idRoles != 1): ?>
                                          <a href="<?php echo base_url()?>sisvent/business/roles/remove/<?php echo $r->idRoles;?>" class="flex items-center justify-between px-2 py-2 text-sm font-medium leading-5 text-red-600 rounded-lg focus:outline-none" onclick="showSureModal(event,this)" title="Eliminar">
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                              <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                            </svg>
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
