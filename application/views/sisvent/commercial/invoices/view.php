<div id="invoice-print">
<div class="grid grid-cols-12 mb-6">
	<!--div class="flex flex-col col-span-4 text-left">
		<img aria-hidden="true" class="object-contain w-32 h-32" src="<?php echo get_images_path('svg/logo.png') ?>" alt="Logo"/>
		<div class="text-xs"></div>
		<div class="text-xs"></div>
	</div>
	<div class="grid col-span-4"></div>
	<div class="grid col-span-4 text-right">
		<b>#<?php echo str_pad($invoice->idInvoice, 6, "0", STR_PAD_LEFT); ?></b><br>
	</div-->
	<div class="text-center">
		<b>#<?php echo str_pad($invoice->idInvoice, 6, "0", STR_PAD_LEFT); ?></b><br>
	</div>
</div> 
<hr class="my-6">
<div class="grid grid-cols-12">
	<div class="grid col-span-7">
		<p class="text-xl font-semibold"><?php echo $invoice->client_name;?></p>
		<p class="text-gray-600"><?php echo $invoice->client_idNum;?></p>
		<p class="text-gray-600"><?php echo $invoice->address;?></p>
		<?php if(isset($invoice->zone)): ?>
    <p class="text-gray-600"><?php echo $invoice->zone;?></p>
    <?php endif; ?> 
		<p class="text-gray-600"><?php echo $invoice->phone;?> - <?php echo $invoice->cellphone;?></p>
		<p class="text-gray-600"><?php echo $invoice->city;?> - <?php echo $invoice->client_state;?></p>
	</div>	
	<div class="grid col-span-5">
		<p><b>Fecha: </b> <?= $invoice->date; ?></p>
		<p><b>Vendedor:</b> <?php echo $invoice->vendor_name;?></p>
		<p><b>Almacén:</b> <?php echo $invoice->store_name;?></p>
	</div>	
</div>
<hr class="my-3">
<?php if(!empty($invoice->comments) && $invoice->comments === 'BALANCE INICIAL'): ?>
<div class="w-full overflow-hidden rounded-lg shadow-xs my-6">
    <div class="bg-amber-50 border border-amber-200 rounded-lg p-6 text-center">
        <span class="inline-block px-3 py-1 text-sm font-semibold rounded-full bg-amber-100 text-amber-800 mb-2">Saldo Inicial</span>
        <p class="text-gray-600 text-sm">Esta factura corresponde al balance inicial de cartera del cliente.</p>
        <p class="text-2xl font-bold text-gray-800 mt-2">$<?php echo number_format($invoice->total, 2); ?></p>
    </div>
</div>
<?php else: ?>
<div class="grid text-xs">
	<!--div class="font-bold">Observaciones</div>
	<div class="grid col-span-7"><?= $invoice->comments; ?></div-->

</div>
<div class="grid">
	<div class="">
		<b>Total Productos: <?php echo sizeof($details);?></b><br>
	</div>
</div>
<div class="w-full overflow-hidden rounded-lg shadow-xs my-6">
   <div class="w-full overflow-x-auto">
     <table class="w-full whitespace-no-wrap">
			<thead>
	            <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
	              <th class="px-4 py-3 text-xs">#</th>
	              <th class="px-4 py-3 text-xs">Código</th>
	              <th class="px-4 py-3 text-xs">Descripción</th>
	              <th class="px-4 py-3 text-xs text-right">Cantidad</th>
	              <th class="px-4 py-3 text-xs text-right">-</th>
	              <th class="px-4 py-3 text-xs text-right">V. Unitario</th>
	              <th class="px-4 py-3 text-xs text-right">Total</th>
	            </tr>
	          </thead>
	          <tbody id="tborders" class="bg-white divide-y">
	            <?php foreach($details as $key => $detail):?>
	                <tr class='text-gray-700 <?php echo $key%2 ? 'bg-gray-300 print:bg-gray-300' : 'bg-gray print:bg-gray' ?>'>
	                <td class='px-2 py-1 text-sm'><?php echo ($key + 1); ?></td>
	                <td class='px-2 py-1 text-xs whitespace-normal'><?php echo $detail->productId; ?></td>
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
<?php endif; ?>
<div class="grid grid-cols-12 mb-6">
	<div class="grid col-span-8 text-xs">
		
	</div>	
	<div class="flex flex-col col-span-4">
		<hr>
		<?php if($invoice->discount > 0): ?>
		<div class="flex flex-col justify-between">
		<p class="flex flex-row justify-between"><span>Subtotal: </span><span><?php echo "$" . number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $invoice->total)), 2);?></span></p>
		<p class="flex flex-row justify-between"><span>Descuento: </span><span><?php echo "-$" . number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $invoice->discount)), 2);?></span></p>
		<p class="flex flex-row justify-between font-bold"><span>Total: </span><span><?php echo "$" . number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $invoice->total-$invoice->discount)), 2);?></span></p>
		</div>
		<?php else: ?>
		<div class="flex flex-row justify-between px-12 font-bold">
		<p>Total:</p>
		<p><?php echo "$" . number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $invoice->total)), 2);//$invoice->total;?></p>
		</div>
		<?php endif; ?>
	</div>	
