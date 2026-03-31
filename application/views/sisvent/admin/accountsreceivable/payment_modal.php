<div class="px-4 py-3 bg-white rounded-lg shadow-md">
    <input type="hidden" id="ar-pay-invoice-id" value="<?php echo $invoice->idInvoice; ?>"/>

    <div class="flex flex-row gap-4 mb-3">
        <div class="flex-1 text-sm">
            <span class="text-gray-500">Factura</span>
            <p class="font-semibold font-mono">#<?php echo str_pad($invoice->idInvoice, 6, "0", STR_PAD_LEFT); ?></p>
        </div>
        <div class="flex-1 text-sm">
            <span class="text-gray-500">Cliente</span>
            <p class="font-semibold"><?php echo $invoice->client_name; ?></p>
        </div>
    </div>

    <label class="block text-sm mb-3">
        <span class="text-gray-700">Metodo de Pago</span>
        <select id="ar-pay-method" class="form-input form-select w-full">
            <?php foreach($methods as $m): ?>
                <option value="<?php echo $m->idMethod; ?>"><?php echo $m->name; ?></option>
            <?php endforeach; ?>
        </select>
    </label>

    <div class="mb-3">
        <span class="block text-sm text-gray-700 mb-1">Caja / Banco</span>
        <div class="flex gap-2">
            <select id="ar-pay-source-type" class="form-input form-select" style="max-width:130px">
                <option value="cashbox">Caja</option>
                <option value="bank">Banco</option>
            </select>
            <div id="ar-pay-cashbox-wrapper" class="flex-1">
                <select id="ar-pay-cashbox" class="form-input form-select w-full">
                    <option value="" disabled selected>Selecciona una caja</option>
                    <?php if(!empty($cashboxes)): ?>
                        <?php foreach($cashboxes as $box): ?>
                            <option value="<?php echo $box->idCashbox; ?>"><?php echo $box->name; ?> - $<?php echo number_format($box->currentBalance, 0, ',', '.'); ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div id="ar-pay-bank-wrapper" class="flex-1 hidden">
                <select id="ar-pay-bank" class="form-input form-select w-full">
                    <option value="" disabled selected>Selecciona un banco</option>
                    <?php if(!empty($bankaccounts)): ?>
                        <?php foreach($bankaccounts as $bank): ?>
                            <option value="<?php echo $bank->idBankAccount; ?>"><?php echo $bank->bankName; ?> - <?php echo $bank->accountNumber; ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
        </div>
    </div>

    <label class="block text-sm mb-3">
        <span class="text-gray-700">Fecha</span>
        <input type="date" id="ar-pay-date" class="form-input w-full" value="<?php echo date('Y-m-d'); ?>"/>
    </label>

    <div class="flex gap-4 mb-3">
        <div class="flex-1 text-sm">
            <span class="text-gray-500">Total Factura</span>
            <p class="text-lg font-bold">$<?php echo number_format($invoice->total, 0, ',', '.'); ?></p>
        </div>
        <div class="flex-1 text-sm">
            <span class="text-gray-500">Abonado</span>
            <p class="text-lg font-bold text-green-600">$<?php echo number_format($invoice->payment, 0, ',', '.'); ?></p>
        </div>
        <div class="flex-1 text-sm">
            <span class="text-gray-500">Saldo</span>
            <?php $balance = $invoice->total - $invoice->payment - $invoice->discount; ?>
            <p class="text-lg font-bold text-blue-600">$<?php echo number_format($balance, 0, ',', '.'); ?></p>
        </div>
    </div>

    <label class="block text-sm mb-3">
        <span class="text-gray-700 font-semibold">Valor del Abono</span>
        <input type="number" id="ar-pay-amount" class="form-input w-full text-lg font-bold"
               value="<?php echo $invoice->list_price ? (($invoice->total * 0.7) - $invoice->payment) : ($invoice->total - $invoice->payment - $invoice->discount); ?>"
               min="1" max="<?php echo $balance; ?>" step="1"/>
    </label>

    <label class="block text-sm mb-4">
        <span class="text-gray-700">Observaciones</span>
        <textarea id="ar-pay-comment" class="form-input w-full" rows="2" placeholder="Opcional..."></textarea>
    </label>

    <button type="button" id="ar-pay-submit" class="w-full px-4 py-2 text-sm font-medium text-white bg-mam-blue-petroleo rounded-lg hover:bg-mam-blue">
        Registrar Abono
    </button>
</div>
