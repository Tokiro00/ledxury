<div class="px-4 py-3 mb-8 bg-white rounded-lg shadow-md">
  <input class="form-input" type="hidden" name="id" value="<?php echo $invoice->idInvoice;?>" readonly/>
  <div class="flex flex-row gap-4">
    <div class="flex-1 mt-4 text-sm col-span-12 sm:col-span-6">
      <span class="text-gray-700">
        Factura #<?php echo str_pad($invoice->idInvoice, 6, "0", STR_PAD_LEFT); ?>
      </span>
    </div>
  </div>

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
      Método de Pago
    </span>
    <select id="invoice-payment-method" name="method" class="form-input form-select">
      <?php foreach($methods as $method): ?>
          <option value="<?php echo $method->idMethod?>" <?php echo set_select("method",$method->idMethod);?>><?php echo $method->name;?></option>
      <?php endforeach;?>
    </select>
  </label>

  <label class="flex flex-row text-xl mt-4">
    <span class="form-input nb font-bold w-18">Abono $</span>
    <input id="invoice-payment-val" class="form-input font-bold" type="number" name="payment" value="<?php echo set_value('payment');?>" />
  </label>

  <label class="block text-sm mt-4">
    <span class="text-gray-700">Observaciones</span>
    <textarea id="invoice-payment-comment" class="form-input" name="comments"><?php echo set_value('comments'); ?></textarea>
  </label>

  <div class="block text-sm mt-4">
      <button type="submit" class="invoice-do-payment-btn px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg active:bg-mam-blue-dark hover:bg-mam-blue-dark focus:outline-none focus:shadow-outline-mam-blue-dark" value="<?php echo $invoice->idInvoice;?>">Abonar</button>
  </div>
</div>