</div>
<div class="grid text-xs mb-6">
	<div class="font-bold">Observaciones Generales</div>
	<?php $store = getStoreData($invoice->storeId); ?>
	<ul class="list-disc">
		<li><?php echo $store->invoice_account ?></li>
		<li><?php echo $store->invoice_support ?></li>
		<li>Si pagan en efectivo, solicite recibo de caja y reporte inmediatamente, o de lo contrario no nos hacemos responsables por el dinero</li>
		<li>Por favor revisar su pedido al momento de recibirlo y notificar si le llegó completo o no, ya que luego de 5 dias no nos hacemos responsables por algún faltante.</li>
	</ul>
</div>	
<div class="mb-6">
	<div class="flex items-center text-sm font-medium leading-5">
	<div class="flex items-center px-4 py-2 text-sm font-medium leading-5"><svg class="w-6 h-6" fill="#000000" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"><path d="M20.445 5h-8.891A6.559 6.559 0 0 0 5 11.554v8.891A6.559 6.559 0 0 0 11.554 27h8.891a6.56 6.56 0 0 0 6.554-6.555v-8.891A6.557 6.557 0 0 0 20.445 5zm4.342 15.445a4.343 4.343 0 0 1-4.342 4.342h-8.891a4.341 4.341 0 0 1-4.341-4.342v-8.891a4.34 4.34 0 0 1 4.341-4.341h8.891a4.342 4.342 0 0 1 4.341 4.341l.001 8.891z"></path><path d="M16 10.312c-3.138 0-5.688 2.551-5.688 5.688s2.551 5.688 5.688 5.688 5.688-2.551 5.688-5.688-2.55-5.688-5.688-5.688zm0 9.163a3.475 3.475 0 1 1-.001-6.95 3.475 3.475 0 0 1 .001 6.95zM21.7 8.991a1.363 1.363 0 1 1-1.364 1.364c0-.752.51-1.364 1.364-1.364z"></path></g></svg><span class="ml-2">@mamlucesled</span></div>
	<div class="flex items-center px-4 py-2 text-sm font-medium leading-5"><svg class="w-6 h-6" fill="#000000" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M12 2.03998C6.5 2.03998 2 6.52998 2 12.06C2 17.06 5.66 21.21 10.44 21.96V14.96H7.9V12.06H10.44V9.84998C10.44 7.33998 11.93 5.95998 14.22 5.95998C15.31 5.95998 16.45 6.14998 16.45 6.14998V8.61998H15.19C13.95 8.61998 13.56 9.38998 13.56 10.18V12.06H16.34L15.89 14.96H13.56V21.96C15.9164 21.5878 18.0622 20.3855 19.6099 18.57C21.1576 16.7546 22.0054 14.4456 22 12.06C22 6.52998 17.5 2.03998 12 2.03998Z"></path> </g></svg><span class="ml-2">MAM Multi Accesorios Medellin</span></div>
