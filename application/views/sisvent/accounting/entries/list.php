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
                Entradas
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
            </div>
    	 		</div>
        </main>
      </div>
    </div>
    <?php $this->load->view('sisvent/layouts/footer'); ?>
  </body>
</html>