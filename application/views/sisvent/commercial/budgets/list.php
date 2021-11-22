<?php
defined('BASEPATH') OR exit('No direct script access allowed');

    //$permissions = $this->session->budgetdata('budget_data')['permissions'];
    $role = $this->session->userdata('user_data')['role'];
    //$showAdmin = (!empty($permissions) && ($permissions['2']['read'] || $permissions['3']['read']));
    $url_params = createFullParamsLinks($page, $pstore, $pvendor, $pstate, $pclient, $piva );

?>
<!DOCTYPE html>
<html lang="en">
    <title>Presupuestos <?php echo $strname; ?></title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<head>
<?php if(isset(($removels)) && $removels): ?>
  <script>
      localStorage.setItem("budget", null);
  </script>
<?php endif; ?>
</head>
  <body>
    <div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
    	<?php $this->load->view('sisvent/layouts/sidebar',array('thisFile' => $_ci_view,'role' => $role)); ?>

    	 <div class="flex flex-col flex-1 w-full">
    		<?php $this->load->view('sisvent/layouts/navbar'); ?>
    	 	<main class="h-full">
    	 		<div class="px-6 mx-auto grid">
                    <h2 class="mb-4 text-lg font-semibold text-gray-600 mt-2">
                        Presupuestos
                    </h2>
                    <div class="flex flex-col flex-wrap mb-8 space-y-4 md:flex-row md:items-end md:space-x-4">
                        <?php //if(in_array($role, [1])): ?>
                            <a href="<?php echo base_url();?>sisvent/commercial/budgets/add"  class="flex items-center justify-between px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg active:bg-mam-blue-dark hover:bg-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark">
                              <span>Nuevo Presupuesto</span>
                              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                            </a>
                            <?php //if(in_array($role, [1])): ?>
                            <a href="<?php echo base_url();?>sisvent/business/clients/add"  class="flex items-center justify-between px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg active:bg-mam-blue-dark hover:bg-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark">
                              <span>Agregar Cliente</span>
                              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                            </a>
                            <?php //endif; ?>
                        <?php // endif; ?>
                        <a href="<?php echo base_url();?>sisvent/commercial/invoices"  class="flex items-center justify-between px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg active:bg-mam-blue-dark hover:bg-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark">
                          <span>Facturas</span>
                        </a>
                        
                        <div class="flex-1"></div>
                        <?php if(strpos(uri_string(), 'search') !== false): ?>
                        <a href="<?php echo base_url();?>sisvent/commercial/budgets<?php echo $url_params ?>"  class="flex items-center justify-between px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg active:bg-mam-blue-dark hover:bg-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark">
                          <span>Volver</span>
                        </a>
                        <?php endif; ?>
                        <label class="block my-4 text-sm">
                          <div class="relative text-gray-500 focus-within:text-purple-600">
                            <input class="form-input-lg inline w-1/2" data-params="<?php echo $url_params ?>" type="text" id="budgets-search" placeholder="Buscar presupuesto"/>
                            <button id="btn-search-budget" class="form-input-lg inline flex items-center justify-between inset-y-0 px-4 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg focus:outline-none" type="button" value="" onclick=""/>
                              <svg class="w-6 h-6 inline" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                              </svg>
                              <span class="inline pr-4">Buscar</span>
                            </button>
                          </div>
                        </label>
                    </div>
                    <div class="flex flex-col flex-wrap mb-8 space-y-4 md:flex-row md:items-end md:space-x-4">
                      <label class="block mt-4 text-sm">
                        <span class="text-gray-700">
                          Filtrar por Almacén
                        </span>
                        <select id="filter-store" class="form-input form-select">
                              <option value="Todos" <?php echo set_select("store",$pstore,'all'==$pstore);?>>Todos</option>
                          <?php foreach($stores as $store):?>
                              <option value="<?php echo $store->idStore?>" <?php echo set_select("store",$pstore,$store->idStore==$pstore);?>><?php echo $store->name;?></option>
                          <?php endforeach;?>
                        </select>
                      </label>
                      <label class="block mt-4 text-sm">
                        <span class="text-gray-700">
                          Filtrar por Vendedor
                        </span>
                        <select id="filter-vendor" class="form-input form-select">
                              <option value="Todos" <?php echo set_select("vendor",$pvendor,'all'==$pvendor);?>>Todos</option>
                          <?php foreach($vendors as $vendor):?>
                              <option value="<?php echo $vendor->idUser?>" <?php echo set_select("vendor",$pvendor,$vendor->idUser==$pvendor);?>><?php echo $vendor->name;?></option>
                          <?php endforeach;?>
                        </select>
                      </label>
                      <label class="block mt-4 text-sm">
                        <span class="text-gray-700">
                          Filtrar por Estado
                        </span>
                        <select id="filter-state" class="form-input form-select">
                              <option value="Todos" <?php echo ($pstate=='all') ? 'selected' : '';?>>Todos</option>
                              <option value="0" <?php echo ($pstate!='all' && $pstate==0) ? 'selected' : '';?>>Pendiente</option>
                              <option value="1" <?php echo ($pstate==1) ? 'selected' : '';?>>Aprobado</option>                          
                        </select>
                      </label>
                      <label class="block mt-4 text-sm">
                        <span class="text-gray-700">
                          Filtrar por Cliente
                        </span>
                        <select id="filter-client" class="form-input form-select">
                              <option value="Todos" <?php echo set_select("client",$pclient,'all'==$pclient);?>>Todos</option>
                          <?php foreach($clients as $client):?>
                              <option value="<?php echo $client->idClient?>" <?php echo set_select("client",$pclient,$client->idClient==$pclient);?>><?php echo $client->name;?></option>
                          <?php endforeach;?>
                        </select>
                      </label>
                       </label>
                      <label class="block mt-4 text-sm">
                        <span class="text-gray-700">
                          Filtrar por IVA
                        </span>
                        <select id="filter-iva" class="form-input form-select">
                              <option value="Todos" <?php echo ($piva=='all') ? 'selected' : '';?>>Todos</option>
                              <option value="0" <?php echo ($piva!='all' && $piva==0) ? 'selected' : '';?>>Remisión</option>
                              <option value="1" <?php echo ($piva==1) ? 'selected' : '';?>>IVA</option>
                        </select>
                      </label>
                    </div>
                    <div class="w-full overflow-hidden rounded-lg shadow-xs">
                      <div class="w-full overflow-x-auto overflow-y-hidden">
                        <table class="w-full whitespace-no-wrap mt-8 lg:mt-0">
                          <thead>
                            <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                              <th class="px-4 py-3 hidden lg:table-cell">Id</th>
                              <th class="px-4 py-3 hidden lg:table-cell">Cliente</th>
                              <th class="px-4 py-3 hidden lg:table-cell">Vendedor</th>
                              <th class="px-4 py-3 hidden lg:table-cell">Almacen</th>
                              <th class="px-4 py-3 hidden lg:table-cell">Valor</th>
                              <th class="px-4 py-3 hidden lg:table-cell">Estado</th>
                              <th class="px-4 py-3 hidden lg:table-cell">IVA</th>
                              <th class="px-4 py-3 hidden lg:table-cell">Fecha</th>
                              <th class="px-4 py-3 hidden lg:table-cell">Observaciones</th>
                              <th class="px-4 py-3 hidden lg:table-cell">Acciones</th>
                            </tr>
                          </thead>
                          <tbody id="tborders" class="bg-white divide-y">
                            <?php if(!empty($budgets)):?>
                                <?php foreach($budgets as $key => $budget):?>
                                    <tr class="text-gray-700 <?php echo $key%2 ? 'bg-gray-300' : 'bg-gray' ?> flex lg:table-row flex-row lg:flex-row flex-wrap lg:flex-no-wrap mb-10 lg:mb-0">
                                      <td class="px-4 py-3 w-full lg:w-auto block lg:table-cell relative lg:static text-sm">
                                        <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Id</span>
                                        <?php echo $budget->idBudget;?>
                                      </td>
                                      <td class="px-4 py-3 w-full lg:w-auto block lg:table-cell relative lg:static">
                                        <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Cliente</span>
                                        <div class="flex items-center text-sm whitespace-normal">
                                          <div>
                                            <p class="font-semibold whitespace-normal"><?php echo $budget->client_name;?></p>
                                            <p class="text-xs text-gray-600">
                                              <?php echo $budget->client_idNum;?>
                                            </p>
                                          </div>
                                          <?php if($budget->client_new): ?>
                                          <div>
                                          <p class="tooltip">
                                             <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" /></svg><span class="tooltip-text bg-blue-200 p-3 -mt-6 -ml-6 rounded">Cliente nuevo</span></p>
                                          </div>
                                          <?php endif; ?>
                                          <button value="<?php echo $budget->clientId;?>" class="btn-view-client flex items-center justify-between px-2 py-2 text-sm font-medium leading-5 text-mam-blue-dark rounded-lg focus:outline-none focus:shadow-outline-gray" aria-label="View">
                                            <p class="tooltip"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg><span class="tooltip-text bg-blue-200 p-3 -mt-6 -ml-6 rounded">Ver</span></p>
                                          </button>
                                        </div>
                                      </td>
                                      <td class="px-4 py-3 w-full lg:w-auto block lg:table-cell relative lg:static text-sm whitespace-normal">
                                        <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Vendedor</span>
                                        <?php echo $budget->vendor_name;?>
                                      </td>
                                      <td class="px-4 py-3 w-full lg:w-auto block lg:table-cell relative lg:static text-xs whitespace-normal">
                                        <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Almacén</span>
                                        <?php echo $budget->store_name;?>
                                      </td>
                                      <td class="px-4 py-3 w-full lg:w-auto block lg:table-cell relative lg:static">
                                        <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Valor</span>
                                        $ <?php echo number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $budget->total)), 2);//$budget->total;?>
                                      </td>
                                      <td class="px-4 py-3 w-full lg:w-auto block lg:table-cell relative lg:static text-sm">
                                        <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Estado</span>
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
                                      <td class="px-4 py-3 w-full lg:w-auto block lg:table-cell relative lg:static">
                                        <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">IVA</span>
                                        <div class="flex flex-col items-center text-sm">
                                          <div>
                                            <p class="">
                                              <?php if($budget->hasIva): ?>
                                              <svg class="w-6 h-6 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                              <?php else: ?>
                                                <svg class="w-6 h-6 text-red-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                              <?php endif; ?></p>
                                          </div>
                                          <div>
                                              <?php if($budget->e_commerce): ?>
                                            <p class="tooltip">
                                             <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" /></svg><span class="tooltip-text bg-blue-200 p-3 -mt-6 -ml-6 rounded">Venta por E-commerce</span></p>
                                              <?php endif; ?>
                                          </div>
                                        </div>
                                      </td>
                                      <td class="px-4 py-3 w-full lg:w-auto block lg:table-cell relative lg:static text-xs whitespace-normal">
                                        <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Fecha</span>
                                        <?php echo date("d-m-Y H:m:s", strtotime($budget->date));//$budget->date;?>
                                      </td>
                                      <td class="px-4 py-3 w-full lg:w-auto block lg:table-cell relative lg:static text-xs max-w-2xl whitespace-normal">
                                        <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Observ.</span>
                                        <?php echo $budget->comments;?>
                                      </td>
                                      <td class="px-4 py-3 w-full lg:w-auto block lg:table-cell relative lg:static">
                                        <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Acciones</span>
                                        <div class="flex items-center space-x-4 text-sm">
                                          <button value="<?php echo $budget->idBudget;?>" class="btn-view-budget flex items-center justify-between px-2 py-2 text-sm font-medium leading-5 text-mam-blue-dark rounded-lg focus:outline-none focus:shadow-outline-gray" aria-label="View">
                                            <p class="tooltip"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg><span class="tooltip-text bg-blue-200 p-3 -mt-6 -ml-6 rounded">Ver</span></p>
                                          </button>
                                          <a href="<?php echo base_url()?>sisvent/commercial/budgets/duplicate/<?php echo $budget->idBudget.$url_params;?>" class="flex items-center justify-between px-2 py-2 text-sm font-medium leading-5 text-mam-blue-dark rounded-lg focus:outline-none focus:shadow-outline-gray" aria-label="Duplicate">
                                            <p class="tooltip"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg><span class="tooltip-text bg-blue-200 p-3 -mt-6 -ml-6 rounded">Duplicar</span></p>
                                          </a>
                                          <?php if($budget->state == 0): ?>
                                          <?php if(!in_array($role, [4])): ?>
                                          <a href="<?php echo base_url()?>sisvent/commercial/budgets/edit/<?php echo $budget->idBudget.$url_params;?>" class="flex items-center justify-between px-2 py-2 text-sm font-medium leading-5 text-mam-blue-dark rounded-lg focus:outline-none focus:shadow-outline-gray" aria-label="Edit">
                                            <p class="tooltip"><svg class="w-5 h-5" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">
                                              <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"></path>
                                            </svg><span class="tooltip-text bg-blue-200 p-3 -mt-6 -ml-6 rounded">Editar</span></p>
                                          </a>
                                          <?php endif; ?>
                                          <?php if(in_array($role, [1,2])): ?>
                                          <a href="<?php echo base_url()?>sisvent/commercial/budgets/approve/<?php echo $budget->idBudget.$url_params;?>" class="flex items-center justify-between px-2 py-2 text-sm font-medium leading-5 text-mam-blue-dark rounded-lg focus:outline-none focus:shadow-outline-gray" onclick="showSureModal(event,this,'¿Está seguro que desea aprobar este presupuesto?')" aria-label="Approve">
                                            <p class="tooltip"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg><span class="tooltip-text bg-blue-200 p-3 -mt-6 -ml-6 rounded">Facturar</span></p>
                                          </a>
                                          
                                          <?php endif; ?>
                                          <?php endif; ?>
                                          <?php if(in_array($role, [1])): ?>
                                          <a href="<?php echo base_url()?>sisvent/commercial/budgets/delete/<?php echo $budget->idBudget;?>" class="flex items-center justify-between px-2 py-2 text-sm font-medium leading-5 text-mam-blue-dark rounded-lg focus:outline-none focus:shadow-outline-gray" onclick="showSureModal(event,this)" aria-label="Delete">
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
                            <?php echo createLinks($page, $total, createParamsLinks($pstore, $pvendor, $pstate, $pclient, $piva ), $limit) ?>
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