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

  <div class="mt-4">
    <span class="block text-sm text-gray-700 mb-1">Caja / Banco</span>
    <div class="flex gap-2">
      <select id="cash-source-type-invoice" name="cash_source_type" class="form-input form-select hidden" style="max-width:140px" required>
        <option value="cashbox">Caja</option>
        <option value="bank">Banco</option>
      </select>
      <div id="cash-source-cashbox-wrapper-invoice" class="flex-1">
        <select id="cash-source-cashbox-invoice" name="cash_source_cashbox" class="form-input form-select w-full" required>
          <option value="" disabled selected>Selecciona una caja</option>
          <?php if(!empty($cashboxes)): ?>
            <?php foreach($cashboxes as $box): ?>
              <option value="<?php echo $box->idCashbox; ?>"><?php echo $box->name; ?> - Saldo: $<?php echo number_format($box->currentBalance, 2); ?></option>
            <?php endforeach; ?>
          <?php endif; ?>
        </select>
      </div>
      <div id="cash-source-bank-wrapper-invoice" class="flex-1 hidden">
        <select id="cash-source-bank-invoice" name="cash_source_bank" class="form-input form-select w-full">
          <option value="" disabled selected>Selecciona un banco</option>
          <?php if(!empty($bankaccounts)): ?>
            <?php foreach($bankaccounts as $bank): ?>
              <option value="<?php echo $bank->idBankAccount; ?>"><?php echo $bank->bankName; ?> - <?php echo $bank->accountNumber; ?></option>
            <?php endforeach; ?>
          <?php endif; ?>
        </select>
      </div>
    </div>
    <?php if(empty($cashboxes) && empty($bankaccounts)): ?>
    <p class="text-xs text-red-500 mt-1">No hay cajas ni bancos activos. Cree una caja o banco primero.</p>
    <?php endif; ?>
  </div>

  <label class="block mt-4 text-sm">
    <span class="text-gray-700">
      Fecha
    </span>
    <input id="datepicker" class="form-input font-bold" type="text" name="date" value="<?php echo date('d-m-Y'); ?>" required/>

  </label>

  <div class="flex flex-row gap-4">
   <label class="flex-1 flex flex-row text-xl mt-4">
      <span class="form-input nb font-bold w-18">Total $</span>
      <input id="invoice-total" class="form-input nb font-bold" type="text" value="<?php echo $invoice->total;?>" disabled/>
    </label>

    <?php if($invoice->discount > 0): ?>
    <label class="flex-1 flex flex-row text-orange-600 text-xl mt-4">
      <span class="form-input nb font-bold w-28">Desc. -$</span>
      <input id="invoice-discount" class="form-input nb font-bold" type="text" value="<?php echo $invoice->discount;?>" disabled/>
    </label>
  <?php endif; ?>

    <?php if($invoice->list_price): ?>
    <label class="flex-1 flex flex-row text-orange-600 text-xl mt-4">
      <span class="form-input nb font-bold w-28">P. de lista -$</span>
      <input class="form-input nb font-bold" type="text" value="<?php echo ($invoice->total * 0.3);?>" disabled/>
    </label>
  <?php endif; ?>

    <label class="flex-1 flex flex-row text-xl mt-4">
      <span class="form-input nb font-bold w-28">Abonado $</span>
      <input id="invoice-payment" class="form-input nb font-bold" type="text" value="<?php echo $invoice->payment;?>" disabled/>
    </label>
  </div>



  <label class="flex flex-row text-xl mt-4">
    <span class="form-input nb font-bold w-18">Abono $</span>
    <input id="invoice-payment-val" class="form-input font-bold" type="number" name="payment" value="<?php echo $invoice->list_price ? (($invoice->total * 0.7) - $invoice->payment) : ($invoice->total - $invoice->payment - $invoice->discount);?>" />
  </label>

  <label class="block text-sm mt-4">
    <span class="text-gray-700">Observaciones</span>
    <textarea id="invoice-payment-comment" class="form-input" name="comments"><?php echo set_value('comments'); ?></textarea>
  </label>

  <div class="block text-sm mt-4">
      <button type="submit" data-params="<?php echo $params ?>" class="invoice-do-payment-btn px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-petroleo border border-transparent rounded-lg active:bg-mam-blue-petroleo hover:bg-mam-blue-petroleo focus:outline-none focus:shadow-outline-mam-blue-petroleo" value="<?php echo $invoice->idInvoice;?>">Abonar</button>
  </div>
</div>

