<?php
defined('BASEPATH') OR exit('No direct script access allowed');

    //$permissions = $this->session->userdata('user_data')['permissions'];
    $role = $this->session->userdata('user_data')['role'];
    //$showAdmin = (!empty($permissions) && ($permissions['2']['read'] || $permissions['3']['read']));
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
        <main class="h-full overflow-y-auto">
          <div class="px-6 mx-auto grid">
                    <h2 class="mb-4 text-lg font-semibold text-gray-600 mt-2">
                        Editar Factura
                    </h2>

                    <div class="flex flex-col flex-wrap mb-8 space-y-4 md:flex-row md:items-end md:space-x-4">
                        <?php if(in_array($role, [1])): ?>
                            <a href="<?php echo base_url();?>sisvent/commercial/invoices"  class="flex items-center justify-between px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg active:bg-mam-blue-dark hover:bg-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark">
                              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                              <span>Volver</span>
                            </a>
                        <?php endif; ?>
                    </div>

                    <form id="new-budget-form" action="<?php echo base_url();?>sisvent/commercial/invoices/update" method="POST">
                      <?php if($this->session->flashdata("error")):?>
                          <div class="flex items-center p-4 mb-8 text-sm font-semibold text-white bg-red-600 rounded-lg shadow-md">
                              <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                              <p><?php echo $this->session->flashdata("error"); ?></p>
                           </div>
                      <?php endif;?>
                      <div class="px-4 py-3 mb-8 bg-white rounded-lg shadow-md">
                        <input class="form-input" type="hidden" name="id" value="<?php echo $invoice->idInvoice;?>" readonly/>

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
                            <input class="form-input" type="hidden" name="client" value="<?php echo $invoice->clientId;?>" readonly/>
                            <input class="form-input" type="text" value="<?php echo $invoice->client_name;?>" disabled/>
                          </div>

                        </div>

                        <label class="block mt-4 text-sm">
                          <span class="text-gray-700">
                            Almacén
                          </span>
                          <input class="form-input" type="hidden" name="store" value="<?php echo $invoice->storeId;?>" readonly/>
                          <input class="form-input" type="text" value="<?php echo $invoice->store_name;?>" disabled/>
                        </label>

                        <label class="flex items-center mt-4 dark:text-gray-400">
                          <input id="budget-tax" type="hidden" name="hasIva" value="<?php echo $invoice->hasIva;?>" readonly/>
                          <input id="budget-tax" type="checkbox" class="text-mam-blue-dark form-checkbox focus:border-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark" <?php echo $invoice->hasIva ? 'checked':''; ?> disabled/>
                          <span class="ml-2">IVA</span>
                        <?php if(in_array($role, [1])): ?>
                          <input id="budget-tax-value" class='form-input <?php echo $invoice->hasIva ? '' : 'hidden'  ?> ml-8 small w-16' type='number' min='1' max='100' name='iva' value='<?php echo $invoice->iva;?>'>
                        <?php endif; ?>
                        </label>

                        <label class="flex flex-row text-xl mt-4">
                          <span class="form-input nb font-bold w-16">Total $</span>
                          <input id="budget-total-val" class="form-input nb font-bold" type="hidden" name="total" value="<?php echo set_value('total');?>" readonly/>
                          <input id="budget-total" class="form-input nb font-bold" type="text" value="<?php echo set_value('total');?>" disabled/>
                        </label>

                        <label class="block text-sm mt-4">
                          <span class="text-gray-700">Observaciones</span>
                          <textarea class="form-input" name="comments"><?php echo set_value('comments',$invoice->comments); ?></textarea>
                        </label>

                        <div class="w-full overflow-hidden rounded-lg shadow-xs">
                          <div class="w-full overflow-x-auto">
                            <table class="stripped-table w-full whitespace-no-wrap mt-8 lg:mt-0">
                              <thead>
                                <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                                  <th class="px-4 py-3 hidden lg:table-cell">Código</th>
                                  <th class="px-4 py-3 hidden lg:table-cell">Descripción</th>
                                  <th class="px-4 py-3 hidden lg:table-cell">Precio</th>
                                  <th class="px-4 py-3 hidden lg:table-cell">Cantidad</th>
                                  <th class="px-4 py-3 hidden lg:table-cell">Subtotal</th>
                                </tr>
                              </thead>
                              <tbody id="tborders" class="bg-white divide-y">
                                <?php foreach($details as $key => $detail):?>
                                    <tr class='text-gray-700 flex lg:table-row flex-row lg:flex-row flex-wrap lg:flex-no-wrap mb-10 lg:mb-0'>
                                    <td class='px-4 py-3 w-full lg:w-auto block lg:table-cell relative lg:static'><span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Código</span><input type='hidden' name='refs[]' value='<?php echo $detail->productId; ?>'><?php echo $detail->productId; ?></td>
                                    <td class='px-4 py-3 w-full lg:w-auto block lg:table-cell relative lg:static text-xs'><span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Descripción</span><?php echo $detail->description; ?></td>
                                    <td class='px-4 py-3 w-full lg:w-auto block lg:table-cell relative lg:static'><span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Precio</span><input class='form-input budget-rates' type='number' min='1' name='budget-rates[]' value='<?php echo $detail->unit; ?>'></td>
                                    <td class='px-4 py-3 w-full lg:w-auto block lg:table-cell relative lg:static'><span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Cantidad</span><input class='form-input budget-quantities' type='number' min='1' name='budget-quantities[]' value='<?php echo $detail->quantity; ?>'></td>
                                    <td class='px-4 py-3 w-full lg:w-auto block lg:table-cell relative lg:static'><span class="lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Subtotal</span><input class='form-input budget-subtotal' type='text' name='budget-subtotal[]' value='<?php echo $detail->subtotal; ?>' readonly></td>
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