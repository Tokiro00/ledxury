<?php
defined('BASEPATH') OR exit('No direct script access allowed');

    //$permissions = $this->session->userdata('user_data')['permissions'];
    $role = $this->session->userdata('user_data')['role'];
    //$showAdmin = (!empty($permissions) && ($permissions['2']['read'] || $permissions['3']['read']));
    $url_params = createFullParamsLinks($page, $pstore, $pvendor, $pstate, $pclient, $piva, $ps )."&lc=".$lc;
    $isSuperAdmin = $this->session->userdata('user_data')['uname'] == "00000" 
    || $this->session->userdata('user_data')['uname'] == '6542543'//Alex
    || $this->session->userdata('user_data')['uname'] == '71339095'//Alex
    || $this->session->userdata('user_data')['uname'] == '13862247'//Yosmar
    || $this->session->userdata('user_data')['uname'] == '12077935'//Yubi
    || $this->session->userdata('user_data')['uname'] == '1126908266';//Yami

    $partner = checkHasPartnerPrivileges();
?>
<!DOCTYPE html>
<html lang="en">
    <title>Factura</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<head>
<script>
  window.inEditInvoice = true;
  window.isadusr = <?php echo $role == 1; ?>;
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

                        <!--label class="block mt-4 text-sm">
                          <span class="text-gray-700">
                            Vendedor
                          </span>
                          <input class="form-input" type="hidden" name="vendor" value="<?php echo $invoice->vendorId;?>" readonly/>
                          <input class="form-input" type="text" value="<?php echo $invoice->vendor_name;?>" disabled/>
                        </label-->

                        <label class="block mt-4 text-sm">
                          <span class="text-gray-700">
                            Vendedor
                          </span>
                          <select name="vendor" class="form-input form-select" required>
                            <?php foreach($vendors as $vendor): ?>
                                <option value="<?php echo $vendor->idUser?>" <?php echo set_select("vendor",$vendor->idUser,$vendor->idUser==$invoice->vendorId);?>><?php echo $vendor->name;?></option>
                            <?php endforeach;?>
                          </select>
                        </label>

                        <div class="flex flex-row gap-4">
                          <div class="flex-1 mt-4 text-sm col-span-12 sm:col-span-6">
                            <span class="text-gray-700">
                              Cliente
                            </span>
                            <input class="form-input" type="hidden" id="budget-client-id" name="client" value="<?php echo $invoice->clientId;?>" readonly/>
                            <input class="form-input" type="text" id="budget-client" value="<?php echo $invoice->client_name;?>" <?php if(!in_array($role, [1])): ?> disabled <?php endif; ?>/>
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

                        <label class="flex items-center mt-4 dark:text-gray-400">
                          <input type="checkbox" name="legal_collection" class="text-mam-blue-dark form-checkbox focus:border-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark" <?php echo $invoice->legal_collection ? 'checked':''; ?> />
                          <span class="ml-2">Cobro jurídico</span>
                        </label>

                        <label class="flex items-center mt-4 dark:text-gray-400">
                          <input id="list_price" type="checkbox" name="list_price" class="text-mam-blue-dark form-checkbox focus:border-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark" <?php echo $invoice->list_price ? 'checked':''; ?> />
                          <span class="ml-2">Precio de Lista</span>
                        </label>
                        <?php endif; ?>

                        <label class="block text-sm mt-4">
                          <span class="text-gray-700">Observaciones</span>
                          <textarea class="form-input" name="comments"><?php echo set_value('comments',$invoice->comments); ?></textarea>
                        </label>

                        <!-- ═══════════════════════════════════════════════════════════════ -->
                        <!-- SECCIÓN DE ENVÍO / TRACKING -->
                        <!-- ═══════════════════════════════════════════════════════════════ -->
                        <div class="border-t border-gray-200 pt-4 mt-6">
                          <h3 class="text-md font-semibold text-gray-700 mb-3 flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                            </svg>
                            Información de Envío
                          </h3>

                          <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                            <!-- Número de guía -->
                            <label class="md:col-span-7 block text-sm">
                              <span class="text-gray-700">Número de Guía</span>
                              <div class="flex flex-row gap-2 mt-1">
                                <input class="form-input flex-1" type="text" name="tracking_number" id="tracking-number"
                                       placeholder="Ej: 700070811697"
                                       value="<?php echo isset($invoice->tracking_number) ? $invoice->tracking_number : ''; ?>"/>
                                <!-- Botón Consultar Estado -->
                                <button type="button" id="btn-check-tracking"
                                        onclick="consultarTracking()"
                                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:outline-none flex items-center gap-1"
                                        title="Consultar estado de la guía">
                                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" id="tracking-icon">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                  </svg>
                                  <svg class="w-4 h-4 animate-spin hidden" id="tracking-spinner" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                  </svg>
                                  <span id="tracking-btn-text">Consultar</span>
                                </button>
                              </div>
                            </label>

                            <!-- Transportadora -->
                            <label class="md:col-span-5 block text-sm">
                              <span class="text-gray-700">Transportadora</span>
                              <select name="tracking_carrier" class="form-input form-select mt-1">
                                <option value="interrapidisimo" <?php echo (!isset($invoice->tracking_carrier) || $invoice->tracking_carrier == 'interrapidisimo') ? 'selected' : ''; ?>>Interrapidísimo</option>
                                <option value="servientrega" <?php echo (isset($invoice->tracking_carrier) && $invoice->tracking_carrier == 'servientrega') ? 'selected' : ''; ?>>Servientrega</option>
                                <option value="coordinadora" <?php echo (isset($invoice->tracking_carrier) && $invoice->tracking_carrier == 'coordinadora') ? 'selected' : ''; ?>>Coordinadora</option>
                                <option value="envia" <?php echo (isset($invoice->tracking_carrier) && $invoice->tracking_carrier == 'envia') ? 'selected' : ''; ?>>Envía</option>
                                <option value="tcc" <?php echo (isset($invoice->tracking_carrier) && $invoice->tracking_carrier == 'tcc') ? 'selected' : ''; ?>>TCC</option>
                                <option value="otro" <?php echo (isset($invoice->tracking_carrier) && $invoice->tracking_carrier == 'otro') ? 'selected' : ''; ?>>Otro</option>
                              </select>
                            </label>
                          </div>

                          <!-- Resultado de la consulta de tracking -->
                          <div id="tracking-result" class="mt-3"></div>

                        </div>
                        <!-- FIN SECCIÓN DE ENVÍO -->

                        <label class="block text-sm mt-4 <?php echo !empty(form_error('discount')) ? 'border-red-600':'';?>">
                          <span class="text-gray-700">Descuento</span>
                          <input class="form-input" type="number" name="discount" value="<?php echo !empty(form_error('discount')) ? set_value('discount') : $invoice->discount;?>"/>
                          <?php echo form_error("discount","<span class='text-xs text-red-600'>","</span>");?>
                        </label>

                        <?php if(in_array($role, [1])): ?>
                        <label class="block text-sm mt-4 <?php echo !empty(form_error('discount_perc')) ? 'border-red-600':'';?>">
                          <span class="text-gray-700">Porcentaje Por Descuento</span>
                          <input class="form-input" type="number" name="discount_perc"  min="1" max="100" value="<?php echo !empty(form_error('discount_perc')) ? set_value('discount_perc') : $invoice->discount_perc;?>"/>
                          <?php echo form_error("discount_perc","<span class='text-xs text-red-600'>","</span>");?>
                        </label>
                        <?php endif; ?>

                        <label class="flex flex-row text-xl mt-4">
                          <span class="form-input nb font-bold w-16">Total $</span>
                          <input id="budget-total-val" class="form-input nb font-bold" type="hidden" name="total" value="<?php echo set_value('total');?>" readonly/>
                          <input id="budget-total" class="form-input nb font-bold" type="text" value="<?php echo set_value('total').($invoice->list_price ? ' -30% = '.($invoice->total * 0.7) : '');?>" disabled/>
                        </label>

                        <label class="flex flex-row text-xl mt-4">
                          <span class="form-input nb font-bold w-36">Total Productos:</span>
                          <input id="budget-total-products" class="form-input nb font-bold" type="text" value="<?php echo sizeof($details);?>" disabled/>
                        </label>

                        <?php if($partner): ?>
                        <div class="flex-1 mt-4 text-sm col-span-12 sm:col-span-6">
                          <span class="text-gray-700">
                            Multiplicador de precio
                          </span>
                          <div class="flex flex-row gap-4">
                            <input id='budget-rate-multiplier' class='form-input' type='number' min='1' max='100' name='budget-rate-multiplier' value='95'>
                            <button id="change-price-multiplier" class="flex items-center justify-between text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg focus:outline-none" type="button" value="Cambiar Precios" @click="multiplyPrices()"/>
                              <span>Cambiar Precios</span>
                            </button>
                          </div>
                        </div>
                        <?php endif; ?>

                        <?php if(in_array($role, [1]) && $isSuperAdmin): ?>
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
                                  <?php if(in_array($role, [1]) && $isSuperAdmin): ?>
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
                                      <input class='form-input budget-quantities' type='text' min='1' name='budget-quantities[]' value='<?php echo $detail->quantity; ?>' <?php echo (in_array($role, [1]) && $isSuperAdmin) ? '' : 'readonly' ?>>
                                      </div></td>
                                    <td class='px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static'><span class="sm:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Precio Base</span><input class='price_base form-input flex-1' type='number' min='1' name='price_base[]' value='<?php echo $detail->base; ?>' <?php echo (in_array($role, [1]) && $isSuperAdmin) ? '' : 'readonly' ?>>
                                    </td>
                                    <td class='px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static'><span class="sm:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Precio Venta</span><input class='form-input budget-rates' type='<?php echo (in_array($role, [1]) && $isSuperAdmin) ? 'number' : 'text' ?>' min='1' name='budget-rates[]' value='<?php echo $detail->unit; ?>' <?php echo (in_array($role, [1]) && $isSuperAdmin) ? '' : 'readonly' ?>></td>
                                    <td class='px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static'><span class="sm:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Subtotal</span><input class='form-input budget-subtotal' type='text' name='budget-subtotal[]' value='<?php echo $detail->subtotal; ?>' readonly></td>
                                    <td class='px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static'><span class="sm:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Revisado</span>
                                      <input type="checkbox" name="reviewed[]" value="<?php echo $key; ?>" class="reviewed-cb text-mam-blue-dark form-checkbox focus:border-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark" <?php echo $detail->reviewed ? 'checked':''; ?> />
                                      </td>
                                  <?php if(in_array($role, [1]) && $isSuperAdmin): ?>
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

    <script>
    // Consultar estado de tracking via AJAX
    function consultarTracking() {
        const trackingNumber = document.getElementById('tracking-number').value.trim();
        const carrier = document.querySelector('select[name="tracking_carrier"]').value;
        const invoiceId = document.querySelector('input[name="id"]').value;
        const btn = document.getElementById('btn-check-tracking');
        const icon = document.getElementById('tracking-icon');
        const spinner = document.getElementById('tracking-spinner');
        const btnText = document.getElementById('tracking-btn-text');
        const resultDiv = document.getElementById('tracking-result');

        if (!trackingNumber) {
            alert('Ingresa un número de guía primero');
            return;
        }

        // Mostrar loading
        btn.disabled = true;
        icon.classList.add('hidden');
        spinner.classList.remove('hidden');
        btnText.textContent = 'Consultando...';
        if (resultDiv) resultDiv.innerHTML = '<p class="text-gray-500 text-sm">Consultando estado...</p>';

        // Hacer petición AJAX
        fetch('<?php echo base_url(); ?>sisvent/commercial/invoices/checkTracking', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `tracking_number=${encodeURIComponent(trackingNumber)}&carrier=${encodeURIComponent(carrier)}&invoice_id=${encodeURIComponent(invoiceId)}`
        })
        .then(response => response.json())
        .then(data => {
            // Restaurar botón
            btn.disabled = false;
            icon.classList.remove('hidden');
            spinner.classList.add('hidden');
            btnText.textContent = 'Consultar';

            if (data.success && data.tracking) {
                mostrarResultadoTracking(data.tracking);
            } else {
                const errorMsg = data.error || 'No se pudo obtener información de la guía';
                if (resultDiv) {
                    resultDiv.innerHTML = `
                        <div class="p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                            <p class="text-yellow-700 text-sm font-medium">API no disponible</p>
                            <p class="text-yellow-600 text-xs mt-1">${errorMsg}</p>
                            <button type="button" onclick="abrirPaginaTracking()" class="mt-2 text-xs text-blue-600 hover:underline flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                Consultar en página de la transportadora
                            </button>
                        </div>`;
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            btn.disabled = false;
            icon.classList.remove('hidden');
            spinner.classList.add('hidden');
            btnText.textContent = 'Consultar';
            if (resultDiv) {
                resultDiv.innerHTML = `
                    <div class="p-3 bg-red-50 border border-red-200 rounded-lg">
                        <p class="text-red-700 text-sm">Error de conexión. Intenta de nuevo.</p>
                    </div>`;
            }
        });
    }

    // Mostrar resultado del tracking
    function mostrarResultadoTracking(tracking) {
        const resultDiv = document.getElementById('tracking-result');
        if (!resultDiv) return;

        const statusConfig = {
            'pending': { color: 'gray', icon: '⏳' },
            'in_transit': { color: 'blue', icon: '🚚' },
            'out_for_delivery': { color: 'yellow', icon: '📦' },
            'delivered': { color: 'green', icon: '✅' },
            'returned': { color: 'red', icon: '↩️' },
            'exception': { color: 'orange', icon: '⚠️' },
            'unknown': { color: 'gray', icon: '❓' }
        };

        const config = statusConfig[tracking.status] || statusConfig['unknown'];

        resultDiv.innerHTML = `
            <div class="p-3 bg-${config.color}-50 border border-${config.color}-200 rounded-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-${config.color}-100 text-${config.color}-800">
                            ${config.icon} ${tracking.status_label}
                        </span>
                        <p class="text-gray-700 text-sm mt-1 font-medium">Guía: ${tracking.guia}</p>
                    </div>
                    <span class="text-xs text-gray-500">${tracking.last_update}</span>
                </div>
                ${tracking.location ? `<p class="text-gray-600 text-xs mt-1">📍 ${tracking.location}</p>` : ''}
                ${tracking.last_event ? `<p class="text-gray-600 text-xs mt-1">📝 ${tracking.last_event}</p>` : ''}
            </div>`;
    }

    // Abrir página de rastreo externa (fallback)
    function abrirPaginaTracking() {
        const trackingNumber = document.getElementById('tracking-number').value.trim();
        const carrier = document.querySelector('select[name="tracking_carrier"]').value;

        const trackingUrls = {
            'interrapidisimo': 'https://www3.interrapidisimo.com/SiguetuEnvio/',
            'servientrega': 'https://www.servientrega.com/wps/portal/colombia/rastrear-guia',
            'coordinadora': 'https://www.coordinadora.com/portafolio-de-servicios/servicios-en-linea/rastrear-guias/',
            'envia': 'https://www.enviacolvanes.com.co/rastreo',
            'tcc': 'https://www.tcc.com.co/rastreo/',
            'otro': ''
        };

        const url = trackingUrls[carrier];
        if (url) {
            navigator.clipboard.writeText(trackingNumber).then(() => {
                alert('Número de guía copiado: ' + trackingNumber + '\n\nPégalo en la página de rastreo.');
                window.open(url, '_blank');
            }).catch(() => {
                window.open(url, '_blank');
            });
        }
    }
    </script>

  </body>
</html>