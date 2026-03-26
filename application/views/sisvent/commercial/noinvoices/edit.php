<?php
defined('BASEPATH') OR exit('No direct script access allowed');

    //$permissions = $this->session->userdata('user_data')['permissions'];
    $role = $this->session->userdata('user_data')['role'];
    //$showAdmin = (!empty($permissions) && ($permissions['2']['read'] || $permissions['3']['read']));
    $url_params = createFullParamsLinks($page, $pstore, $pvendor, $pstate, $pclient, $ps );
?>
<!DOCTYPE html>
<html lang="en">
    <title>Factura</title>
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
                        Editar Factura 2020
                    </h2>

                    <div class="flex flex-col flex-wrap mb-8 space-y-4 md:flex-row md:items-end md:space-x-4">
                        <?php if(in_array($role, [1])): ?>
                            <a href="<?php echo base_url();?>sisvent/commercial/noinvoices<?php echo $url_params; ?>"  class="flex items-center justify-between px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-petroleo border border-transparent rounded-lg active:bg-mam-blue-petroleo hover:bg-mam-blue-petroleo focus:outline-none focus:shadow-outline-mam-blue-petroleo">
                              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                              <span>Volver</span>
                            </a>
                        <?php endif; ?>
                    </div>

                    <form id="new-noinvoice-form" action="<?php echo base_url();?>sisvent/commercial/noinvoices/update<?php echo $url_params; ?>" method="POST">
                      <?php if($this->session->flashdata("error")):?>
                          <div class="flex items-center p-4 mb-8 text-sm font-semibold text-white bg-red-600 rounded-lg shadow-md">
                              <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                              <p><?php echo $this->session->flashdata("error"); ?></p>
                           </div>
                      <?php endif;?>
                      <div class="px-4 py-3 mb-8 bg-white rounded-lg shadow-md">
                        <input class="form-input" type="hidden" name="id" value="<?php echo $invoice->idInvoice;?>" readonly/>

                        <label class="block text-sm mt-4 <?php echo !empty(form_error('if_id')) ? 'border-red-600':'';?>">
                          <span class="text-gray-700">Id Factusol</span>
                          <input class="form-input" type="number" name="if_id" value="<?php echo !empty(form_error('if_id')) ? set_value('if_id') : $invoice->if_id;?>"/>
                          <?php echo form_error("if_id","<span class='text-xs text-red-600'>","</span>");?>
                        </label>

                        <label class="block mt-4 text-sm">
                          <span class="text-gray-700">
                            Vendedor
                          </span>
                          <input class="form-input" type="hidden" name="vendor" value="<?php echo $invoice->vendorId;?>" readonly/>
                          <input class="form-input" type="text" value="<?php echo $invoice->vendor_name;?>" disabled/>
                        </label>

                        <div class="flex flex-row gap-4">
                          <div class="flex-1 mt-4 text-sm col-span-12 sm:col-span-6">
                            <span class="text-gray-700">
                              Cliente
                            </span>
                            <input class="form-input" type="hidden" id="budget-client-id" name="client" value="<?php echo $invoice->clientId;?>" readonly/>
                            <input class="form-input" type="text" id="budget-client" value="<?php echo $invoice->client_name;?>" disabled/>
                          </div>
                        </div>

                        <label class="block mt-4 text-sm">
                          <span class="text-gray-700">
                            Almacén
                          </span>
                          <input class="form-input" type="hidden" name="store" value="<?php echo $invoice->storeId;?>" readonly/>
                          <input class="form-input" type="text" value="<?php echo $invoice->store_name;?>" disabled/>
                        </label>

                        <!--label class="flex items-center mt-4 dark:text-gray-400">
                          <input  type="hidden" id="hasiva-field" name="hasIva" value="<?php echo $invoice->hasIva;?>" readonly/>
                          <input id="budget-tax" type="checkbox" class="text-mam-blue-petroleo form-checkbox focus:border-mam-blue-petroleo focus:outline-none focus:shadow-outline-mam-blue-petroleo" <?php echo $invoice->hasIva ? 'checked':''; ?> disabled/>
                          <span class="ml-2">IVA</span>
                        <?php if(in_array($role, [1])): ?>
                          <! --input id="budget-tax-value" class='form-input <?php echo $invoice->hasIva ? '' : 'hidden'  ?> ml-8 small w-16' type='number' min='1' max='100' name='iva' value='<?php echo $invoice->iva;?>'- - >
                        <?php endif; ?>
                        </label-->

                        <!--label class="block mt-4 text-sm">
                          <span class="text-gray-700">
                            IVA
                          </span>
                          <select id="hasiva-field" name="hasIva" class="form-input form-select" required>
                            <option value="0" <?php echo !$invoice->hasIva ? 'selected' : '';?>>Remisión</option>
                            <option value="1" <?php echo $invoice->hasIva ? 'selected' : '';?>>IVA</option>
                          </select>
                        </label-->

                        <label class="block mt-4 text-sm">
                          <span class="text-gray-700">
                            Fecha
                          </span>
                          <input id="datepicker" class="form-input font-bold" type="text" name="date" value="<?php echo date("d-m-Y", strtotime($invoice->date));?>" required/>
                          
                        </label>

                        <label class="block text-sm mt-4">
                          <span class="text-gray-700">Observaciones</span>
                          <textarea class="form-input" name="comments"><?php echo set_value('comments',$invoice->comments); ?></textarea>
                        </label>

                        <label class="block text-sm mt-4 <?php echo !empty(form_error('discount')) ? 'border-red-600':'';?>">
                          <span class="text-gray-700">Descuento</span>
                          <input class="form-input" type="number" name="discount" value="<?php echo !empty(form_error('discount')) ? set_value('discount') : $invoice->discount;?>"/>
                          <?php echo form_error("discount","<span class='text-xs text-red-600'>","</span>");?>
                        </label>

                        <label class="flex flex-row text-xl mt-4">
                          <span class="form-input nb font-bold w-16">Total $</span>
                          <input class="form-input nb font-bold" type="number" name="total" value="<?php echo !empty(form_error('total')) ? set_value('total') : $invoice->total;?>" />
                        </label>

                        <div class="block text-sm mt-4">
                            <input type="submit" class="px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-petroleo border border-transparent rounded-lg active:bg-mam-blue-petroleo hover:bg-mam-blue-petroleo focus:outline-none focus:shadow-outline-mam-blue-petroleo" value="Guardar">
                        </div>
                      </div>
                    </form>

              </div>
          </main>
        </div>
    </div>
    <?php $this->load->view('sisvent/layouts/footer'); ?>

  </body>
</html>