</div>
<div class="flex items-center px-4 pb-2 text-sm font-medium leading-5"></div>
</div>
</div>
<!-- Shipping Info -->
<?php
$shippingGuides = $this->db->where('invoiceId', $invoice->idInvoice)->order_by('created_at','DESC')->get('shipping_guides')->result();
?>
<?php if (!empty($shippingGuides)): ?>
<div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
    <p class="text-sm font-bold text-blue-800 mb-2">Envíos Interrapidísimo</p>
    <?php foreach ($shippingGuides as $sg): ?>
    <div class="flex items-center justify-between text-xs bg-white rounded p-2 mb-1 border">
        <div>
            <span class="font-mono font-bold"><?= $sg->numeroPreenvio ?></span>
            <span class="ml-2 text-gray-500"><?= $sg->ciudadDestinoNombre ?></span>
            <span class="ml-2 px-2 py-0.5 rounded-full text-white text-xs font-bold <?= $sg->estadoGuia == 11 ? 'bg-green-500' : ($sg->estadoGuia == 15 ? 'bg-red-500' : 'bg-blue-500') ?>"><?= $sg->estadoNombre ?></span>
            <span class="ml-2 text-gray-500">$<?= number_format($sg->valorTotal, 0, ',', '.') ?></span>
        </div>
        <div class="flex gap-1">
            <a href="<?= base_url() ?>sisvent/commercial/shipping/descargarGuia/<?= $sg->numeroPreenvio ?>" target="_blank" class="px-2 py-1 text-xs bg-green-500 text-white rounded hover:bg-green-600" title="Descargar guía PDF">PDF</a>
            <button onclick="trackGuia(<?= $sg->numeroPreenvio ?>)" class="px-2 py-1 text-xs bg-blue-500 text-white rounded hover:bg-blue-600" title="Consultar estado">Tracking</button>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Buttons -->
<div class="flex flex-wrap gap-2 mb-4">
<button onclick="printDiv('No. <?= $invoice->idInvoice; ?>','invoice-print', 2, '<?= $invoice->idInvoice; ?>')"  class="flex items-center justify-between px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-petroleo border border-transparent rounded-lg active:bg-mam-blue-petroleo hover:bg-mam-blue-petroleo focus:outline-none focus:shadow-outline-mam-blue-petroleo">
  <span>Imprimir</span>
  <svg class="w-6 h-6 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
</button>

<button onclick="document.getElementById('shippingModal').classList.remove('hidden')" class="flex items-center px-4 py-2 text-sm font-medium leading-5 text-white rounded-lg" style="background:#FF6B00;">
  <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"></path></svg>
  Enviar con Interrapidísimo
</button>

<button onclick="document.getElementById('otherCarrierModal').classList.remove('hidden')" class="flex items-center px-4 py-2 text-sm font-medium leading-5 text-white rounded-lg bg-purple-600 hover:bg-purple-700">
  <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
  Otra Transportadora
</button>
</div>

