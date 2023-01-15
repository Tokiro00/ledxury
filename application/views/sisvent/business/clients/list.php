<?php
defined('BASEPATH') OR exit('No direct script access allowed');

    //$permissions = $this->session->userdata('user_data')['permissions'];
    $role = $this->session->userdata('user_data')['role'];
    //$showAdmin = (!empty($permissions) && ($permissions['2']['read'] || $permissions['3']['read']));
     $url_params = createFullParamsLinks($page);
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
    	 	<main class="h-full">
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
                        <div class="flex-1"></div>
                        <?php if(strpos(uri_string(), 'search') !== false): ?>
                        <a href="<?php echo base_url();?>sisvent/business/clients<?php echo $url_params ?>"  class="flex items-center justify-between px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg active:bg-mam-blue-dark hover:bg-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark">
                          <span>Volver</span>
                        </a>
                        <?php endif; ?>
                        <label class="block my-4 text-sm">
                          <div class="relative text-gray-500 focus-within:text-purple-600">
                            <input class="form-input-lg inline w-1/2" data-params="<?php echo $url_params ?>" type="text" id="clients-search" placeholder="Buscar cliente"/>
                            <button id="btn-search-client" class="form-input-lg inline flex items-center justify-between inset-y-0 px-4 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg focus:outline-none" type="button" value="" onclick=""/>
                              <svg class="w-6 h-6 inline" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                              </svg>
                              <span class="inline pr-4">Buscar</span>
                            </button>
                          </div>
                        </label>
                    </div>
                    <div class="w-full overflow-hidden rounded-lg shadow-xs">
                      <div class="w-full overflow-x-auto overflow-y-hidden">
                        <table class="w-full whitespace-no-wrap">
                          <thead>
                            <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                              <th class="px-4 py-3">Id</th>
                              <th class="px-4 py-3">Cliente</th>
                              <th class="px-4 py-3">Id Factusol</th>
                              <th class="px-4 py-3">Tipo</th>
                              <th class="px-4 py-3">Detal</th>
                              <th class="px-4 py-3">Dirección</th>
                              <th class="px-4 py-3">Teléfono</th>
                              <th class="px-4 py-3">Email</th>
                              <th class="px-4 py-3">Vendedor</th>
                              <?php if(in_array($role, [1])): ?>
                              <th class="px-4 py-3">Acciones</th>
                              <th class="px-4 py-3"><button id="export_all" class="flex items-center justify-between px-2 py-2 text-sm font-medium leading-5 text-mam-blue-dark rounded-lg focus:outline-none focus:shadow-outline-gray" aria-label="Excel Factusol">
                                            <p class="tooltip"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg><span class="tooltip-text bg-blue-200 p-3 -mt-6 -ml-6 rounded">Excel Factusol</span></p>
                                          </button></th>
                              <?php endif; ?>
                            </tr>
                          </thead>
                          <tbody class="bg-white divide-y">
                            <?php if(!empty($clients)):?>
                                <?php foreach($clients as $key => $client):?>
                                    <tr class="text-gray-700 <?php echo $client->blacklisted ? 'bg-red-300' : ($key%2 ? 'bg-gray-300' : 'bg-gray') ?>">
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
                                      <td class="px-4 py-3 text-xs flex flex-row gap-2 whitespace-normal">
                                        <div>
                                          <?php if($client->is_new): ?>
                                          <p class="tooltip">
                                             <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" /></svg><span class="tooltip-text bg-blue-200 p-3 -mt-6 -ml-6 rounded">Cliente nuevo</span></p>
                                          <?php endif; ?>
                                        </div>
                                        <div><?php echo $client->f_id;?></div>
                                      </td>
                                      <td class="px-4 py-3 text-sm">
                                          <?php echo $client->type;?>
                                      </td>
                                      <td class="px-4 py-3 w-full lg:w-auto block lg:table-cell relative lg:static">
                                        <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Detal</span>
                                        <div class="flex flex-col items-center text-sm">
                                          <p class="">
                                            <?php if($client->retail): ?>
                                            <svg class="w-6 h-6 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            <?php else: ?>
                                              <svg class="w-6 h-6 text-red-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            <?php endif; ?></p>                    
                                        </div>
                                      </td>
                                      <td class="px-4 py-3 text-xs whitespace-normal">
                                        <p><?php echo $client->address;?></p>
                                        <?php if(isset($client->zone)): ?>
                                        <p><?php echo $client->zone;?></p>
                                        <?php endif; ?>                    
                                        <p><?php echo $client->city." - ".$client->state;?></p>
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

                                          <button value="<?php echo $client->idClient;?>" class="btn-view-client flex items-center justify-between px-2 py-2 text-sm font-medium leading-5 text-mam-blue-dark rounded-lg focus:outline-none focus:shadow-outline-gray" aria-label="View">
                                            <p class="tooltip"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg><span class="tooltip-text bg-blue-200 p-3 -mt-6 -ml-6 rounded">Ver</span></p>
                                          </button>
                                          <a href="<?php echo base_url()?>sisvent/business/clients/duplicate/<?php echo $client->idClient.$url_params;?>" class="flex items-center justify-between px-2 py-2 text-sm font-medium leading-5 text-mam-blue-dark rounded-lg focus:outline-none focus:shadow-outline-gray" aria-label="Duplicate">
                                            <p class="tooltip"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg><span class="tooltip-text bg-blue-200 p-3 -mt-6 -ml-6 rounded">Duplicar</span></p>
                                          </a>
                                          <a href="<?php echo base_url()?>sisvent/business/clients/edit/<?php echo $client->idClient.$url_params;?>" class="flex items-center justify-between px-2 py-2 text-sm font-medium leading-5 text-mam-blue-dark rounded-lg focus:outline-none focus:shadow-outline-gray" aria-label="Edit">
                                            <p class="tooltip"><svg class="w-5 h-5" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">
                                              <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"></path>
                                            </svg><span class="tooltip-text bg-blue-200 p-3 -mt-6 -ml-6 rounded">Editar</span></p>
                                          </a>
                                          <a href="<?php echo base_url()?>sisvent/business/clients/delete/<?php echo $client->idClient;?>" class="flex items-center justify-between px-2 py-2 text-sm font-medium leading-5 text-mam-blue-dark rounded-lg focus:outline-none focus:shadow-outline-gray" onclick="showSureModal(event,this)" aria-label="Delete">
                                            <p class="tooltip"><svg class="w-5 h-5" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">
                                              <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                            </svg><span class="tooltip-text bg-blue-200 p-3 -mt-6 -ml-6 rounded">Delete</span></p>
                                          </a>
                                          <?php if(!$client->blacklisted): ?>
                                          <a href="<?php echo base_url()?>sisvent/business/clients/blacklisted/<?php echo $client->idClient;?>" class="flex items-center justify-between px-2 py-2 text-sm font-medium leading-5 text-mam-blue-dark rounded-lg focus:outline-none focus:shadow-outline-gray" onclick="showSureModal(event,this,'¿Está seguro que desea poner este cliente en lista negra?')" aria-label="Blacklistedlist">
                                            <p class="tooltip"><svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg><span class="tooltip-text bg-blue-200 p-3 -mt-6 -ml-6 rounded">Lista Negra</span></p>
                                          </a>
                                          <?php endif; ?>
                                          
                                        </div>
                                      </td>
                                      <td class="px-4 py-3 text-sm whitespace-normal">
                                        <input type="checkbox" class="export_checkbox" value="<?php echo $client->idClient;?>" />
                                      </td>
                                        <?php endif; ?>
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
                            <?php echo createLinks($page, $total, "", $limit) ?>
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