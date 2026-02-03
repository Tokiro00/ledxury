<?php
defined('BASEPATH') OR exit('No direct script access allowed');

    //$permissions = $this->session->userdata('user_data')['permissions'];
    $role = $this->session->userdata('user_data')['role'];
    //$showAdmin = (!empty($permissions) && ($permissions['2']['read'] || $permissions['3']['read']));
?>
<!DOCTYPE html>
<html lang="en">
    <title>Entradas</title>
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
                Entradas <span class="text-sm font-normal text-gray-400">(<?php echo number_format($total); ?> registros)</span>
            </h2>
            
            <div class="w-full overflow-hidden rounded-lg shadow-xs">
              <div class="w-full overflow-x-auto overflow-y-hidden">
                <table class="w-full whitespace-no-wrap">
                  <thead>
                    <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                      <th class="px-4 py-3">Id</th>
                      <th class="px-4 py-3">Descripción</th>
                      <th class="px-4 py-3">Tipo</th>
                      <th class="px-4 py-3">Debito</th>
                      <th class="px-4 py-3">Credito</th>
                      <th class="px-4 py-3">Estado</th>
                      <th class="px-4 py-3">Fecha</th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y">
                    <?php if(!empty($entries)):?>
                        <?php foreach($entries as $entry):?>
                            <tr class="text-gray-700">
                              <td class="px-4 py-3 text-sm">
                                <?php echo $entry->entryID;?>
                              </td>
                              <td class="px-4 py-3">
                                <p class="font-semibold"><?php echo $entry->entryDescription;?></p>
                              </td>
                              <td class="px-4 py-3">
                                <p class="font-semibold"><?php echo $entry->entryType;?></p>
                              </td>
                              <td class="px-4 py-3">
                                <p class="font-semibold"><?php echo $entry->debitaccName;?></p>
                                <?php if (!empty($entry->debitauxaccName)): ?>
                                <p class="font-semibold"><?php echo $entry->debitauxaccName;?></p>
                                <?php endif ?>
                                <p class="font-semibold">$ <?php echo number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $entry->entryDebitBalance)), 2);?></p>
                              </td>
                              <td class="px-4 py-3">
                                <p class="font-semibold"><?php echo $entry->creditaccName;?></p>
                                <?php if (!empty($entry->creditauxaccName)): ?>
                                <p class="font-semibold"><?php echo $entry->creditauxaccName;?></p>
                                <?php endif ?>
                                <p class="font-semibold">$ <?php echo number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $entry->entryCreditBalance)), 2);?></p>
                              </td>
                              <td class="px-4 py-3">
                                <p class="font-semibold"><?php echo $entry->entryStatus;?></p>
                                <p class="font-semibold"><?php echo $entry->entryStatusComment;?></p>
                              </td>
                              <td class="px-4 py-3 text-xs whitespace-normal">
                                <?php echo date("d-m-Y", strtotime($entry->entryCreateDate));// $payment->created_at;?>
                              </td>
                            </tr>
                        <?php endforeach;?>
                    <?php endif;?>
                  </tbody>
                </table>
              </div>
              <div class="grid px-4 py-3 text-xs font-semibold tracking-wide text-gray-500 uppercase border-t dark:border-gray-700 bg-gray-50 sm:grid-cols-9 dark:text-gray-400 dark:bg-gray-800">
                <span class="flex items-center col-span-3">
                  <?php $last = ceil($total / $limit); ?>
                  Mostrando <?php echo ((($page-1) * $limit)+1).'-'.(($last == $page) ? ($total) : ((($page-1) * $limit)+$limit)).' de '.($total); ?>
                </span>
                <span class="col-span-2"></span>
                <!-- Pagination -->
                <span class="flex col-span-4 mt-2 sm:mt-auto sm:justify-end">
                  <nav aria-label="Table navigation">
                    <?php echo createLinks($page, $total, "", $limit); ?>
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