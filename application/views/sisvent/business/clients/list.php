<?php
defined('BASEPATH') OR exit('No direct script access allowed');

    //$permissions = $this->session->userdata('user_data')['permissions'];
    $role = $this->session->userdata('user_data')['role'];
    //$showAdmin = (!empty($permissions) && ($permissions['2']['read'] || $permissions['3']['read']));
?>
<!DOCTYPE html>
<html lang="en">
    <title>Clientes</title>
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
                        Clientes
                    </h2>
                    <div class="flex flex-col flex-wrap mb-8 space-y-4 md:flex-row md:items-end md:space-x-4">
                        <?php //if(in_array($role, [1])): ?>
                            <a href="<?php echo base_url();?>sisvent/business/clients/add"  class="flex items-center justify-between px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg active:bg-mam-blue-dark hover:bg-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark">
                              <span>Agregar Cliente</span>
                              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                            </a>
                        <?php //endif; ?>
                    </div>
                    <div class="w-full overflow-hidden rounded-lg shadow-xs">
                      <div class="w-full overflow-x-auto">
                        <table class="w-full whitespace-no-wrap">
                          <thead>
                            <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                              <th class="px-4 py-3">Id</th>
                              <th class="px-4 py-3">Cliente</th>
                              <th class="px-4 py-3">Dirección</th>
                              <th class="px-4 py-3">Teléfono</th>
                              <th class="px-4 py-3">Email</th>
                              <th class="px-4 py-3">Vendedor</th>
                              <?php if(in_array($role, [1])): ?>
                              <th class="px-4 py-3">Acciones</th>
                              <?php endif; ?>
                            </tr>
                          </thead>
                          <tbody class="bg-white divide-y">
                            <?php if(!empty($clients)):?>
                                <?php foreach($clients as $client):?>
                                    <tr class="text-gray-700">
                                      <td class="px-4 py-3 text-sm">
                                        <?php echo $client->idClient;?>
                                      </td>
                                      <td class="px-4 py-3">
                                        <div class="flex items-center text-sm whitespace-normal">
                                            <div>
                                              <p class="font-semibold whitespace-normal"><?php echo $client->name;?></p>
                                              <p class="text-xs text-gray-600">
                                                <?php echo $client->idNum;?>
                                              </p>
                                            </div>
                                        </div>
                                      </td>
                                      <td class="px-4 py-3 text-xs whitespace-normal">
                                        <?php echo $client->address;?>
                                      </td>
                                      <td class="flex items-center text-xs">
                                        <div>
                                          <p><?php echo $client->phone;?></p>
                                          <p><?php echo $client->cellphone;?></p>
                                        </div>
                                      </td>
                                      <td class="px-4 py-3 text-xs">
                                        <?php echo $client->email;?>
                                      </td>
                                      <td class="px-4 py-3 text-sm whitespace-normal">
                                        <?php echo $client->vendor_name;?>
                                      </td>
                                        <?php if(in_array($role, [1])): ?>
                                      <td class="px-4 py-3">
                                        <div class="flex items-center space-x-4 text-sm">
                                          <a href="<?php echo base_url()?>sisvent/business/clients/edit/<?php echo $client->idClient;?>" class="flex items-center justify-between px-2 py-2 text-sm font-medium leading-5 text-mam-blue-dark rounded-lg focus:outline-none focus:shadow-outline-gray" aria-label="Edit">
                                            <p class="tooltip"><svg class="w-5 h-5" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">
                                              <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"></path>
                                            </svg><span class="tooltip-text bg-blue-200 p-3 -mt-6 -ml-6 rounded">Editar</span></p>
                                          </a>
                                          <a href="<?php echo base_url()?>sisvent/business/clients/delete/<?php echo $client->idClient;?>" class="flex items-center justify-between px-2 py-2 text-sm font-medium leading-5 text-mam-blue-dark rounded-lg focus:outline-none focus:shadow-outline-gray" onclick="showSureModal(event,this)" aria-label="Delete">
                                            <p class="tooltip"><svg class="w-5 h-5" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">
                                              <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                            </svg><span class="tooltip-text bg-blue-200 p-3 -mt-6 -ml-6 rounded">Delete</span></p>
                                          </a>
                                        </div>
                                      </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach;?>
                            <?php endif;?>
                          </tbody>
                        </table>
                      </div>
                      <!--div class="grid px-4 py-3 text-xs font-semibold tracking-wide text-gray-500 uppercase border-t bg-gray-50 sm:grid-cols-9">
                        <span class="flex items-center col-span-3">
                          Mostrando 21-30 of 100
                        </span>
                        <span class="col-span-2"></span>
                        < !-- Pagination - ->
                        <span class="flex col-span-4 mt-2 sm:mt-auto sm:justify-end">
                          <nav aria-label="Table navigation">
                            <ul class="inline-flex items-center">
                              <li>
                                <button class="px-3 py-1 rounded-md rounded-l-lg focus:outline-none focus:shadow-outline-mam-blue-dark" aria-label="Ant.">
                                  <svg class="w-4 h-4 fill-current" aria-hidden="true" viewBox="0 0 20 20">
                                    <path d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" fill-rule="evenodd"></path>
                                  </svg>
                                </button>
                              </li>
                              <li>
                                <button class="px-3 py-1 rounded-md focus:outline-none focus:shadow-outline-mam-blue-dark">
                                  1
                                </button>
                              </li>
                              <li>
                                <button class="px-3 py-1 rounded-md focus:outline-none focus:shadow-outline-mam-blue-dark">
                                  2
                                </button>
                              </li>
                              <li>
                                <button class="px-3 py-1 text-white transition-colors duration-150 bg-mam-blue-dark border border-r-0 border-mam-blue-dark rounded-md focus:outline-none focus:shadow-outline-mam-blue-dark">
                                  3
                                </button>
                              </li>
                              <li>
                                <button class="px-3 py-1 rounded-md focus:outline-none focus:shadow-outline-mam-blue-dark">
                                  4
                                </button>
                              </li>
                              <li>
                                <span class="px-3 py-1">...</span>
                              </li>
                              <li>
                                <button class="px-3 py-1 rounded-md focus:outline-none focus:shadow-outline-mam-blue-dark">
                                  8
                                </button>
                              </li>
                              <li>
                                <button  class="px-3 py-1 rounded-md focus:outline-none focus:shadow-outline-mam-blue-dark">
                                  9
                                </button>
                              </li>
                              <li>
                                <button class="px-3 py-1 rounded-md rounded-r-lg focus:outline-none focus:shadow-outline-mam-blue-dark" aria-label="Sig.">
                                  <svg class="w-4 h-4 fill-current" aria-hidden="true" viewBox="0 0 20 20">
                                    <path d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" fill-rule="evenodd"></path>
                                  </svg>
                                </button>
                              </li>
                            </ul>
                          </nav>
                        </span>
                      </div-->
                    </div>
    	 		</div>
	        </main>
	      </div>
    </div>
    <?php $this->load->view('sisvent/layouts/footer'); ?>
  </body>
</html>