<?php
defined('BASEPATH') OR exit('No direct script access allowed');

    //$permissions = $this->session->userdata('user_data')['permissions'];
    $role = $this->session->userdata('user_data')['role'];
    //$showAdmin = (!empty($permissions) && ($permissions['2']['read'] || $permissions['3']['read']));
    $url_params = createFullParamsLinks($page);
?>
<!DOCTYPE html>
<html lang="en">
    <title>Vales</title>
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
                Vales
            </h2>
            <div class="flex flex-col flex-wrap mb-8 space-y-4 md:flex-row md:items-end md:space-x-4">
                <?php if(in_array($role, [1])): ?>
                    <a href="<?php echo base_url();?>sisvent/admin/vouchers/add"  class="flex items-center justify-between px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg active:bg-mam-blue-dark hover:bg-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark">
                      <span>Agregar Vale</span>
                      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                    </a>
                    <div class="flex-1"></div>
                <?php endif; ?>
                 <?php if(strpos(uri_string(), 'search') !== false): ?>
                <a href="<?php echo base_url();?>sisvent/admin/vouchers<?php echo $url_params ?>"  class="flex items-center justify-between px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg active:bg-mam-blue-dark hover:bg-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark">
                  <span>Volver</span>
                </a>
                <?php endif; ?>
                <label class="block my-4 text-sm">
                  <div class="relative text-gray-500 focus-within:text-purple-600">
                    <input class="form-input-lg inline w-1/2" data-params="<?php echo $url_params ?>" type="text" id="vouchers-search" placeholder="Buscar Vale"/>
                    <button id="btn-search-vouchers" class="form-input-lg inline flex items-center justify-between inset-y-0 px-4 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg focus:outline-none" type="button" value="" onclick=""/>
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
                      <th class="px-4 py-3">Vendedor</th>
                      <th class="px-4 py-3">Valor</th>
                      <th class="px-4 py-3">Método</th>
                      <th class="px-4 py-3">Estado</th>
                      <th class="px-4 py-3">Fecha</th>
                      <th class="px-4 py-3">Observaciones</th>
                      <th class="px-4 py-3">Acciones</th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y">
                    <?php if(!empty($vouchers)):?>
                        <?php foreach($vouchers as $key => $voucher):?>
                            <tr class="text-gray-700 <?php echo $key%2 ? 'bg-gray-300' : 'bg-gray' ?>">
                              <td class="px-4 py-3 text-sm">
                                <?php echo $voucher->idVoucher;?>
                              </td>
                              <td class="px-4 py-3">
                                <div class="flex items-center text-sm whitespace-normal">
                                  <div>
                                    <p class="font-semibold whitespace-normal"><?php echo $voucher->vendor_name;?></p>
                                    <p class="text-xs text-gray-600">
                                      <?php echo $voucher->userId;?>
                                    </p>
                                  </div>
                                </div>
                              </td>
                              <td class="px-4 py-3">
                                $ <?php echo number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $voucher->value)), 2);//$voucher->total;?>
                              </td>
                              <td class="px-4 py-3 text-xs whitespace-normal">
                                <?php echo $voucher->method_name;?>
                              </td>
                              <td>
                                <?php switch ($voucher->state) {
                                   case 1:?>
                                    <span class="px-2 py-1 font-semibold leading-tight text-orange-700 bg-orange-100 rounded-full dark:text-white dark:bg-orange-600">
                                      Pagada
                                    </span>
                                   <?php break;
                                   case 2:?>
                                    <span class="px-2 py-1 font-semibold leading-tight text-green-700 bg-green-100 rounded-full dark:bg-green-700 dark:text-green-100">
                                      Liquidada
                                    </span>
                                   <?php break;
                                  
                                  default:?>
                                    <span class="px-2 py-1 font-semibold leading-tight text-gray-700 bg-gray-100 rounded-full dark:text-gray-100 dark:bg-gray-700">
                                      Expired
                                    </span>
                                   <?php break;
                                } ?>
                              </td>
                              <td class="px-4 py-3 text-xs whitespace-normal">
                                <?php echo date("d-m-Y", strtotime($voucher->date));?>
                              </td>
                              <td class="px-4 py-3 text-xs max-w-2xl whitespace-normal">
                                <?php echo $voucher->description;?>
                              </td>
                              <td class="px-4 py-3">
                                <div class="flex items-center space-x-4 text-sm">
                                  <a href="<?php echo base_url()?>sisvent/admin/vouchers/edit/<?php echo $voucher->idVoucher;?>" class="flex items-center justify-between px-2 py-2 text-sm font-medium leading-5 text-mam-blue-dark rounded-lg focus:outline-none focus:shadow-outline-gray" aria-label="Edit">
                                    <p class="tooltip"><svg class="w-5 h-5" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">
                                      <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"></path>
                                    </svg><span class="tooltip-text bg-blue-200 p-3 -mt-6 -ml-6 rounded">Editar</span></p>
                                  </a>
                                  <?php if(in_array($role, [1])): ?>
                                  <a href="<?php echo base_url()?>sisvent/admin/vouchers/delete/<?php echo $voucher->idVoucher;?>" class="flex items-center justify-between px-2 py-2 text-sm font-medium leading-5 text-mam-blue-dark rounded-lg focus:outline-none focus:shadow-outline-gray" onclick="showSureModal(event,this)" aria-label="Delete">
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