<!-- Modal Envío Interrapidísimo -->
<div id="shippingModal" class="hidden fixed inset-0 z-50 overflow-y-auto" data-invoice-id="<?= $invoice->idInvoice ?>">
  <div class="flex items-center justify-center min-h-screen px-4">
    <div class="fixed inset-0 bg-black opacity-50" onclick="document.getElementById('shippingModal').classList.add('hidden')"></div>
    <div class="relative bg-white rounded-lg shadow-xl w-full max-w-lg p-6 z-10">
      <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-bold text-gray-800">Enviar con Interrapidísimo</h3>
        <button onclick="document.getElementById('shippingModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
      </div>
      <div class="space-y-3">
        <!-- Quién paga el envío -->
        <div class="bg-gray-50 rounded-lg p-3 border">
          <p class="text-xs font-bold text-gray-500 mb-2">COBRO DEL ENVÍO</p>
          <div class="flex gap-4 mb-2">
            <label class="flex items-center gap-2 cursor-pointer">
              <input type="radio" name="shipCobro" value="empresa" checked class="text-blue-600" onchange="toggleCobro(this.value)">
              <span class="text-sm font-medium text-gray-700">Envío gratis (MAM paga)</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
              <input type="radio" name="shipCobro" value="contrapago" class="text-blue-600" onchange="toggleCobro(this.value)">
              <span class="text-sm font-medium text-gray-700">Pago en casa (cliente paga)</span>
            </label>
          </div>
          <div id="contrapagoCostWrap" class="hidden mt-2 p-2 bg-yellow-50 border border-yellow-200 rounded text-xs text-yellow-800">
            El cliente pagará el valor del envío al recibir. Interrapidísimo cobra y te lo devuelve.
          </div>
        </div>

        <!-- Tipo de entrega -->
        <div class="bg-gray-50 rounded-lg p-3 border">
          <p class="text-xs font-bold text-gray-500 mb-2">TIPO DE ENTREGA</p>
          <div class="flex gap-4">
            <label class="flex items-center gap-2 cursor-pointer">
              <input type="radio" name="shipTipoEntrega" value="1" checked class="text-blue-600" onchange="toggleTipoEntrega(1)">
              <span class="text-sm font-medium text-gray-700">Domicilio</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
              <input type="radio" name="shipTipoEntrega" value="2" class="text-blue-600" onchange="toggleTipoEntrega(2)">
              <span class="text-sm font-medium text-gray-700">Reclamar en oficina Inter</span>
            </label>
          </div>
        </div>

        <!-- Datos del destinatario (prellenados de la factura) -->
        <div class="bg-gray-50 rounded-lg p-3 border">
          <p class="text-xs font-bold text-gray-500 mb-2">DESTINATARIO</p>
          <div class="grid grid-cols-2 gap-2">
            <?php
              $shipPhone = isset($invoice->cellphone) ? $invoice->cellphone : (isset($invoice->client_cellphone) ? $invoice->client_cellphone : (isset($invoice->phone) ? $invoice->phone : ''));
              $shipAddress = isset($invoice->address) ? $invoice->address : (isset($invoice->client_address) ? $invoice->client_address : '');
              $shipName = isset($invoice->client_name) ? $invoice->client_name : '';
              $shipCity = isset($invoice->city) ? trim($invoice->city) : '';
              $shipDoc = isset($invoice->client_idNum) ? $invoice->client_idNum : (isset($invoice->idNum) ? $invoice->idNum : '');
            ?>
            <div><label class="block text-xs font-medium text-gray-700 mb-1">Nombre</label><input type="text" id="shipNombre" value="<?= htmlspecialchars($shipName) ?>" class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm bg-white"></div>
            <div><label class="block text-xs font-medium text-gray-700 mb-1">Teléfono</label><input type="text" id="shipTelefono" value="<?= htmlspecialchars($shipPhone) ?>" class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm bg-white"></div>
          </div>
          <div class="mt-2" id="shipDireccionWrap">
            <label class="block text-xs font-medium text-gray-700 mb-1">Dirección de entrega</label>
            <input type="text" id="shipDireccion" value="<?= htmlspecialchars($shipAddress) ?>" class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm bg-white">
          </div>
          <div class="grid grid-cols-2 gap-2 mt-2">
            <div class="relative">
              <label class="block text-xs font-medium text-gray-700 mb-1" id="shipCiudadLabel">Ciudad destino</label>
              <input type="text" id="shipCiudad" value="<?= htmlspecialchars($shipCity) ?>" data-original-city="<?= htmlspecialchars($shipCity) ?>" placeholder="Escriba la ciudad..." class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm bg-white" autocomplete="off">
              <input type="hidden" id="shipCiudadId">
              <div id="shipCiudadResults" class="absolute bg-white border rounded-lg shadow-lg max-h-40 overflow-y-auto hidden z-50 w-full"></div>
            </div>
            <div><label class="block text-xs font-medium text-gray-700 mb-1">Documento</label><input type="text" id="shipDocumento" value="<?= htmlspecialchars($shipDoc) ?>" class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm bg-white"></div>
          </div>
        </div>

        <!-- Datos del paquete -->
        <div class="bg-gray-50 rounded-lg p-3 border">
          <p class="text-xs font-bold text-gray-500 mb-2">PAQUETE</p>
          <div class="grid grid-cols-3 gap-2">
            <div><label class="block text-xs font-medium text-gray-700 mb-1">Peso (kg)</label><input type="number" id="shipPeso" value="1" min="0.1" step="0.1" class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm bg-white"></div>
            <div><label class="block text-xs font-medium text-gray-700 mb-1">Valor declarado ($)</label><input type="number" id="shipValor" value="<?= $invoice->total ?>" class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm bg-white"></div>
            <div><label class="block text-xs font-medium text-gray-700 mb-1">No. Piezas</label><input type="number" id="shipPiezas" value="1" min="1" class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm bg-white"></div>
          </div>
          <div class="grid grid-cols-3 gap-2 mt-2">
            <div><label class="block text-xs font-medium text-gray-700 mb-1">Largo (cm)</label><input type="number" id="shipLargo" value="30" class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm bg-white"></div>
            <div><label class="block text-xs font-medium text-gray-700 mb-1">Ancho (cm)</label><input type="number" id="shipAncho" value="20" class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm bg-white"></div>
            <div><label class="block text-xs font-medium text-gray-700 mb-1">Alto (cm)</label><input type="number" id="shipAlto" value="15" class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm bg-white"></div>
          </div>
        </div>

        <div><label class="block text-xs font-medium text-gray-700 mb-1">Observaciones</label><input type="text" id="shipObs" placeholder="Ej: Frágil, llamar antes..." class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></div>
        <div id="shipCotizacion" class="hidden bg-green-50 border border-green-200 rounded-lg p-3">
          <p class="text-sm font-bold text-green-800">Cotización</p>
          <div id="shipCotResult" class="text-sm text-green-700"></div>
        </div>
        <div id="shipError" class="hidden bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-700"></div>
        <div class="flex gap-2 pt-2">
          <button onclick="cotizarEnvio()" id="btnCotizar" class="flex-1 px-4 py-2 text-sm font-bold text-white rounded-lg" style="background:#2E7D91;">Cotizar</button>
          <button onclick="crearGuia()" id="btnCrearGuia" class="flex-1 px-4 py-2 text-sm font-bold text-white rounded-lg hidden" style="background:#FF6B00;">Generar Guía</button>
        </div>

        <!-- Guías existentes -->
        <div id="shipGuiasExistentes" class="hidden border-t pt-3 mt-2">
          <div class="flex items-center justify-between mb-2">
            <p class="text-xs font-bold text-gray-500 uppercase">Guías generadas</p>
            <a id="shipPrintAll" href="#" target="_blank" class="text-xs font-bold text-white px-3 py-1 rounded" style="background:#1B365D;">Imprimir todas</a>
          </div>
          <div id="shipGuiasList" class="space-y-1"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal Otra Transportadora -->
