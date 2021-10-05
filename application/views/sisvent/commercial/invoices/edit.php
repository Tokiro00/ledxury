<?php
defined('BASEPATH') OR exit('No direct script access allowed');

    //$permissions = $this->session->userdata('user_data')['permissions'];
    $role = $this->session->userdata('user_data')['role'];
    //$showAdmin = (!empty($permissions) && ($permissions['2']['read'] || $permissions['3']['read']));
    $url_params = createFullParamsLinks($page, $pstore, $pvendor, $pstate, $pclient, $piva, $ps );
?>
<!DOCTYPE html>
<html lang="en">
    <title>Factura</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<head>
<script>
  window.inEditInvoice = true;
</script>
</head>
  <body>
    <div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
      <?php $this->load->view('sisvent/layouts/sidebar',array('thisFile' => $_ci_view,'role' => $role)); ?>

       <div class="flex flex-col flex-1 w-full">
        <?php $this->load->view('sisvent/layouts/navbar'); ?>
        <main class="h-full overflow-y-auto">
          <div class="px-6 mx-auto grid">
                    <h2 class="mb-4 text-lg font-semibold text-gray-600 mt-2">
                        Editar Factura
                    </h2>

                    <div class="flex flex-col flex-wrap mb-8 space-y-4 md:flex-row md:items-end md:space-x-4">
                        <?php if(in_array($role, [1])): ?>
                            <a href="<?php echo base_url();?>sisvent/commercial/invoices<?php echo $url_params; ?>"  class="flex items-center justify-between px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg active:bg-mam-blue-dark hover:bg-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark">
                              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                              <span>Volver</span>
                            </a>
                        <?php endif; ?>
                    </div>

                    <form id="new-budget-form" action="<?php echo base_url();?>sisvent/commercial/invoices/update<?php echo $url_params; ?>" method="POST">
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
                          <input id="budget-tax" type="checkbox" class="text-mam-blue-dark form-checkbox focus:border-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark" <?php echo $invoice->hasIva ? 'checked':''; ?> disabled/>
                          <span class="ml-2">IVA</span>
                        <?php if(in_array($role, [1])): ?>
                          <! --input id="budget-tax-value" class='form-input <?php echo $invoice->hasIva ? '' : 'hidden'  ?> ml-8 small w-16' type='number' min='1' max='100' name='iva' value='<?php echo $invoice->iva;?>'- - >
                        <?php endif; ?>
                        </label-->

                        <label class="block mt-4 text-sm">
                          <span class="text-gray-700">
                            IVA
                          </span>
                          <select id="hasiva-field" name="hasIva" class="form-input form-select" required>
                            <option value="0" <?php echo !$invoice->hasIva ? 'selected' : '';?>>Remisión</option>
                            <option value="1" <?php echo $invoice->hasIva ? 'selected' : '';?>>IVA</option>
                          </select>
                        </label>

                        <?php if(in_array($role, [1])): ?>
                        <label class="flex items-center mt-4 dark:text-gray-400">
                          <input type="checkbox" name="e_commerce" class="text-mam-blue-dark form-checkbox focus:border-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark" <?php echo $invoice->e_commerce ? 'checked':''; ?> />
                          <span class="ml-2">Venta por E-commerce</span>
                        </label>
                        <?php endif; ?>

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
                          <input id="budget-total-val" class="form-input nb font-bold" type="hidden" name="total" value="<?php echo set_value('total');?>" readonly/>
                          <input id="budget-total" class="form-input nb font-bold" type="text" value="<?php echo set_value('total');?>" disabled/>
                        </label>

                        <label class="flex flex-row text-xl mt-4">
                          <span class="form-input nb font-bold w-36">Total Productos:</span>
                          <input id="budget-total-products" class="form-input nb font-bold" type="text" value="<?php echo sizeof($details);?>" disabled/>
                        </label>

                        <?php if(in_array($role, [1])): ?>
                        <label class="block my-4 text-sm">
                          <span class="text-gray-700">Producto</span>
                          <div class="relative text-gray-500 focus-within:text-purple-600">
                            <input class="form-input-lg inline w-1/2" type="text" id="budgets-product"/>
                            <input id="budget-quantities-ele" class='form-input-lg inline' type='number' placeholder="Cantidad" min='1' value='1'>
                            <input id="budget-price-ele" class='form-input-lg inline' type='number' placeholder="Precio" min='1' value=''>
                            <button id="btn-agregar-budget" class="form-input-lg inline flex items-center justify-between inset-y-0 px-4 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg focus:outline-none" type="button" value="" onclick=""/>
                              <svg class="w-6 h-6 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                              <span class="inline pr-4">Agregar</span>
                            </button>
                          </div>
                        </label>
                        <?php endif; ?>

                        <div class="w-full overflow-hidden rounded-lg shadow-xs">
                          <div class="w-full overflow-x-auto">
                            <table class="stripped-table w-full whitespace-no-wrap mt-8 sm:mt-0">
                              <thead>
                                <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                                  <th class="px-4 py-3 hidden sm:table-cell">Código</th>
                                  <th class="px-4 py-3 hidden sm:table-cell">Descripción</th>
                                  <th class="px-4 py-3 hidden sm:table-cell">Cantidad</th>
                                  <th class="px-4 py-3 hidden sm:table-cell">Precio Base</th>
                                  <th class="px-4 py-3 hidden sm:table-cell">Precio Venta</th>
                                  <th class="px-4 py-3 hidden sm:table-cell">Subtotal</th>
                                  <th class="px-4 py-3 hidden sm:table-cell">Revisado</th>
                                  <?php if(in_array($role, [1])): ?>
                                  <th class="px-4 py-3 hidden sm:table-cell">Acciones</th>
                                  <?php endif; ?>
                                </tr>
                              </thead>
                              <tbody id="tborders" class="bg-white divide-y">
                                <?php foreach($details as $key => $detail):?>
                                    <tr class='text-gray-700 flex sm:table-row flex-row sm:flex-row flex-wrap sm:flex-no-wrap mb-10 sm:mb-0'>
                                    <td class='px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static'><span class="sm:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Código</span><input type='hidden' name='refs[]' value='<?php echo $detail->productId; ?>'><?php echo $detail->productId; ?></td>
                                    <td class='px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static text-xs'><span class="sm:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Descripción</span><?php echo $detail->description; ?></td>
                                    <td class='px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static'><span class="sm:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Cantidad</span>
                                      <div class="flex flex-row items-center gap-2">
                                      <p class="tooltip"><svg class="alarm-sim w-6 h-6 <?php if($detail->reviewed || $detail->base < $detail->unit) echo 'hidden' ?>" fill="none" stroke="red" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg><span class="tooltip-text bg-blue-200 p-3 -mt-6 -ml-6 rounded">Precios de venta por debajo del Precio Base</span></p>
                                      <input class='form-input budget-quantities' type='text' min='1' name='budget-quantities[]' value='<?php echo $detail->quantity; ?>' <?php echo (in_array($role, [1])) ? '' : 'readonly' ?>>
                                      </div></td>
                                    <td class='px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static'><span class="sm:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Precio Base</span><input class='price_base form-input flex-1' type='number' min='1' name='price_base[]' value='<?php echo $detail->base; ?>'>
                                    </td>
                                    <td class='px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static'><span class="sm:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Precio Venta</span><input class='form-input budget-rates' type='<?php echo (in_array($role, [1])) ? 'number' : 'text' ?>' min='1' name='budget-rates[]' value='<?php echo $detail->unit; ?>' <?php echo (in_array($role, [1])) ? '' : 'readonly' ?>></td>
                                    <td class='px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static'><span class="sm:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Subtotal</span><input class='form-input budget-subtotal' type='text' name='budget-subtotal[]' value='<?php echo $detail->subtotal; ?>' readonly></td>
                                    <td class='px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static'><span class="sm:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Revisado</span>
                                      <input type="checkbox" name="reviewed[]" value="<?php echo $key; ?>" class="reviewed-cb text-mam-blue-dark form-checkbox focus:border-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark" <?php echo $detail->reviewed ? 'checked':''; ?> />
                                      </td>
                                  <?php if(in_array($role, [1])): ?>
                                      <td class='px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static'><span class="sm:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Acciones</span><button type='button' class='button-main btn-remove-budget-product'><svg class='w-6 h-6' fill='none' stroke='currentColor' viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M6 18L18 6M6 6l12 12'></path></svg></button></td>
                                  <?php endif; ?>
                                    </tr>
                                <?php endforeach;?>
                              </tbody>
                            </table>
                          </div>
                        </div>

                        <div class="block text-sm mt-4">
                            <input type="submit" class="px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg active:bg-mam-blue-dark hover:bg-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark" value="Guardar">
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