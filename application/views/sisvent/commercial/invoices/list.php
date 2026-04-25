<?php
defined('BASEPATH') OR exit('No direct script access allowed');

    //$permissions = $this->session->invoicedata('invoice_data')['permissions'];
    $role = $this->session->userdata('user_data')['role'];
    //$showAdmin = (!empty($permissions) && ($permissions['2']['read'] || $permissions['3']['read']));

    $now = date("d-m-Y");
    $url_params = createFullParamsLinks($page, $pstore, $pvendor, $pstate, $pclient, $piva, $ps );
    $url_params_search = createFullParamsLinks($page, $pstore, $pvendor, $pstate, $pclient, $piva );
    $todayMin3M = date( "Y-m-d H:i:s", strtotime('-2 months'));
?>
<!DOCTYPE html>
<html lang="en">
    <title>Facturas <?php echo $strname; ?></title>
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
                        Facturas
                    </h2>
                    <div class="flex flex-col flex-wrap mb-8 space-y-4 md:flex-row md:items-end md:space-x-4">
                        <a href="<?php echo base_url();?>sisvent/commercial/budgets"  class="flex items-center justify-between px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-petroleo border border-transparent rounded-lg active:bg-mam-blue-petroleo hover:bg-mam-blue-petroleo focus:outline-none focus:shadow-outline-mam-blue-petroleo">
                          <span>Presupuestos</span>
                        </a>
                        <?php if(in_array($role, [1, 2])): ?>
                          <a href="<?php echo base_url();?>sisvent/commercial/invoices/export"  class="flex items-center justify-between px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-petroleo border border-transparent rounded-lg active:bg-mam-blue-petroleo hover:bg-mam-blue-petroleo focus:outline-none focus:shadow-outline-mam-blue-petroleo">
                            <span>Excel</span>
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                          </a>
                          <a href="<?php echo base_url();?>sisvent/commercial/invoices/exportFacCompra"  class="flex items-center justify-between px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg active:bg-mam-blue-dark hover:bg-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark">
                            <span>Excel Fac. Compras</span>
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                          </a>
                          <a href="<?php echo base_url();?>sisvent/commercial/invoices/exportRem"  class="flex items-center justify-between px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg active:bg-mam-blue-dark hover:bg-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark">
                            <span>Excel REM</span>
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                          </a>
                          <!--a href="<?php echo base_url();?>sisvent/commercial/invoices/validate"  class="flex items-center justify-between px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg active:bg-mam-blue-dark hover:bg-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark">
                            <span>Validar</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>
                          </a-->
                        <?php endif; ?>
                        <div class="flex-1"></div>
                      <?php if(strpos(uri_string(), 'search') !== false): ?>
                        <a href="<?php echo base_url();?>sisvent/commercial/invoices<?php echo $url_params_search ?>"  class="flex items-center justify-between px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-petroleo border border-transparent rounded-lg active:bg-mam-blue-petroleo hover:bg-mam-blue-petroleo focus:outline-none focus:shadow-outline-mam-blue-petroleo">
                          <span>Volver</span>
                        </a>
                        <?php endif; ?>
                        <label class="block my-4 text-sm">
                          <div class="relative text-gray-500 focus-within:text-purple-600">
                            <input class="form-input-lg inline w-1/2" data-params="<?php echo $url_params_search ?>" type="text" id="invoices-search" placeholder="Buscar factura" value="<?php echo $ps; ?>"/>
                            <button id="btn-search-invoice" class="form-input-lg inline flex items-center justify-between inset-y-0 px-4 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-petroleo border border-transparent rounded-lg focus:outline-none" type="button" value="" onclick=""/>
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
                              <option value="1" <?php echo ($pstate==1) ? 'selected' : '';?>>Parcial</option>
                              <option value="2" <?php echo ($pstate==2) ? 'selected' : '';?>>Pagada</option>
                              <option value="3" <?php echo ($pstate==3) ? 'selected' : '';?>>Liquidada</option>
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
                              <th class="px-4 py-3 hidden lg:table-cell">Almacén</th>
                              <th class="px-4 py-3 hidden lg:table-cell">Valor</th>
                              <th class="px-4 py-3 hidden lg:table-cell">Estado</th>
                              <th class="px-4 py-3 hidden lg:table-cell">IVA</th>
                              <th class="px-4 py-3 hidden lg:table-cell">Guía</th>
                              <th class="px-4 py-3 hidden lg:table-cell">Fecha</th>
                              <th class="px-4 py-3 hidden lg:table-cell">Días F.</th>
                              <th class="px-4 py-3 hidden lg:table-cell">Observaciones</th>
                              <th class="px-4 py-3 hidden lg:table-cell">Acciones</th>
                            </tr>
                          </thead>
                          <tbody id="tborders" class="bg-white divide-y">
                            <?php if(!empty($invoices)):?>
                                <?php foreach($invoices as $key => $invoice):?>
                                    <tr class="text-gray-700 <?php echo ($invoice->date < $todayMin3M && ($invoice->state == 0 || $invoice->state == 1)) ? 'bg-red-300' : ($key%2 ? 'bg-gray-300' : 'bg-gray') ?> flex lg:table-row flex-row lg:flex-row flex-wrap lg:flex-no-wrap mb-10 lg:mb-0">
                                      <td class="px-4 py-3 text-sm w-full lg:w-auto block lg:table-cell relative lg:static">
                                        <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Id</span>
                                        <?php echo $invoice->idInvoice;?>
                                      </td>
                                      <td class="px-4 py-3 w-full lg:w-auto block lg:table-cell relative lg:static">
                                        <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Cliente</span>
                                        <div class="flex items-center text-sm whitespace-normal">
                                          <div>
                                            <p class="font-semibold whitespace-normal"><?php echo $invoice->client_name;?></p>
                                            <p class="text-xs text-gray-600">
                                              <?php echo $invoice->client_idNum;?>
                                            </p>
                                          </div>
                                          <?php if(isset($invoice->client_new) && $invoice->client_new): ?>
                                          <div>
                                          <p class="tooltip">
                                             <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" /></svg><span class="tooltip-text bg-blue-200 p-3 -mt-6 -ml-6 rounded">Cliente nuevo</span></p>
                                          </div>
                                          <?php endif; ?>
                                          <button value="<?php echo $invoice->clientId;?>" class="btn-view-client flex items-center justify-between px-2 py-2 text-sm font-medium leading-5 text-mam-blue-petroleo rounded-lg focus:outline-none focus:shadow-outline-gray" aria-label="View">
                                            <p class="tooltip"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg><span class="tooltip-text bg-blue-200 p-3 -mt-6 -ml-6 rounded">Ver</span></p>
                                          </button>
                                        </div>
                                      </td>
                                      <td class="px-4 py-3 text-sm whitespace-normal w-full lg:w-auto block lg:table-cell relative lg:static">
                                        <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Vendedor</span>
                                        <?php echo $invoice->vendor_name;?>
                                      </td>
                                      <td class="px-4 py-3 text-xs whitespace-normal w-full lg:w-auto block lg:table-cell relative lg:static">
                                        <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Almacén</span>
                                        <?php echo $invoice->store_name;?>
                                      </td>
                                      <td class="px-4 py-3 w-full lg:w-auto block lg:table-cell relative lg:static">
                                        <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Valor</span>
                                        $ <?php echo number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $invoice->total)), 2);//$invoice->total;?>
                                        <?php if($invoice->discount > 0): ?>
                                        <p class="text-xs w-full text-center text-orange-600">
                                          -$ <?php echo number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $invoice->discount)), 2);//$invoice->payment;?>
                                        </p>
                                        <?php endif; ?>
                                      </td>
                                      <td class="px-4 py-3 text-sm w-full lg:w-auto block lg:table-cell relative lg:static">
                                        <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Estado</span>
                                        <div class="flex flex-row">
                                        <?php if($invoice->printed): ?>
                                          <div class="flex items-center w-6 h-6 justify-between text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-petroleo border border-transparent rounded-full active:bg-mam-blue-petroleo hover:bg-mam-blue-petroleo focus:outline-none focus:shadow-outline-mam-blue-petroleo">
                                            <p class="tooltip m-auto"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg><span class="tooltip-text text-mam-blue-petroleo bg-blue-200 p-3 -mt-6 -ml-6 rounded">Factura Impresa</span></p>
                                          </div>
                                        <?php endif; ?>
                                        <?php if(isset($invoice->transportadora) && $invoice->transportadora && $invoice->transportadora !== 'sin_despacho'):
                                          $tColors = ['interrapidisimo'=>'#FF6B00','coordinadora'=>'#F59E0B','estelar'=>'#8B5CF6','carro_mam'=>'#10B981','moto_mam'=>'#14B8A6','particular'=>'#EAB308','recoge_cliente'=>'#EC4899'];
                                          $tLabels = ['interrapidisimo'=>'Interrapidísimo','coordinadora'=>'Coord.','estelar'=>'Estelar','carro_mam'=>'Carro','moto_mam'=>'Moto','particular'=>'Partic.','recoge_cliente'=>'Recoge'];
                                          $tColor = isset($tColors[$invoice->transportadora]) ? $tColors[$invoice->transportadora] : '#6B7280';
                                          $tLabel = isset($tLabels[$invoice->transportadora]) ? $tLabels[$invoice->transportadora] : $invoice->transportadora;
                                        ?>
                                          <div class="flex items-center w-6 h-6 justify-between text-sm font-medium leading-5 text-white border border-transparent rounded-full" style="background:<?= $tColor ?>;">
                                            <p class="tooltip m-auto"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"></path></svg><span class="tooltip-text p-3 -mt-6 -ml-6 rounded text-white" style="background:<?= $tColor ?>;"><?= $tLabel ?></span></p>
                                          </div>
                                        <?php endif; ?>
                                        <div class="flex flex-col">
                                        <?php switch ($invoice->state) {
                                          case 0:?>
                                            <span class="px-2 py-1 font-semibold leading-tight text-red-700 bg-red-100 rounded-full dark:text-red-100 dark:bg-red-700">
                                              Pendiente
                                            </span>
                                           <?php break;
                                           case 1:?>
                                            <button value="<?php echo $invoice->idInvoice;?>" class="btn-view-invoice-payment px-2 py-1 font-semibold leading-tight text-orange-700 bg-orange-100 rounded-full dark:text-white dark:bg-orange-600">
                                              Parcial
                                            </button>
                                            <p class="text-xs w-full text-center text-gray-600">
                                              $ <?php echo number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $invoice->payment)), 2);//$invoice->payment;?>
                                            </p>
                                           <?php break;
                                           case 2:?>
                                            <span class="px-2 py-1 font-semibold leading-tight text-green-700 bg-green-100 rounded-full dark:bg-green-700 dark:text-green-100">
                                              Pagada
                                            </span>
                                           <?php break;
                                           case 3:?>
                                            <span class="px-2 py-1 font-semibold leading-tight text-green-700 bg-green-100 rounded-full dark:bg-green-700 dark:text-green-100">
                                              Liquidada
                                            </span>
                                           <?php break;
                                          
                                          default:?>
                                            <span class="px-2 py-1 font-semibold leading-tight text-gray-700 bg-gray-100 rounded-full dark:text-gray-100 dark:bg-gray-700">
                                              Desconocido
                                            </span>
                                           <?php break;
                                        } ?>
                                        </div>
                                        </div>
                                      </td>
                                      <td class="px-4 py-3 w-full lg:w-auto block lg:table-cell relative lg:static">
                                        <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">IVA</span>
                                        <div class="flex flex-col items-center text-sm">
                                          <div>
                                            <p class="">
                                              <?php if($invoice->hasIva): ?>
                                              <svg class="w-6 h-6 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                              <?php else: ?>
                                                <svg class="w-6 h-6 text-red-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                              <?php endif; ?></p>
                                          </div>
                                          <div>
                                              <?php if($invoice->e_commerce): ?>
                                            <p class="tooltip">
                                             <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" /></svg><span class="tooltip-text bg-blue-200 p-3 -mt-6 -ml-6 rounded">Venta por E-commerce</span></p>
                                              <?php endif; ?>
                                          </div>
                                        </div>
                                      </td>
                                      <!-- Columna de Envío/Tracking -->
                                      <td class="px-4 py-3 text-xs w-full lg:w-auto block lg:table-cell relative lg:static">
                                        <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Guía</span>
                                        <?php if(!empty($invoice->tracking_number)): ?>
                                          <span class="text-gray-600"><?php echo $invoice->tracking_number; ?></span>
                                        <?php else: ?>
                                          <span class="text-gray-400">—</span>
                                        <?php endif; ?>
                                      </td>
                                      <td class="px-4 py-3 text-xs whitespace-normal w-full lg:w-auto block lg:table-cell relative lg:static">
                                        <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Fecha</span>
                                        <?php echo date("d-m-Y H:m:s", strtotime($invoice->date));// $invoice->date;?>
                                      </td>
                                       <td class="px-4 py-3 text-xs whitespace-normal w-full lg:w-auto block lg:table-cell relative lg:static">
                                        <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Días de factura</span>
                                        <?php //echo $now."   ".date("d-m-Y", strtotime($invoice->date)); ?>
                                        <?php echo date_diff(date_create($now), date_create(date("d-m-Y", strtotime($invoice->date))))->format('%a'); ?>
                                        <?php //echo (($now - date("d-m-Y", strtotime($invoice->date)))/ (60 * 60 * 24));// $invoice->date;?>
                                      </td>
                                      <td class="px-4 py-3 text-xs max-w-2xl whitespace-normal w-full lg:w-auto block lg:table-cell relative lg:static">
                                        <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Observ.</span>
                                        <?php echo $invoice->comments;?>
                                      </td>
                                      <td class="px-4 py-3 w-full lg:w-auto block lg:table-cell relative lg:static">
                                        <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Acciones</span>
                                        <div class="flex items-center space-x-4 text-sm">
                                          <button value="<?php echo $invoice->idInvoice;?>" class="btn-view-invoice flex items-center justify-between px-2 py-2 text-sm font-medium leading-5 text-mam-blue-petroleo rounded-lg focus:outline-none focus:shadow-outline-gray" aria-label="View">
                                            <p class="tooltip"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg><span class="tooltip-text bg-blue-200 p-3 -mt-6 -ml-6 rounded">Ver</span></p>
                                          </button>
                                          <?php if(!in_array($role, [4])): ?>
                                          <a href="<?php echo base_url()?>sisvent/commercial/invoices/edit/<?php echo $invoice->idInvoice.$url_params;?>" class="flex items-center justify-between px-2 py-2 text-sm font-medium leading-5 text-mam-blue-petroleo rounded-lg focus:outline-none focus:shadow-outline-gray" aria-label="Edit">
                                            <p class="tooltip"><svg class="w-5 h-5" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">
                                              <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"></path>
                                            </svg><span class="tooltip-text bg-blue-200 p-3 -mt-6 -ml-6 rounded">Editar</span></p>
                                          </a>
                                          <?php endif; ?>
                                          <?php if(in_array($role, [1])): ?>
                                            <a href="<?php echo base_url()?>sisvent/commercial/invoices/refund/<?php echo $invoice->idInvoice.$url_params;?>" class="flex items-center justify-between px-2 py-2 text-sm font-medium leading-5 text-mam-blue-petroleo rounded-lg focus:outline-none focus:shadow-outline-gray" aria-label="Refund">
                                            <p class="tooltip"><svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3m9 14V5a2 2 0 00-2-2H6a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z" /></svg><span class="tooltip-text bg-blue-200 p-3 -mt-6 -ml-6 rounded">Devolución</span></p>
                                            </a>
                                            <?php if(in_array($invoice->state, [0,1])): ?>
                                            <button value="<?php echo $invoice->idInvoice;?>"  data-params="<?php echo $url_params ?>" class="btn-payment-invoice flex items-center justify-between px-2 py-2 text-sm font-medium leading-5 text-mam-blue-petroleo rounded-lg focus:outline-none focus:shadow-outline-gray" aria-label="Payment">
                                              <p class="tooltip"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg><span class="tooltip-text bg-blue-200 p-3 -mt-6 -ml-6 rounded">Realizar Abono</span></p>
                                            </button>
                                            <?php endif; ?>
                                          <?php endif; ?>
                                          <?php if(in_array($role, [1])): ?>
                                          <a href="<?php echo base_url()?>sisvent/commercial/invoices/delete/<?php echo $invoice->idInvoice;?>" class="flex items-center justify-between px-2 py-2 text-sm font-medium leading-5 text-mam-blue-petroleo rounded-lg focus:outline-none focus:shadow-outline-gray" onclick="showSureModal(event,this)" aria-label="Delete">
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
                            <?php echo createLinks($page, $total, createParamsLinks($pstore, $pvendor, $pstate, $pclient, $piva, $ps ), $limit) ?>
                          </nav>
                        </span>
                      </div>
                    </div>
          </div>
          </main>
        </div>
    </div>
    <?php $this->load->view('sisvent/layouts/footer'); ?>

    <script>
    // === Interrapidísimo Shipping Functions ===
    var shipCiudadNombre = '';
    var shipServicio = null;

    // Limpiar nombre de ciudad: quitar departamento, parentesis, etc.
    function cleanCityName(raw) {
        if (!raw) return '';
        // "VILLAVICENCIO - META" → "VILLAVICENCIO"
        var clean = raw.split('-')[0].split(',')[0].trim();
        // "POMPEYA (VILLAVICENCIO)" → extraer VILLAVICENCIO
        var m = clean.match(/\(([^)]+)\)/);
        if (m) clean = m[1].trim();
        return clean;
    }

    // Auto-resolver ciudad al abrir el modal
    function autoResolveCiudad() {
        // Buscar el modal visible (el que se acaba de abrir)
        var modal = $('.fixed:not(.hidden)').has('#shipCiudad');
        var cityInput = modal.length ? modal.find('#shipCiudad') : $('#shipCiudad:visible');
        if (!cityInput.length) cityInput = $('#shipCiudad').last(); // fallback

        var rawCity = cityInput.data('original-city') || cityInput.val();
        var rawState = (cityInput.data('original-state') || '').toString().toUpperCase().trim();
        var searchTerm = cleanCityName(rawCity);
        if (!searchTerm || searchTerm.length < 2) return;

        $.getJSON('<?= base_url() ?>sisvent/commercial/shipping/buscarCiudad?q=' + encodeURIComponent(searchTerm), function(data) {
            if (data && data.length > 0) {
                var best = data[0];
                // Si hay departamento del cliente, buscar coincidencia exacta
                if (rawState && data.length > 1) {
                    for (var i = 0; i < data.length; i++) {
                        var dept = (data[i].departamento || '').toUpperCase();
                        if (dept.indexOf(rawState) !== -1 || rawState.indexOf(dept) !== -1) {
                            best = data[i];
                            break;
                        }
                    }
                }
                modal.find('#shipCiudadId').val(best.id);
                cityInput.val(best.label);
                shipCiudadNombre = best.label;
                cityInput.css('border-color', '#10B981');
            } else {
                cityInput.val(rawCity);
                cityInput.css('border-color', '#EF4444');
            }
        });
    }

    $(document).on('click', '[onclick*="shippingModal"]', function() {
        // Find the modal that belongs to this invoice's view.php
        var modal = $(this).closest('.relative, div').find('#shippingModal');
        if (!modal.length) modal = $(this).parent().find('#shippingModal');
        if (!modal.length) modal = $(this).closest('tr').length ? $(this).closest('tr').find('#shippingModal') : null;
        // Set active modal context for autoResolve
        window._activeShipModal = modal && modal.length ? modal : null;
        setTimeout(autoResolveCiudad, 300);
        cargarGuiasExistentes();
    });

    // Cargar guías existentes para esta factura
    function cargarGuiasExistentes() {
        var invoiceId = $('#shippingModal').data('invoice-id');
        $.getJSON('<?= base_url() ?>sisvent/commercial/shipping/guiasFactura/' + invoiceId, function(guias) {
            if (!guias || !guias.length) { $('#shipGuiasExistentes').addClass('hidden'); return; }
            $('#shipGuiasExistentes').removeClass('hidden');
            $('#shipPrintAll').attr('href', '<?= base_url() ?>sisvent/commercial/shipping/imprimirGuias/' + invoiceId);
            var h = '';
            $.each(guias, function(i, g) {
                var badge = g.isContrapago == 1
                    ? '<span class="px-1.5 py-0.5 bg-yellow-100 text-yellow-800 rounded text-xs">Pago en casa</span>'
                    : '<span class="px-1.5 py-0.5 bg-green-100 text-green-800 rounded text-xs">Ledxury paga</span>';
                h += '<div class="flex items-center justify-between bg-gray-50 border rounded px-3 py-2 text-xs">';
                h += '<div><span class="font-mono font-bold">' + g.numeroPreenvio + '</span> ' + badge;
                h += ' <span class="text-gray-500 ml-1">' + (g.ciudadDestinoNombre || '') + '</span>';
                h += ' <span class="text-gray-400">$' + Math.round(g.valorTotal).toLocaleString('es-CO') + '</span></div>';
                h += '<div class="flex gap-1">';
                h += '<a href="<?= base_url() ?>sisvent/commercial/shipping/descargarGuia/' + g.numeroPreenvio + '" target="_blank" class="px-2 py-1 bg-blue-600 text-white rounded hover:bg-blue-700" title="Imprimir">PDF</a>';
                h += '<button onclick="eliminarGuia(' + g.id + ', \'' + g.numeroPreenvio + '\')" class="px-2 py-1 bg-red-500 text-white rounded hover:bg-red-600" title="Eliminar">&times;</button>';
                h += '</div></div>';
            });
            $('#shipGuiasList').html(h);
        });
    }

    // Eliminar guía
    function eliminarGuia(guideId, numero) {
        if (!confirm('¿Eliminar guía ' + numero + '? Se revertirá el flete de la factura.')) return;
        var csrf = $('input[name="<?= $this->security->get_csrf_token_name() ?>"]').first().val() || '<?= $this->security->get_csrf_hash() ?>';
        var d = {};
        d['<?= $this->security->get_csrf_token_name() ?>'] = csrf;
        d.guideId = guideId;
        $.post('<?= base_url() ?>sisvent/commercial/shipping/eliminarGuia', d, function(r) {
            if (r.error) { alert(r.error); return; }
            alert(r.mensaje);
            cargarGuiasExistentes();
        }, 'json');
    }

    // Toggle cobro: empresa paga vs contrapago (pago en casa)
    function toggleCobro(val) {
        shipServicio = null;
        $('#shipCotizacion').addClass('hidden');
        $('#btnCrearGuia').addClass('hidden');
        if (val == 'contrapago') {
            $('#contrapagoCostWrap').slideDown(200);
        } else {
            $('#contrapagoCostWrap').slideUp(200);
        }
    }

    // Toggle tipo de entrega: Domicilio vs Oficina
    function toggleTipoEntrega(tipo) {
        shipServicio = null;
        $('#shipCotizacion').addClass('hidden');
        $('#btnCrearGuia').addClass('hidden');
        $('#shipOficinasWrap').addClass('hidden');
        if (tipo == 2) {
            $('#shipDireccionWrap').slideUp(200);
            $('#shipCiudadLabel').text('Ciudad oficina Interrapidísimo');
            $('#shipCiudad').attr('placeholder', 'Ciudad donde recogerá...');
            cargarOficinas();
        } else {
            $('#shipDireccionWrap').slideDown(200);
            $('#shipCiudadLabel').text('Ciudad destino');
            $('#shipCiudad').attr('placeholder', 'Escriba la ciudad...');
        }
    }

    // Cargar oficinas de Interrapidísimo para la ciudad seleccionada
    function cargarOficinas() {
        var modal = $('.fixed:not(.hidden)').has('#shipCiudadId');
        var ciudadId = modal.length ? modal.find('#shipCiudadId').val() : $('#shipCiudadId').val();
        var oficinasWrap = modal.length ? modal.find('#shipOficinasWrap') : $('#shipOficinasWrap');
        var oficinaSelect = modal.length ? modal.find('#shipOficinaSelect') : $('#shipOficinaSelect');
        if (!ciudadId) { oficinasWrap.addClass('hidden'); return; }
        oficinasWrap.removeClass('hidden');
        oficinaSelect.html('<option value="">Cargando oficinas...</option>');
        $.getJSON('<?= base_url() ?>sisvent/commercial/shipping/oficinas?ciudadId=' + encodeURIComponent(ciudadId), function(data) {
            if (!data || !data.length) {
                oficinaSelect.html('<option value="">No hay oficinas disponibles</option>');
                return;
            }
            var h = '<option value="">Seleccione oficina...</option>';
            $.each(data, function(i, o) {
                var label = o.nombre;
                if (o.direccion) label += ' - ' + o.direccion;
                if (o.tipo) label += ' (' + o.tipo + ')';
                h += '<option value="' + o.id + '" data-nombre="' + o.nombre + '" data-dir="' + (o.direccion || '') + '">' + label + '</option>';
            });
            oficinaSelect.html(h);
        }).fail(function() {
            oficinaSelect.html('<option value="">Error al cargar oficinas</option>');
        });
    }

    // Cuando cambia la ciudad, recargar oficinas si está en modo oficina
    $(document).on('click', '.ship-city-opt', function() {
        if ($('input[name="shipTipoEntrega"]:checked').val() == '2') {
            setTimeout(cargarOficinas, 200);
        }
    });

    // Cuando selecciona una oficina, poner la dirección
    $(document).on('change', '#shipOficinaSelect', function() {
        var opt = $(this).find(':selected');
        var nombre = opt.data('nombre') || '';
        var dir = opt.data('dir') || '';
        if (nombre) {
            $('#shipDireccion').val('Reclame en oficina: ' + nombre + (dir ? ' - ' + dir : ''));
        }
    });

    // Reset cotización cuando cambian datos del paquete o tipo de entrega
    $(document).on('input', '#shipPeso, #shipPiezas, #shipLargo, #shipAncho, #shipAlto, #shipValor', function() {
        shipServicio = null;
        $('#shipCotizacion').addClass('hidden');
        $('#btnCrearGuia').addClass('hidden');
    });

    $(document).on('input', '#shipCiudad', function() {
        $('#shipCiudadId').val('');
        $(this).css('border-color', '#D1D5DB');
        shipServicio = null;
        $('#shipCotizacion').addClass('hidden');
        $('#btnCrearGuia').addClass('hidden');
        var q = $(this).val();
        if (q.length < 2) { $('#shipCiudadResults').addClass('hidden'); return; }
        var self = this;
        clearTimeout($(self).data('to'));
        $(self).data('to', setTimeout(function() {
            $.getJSON('<?= base_url() ?>sisvent/commercial/shipping/buscarCiudad?q=' + encodeURIComponent(q), function(data) {
                var h = '';
                $.each(data, function(i, c) {
                    h += '<div class="px-3 py-2 hover:bg-blue-50 cursor-pointer text-sm border-b ship-city-opt" data-id="' + c.id + '" data-label="' + c.label + '">' + c.label + '</div>';
                });
                if (h) $('#shipCiudadResults').removeClass('hidden').html(h);
                else $('#shipCiudadResults').addClass('hidden');
            });
        }, 300));
    });

    $(document).on('click', '.ship-city-opt', function() {
        $('#shipCiudad').val($(this).data('label')).css('border-color', '#10B981');
        $('#shipCiudadId').val($(this).data('id'));
        shipCiudadNombre = $(this).data('label');
        $('#shipCiudadResults').addClass('hidden');
    });

    function cotizarEnvio() {
        var m = getActiveModal();
        var cid = m.find('#shipCiudadId').val();
        if (!cid) { m.find('#shipError').removeClass('hidden').text('Seleccione una ciudad del listado'); return; }
        m.find('#shipError').addClass('hidden');
        m.find('#btnCotizar').prop('disabled', true).text('Cotizando...');
        var tipoEntrega = m.find('input[name="shipTipoEntrega"]:checked').val() || 1;
        var csrf = $('input[name="<?= $this->security->get_csrf_token_name() ?>"]').first().val() || '<?= $this->security->get_csrf_hash() ?>';
        var d = {};
        d['<?= $this->security->get_csrf_token_name() ?>'] = csrf;
        var esContrapago = m.find('input[name="shipCobro"]:checked').val() == 'contrapago';
        d.ciudadDestinoId = cid;
        d.peso = m.find('#shipPeso').val();
        d.numeroPiezas = m.find('#shipPiezas').val();
        d.valorDeclarado = m.find('#shipValor').val();
        d.idTipoEntrega = tipoEntrega;
        d.contrapago = esContrapago ? 1 : 0;
        $.post('<?= base_url() ?>sisvent/commercial/shipping/cotizar', d, function(r) {
            m.find('#btnCotizar').prop('disabled', false).text('Cotizar');
            if (r.error) { m.find('#shipError').removeClass('hidden').text(r.error); console.log('Cotizar debug:', r.debug); return; }
            if (r.servicios && r.servicios.length) {
                shipServicio = r.servicios[0];
                var pzas = parseInt(r.piezas) || 1;
                var unitario = shipServicio.Precio.Valor + shipServicio.Precio.ValorPrimaSeguro;
                var totalFlete = unitario * pzas;
                var tipoLabel = tipoEntrega == 2 ? ' (Reclamar en oficina)' : ' (A domicilio)';
                var cobroLabel = esContrapago ? ' — <span class="text-yellow-700 font-bold">Pago en casa</span>' : ' — <span class="text-green-700">Ledxury paga</span>';
                var h = '<p>' + shipServicio.NombreServicio + tipoLabel + cobroLabel + '</p>';
                if (pzas > 1) {
                    h += '<p class="text-xs">Por caja: $' + Math.round(unitario).toLocaleString('es-CO') + ' × ' + pzas + ' cajas</p>';
                }
                h += '<p class="text-lg font-bold mt-1">Total flete: $' + Math.round(totalFlete).toLocaleString('es-CO') + '</p>';
                h += '<p class="text-xs">Entrega estimada: ' + shipServicio.TiempoEntrega + ' día(s)</p>';
                m.find('#shipCotResult').html(h);
                m.find('#shipCotizacion').removeClass('hidden');
                m.find('#btnCrearGuia').removeClass('hidden');
            }
        }, 'json').fail(function() {
            m.find('#btnCotizar').prop('disabled', false).text('Cotizar');
            m.find('#shipError').removeClass('hidden').text('Error de conexión');
        });
    }

    // Helper: get the visible/active shipping modal
    function getActiveModal() {
        var m = $('#shippingModal:visible');
        if (!m.length) m = $('.fixed:not(.hidden)').has('#shipCiudad');
        if (!m.length) m = $(document);
        return m;
    }

    function crearGuia() {
        if (!shipServicio) return;
        var m = getActiveModal();
        m.find('#btnCrearGuia').prop('disabled', true).text('Generando...');
        m.find('#shipError').addClass('hidden');
        var tipoEntrega = m.find('input[name="shipTipoEntrega"]:checked').val() || 1;
        var csrf = $('input[name="<?= $this->security->get_csrf_token_name() ?>"]').first().val() || '<?= $this->security->get_csrf_hash() ?>';
        var invoiceId = m.find('#shippingModal').data('invoice-id') || m.data('invoice-id');
        var d = {};
        d['<?= $this->security->get_csrf_token_name() ?>'] = csrf;
        d.invoiceId = invoiceId;
        d.ciudadDestinoId = m.find('#shipCiudadId').val();
        d.ciudadDestinoNombre = shipCiudadNombre;
        d.peso = m.find('#shipPeso').val();
        d.largo = m.find('#shipLargo').val();
        d.ancho = m.find('#shipAncho').val();
        d.alto = m.find('#shipAlto').val();
        d.valorDeclarado = m.find('#shipValor').val();
        d.idServicio = shipServicio.IdServicio;
        d.idTipoEnvio = 3;
        d.idTipoEntrega = tipoEntrega;
        d.contrapago = m.find('input[name="shipCobro"]:checked').val() == 'contrapago' ? 1 : 0;
        d.diceContener = 'Mercancía';
        d.numeroPiezas = m.find('#shipPiezas').val();
        d.nombre = m.find('#shipNombre').val();
        d.telefono = m.find('#shipTelefono').val();
        d.direccion = tipoEntrega == 2 ? 'Reclamar en oficina Interrapidísimo - ' + shipCiudadNombre : m.find('#shipDireccion').val();
        d.documento = m.find('#shipDocumento').val();
        d.observaciones = m.find('#shipObs').val();
        $.post('<?= base_url() ?>sisvent/commercial/shipping/crearGuia', d, function(r) {
            if (r.error) {
                m.find('#btnCrearGuia').prop('disabled', false).text('Generar Guía');
                m.find('#shipError').removeClass('hidden').text(r.error);
                console.log('CrearGuia debug:', r.debug);
                return;
            }
            var h = '<p class="font-bold">' + r.mensaje + '</p>';
            h += '<p class="text-xs mt-1">Guías: ' + r.guias.join(', ') + '</p>';
            h += '<div class="flex gap-2 mt-2">';
            h += '<a href="<?= base_url() ?>sisvent/commercial/shipping/imprimirGuias/' + invoiceId + '" target="_blank" class="inline-flex items-center px-3 py-1.5 text-xs font-bold text-white rounded-lg" style="background:#1B365D;">';
            h += '<svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>';
            h += 'Imprimir Guías (' + r.piezas + ')</a>';
            h += '<button onclick="location.reload()" class="px-3 py-1.5 text-xs font-medium border border-gray-300 text-gray-700 bg-white rounded-lg hover:bg-gray-50">Cerrar</button>';
            h += '</div>';
            m.find('#shipCotizacion').removeClass('hidden').removeClass('bg-green-50 border-green-200').addClass('bg-blue-50 border-blue-200');
            m.find('#shipCotResult').html(h);
            m.find('#btnCotizar, #btnCrearGuia').addClass('hidden');
        }, 'json').fail(function() {
            m.find('#btnCrearGuia').prop('disabled', false).text('Generar Guía');
            m.find('#shipError').removeClass('hidden').text('Error de conexión');
        });
    }

    function trackGuia(n) {
        var csrf = $('input[name="<?= $this->security->get_csrf_token_name() ?>"]').first().val() || '<?= $this->security->get_csrf_hash() ?>';
        var d = {};
        d['<?= $this->security->get_csrf_token_name() ?>'] = csrf;
        d.numeroGuia = n;
        $.post('<?= base_url() ?>sisvent/commercial/shipping/tracking', d, function(r) {
            if (r.error) { alert(r.error); return; }
            alert('Estado actualizado');
            location.reload();
        }, 'json');
    }
    </script>
  </body>
</html>