<div id="otherCarrierModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
  <div class="flex items-center justify-center min-h-screen px-4">
    <div class="fixed inset-0 bg-black opacity-50" onclick="document.getElementById('otherCarrierModal').classList.add('hidden')"></div>
    <div class="relative bg-white rounded-lg shadow-xl w-full max-w-md p-6 z-10">
      <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-bold text-gray-800">Despachar con otra transportadora</h3>
        <button onclick="document.getElementById('otherCarrierModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
      </div>
      <form id="formOtherCarrier" method="POST" action="<?= base_url() ?>sisvent/commercial/shipping/otherCarrier">
        <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
        <input type="hidden" name="invoiceId" value="<?= $invoice->idInvoice ?>">

        <label class="block text-xs text-gray-500 uppercase mb-1">Transportadora</label>
        <select name="transportadora" id="otherCarrierSelect" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm mb-3" required>
          <option value="">Seleccionar...</option>
          <option value="coordinadora">Coordinadora</option>
          <option value="estelar">Estelar</option>
          <option value="carro_mam">Carro MAM</option>
          <option value="moto_mam">Moto MAM</option>
          <option value="particular">Transporte Particular</option>
          <option value="recoge_cliente">Recoge el Cliente</option>
        </select>

        <div id="otherGuiaField" style="display:none">
          <label class="block text-xs text-gray-500 uppercase mb-1">Numero de Guia</label>
          <input type="text" name="numero_guia" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm mb-3" placeholder="Ej: 1234567890">
        </div>

        <label class="block text-xs text-gray-500 uppercase mb-1">Destino</label>
        <input type="text" name="destino" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm mb-3" value="<?= htmlspecialchars(($invoice->city ?? '') . ($invoice->client_state ? ' - ' . $invoice->client_state : '')) ?>">

        <label class="block text-xs text-gray-500 uppercase mb-1">Costo del transporte ($)</label>
        <input type="number" name="costo_transporte" id="otherCostoTransporte" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm mb-2" placeholder="0" min="0" step="100">
        <label class="flex items-center gap-2 mb-3 cursor-pointer">
          <input type="checkbox" name="sumar_a_factura" value="1" class="rounded border-gray-300">
          <span class="text-sm text-gray-600">Sumar costo del transporte a la factura</span>
        </label>

        <label class="block text-xs text-gray-500 uppercase mb-1">Observaciones</label>
        <textarea name="observaciones" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm mb-4" rows="2" placeholder="Notas del despacho..."></textarea>

        <div class="flex justify-end gap-2">
          <button type="button" onclick="document.getElementById('otherCarrierModal').classList.add('hidden')" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Cancelar</button>
          <button type="submit" class="px-4 py-2 text-sm font-medium text-white rounded-lg bg-purple-600 hover:bg-purple-700">Despachar</button>
        </div>
      </form>
    </div>
  </div>
</div>
<script>
document.getElementById('otherCarrierSelect').addEventListener('change', function(){
  var needs = ['coordinadora','estelar','particular'].includes(this.value);
  document.getElementById('otherGuiaField').style.display = needs ? 'block' : 'none';
});
</script>

<!-- JS functions loaded in list.php -->
