<?php
defined('BASEPATH') OR exit('No direct script access allowed');

    //$permissions = $this->session->userdata('user_data')['permissions'];
    $role = $this->session->userdata('user_data')['role'];
    //$showAdmin = (!empty($permissions) && ($permissions['2']['read'] || $permissions['3']['read']));
    $url_params = createFullParamsLinks($page, $pstore, $pvendor, $pstate, $pclient );
    $isSuperAdmin = $this->session->userdata('user_data')['uname'] == "00000" 
    || $this->session->userdata('user_data')['uname'] == '6542543'//Alex
    || $this->session->userdata('user_data')['uname'] == '71339095'//Alex
    || $this->session->userdata('user_data')['uname'] == '13862247'//Yosmar
    || $this->session->userdata('user_data')['uname'] == '12077935'//Yubi
    || $this->session->userdata('user_data')['uname'] == '1126908266';//Yami
?>
<!DOCTYPE html>
<html lang="en">
    <title>Presupuestos</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<head>
<script>
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
                        Editar Presupuesto
                    </h2>

                    <div class="flex flex-col flex-wrap mb-8 space-y-4 md:flex-row md:items-end md:space-x-4">
                        <?php if(in_array($role, [1])): ?>
                            <a href="<?php echo base_url();?>sisvent/commercial/budgets<?php echo $url_params; ?>"  class="flex items-center justify-between px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-petroleo border border-transparent rounded-lg active:bg-mam-blue-petroleo hover:bg-mam-blue-petroleo focus:outline-none focus:shadow-outline-mam-blue-petroleo">
                              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                              <span>Volver</span>
                            </a>
                        <?php endif; ?>
                    </div>

                    <form id="new-budget-form" action="<?php echo base_url();?>sisvent/commercial/budgets/update<?php echo $url_params; ?>" method="POST">
                      <?php if($this->session->flashdata("error")):?>
                          <div class="flex items-center p-4 mb-8 text-sm font-semibold text-white bg-red-600 rounded-lg shadow-md">
                              <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                              <p><?php echo $this->session->flashdata("error"); ?></p>
                           </div>
                      <?php endif;?>
                      <div class="px-4 py-3 mb-8 bg-white rounded-lg shadow-md">
                        <input class="form-input" type="hidden" name="id" value="<?php echo $budget->idBudget;?>" readonly/>
                        
                        <div class="flex flex-row gap-4">
                          <div class="flex-1 mt-4 text-sm col-span-12 sm:col-span-6">
                            <span class="text-gray-700">
                              Cliente
                            </span>
                            <input class="form-input" type="text" id="budget-client" value="<?php echo $budget->client_name;?>"/>
                            <input class="form-input" name="client" id="budget-client-id" type="hidden" id="budget-client" value="<?php echo $budget->clientId;?>" readonly/>
                        </div>

                          <div class="flex-1 mt-4 text-sm col-span-12 sm:col-span-6">
                            <span class="text-gray-700">
                              Tarifa
                            </span>
                            <div class="flex flex-row gap-4">
                              <select id="budget-rate" name="rate" class="form-input form-select">
                                  <option value="1" <?php echo set_select("rate",1);?>>Precio</option>
                                  <option value="2" <?php echo set_select("rate",2);?>>Precio Base</option>
                                  <option value="3" <?php echo set_select("rate",3);?>>Precio Escala</option>
                                  <option value="4" <?php echo set_select("rate",4);?>>Precio Distribución</option>
                              </select>
                              <button id="change-price" class="flex items-center justify-between text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-petroleo border border-transparent rounded-lg focus:outline-none" type="button" value="Agregar" @click="changePrices()"/>
                                <span>Cambiar Tarifa</span>
                              </button>
                            </div>
                          </div>

                        </div>

                        <label class="block mt-4 text-sm">
                          <span class="text-gray-700">
                            Vendedor
                          </span>
                          <select id="budget-vendor" name="vendor" class="form-input form-select" required>
                            <?php foreach($vendors as $vendor): ?>
                                <option value="<?php echo $vendor->idUser?>" <?php echo set_select("vendor",$vendor->idUser,$vendor->idUser==$budget->vendorId);?>><?php echo $vendor->name;?></option>
                            <?php endforeach;?>
                          </select>
                          <!--input id="budget-vendor" class="form-input" type="hidden" name="vendor" value="<?php echo $budget->vendorId;?>" readonly/>
                          <input class="form-input" type="text" value="<?php echo $budget->vendor_name;?>" disabled/-->
                        </label>

                        
                        <label class="block mt-4 text-sm">
                          <span class="text-gray-700">
                            Almacén
                          </span>
                          <select name="store" class="form-input form-select">
                            <?php foreach($stores as $store):?>
                                <option value="<?php echo $store->idStore?>" <?php echo set_select("store",$store->idStore,$store->idStore==$budget->storeId);?>><?php echo $store->name;?></option>
                            <?php endforeach;?>
                          </select>
                          <!--input class="form-input" type="hidden" name="store" value="<?php echo $budget->storeId;?>" readonly/>
                          <input class="form-input" type="text" value="<?php echo $budget->store_name;?>" disabled/-->
                        </label>

                        <label class="block mt-4 text-sm">
                          <span class="text-gray-700">
                            IVA
                          </span>
                          <select id="hasiva-field" name="hasIva" class="form-input form-select" required>
                            <option value="0" <?php echo !$budget->hasIva ? 'selected' : '';?>>Remisión</option>
                            <option value="1" <?php echo $budget->hasIva ? 'selected' : '';?>>IVA</option>
                          </select>
                        </label>

                         <?php if(!empty($budget->e_commerce) && (int)$budget->state === 0): ?>
                        <button type="button" id="btn-reextract-ai" data-budget-id="<?php echo (int)$budget->idBudget; ?>"
                          class="mt-4 inline-flex items-center px-4 py-2 text-sm font-medium leading-5 text-white bg-purple-600 hover:bg-purple-700 border border-transparent rounded-lg focus:outline-none transition-colors duration-150">
                          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                          Re-extraer datos del cliente con AI
                        </button>
                        <?php endif; ?>

                         <?php if(in_array($role, [1])): ?>
                        <label class="flex items-center mt-4 dark:text-gray-400">
                          <input type="checkbox" name="e_commerce" class="text-mam-blue-petroleo form-checkbox focus:border-mam-blue-petroleo focus:outline-none focus:shadow-outline-mam-blue-petroleo" <?php echo $budget->e_commerce ? 'checked':''; ?> />
                          <span class="ml-2">Venta por E-commerce</span>
                        </label>

                        <label class="flex items-center mt-4 dark:text-gray-400">
                          <input id="list_price" type="checkbox" name="list_price" class="text-mam-blue-petroleo form-checkbox focus:border-mam-blue-petroleo focus:outline-none focus:shadow-outline-mam-blue-petroleo" <?php echo $budget->list_price ? 'checked':''; ?> />
                          <span class="ml-2">Precio de lista</span>
                        </label>
                        <?php endif; ?>

                        <label class="block text-sm mt-4">
                          <span class="text-gray-700">Observaciones</span>
                          <textarea class="form-input" name="comments"><?php echo set_value('comments',$budget->comments); ?></textarea>
                        </label>

                        <label class="flex flex-row text-xl mt-4">
                          <span class="form-input nb font-bold w-16">Total $</span>
                          <input id="budget-total-val" class="form-input nb font-bold" type="hidden" name="total" value="<?php echo set_value('total');?>" readonly/>
                          <input id="budget-total" class="form-input nb font-bold" type="text" value="<?php echo set_value('total').($budget->list_price ? ' -30% = '.($budget->total * 0.7) : '');?>" disabled/>
                        </label>

                        <label class="flex flex-row text-xl mt-4">
                          <span class="form-input nb font-bold w-36">Total Productos:</span>
                          <input id="budget-total-products" class="form-input nb font-bold" type="text" value="<?php echo sizeof($details);?>" disabled/>
                        </label>

                        <label class="block my-4 text-sm">
                          <span class="text-gray-700">Producto</span>
                          <div class="relative text-gray-500 focus-within:text-purple-600">
                            <input class="form-input-lg inline w-1/2" type="text" id="budgets-product"/>
                            <input id="budget-quantities-ele" class='form-input-lg inline' type='number' placeholder="Cantidad" min='1' value='1'>
                            <input id="budget-price-ele" class='form-input-lg inline' type='number' placeholder="Precio" min='1' value=''>
                            <button id="btn-agregar-budget" class="form-input-lg inline flex items-center justify-between inset-y-0 px-4 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-petroleo border border-transparent rounded-lg focus:outline-none" type="button" value="" onclick=""/>
                              <svg class="w-6 h-6 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                              <span class="inline pr-4">Agregar</span>
                            </button>
                          </div>
                        </label>

                        <div class="w-full overflow-hidden rounded-lg shadow-xs">
                          <div class="w-full overflow-x-auto">
                            <table class="stripped-table w-full whitespace-no-wrap">
                              <thead>
                                <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                                  <th class="px-4 py-3 hidden sm:table-cell">#</th>
                                  <th class="px-4 py-3 hidden sm:table-cell">Código</th>
                                  <th class="px-4 py-3 hidden sm:table-cell">Descripción</th>
                                  <th class="px-4 py-3 hidden lg:table-cell">Stock</th>
                                  <th class="px-4 py-3 hidden sm:table-cell">Cantidad</th>
                                  <th class="px-4 py-3 hidden sm:table-cell">Precio</th>
                                  <th class="px-4 py-3 hidden sm:table-cell">Subtotal</th>
                                  <th class="px-4 py-3 hidden sm:table-cell">Acciones</th>
                                </tr>
                              </thead>
                              <tbody id="tborders" class="bg-white divide-y">
                                <?php foreach($details as $key => $detail):?>
                                    <tr class='text-gray-700 flex sm:table-row flex-row sm:flex-row flex-wrap sm:flex-no-wrap mb-10 sm:mb-0'>
                                    <td class='px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static text-xs'><span class="sm:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">#</span><?php echo ($key + 1); ?></td>
                                    <td class='px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static'><span class="sm:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Código</span><input type='hidden' name='refs[]' value='<?php echo $detail->productId; ?>'><?php echo $detail->productId; ?><input class='price' type='hidden' name='price[]' value='<?php echo $detail->price; ?>' readonly><input class='price_base' type='hidden' name='price_base[]' value='<?php echo $detail->price_base; ?>' readonly><input class='price_scale' type='hidden' name='price_scale[]' value='<?php echo $detail->price_scale; ?>' readonly><input class='price_dist' type='hidden' name='price_dist[]' value='<?php echo $detail->price_dist; ?>' readonly></td>
                                    <td class='px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static text-xs'><span class="sm:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Descripción</span><?php echo $detail->description; ?></td>
                                    <td class='px-4 py-3 w-full lg:w-auto block lg:table-cell relative lg:static'><span class='lg:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold'>Stock</span><input class='stock w-full' type='text' name='stock[]' value='<?php echo $detail->stock ?? 0; ?>' readonly></td>
                                    <td class='px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static'><span class="sm:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Cantidad</span><input class='form-input budget-quantities' type='number' min='1' name='budget-quantities[]' value='<?php echo $detail->quantity; ?>'></td>
                                    <td class='px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static'><span class="sm:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Precio</span><input class='form-input budget-rates' type='number' min='1' name='budget-rates[]' value='<?php echo $detail->unit; ?>' <?php //echo (in_array($role, [1]) && $isSuperAdmin) ? '' : 'readonly' ?>></td>
                                    <td class='px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static'><span class="sm:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Subtotal</span><input class='form-input budget-subtotal' type='text' name='budget-subtotal[]' value='<?php echo $detail->subtotal; ?>' readonly></td>
                                    <td class='px-4 py-3 w-full sm:w-auto block sm:table-cell relative sm:static'><span class="sm:hidden absolute top-0 right-0 text-gray-500 uppercase border-b bg-gray-50 px-2 py-1 text-xxs font-bold">Acciones</span><button type='button' class='button-main btn-base-price-product'><svg class='w-6 h-6' fill='none' stroke='currentColor' viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'></path></svg></button><button type='button' class='button-main btn-remove-budget-product'><svg class='w-6 h-6' fill='none' stroke='currentColor' viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M6 18L18 6M6 6l12 12'></path></svg></button></td>
                                    </tr>
                                <?php endforeach;?>
                              </tbody>
                            </table>
                          </div>
                        </div>

                        <div class="block text-sm mt-4">
                            <input type="submit" class="px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-petroleo border border-transparent rounded-lg active:bg-mam-blue-petroleo hover:bg-mam-blue-petroleo focus:outline-none focus:shadow-outline-mam-blue-petroleo" value="Guardar">
                        </div>
                      </div>
                    </form>

              </div>
          </main>
        </div>
    </div>
    <!-- Modal Re-extraer AI -->
    <div id="reextract-modal" class="fixed inset-0 z-50 hidden bg-black bg-opacity-50 flex items-center justify-center">
      <div class="bg-white rounded-lg shadow-xl max-w-3xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="px-6 py-4 border-b flex justify-between items-center">
          <h3 class="text-lg font-semibold text-gray-700">Sugerencias de AI</h3>
          <button type="button" id="reextract-close" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
        </div>
        <div id="reextract-body" class="px-6 py-4">
          <p class="text-sm text-gray-500">Cargando…</p>
        </div>
        <div class="px-6 py-3 border-t bg-gray-50 flex justify-end space-x-2">
          <button type="button" id="reextract-cancel" class="px-4 py-2 text-sm border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-100">Cancelar</button>
          <button type="button" id="reextract-apply" class="px-4 py-2 text-sm bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:opacity-50" disabled>Aplicar seleccionados</button>
        </div>
      </div>
    </div>

    <script>
    (function(){
      var btn = document.getElementById('btn-reextract-ai');
      if (!btn) return;
      var modal = document.getElementById('reextract-modal');
      var body  = document.getElementById('reextract-body');
      var closeBtn = document.getElementById('reextract-close');
      var cancelBtn = document.getElementById('reextract-cancel');
      var applyBtn = document.getElementById('reextract-apply');
      var lastData = null;

      function show() { modal.classList.remove('hidden'); }
      function hide() { modal.classList.add('hidden'); applyBtn.disabled = true; }
      closeBtn.addEventListener('click', hide);
      cancelBtn.addEventListener('click', hide);

      btn.addEventListener('click', function(){
        var bid = btn.getAttribute('data-budget-id');
        body.innerHTML = '<p class="text-sm text-gray-500">Consultando AI… (puede tardar 5-7 segundos)</p>';
        applyBtn.disabled = true;
        show();
        fetch(window.base_url + 'sisvent/rest/botimport/reextract_ai?budget_id=' + encodeURIComponent(bid), {
          credentials: 'same-origin'
        })
        .then(function(r){ return r.json().then(function(j){ return {ok: r.ok, body: j}; }); })
        .then(function(res){
          if (!res.ok || !res.body || !res.body.success) {
            var msg = (res.body && res.body.error) ? res.body.error : 'Error desconocido';
            body.innerHTML = '<p class="text-sm text-red-600">' + msg + '</p>';
            return;
          }
          lastData = res.body;
          renderComparison(res.body.current, res.body.extracted);
          applyBtn.disabled = false;
        })
        .catch(function(err){
          body.innerHTML = '<p class="text-sm text-red-600">Error de red: ' + err.message + '</p>';
        });
      });

      function renderComparison(current, extracted) {
        // Mapeo: campo cliente → campo del JSON AI
        var rows = [
          { key: 'name',    label: 'Nombre',     ai: extracted.nombre      || '' },
          { key: 'idNum',   label: 'Documento',  ai: extracted.cedula      || '' },
          { key: 'address', label: 'Dirección',  ai: extracted.direccion   || '' },
          { key: 'city',    label: 'Ciudad',     ai: extracted.ciudad      || '' },
          { key: 'state',   label: 'Departamento', ai: extracted.departamento || '' }
        ];
        var html = '<table class="w-full text-sm">';
        html += '<thead><tr class="text-xs uppercase text-gray-500 border-b"><th class="text-left py-2 w-8"></th><th class="text-left py-2">Campo</th><th class="text-left py-2">Actual</th><th class="text-left py-2">Sugerido (AI)</th></tr></thead><tbody>';
        rows.forEach(function(r){
          var diff = (r.ai || '').trim() !== '' && (r.ai || '').trim() !== (current[r.key] || '').trim();
          html += '<tr class="border-b">';
          html += '<td class="py-2"><input type="checkbox" class="reextract-cb" data-field="' + r.key + '" ' + (diff ? 'checked' : '') + ' ' + ((r.ai||'').trim()==='' ? 'disabled' : '') + '/></td>';
          html += '<td class="py-2 font-medium">' + r.label + '</td>';
          html += '<td class="py-2 text-gray-500">' + escapeHtml(current[r.key] || '—') + '</td>';
          html += '<td class="py-2"><input type="text" class="reextract-input form-input w-full" data-field="' + r.key + '" value="' + escapeHtml(r.ai) + '"/></td>';
          html += '</tr>';
        });
        html += '</tbody></table>';
        html += '<p class="text-xs text-gray-500 mt-3">Marca los campos que quieres aplicar al cliente. Puedes editar el valor antes de guardar.</p>';
        body.innerHTML = html;
      }

      function escapeHtml(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
      }

      applyBtn.addEventListener('click', function(){
        if (!lastData) return;
        var fields = {};
        var cbs = body.querySelectorAll('.reextract-cb:checked');
        cbs.forEach(function(cb){
          var key = cb.getAttribute('data-field');
          var input = body.querySelector('.reextract-input[data-field="' + key + '"]');
          if (input && input.value.trim() !== '') fields[key] = input.value.trim();
        });
        if (Object.keys(fields).length === 0) { hide(); return; }

        applyBtn.disabled = true;
        applyBtn.textContent = 'Guardando…';
        var fd = new FormData();
        fd.append('budget_id', lastData.budget_id);
        fd.append('client_id', lastData.client_id);
        fd.append('fields', JSON.stringify(fields));
        fetch(window.base_url + 'sisvent/rest/botimport/apply_reextract', {
          method: 'POST', body: fd, credentials: 'same-origin'
        })
        .then(function(r){ return r.json().then(function(j){ return {ok: r.ok, body: j}; }); })
        .then(function(res){
          if (res.ok && res.body && res.body.success) {
            location.reload();
          } else {
            var msg = (res.body && res.body.error) ? res.body.error : 'Error desconocido';
            applyBtn.textContent = 'Aplicar seleccionados';
            applyBtn.disabled = false;
            alert('Error: ' + msg);
          }
        })
        .catch(function(err){
          applyBtn.textContent = 'Aplicar seleccionados';
          applyBtn.disabled = false;
          alert('Error de red: ' + err.message);
        });
      });
    })();
    </script>

    <?php $this->load->view('sisvent/layouts/footer'); ?>

  </body>
</html>