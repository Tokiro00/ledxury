<?php
defined('BASEPATH') OR exit('No direct script access allowed');

    //$permissions = $this->session->userdata('user_data')['permissions'];
    $role = $this->session->userdata('user_data')['role'];
    //$showAdmin = (!empty($permissions) && ($permissions['2']['read'] || $permissions['3']['read']));
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
    	 	<main class="h-full overflow-y-auto">
    	 		<div class="px-6 mx-auto grid">
            <h2 class="mb-4 text-lg font-semibold text-gray-600 mt-2">
                Vales
            </h2>
            <div class="flex flex-col flex-wrap mb-8 space-y-4 md:flex-row md:items-end md:space-x-4">
                
                <a href="<?php echo base_url();?>sisvent/admin/vouchers"  class="flex items-center justify-between px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg active:bg-mam-blue-dark hover:bg-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark">
                  <span>Volver</span>
                </a>
                <div class="flex-1"></div>
                
            </div>
            <div class="flex flex-col flex-wrap mb-8 space-y-4 md:flex-row md:items-end md:space-x-4">
              <label class="block mt-4 text-sm">
                <span class="text-gray-700">
                  Vendedor
                </span>
                <select id="vendor-voucher" class="form-input form-select">
                      <option value="-1" <?php echo set_select("vendor",-1);?>>Selecione Vendedor</option>
                  <?php foreach($vendors as $vendor):?>
                      <option value="<?php echo $vendor->idUser?>" <?php echo set_select("vendor",$vendor->idUser);?>><?php echo $vendor->name;?></option>
                  <?php endforeach;?>
                </select>
              </label>
              <label class="block mt-4 text-sm">
                  <span class="text-gray-700">
                    Desde
                  </span>
                  <input id="datepicker-since" class="form-input font-bold" type="text"/>
                  
                </label>
                <label class="block mt-4 text-sm">
                  <span class="text-gray-700">
                    Hasta
                  </span>
                  <input id="datepicker-until" class="form-input font-bold" type="text"/>
                  
                </label>
                <button id="update-user-voucher" class="px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg active:bg-mam-blue-dark hover:bg-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark disabled:opacity-50">Actualizar</button>
                <button id="export2excel" class="px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg active:bg-mam-blue-dark hover:bg-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark disabled:opacity-50">Excel</button>
            </div>

            <div class="w-full overflow-hidden rounded-lg shadow-xs">
              <div class="w-full overflow-x-auto overflow-y-hidden">
                <table class="w-full whitespace-no-wrap table2excel" data-tableName="Vales">
                  <thead>
                    <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                      <th class="px-4 py-3">Id</th>
                      <th class="px-4 py-3">Vendedor</th>
                      <th class="px-4 py-3">Valor</th>
                      <th class="px-4 py-3">Método</th>
                      <th class="px-4 py-3">Estado</th>
                      <th class="px-4 py-3">Fecha</th>
                      <th class="px-4 py-3">Observaciones</th>
                    </tr>
                  </thead>
                  <tbody id="user-vouchers" class="bg-white divide-y">
                    <?php /*if(!empty($vouchers)):?>
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
                              
                            </tr>
                        <?php endforeach;?>
                    <?php endif; */?>
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