<?php
defined('BASEPATH') OR exit('No direct script access allowed');

    $role = $this->session->userdata('user_data')['role'];
    //$showAdmin = (!empty($permissions) && ($permissions['2']['read'] || $permissions['3']['read']));
?>
<!DOCTYPE html>
<html lang="en">
    <title>Liquidaciones</title>
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
                    <div class="flex items-center justify-between mt-2 mb-4">
                        <h2 class="text-lg font-semibold text-gray-600">Liquidaciones</h2>
                        <a href="<?php echo base_url() ?>sisvent/admin/settlements/history" class="px-3 py-1.5 text-xs font-medium text-mam-blue-petroleo bg-blue-50 hover:bg-blue-100 rounded">Historial &rarr;</a>
                    </div>
                    <div class="w-full overflow-hidden rounded-lg shadow-xs">
                      <div class="w-full overflow-x-auto overflow-y-hidden">
                        <table class="stripped-table w-full whitespace-no-wrap mt-8 lg:mt-0">
                          <thead>
                            <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                              <th class="px-4 py-3 hidden lg:table-cell">Vendedor</th>
                              <th class="px-4 py-3 hidden lg:table-cell">Total</th>
                              <!--th class="px-4 py-3 hidden lg:table-cell">No Pagado</th-->
                              <th class="px-4 py-3 hidden lg:table-cell">Pagado</th>
                              <th class="px-4 py-3 hidden lg:table-cell">Acciones</th>
                            </tr>
                          </thead>
                          <tbody id="tborders" class="bg-white divide-y">
                            <?php if(!empty($settlements)):?>
                                <?php foreach($settlements as $settlement):?>
                                    <tr class="text-gray-700 flex lg:table-row flex-row lg:flex-row flex-wrap lg:flex-no-wrap mb-10 lg:mb-0">
                                      <td class="px-4 py-3 text-sm whitespace-normal w-full lg:w-auto block lg:table-cell relative lg:static">
                                        <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Vendedor</span>
                                        <?php echo $settlement->name;?>
                                      </td>
                                      <td class="px-4 py-3 w-full lg:w-auto block lg:table-cell relative lg:static">
                                        <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Total</span>
                                        <?php echo ($settlement->totalSettlement >= 0 ? '' : '-') ?>$ <?php echo number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $settlement->totalSettlement)), 2);//$settlement->total;?><?php if($settlement->totalalert) echo '<p class="tooltip"><svg class="w-6 h-6" fill="none" stroke="red" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg><span class="tooltip-text bg-blue-200 p-3 -mt-6 -ml-6 rounded">Precios de venta por debajo del Precio Base</span></p>' ?>
                                      </td>
                                      <!--td class="px-4 py-3 w-full lg:w-auto block lg:table-cell relative lg:static">
                                        <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Valor</span>
                                        <?php echo ($settlement->possibleSettlement >= 0 ? '' : '-') ?>$ <?php echo number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $settlement->possibleSettlement)), 2);//$settlement->total;?>
                                      </td-->
                                     <td class="px-4 py-3 w-full lg:w-auto block lg:table-cell relative lg:static">
                                        <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Pagado</span>
                                        <?php echo ($settlement->settlement >= 0 ? '' : '-') ?>$ <?php echo number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $settlement->settlement)), 2);//$settlement->total;?>
                                      </td>
                                      <td class="px-4 py-3 w-full lg:w-auto block lg:table-cell relative lg:static">
                                        <span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Acciones</span>
                                        <div class="flex items-center space-x-4 text-sm">
                                          <?php if($settlement->alert) echo '<p class="tooltip"><svg class="w-6 h-6" fill="none" stroke="red" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg><span class="tooltip-text bg-blue-200 p-3 -mt-6 -ml-6 rounded">Precios de venta por debajo del Precio Base</span></p>' ?>
                                          <button value="<?php echo $settlement->idUser;?>" class="btn-view-settlement flex items-center justify-between px-2 py-2 text-sm font-medium leading-5 text-mam-blue-petroleo rounded-lg focus:outline-none focus:shadow-outline-gray" aria-label="View">
                                            <p class="tooltip"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg><span class="tooltip-text bg-blue-200 p-3 -mt-6 -ml-6 rounded">Ver</span></p>
                                          </button>
                                          <button value="<?php echo $settlement->idUser;?>" class="btn-view-total-settlement flex items-center justify-between px-2 py-2 text-sm font-medium leading-5 text-mam-blue-petroleo rounded-lg focus:outline-none focus:shadow-outline-gray" aria-label="ViewTotal">
                                            <p class="tooltip"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg><span class="tooltip-text bg-blue-200 p-3 -mt-6 -ml-6 rounded">Ver Total</span></p>
                                          </button>
                                          <a href="<?php echo base_url()?>sisvent/admin/settlements/statement/<?php echo urlencode($settlement->idUser);?>" class="flex items-center justify-between px-2 py-2 text-sm font-medium leading-5 text-green-700 rounded-lg focus:outline-none focus:shadow-outline-gray" aria-label="Statement">
                                            <p class="tooltip"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg><span class="tooltip-text bg-blue-200 p-3 -mt-6 -ml-6 rounded">Estado de cuenta</span></p>
                                          </a>
                                          <a href="<?php echo base_url()?>sisvent/admin/settlements/history?vendor=<?php echo urlencode($settlement->idUser);?>" class="flex items-center justify-between px-2 py-2 text-sm font-medium leading-5 text-mam-blue-petroleo rounded-lg focus:outline-none focus:shadow-outline-gray" aria-label="History">
                                            <p class="tooltip"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg><span class="tooltip-text bg-blue-200 p-3 -mt-6 -ml-6 rounded">Historial</span></p>
                                          </a>
                                          <?php if(in_array($role, [1])): ?>
                                          <button value="<?php echo $settlement->idUser;?>" class="btn-view-userlostinvoices flex items-center justify-between px-2 py-2 text-sm font-medium leading-5 text-mam-blue-petroleo rounded-lg focus:outline-none focus:shadow-outline-gray" aria-label="View">
                                            <p class="tooltip"><svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="red" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" /></svg><span class="tooltip-text bg-blue-200 p-3 -mt-6 -ml-6 rounded">F. Perdidas</span></p>
                                          </button>
                                           <?php endif; ?>
                                          <?php if($settlement->settlement != 0): ?>
                                          <a href="<?php echo base_url()?>sisvent/admin/settlements/calculate/<?php echo $settlement->idUser;?>" class="flex items-center justify-between px-2 py-2 text-sm font-medium leading-5 text-blue-600 rounded-lg focus:outline-none focus:shadow-outline-gray" onclick="showSureModal(event,this,'Se va a calcular la liquidación. Después podrás revisarla y pagar o descartar.')" aria-label="Calcular">
                                            <p class="tooltip"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg><span class="tooltip-text bg-blue-200 p-3 -mt-6 -ml-6 rounded">Calcular</span></p>
                                          </a>
                                          <a href="<?php echo base_url()?>sisvent/admin/settlements/approve/<?php echo $settlement->idUser;?>" class="flex items-center justify-between px-2 py-2 text-sm font-medium leading-5 text-mam-blue-petroleo rounded-lg focus:outline-none focus:shadow-outline-gray" onclick="showSureModal(event,this,'¿Está seguro que desea liquidar y pagar directamente? (saltea revisión)')" aria-label="Approve">
                                            <p class="tooltip"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg><span class="tooltip-text bg-blue-200 p-3 -mt-6 -ml-6 rounded">Liquidar directo</span></p>
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