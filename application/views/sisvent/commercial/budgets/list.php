<?php
defined('BASEPATH') OR exit('No direct script access allowed');

    //$permissions = $this->session->budgetdata('budget_data')['permissions'];
    $role = $this->session->userdata('user_data')['role'];
    //$showAdmin = (!empty($permissions) && ($permissions['2']['read'] || $permissions['3']['read']));
?>
<!DOCTYPE html>
<html lang="en">
    <title>Presupuestos</title>
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
                        Presupuestos
                    </h2>
                    <div class="flex flex-col flex-wrap mb-8 space-y-4 md:flex-row md:items-end md:space-x-4">
                        <?php if(in_array($role, [1])): ?>
                            <a href="<?php echo base_url();?>sisvent/commercial/budgets/add"  class="flex items-center justify-between px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg active:bg-mam-blue-dark hover:bg-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark">
                              <span>Nuevo Presupuesto</span>
                              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="w-full overflow-hidden rounded-lg shadow-xs">
                      <div class="w-full overflow-x-auto">
                        <table class="w-full whitespace-no-wrap">
                          <thead>
                            <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                              <th class="px-4 py-3">Id</th>
                              <th class="px-4 py-3">Cliente</th>
                              <th class="px-4 py-3">Vendedor</th>
                              <th class="px-4 py-3">Almacen</th>
                              <th class="px-4 py-3">Valor</th>
                              <th class="px-4 py-3">Estado</th>
                              <th class="px-4 py-3">IVA</th>
                              <th class="px-4 py-3">Fecha</th>
                              <th class="px-4 py-3">Acciones</th>
                            </tr>
                          </thead>
                          <tbody class="bg-white divide-y">
                            <?php if(!empty($budgets)):?>
                                <?php foreach($budgets as $key => $budget):?>
                                    <tr class="text-gray-700 <?php echo $key%2 ? 'bg-gray-300' : 'bg-gray-100' ?>">
                                      <td class="px-4 py-3 text-sm">
                                        <?php echo $budget->idBudget;?>
                                      </td>
                                      <td class="px-4 py-3">
                                        <div class="flex items-center text-sm">
                                          <div>
                                            <p class="font-semibold"><?php echo $budget->client_name;?></p>
                                            <p class="text-xs text-gray-600">
                                              <?php echo $budget->client_idNum;?>
                                            </p>
                                          </div>
                                        </div>
                                      </td>
                                      <td class="px-4 py-3 text-sm">
                                        <?php echo $budget->vendor_name;?>
                                      </td>
                                      <td class="px-4 py-3 text-xs">
                                        <?php echo $budget->store_name;?>
                                      </td>
                                      <td class="px-4 py-3">
                                        $ <?php echo number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $budget->total)), 2);//$budget->total;?>
                                      </td>
                                      <td class="px-4 py-3 text-sm">
                                        <?php switch ($budget->state) {
                                          case 0:?>
                                            <span class="px-2 py-1 font-semibold leading-tight text-red-700 bg-red-100 rounded-full dark:text-red-100 dark:bg-red-700">
                                              Pendiente
                                            </span>
                                           <?php break;
                                           case 1:?>
                                            <span class="px-2 py-1 font-semibold leading-tight text-green-700 bg-green-100 rounded-full dark:bg-green-700 dark:text-green-100">
                                              Aprobado
                                            </span>
                                           <?php break;
                                          
                                          default:?>
                                            <span class="px-2 py-1 font-semibold leading-tight text-gray-700 bg-gray-100 rounded-full dark:text-gray-100 dark:bg-gray-700">
                                              Desconocido
                                            </span>
                                           <?php break;
                                        } ?>
                                      </td>
                                      <td class="px-4 py-3">
                                        <div class="flex items-center text-sm">
                                          <div>
                                            <p class="">
                                              <?php if($budget->hasIva): ?>
                                              <svg class="w-6 h-6 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                              <?php else: ?>
                                                <svg class="w-6 h-6 text-red-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                              <?php endif; ?></p>
                                            <p class="text-xs text-gray-600">
                                              <?php echo (in_array($role, [1])) ? $budget->iva.'%' : '';?>
                                            </p>
                                          </div>
                                        </div>
                                      </td>
                                      <td class="px-4 py-3 text-xs">
                                        <?php echo $budget->date;?>
                                      </td>
                                      <td class="px-4 py-3">
                                        <div class="flex items-center space-x-4 text-sm">
                                          <button value="<?php echo $budget->idBudget;?>" class="btn-view-budget flex items-center justify-between px-2 py-2 text-sm font-medium leading-5 text-mam-blue-dark rounded-lg focus:outline-none focus:shadow-outline-gray" aria-label="Edit">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                          </button>
                                          <a href="<?php echo base_url()?>sisvent/commercial/budgets/edit/<?php echo $budget->idBudget;?>" class="flex items-center justify-between px-2 py-2 text-sm font-medium leading-5 text-mam-blue-dark rounded-lg focus:outline-none focus:shadow-outline-gray" aria-label="Edit">
                                            <svg class="w-5 h-5" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">
                                              <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"></path>
                                            </svg>
                                          </a>
                                           <?php if(in_array($role, [1])): ?>
                                          <a href="<?php echo base_url()?>sisvent/commercial/budgets/delete/<?php echo $budget->idBudget;?>" class="flex items-center justify-between px-2 py-2 text-sm font-medium leading-5 text-mam-blue-dark rounded-lg focus:outline-none focus:shadow-outline-gray" onclick="showSureModal(event,this)" aria-label="Delete">
                                            <svg class="w-5 h-5" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">
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