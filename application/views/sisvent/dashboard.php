<?php
defined('BASEPATH') OR exit('No direct script access allowed');

    //$permissions = $this->session->userdata('user_data')['permissions'];
    $role = $this->session->userdata('user_data')['role'];
    //$showAdmin = (!empty($permissions) && ($permissions['2']['read'] || $permissions['3']['read']));
    $thisFile = pathinfo(__FILE__, PATHINFO_FILENAME);
    $thisViewName = trim($thisFile, '.php');
    $url_params = createFullParamsLinks($page);
    $url_params2 = createFullParamsLinks($page2);
?>
<!DOCTYPE html>
<html lang="en">
    <title>M.A.M. Dashboard</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<head>

</head>
  <body>
    <div id="bars" class="flex h-screen bg-gray-50 dark:bg-gray-900" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
    	<?php $this->load->view('sisvent/layouts/sidebar',array('thisFile' => $_ci_view,'role' => $role)); ?>

    	 <div class="flex flex-col flex-1 w-full">
    		<?php $this->load->view('sisvent/layouts/navbar'); ?>
    	 	<main class="h-full">
    	 		<div class="px-6 mx-auto grid">
                    <h2 class="mb-4 text-lg font-semibold text-gray-600 mt-2">
                        Dashboard
                    </h2>
    	 		</div>
                <!-- Cards -->
                <div class="grid gap-6 px-8 mb-8 md:grid-cols-2 xl:grid-cols-4">
                  <!-- Card -->
                  <div class="flex items-center p-4 bg-white rounded-lg shadow-md">
                    <button value="<?php echo $this->session->userdata('user_data')['uname'];?>"  class="btn-view-settlement p-3 mr-4 text-green-500 bg-green-100 rounded-full">
                      <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path></svg>
                    </button>
                    <div>
                      <p class="mb-2 text-sm font-medium text-gray-600">
                        Tu Balance 
                      </p>
                      <p class="text-lg font-semibold text-gray-700">
                        Total <?php echo ($settlement >= 0 ? '' : '-') ?> $<?php echo number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "",$settlement)), 2); ?>
                      </p>
                      <p class="text-xs text-gray-700">
                        IVA <?php echo ($settlementiva >= 0 ? '' : '-') ?> $<?php echo number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "",$settlementiva)), 2); ?>  -  Rem. <?php echo ($settlementnoiva >= 0 ? '' : '-') ?> $<?php echo number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "",$settlementnoiva)), 2); ?>
                      </p>
                    </div>
                  </div>
                  <!-- Card -->
                  <div class="flex items-center p-4 bg-white rounded-lg shadow-md">
                    <button value="<?php echo $this->session->userdata('user_data')['uname'];?>"  class="btn-view-unattenclients p-3 mr-4 text-orange-500 bg-orange-100 rounded-full">
                      <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"></path></svg>
                    </button>
                    <div>
                      <p class="mb-2 text-sm font-medium text-gray-600">
                        Total Clientes
                      </p>
                      <p class="text-lg font-semibold text-gray-700">
                        <?php echo $numClients; ?>
                      </p>
                    </div>
                  </div>
                  
                  <!-- Card -->
                  <div class="flex items-center p-4 bg-white rounded-lg shadow-md">
                    <div class="p-3 mr-4 text-blue-500 bg-blue-100 rounded-full dark:text-blue-100 dark:bg-blue-500">
                      <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H6.28l-.31-1.243A1 1 0 005 1H3zM16 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM6.5 18a1.5 1.5 0 100-3 1.5 1.5 0 000 3z"></path>
                      </svg>
                    </div>
                    <div>
                      <p class="mb-2 text-sm font-medium text-gray-600">
                        Facturas Pagadas
                      </p>
                      <p class="text-lg font-semibold text-gray-700">
                        <?php echo $paidInvoices; ?>
                      </p>
                    </div>
                  </div>
                  <!-- Card -->
                  <div class="flex items-center p-4 bg-white rounded-lg shadow-md">
                    <div class="p-3 mr-4 text-teal-500 bg-teal-100 rounded-full dark:text-teal-100 dark:bg-teal-500">
                      <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 5v8a2 2 0 01-2 2h-5l-5 4v-4H4a2 2 0 01-2-2V5a2 2 0 012-2h12a2 2 0 012 2zM7 8H5v2h2V8zm2 0h2v2H9V8zm6 0h-2v2h2V8z" clip-rule="evenodd"></path></svg>
                    </div>
                    <div>
                      <p class="mb-2 text-sm font-medium text-gray-600">
                        Facturas Pendientes
                      </p>
                      <p class="text-lg font-semibold text-gray-700">
                        <?php echo $nonPaidInvoices; ?>
                      </p>
                    </div>
                  </div>
                </div>
	        </main>

          <div class="">
          <p class="mb-2 text-xl font-medium text-gray-600">
            Productos Agotados <?php if(!empty($noInventory)): echo $noInventory[0]->store_name; endif; ?>
          </p>
          <div class="w-full overflow-hidden rounded-lg shadow-xs">
              <div class="w-full overflow-x-auto overflow-y-hidden">
                <table class="w-full whitespace-no-wrap">
                  <thead>
                    <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                      <th class="px-4 py-3">Código</th>
                      <th class="px-4 py-3">Descripción</th>
                      <th class="px-4 py-3">Stock</th>
                      
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y">
                    <?php if(!empty($noInventory)):?>
                        <?php foreach($noInventory as $product):?>
                            <tr class="text-gray-700">
                              <td class="px-4 py-3">
                                <div class="flex items-center text-sm">
                                  <div class="relative hidden w-8 h-8 mr-3 md:block">
                                    <img class="object-cover w-full h-full" src="<?php echo get_images_path($product->picture_url) ?>" alt="" loading="lazy"/>
                                    <div class="absolute inset-0 shadow-inner" aria-hidden="true"></div>
                                  </div>
                                    <div>
                                      <p class="font-semibold"><?php echo $product->idProduct;?></p>
                                    </div>
                                </div>
                              </td>
                              <td class="px-4 py-3 text-xs whitespace-normal">
                                <?php echo $product->description;?>
                              </td>
                              <td class="px-4 py-3 text-sm">
                                <?php echo $product->stock;?>
                              </td>
                              
                              
                            </tr>
                        <?php endforeach;?>
                    <?php endif;?>
                  </tbody>
                </table>
              </div>
              <div class="grid px-4 py-3 text-xs font-semibold tracking-wide text-gray-500 uppercase border-t dark:border-gray-700 bg-gray-50 sm:grid-cols-9 dark:text-gray-400 dark:bg-gray-800">
                <span class="flex items-center col-span-3">
                  <?php  $last2       = ceil( $total2 / $limit ); ?>
                  Mostrando <?php echo ((($page2-1) * $limit)+1).'-'.(($last2 == $page2) ? ($total2) : ((($page2-1) * $limit)+$limit)).' de '.($total2) ?>
                </span>
                <span class="col-span-2"></span>
                <!-- Pagination -->
                <span class="flex col-span-4 mt-2 sm:mt-auto sm:justify-end">
                  <nav aria-label="Table navigation">
                    <?php echo createLinks($page2, $total2, "", $limit, 2, "2") ?>
                  </nav>
                </span>
              </div>
            </div>
          </div>

          <hr class="my-8">

        <div class="">
          <p class="mb-2 text-xl font-medium text-gray-600">
            Productos con bajo inventario <?php if(!empty($lowInventory)): echo $lowInventory[0]->store_name; endif; ?>
          </p>
          <div class="w-full overflow-hidden rounded-lg shadow-xs">
              <div class="w-full overflow-x-auto overflow-y-hidden">
                <table class="w-full whitespace-no-wrap">
                  <thead>
                    <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                      <th class="px-4 py-3">Código</th>
                      <th class="px-4 py-3">Descripción</th>
                      <th class="px-4 py-3">Stock</th>
                      
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y">
                    <?php if(!empty($lowInventory)):?>
                        <?php foreach($lowInventory as $product):?>
                            <tr class="text-gray-700">
                              <td class="px-4 py-3">
                                <div class="flex items-center text-sm">
                                  <div class="relative hidden w-8 h-8 mr-3 md:block">
                                    <img class="object-cover w-full h-full" src="<?php echo get_images_path($product->picture_url) ?>" alt="" loading="lazy"/>
                                    <div class="absolute inset-0 shadow-inner" aria-hidden="true"></div>
                                  </div>
                                    <div>
                                      <p class="font-semibold"><?php echo $product->idProduct;?></p>
                                    </div>
                                </div>
                              </td>
                              <td class="px-4 py-3 text-xs whitespace-normal">
                                <?php echo $product->description;?>
                              </td>
                              <td class="px-4 py-3 text-sm">
                                <?php echo $product->stock;?>
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


	      </div>

    </div>
    <?php $this->load->view('sisvent/layouts/footer'); ?>
  </body>
</html>