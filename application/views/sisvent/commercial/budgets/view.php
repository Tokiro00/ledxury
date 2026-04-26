<div id="budget-print">
<div class="grid mb-6">
	<div class="text-center">
		<b>Presupuesto #<?php echo str_pad($budget->idBudget, 6, "0", STR_PAD_LEFT); ?></b><br>
	</div>
</div> 
<hr class="my-6">
<div class="grid grid-cols-12">
	<div class="grid col-span-7">
		<p class="text-xl font-semibold"><?php echo $budget->client_name;?></p>
		<p class="text-gray-600"><?php echo $budget->client_idNum;?></p>
		<p class="text-gray-600"><?php echo $budget->address;?></p>
		<p class="text-gray-600"><?php echo $budget->phone;?> - <?php echo $budget->cellphone;?></p>
		<p class="text-gray-600"><?php echo $budget->city;?> - <?php echo $budget->client_state;?></p>
	</div>	
	<div class="grid col-span-5">
		<p><b>Fecha: </b> <?= $budget->date; ?></p>
		<p><b>Vendedor:</b> <?php echo $budget->vendor_name;?></p>
		<p><b>Almacén:</b> <?php echo $budget->store_name;?></p>
	</div>	
</div>
<hr class="my-6">
<div class="grid mb-6">
	<div class="">
		<b>Observaciones:</b><br> <?php echo $budget->comments;?>
	</div>
</div> 
<hr class="my-6">
<div class="grid mb-6">
	<div class="">
		<b>Total Productos: <?php echo sizeof($details);?></b><br>
	</div>
</div> 
<div class="w-full overflow-hidden rounded-lg shadow-xs my-8">
   <div class="w-full overflow-x-auto">
     <table id="tborders" class="w-full whitespace-no-wrap">
			<thead>
	            <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
	              <th class="px-4 py-3 text-xs">#</th>
	              <th class="px-4 py-3 text-xs">Código</th>
	              <th class="px-4 py-3 text-xs">Ubicación</th>
	              <th class="px-4 py-3 text-xs">Descripción</th>
	              <th class="px-4 py-3 text-xs text-right">Cantidad</th>
	              <th class="px-4 py-3 text-xs text-right">-</th>
	              <th class="px-4 py-3 text-xs text-right">V. Unitario</th>
	              <th class="px-4 py-3 text-xs text-right">Total</th>
	            </tr>
	          </thead>
	          <tbody id="tborders" class="bg-white divide-y">
	            <?php foreach($details as $key => $detail):?>
	                <tr class='text-gray-700'>
	                <td class='px-2 py-1 text-sm'><?php echo ($key + 1); ?></td>
	                <td class='px-2 py-1 text-xs whitespace-normal'><?php echo $detail->productId; ?></td>
	                <td class='px-2 py-1 text-xs whitespace-normal'><?php if(!empty($detail->location)) echo "(".$detail->location.")";?></td>
	                <td class='px-2 py-1 text-xs whitespace-normal'><?php echo $detail->description; ?></td>
	                <td class='px-2 py-1 text-sm text-right'><?php echo $detail->quantity; ?></td>
	                <td class='px-2 py-1 text-sm text-right'>___</td>
	                <td class='px-2 py-1 text-sm text-right'><?php echo number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $detail->unit)), 2);//$detail->unit; ?></td>
	                <td class='px-2 py-1 text-sm text-right'><?php echo number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $detail->subtotal)), 2);//$detail->subtotal; ?></td>
	                </tr>
	            <?php endforeach;?>
	          </tbody>
			
		</table>
	</div>
</div>
<div class="grid grid-cols-12 mb-8">
	<div class="grid col-span-7">
		
	</div>	
	<div class="flex flex-col col-span-5">
		<hr>
		<div class="flex flex-row justify-between px-12 font-bold">
		<p>Total:</p>
		<p><?php echo "$" . number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $budget->total)), 2);//$budget->total;?></p>
		</div>
	</div>	
</div>
</div>
<div class="flex items-center gap-2 mt-4">
  <button onclick="printDiv('Presupuesto <?= $budget->idBudget; ?>','budget-print', 1, '<?= $budget->idBudget; ?>')"  class="flex items-center justify-between px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-petroleo border border-transparent rounded-lg active:bg-mam-blue-petroleo hover:bg-mam-blue-petroleo focus:outline-none focus:shadow-outline-mam-blue-petroleo">
    <span>Imprimir</span>
    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
  </button>
  <?php if ((int)($this->session->userdata('user_data')['role'] ?? 0) === 1): ?>
  <button id="btn-save-pdf-<?= $budget->idBudget; ?>" type="button"
          data-id="<?= $budget->idBudget; ?>"
          class="btn-save-pdf flex items-center justify-between px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-red-600 border border-transparent rounded-lg hover:bg-red-700 focus:outline-none">
    <span>Guardar PDF</span>
    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h11l5 5v7a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg>
  </button>
  <?php if (!empty($budget->pdf_url)): ?>
  <a href="<?= base_url() ?>sisvent/commercial/budgets/viewPdf/<?= $budget->idBudget; ?>" target="_blank"
     class="flex items-center px-4 py-2 text-sm font-medium leading-5 text-red-700 bg-red-100 border border-red-200 rounded-lg hover:bg-red-200 focus:outline-none">
    <span>Ver PDF guardado</span>
    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 3h7v7m0-7L10 14m-4 0v6a2 2 0 002 2h11a2 2 0 002-2v-7"/></svg>
  </a>
  <?php endif; ?>
  <?php endif; ?>
</div>
<script>
$(document).on('click', '.btn-save-pdf', function(){
  var btn = $(this);
  var id = btn.data('id');
  var orig = btn.html();
  btn.prop('disabled', true).html('Generando...');
  $.ajax({
    url: base_url + 'sisvent/commercial/budgets/savePdf/' + id,
    type: 'GET',
    dataType: 'json',
    success: function(res){
      if (res && res.success && res.pdf_url) {
        window.open(res.pdf_url, '_blank');
        btn.html('PDF guardado ✓').removeClass('bg-red-600 hover:bg-red-700').addClass('bg-green-600');
      } else {
        alert('Error: ' + (res && res.error ? res.error : 'no se pudo generar'));
        btn.prop('disabled', false).html(orig);
      }
    },
    error: function(xhr){
      alert('Error generando PDF (HTTP ' + xhr.status + ')');
      btn.prop('disabled', false).html(orig);
    }
  });
});
</script>