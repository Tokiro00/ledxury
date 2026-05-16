<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$role = $this->session->userdata('user_data')['role'];
$fmt = function ($n) { return number_format((float)$n, 0, ',', '.'); };

// Totales agregados
$totBots = 0; $totAnticipos = 0; $totNeto = 0;
foreach ($settlements as $s) {
    $totBots      += (float)($s->bot_commission ?? 0);
    $totAnticipos += (float)($s->advanceBalance ?? 0);
    $totNeto      += (float)($s->netoPagar ?? 0);
}
?>
<!DOCTYPE html>
<html lang="es">
<title>Liquidaciones — Ledxury</title>
<?php $this->load->view('sisvent/layouts/meta_header'); ?>
<body>
<div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
    <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => $_ci_view, 'role' => $role)); ?>
    <div class="flex flex-col flex-1 w-full">
        <?php $this->load->view('sisvent/layouts/navbar'); ?>
        <main class="h-full">
            <div class="px-6 mx-auto grid">

                <div class="flex items-center justify-between mt-2 mb-4">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-700">Liquidaciones</h2>
                        <p class="text-xs text-gray-400">Saldo a pagar = comisión de bots menos anticipos pendientes.</p>
                    </div>
                    <a href="<?= base_url() ?>sisvent/admin/settlements/history"
                       class="px-3 py-1.5 text-xs font-medium text-mam-blue-petroleo bg-blue-50 hover:bg-blue-100 rounded">Historial &rarr;</a>
                </div>

                <!-- Totales agregados -->
                <div class="grid grid-cols-3 gap-3 mb-4">
                    <div class="p-3 bg-white rounded-lg shadow-xs">
                        <p class="text-xxs text-gray-400 uppercase">Comisión bots</p>
                        <p class="text-lg font-semibold text-purple-700">$<?= $fmt($totBots) ?></p>
                    </div>
                    <div class="p-3 bg-white rounded-lg shadow-xs">
                        <p class="text-xxs text-gray-400 uppercase">Anticipos pendientes</p>
                        <p class="text-lg font-semibold text-yellow-700">$<?= $fmt($totAnticipos) ?></p>
                    </div>
                    <div class="p-3 bg-white rounded-lg shadow-xs border-2 <?= $totNeto >= 0 ? 'border-green-400' : 'border-red-400' ?>">
                        <p class="text-xxs text-gray-400 uppercase">Saldo neto a pagar</p>
                        <p class="text-xl font-bold <?= $totNeto >= 0 ? 'text-green-700' : 'text-red-600' ?>">$<?= $fmt($totNeto) ?></p>
                    </div>
                </div>

                <div class="w-full overflow-hidden rounded-lg shadow-xs bg-white">
                    <div class="w-full overflow-x-auto">
                        <table class="w-full whitespace-no-wrap text-sm">
                            <thead>
                                <tr class="text-xxs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                                    <th class="px-4 py-3">Persona</th>
                                    <th class="px-4 py-3 text-right">Comisión bots</th>
                                    <th class="px-4 py-3 text-right">Anticipos pendientes</th>
                                    <th class="px-4 py-3 text-right">Saldo neto</th>
                                    <th class="px-4 py-3 text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <?php if (empty($settlements)): ?>
                                    <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400">No hay personas con saldo pendiente.</td></tr>
                                <?php else: foreach ($settlements as $s):
                                    $bot  = (float)($s->bot_commission ?? 0);
                                    $adv  = (float)($s->advanceBalance ?? 0);
                                    $neto = (float)($s->netoPagar ?? 0);
                                    $hasBot  = $bot != 0;
                                ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <p class="text-sm font-medium text-gray-700"><?= htmlspecialchars($s->name) ?></p>
                                        <?php if ($hasBot && !empty($s->bot_desc)): ?>
                                            <p class="text-xxs text-purple-600 mt-0.5"><?= htmlspecialchars($s->bot_desc) ?></p>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-right <?= $hasBot ? 'text-purple-700 font-semibold' : 'text-gray-300' ?>">
                                        <?= $hasBot ? '$' . $fmt($bot) : '—' ?>
                                    </td>
                                    <td class="px-4 py-3 text-right <?= $adv > 0 ? 'text-yellow-700' : 'text-gray-300' ?>">
                                        <?= $adv > 0 ? '$' . $fmt($adv) : '—' ?>
                                    </td>
                                    <td class="px-4 py-3 text-right text-base font-bold <?= $neto > 0 ? 'text-green-700' : ($neto < 0 ? 'text-red-600' : 'text-gray-400') ?>">
                                        $<?= $fmt($neto) ?>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="<?= base_url() ?>sisvent/admin/settlements/statement/<?= urlencode($s->idUser) ?>"
                                               class="px-3 py-1.5 text-xs font-medium text-mam-blue-petroleo border border-gray-200 hover:bg-blue-50 rounded"
                                               title="Estado de cuenta del vendedor">📊 Estado de cuenta</a>

                                            <a href="<?= base_url() ?>sisvent/admin/advances/add?employee_id=<?= urlencode($s->idUser) ?>"
                                               class="px-3 py-1.5 text-xs font-medium text-yellow-700 border border-yellow-300 hover:bg-yellow-50 rounded"
                                               title="Dar anticipo a este vendedor">💸 Anticipo</a>

                                            <?php if (!empty($s->bot_commission) && (float)$s->bot_commission > 0): ?>
                                            <button type="button"
                                                    class="btn-pay-comm px-3 py-1.5 text-xs font-bold text-white bg-green-600 hover:bg-green-700 rounded"
                                                    data-vendor-id="<?= htmlspecialchars($s->idUser) ?>"
                                                    data-vendor-name="<?= htmlspecialchars($s->name) ?>"
                                                    data-commission="<?= (float)$s->bot_commission ?>"
                                                    data-advances="<?= (float)$s->advanceBalance ?>"
                                                    title="Liquidar comisión: cruza anticipos + paga remanente">💰 Pagar</button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Modal compartido: Pagar comisión (uno por persona, JS rellena al click) -->
<div id="pay-comm-modal" class="fixed inset-0 z-50 hidden items-center justify-center" style="background:rgba(0,0,0,0.5);">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-lg font-bold text-gray-800">Pagar comisión a <span id="pcm-name">—</span></h3>
            <button type="button" class="pcm-close text-gray-400 hover:text-gray-700 text-2xl leading-none">&times;</button>
        </div>

        <div class="mb-3 p-3 rounded border border-gray-200 bg-gray-50">
            <div class="flex justify-between text-sm mb-1">
                <span class="text-gray-600">Comisión pendiente total:</span>
                <span class="font-mono font-bold text-green-700" id="pcm-comm">$0</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-gray-600">Anticipos disponibles:</span>
                <span class="font-mono font-bold text-yellow-700" id="pcm-adv-total">$0</span>
            </div>
        </div>

        <form id="pay-comm-form">
            <input type="hidden" name="vendor_id" id="pcm-vendor-id" value="">

            <label class="block mb-3">
                <span class="block text-xs font-bold text-gray-600 uppercase mb-1">Monto a liquidar este pago</span>
                <div class="flex items-center gap-2">
                    <input type="number" name="amount" id="pcm-amount" min="1" step="1"
                           class="flex-1 px-3 py-2 text-sm border rounded font-mono">
                    <button type="button" id="pcm-amount-all"
                            class="px-2 py-2 text-xs font-medium text-gray-600 border border-gray-300 rounded hover:bg-gray-100"
                            title="Liquidar todo el saldo">Todo</button>
                </div>
                <span id="pcm-amount-hint" class="block text-xxs text-gray-400 mt-1"></span>
            </label>

            <div class="mb-3 p-3 rounded border border-gray-200 bg-gray-50">
                <div class="flex justify-between text-sm mb-1" id="pcm-cross-row">
                    <span class="text-gray-600">− Cruce con anticipos:</span>
                    <span class="font-mono font-bold text-yellow-700" id="pcm-cross">−$0</span>
                </div>
                <hr class="my-2 border-gray-300">
                <div class="flex justify-between text-base">
                    <span class="font-bold text-gray-700">= A pagar en efectivo:</span>
                    <span class="font-mono font-bold text-mam-blue-petroleo" id="pcm-cash">$0</span>
                </div>
            </div>

            <div id="pcm-source-wrap" class="mb-3">
                <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Caja o banco origen del pago</label>
                <select name="source" id="pcm-source" class="w-full px-2 py-2 text-sm border rounded">
                    <option value="">-- Selecciona --</option>
                    <?php if (!empty($cashboxes)): ?>
                    <optgroup label="Cajas">
                        <?php foreach ($cashboxes as $cb): ?>
                        <option value="caja:<?= (int)$cb->id ?>"><?= htmlspecialchars($cb->name) ?> ($<?= number_format((float)$cb->currentBalance, 0, ',', '.') ?>)</option>
                        <?php endforeach; ?>
                    </optgroup>
                    <?php endif; ?>
                    <?php if (!empty($bank_accounts)): ?>
                    <optgroup label="Bancos">
                        <?php foreach ($bank_accounts as $ba): ?>
                        <option value="banco:<?= (int)$ba->id ?>"><?= htmlspecialchars($ba->name) ?> ($<?= number_format((float)$ba->currentBalance, 0, ',', '.') ?>)</option>
                        <?php endforeach; ?>
                    </optgroup>
                    <?php endif; ?>
                </select>
            </div>
            <p id="pcm-no-cash-msg" class="text-xs text-gray-500 mb-3 hidden">No hay efectivo a pagar — todo el saldo se cruza con anticipos. No requiere caja/banco.</p>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" class="pcm-close px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded hover:bg-gray-100">Cancelar</button>
                <button type="submit" id="pcm-submit" class="px-4 py-2 text-sm font-bold text-white bg-green-600 hover:bg-green-700 rounded">Confirmar pago</button>
            </div>
            <p id="pcm-msg" class="text-xs text-red-600 mt-2 hidden"></p>
        </form>
    </div>
</div>

<script>
(function() {
    var modal     = document.getElementById('pay-comm-modal');
    var form      = document.getElementById('pay-comm-form');
    var submitBtn = document.getElementById('pcm-submit');
    var msgEl     = document.getElementById('pcm-msg');
    var nameEl    = document.getElementById('pcm-name');
    var commEl    = document.getElementById('pcm-comm');
    var advTotEl  = document.getElementById('pcm-adv-total');
    var amountEl  = document.getElementById('pcm-amount');
    var amountAll = document.getElementById('pcm-amount-all');
    var amtHint   = document.getElementById('pcm-amount-hint');
    var crossEl   = document.getElementById('pcm-cross');
    var crossRow  = document.getElementById('pcm-cross-row');
    var cashEl    = document.getElementById('pcm-cash');
    var vendorEl  = document.getElementById('pcm-vendor-id');
    var sourceWrap= document.getElementById('pcm-source-wrap');
    var sourceSel = document.getElementById('pcm-source');
    var noCashMsg = document.getElementById('pcm-no-cash-msg');
    if (!modal || !form) return;

    var fmt = function(n) { return '$' + Math.round(n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.'); };
    var state = { comm: 0, advances: 0 };

    function recompute() {
        var amount = parseFloat(amountEl.value) || 0;
        amount = Math.max(0, Math.min(amount, state.comm));
        var cross = Math.min(amount, state.advances);
        var cash  = Math.max(0, amount - cross);

        crossEl.textContent = '−' + fmt(cross);
        cashEl.textContent  = fmt(cash);
        crossRow.style.display = (state.advances > 0) ? '' : 'none';
        amtHint.textContent = 'Saldo total: ' + fmt(state.comm) + (amount < state.comm ? ' · Quedará pendiente: ' + fmt(state.comm - amount) : '');

        if (cash > 0) {
            sourceWrap.classList.remove('hidden');
            noCashMsg.classList.add('hidden');
            sourceSel.required = true;
        } else {
            sourceWrap.classList.add('hidden');
            noCashMsg.classList.remove('hidden');
            sourceSel.required = false;
        }
    }

    function openModal(data) {
        state.comm     = parseFloat(data.commission) || 0;
        state.advances = parseFloat(data.advances) || 0;

        nameEl.textContent  = data.vendorName;
        commEl.textContent  = fmt(state.comm);
        advTotEl.textContent= fmt(state.advances);
        vendorEl.value      = data.vendorId;
        amountEl.max        = Math.round(state.comm);
        amountEl.value      = Math.round(state.comm); // default: todo
        msgEl.classList.add('hidden');
        sourceSel.value = '';
        recompute();

        submitBtn.disabled = false;
        submitBtn.textContent = 'Confirmar pago';
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
    function closeModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    // Delegación: sobrevive re-renders de Vue y se enlaza aunque los botones
    // se inserten tarde en el DOM.
    document.addEventListener('click', function(e) {
        var btn = e.target.closest ? e.target.closest('.btn-pay-comm') : null;
        if (btn) {
            e.preventDefault();
            openModal({
                vendorId:   btn.getAttribute('data-vendor-id'),
                vendorName: btn.getAttribute('data-vendor-name'),
                commission: btn.getAttribute('data-commission'),
                advances:   btn.getAttribute('data-advances'),
            });
            return;
        }
        var closer = e.target.closest ? e.target.closest('.pcm-close') : null;
        if (closer && modal.contains(closer)) { e.preventDefault(); closeModal(); }
    });
    amountEl.addEventListener('input', recompute);
    amountAll.addEventListener('click', function() { amountEl.value = Math.round(state.comm); recompute(); });

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        msgEl.classList.add('hidden');
        var amount = parseFloat(amountEl.value) || 0;
        if (amount <= 0) {
            msgEl.textContent = 'Ingresa un monto válido.';
            msgEl.classList.remove('hidden');
            return;
        }
        var sourceVal = sourceSel.value;
        var needsCash = !sourceWrap.classList.contains('hidden');
        if (needsCash && !sourceVal) {
            msgEl.textContent = 'Selecciona caja o banco.';
            msgEl.classList.remove('hidden');
            return;
        }
        var parts = sourceVal ? sourceVal.split(':') : ['caja','0'];
        var body = new FormData();
        body.append('vendor_id', vendorEl.value);
        body.append('source_type', parts[0]);
        body.append('source_id', parts[1]);
        body.append('amount', amount);

        submitBtn.disabled = true;
        submitBtn.textContent = 'Procesando…';

        fetch('<?= base_url() ?>sisvent/admin/settlements/payCommission', {
            method: 'POST', body: body, credentials: 'same-origin'
        })
        .then(function(r) { return r.json(); })
        .then(function(json) {
            if (json.success) {
                alert(json.message || 'Liquidación realizada.');
                window.location.reload();
            } else {
                msgEl.textContent = json.message || 'Error procesando el pago.';
                msgEl.classList.remove('hidden');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Confirmar pago';
            }
        })
        .catch(function(err) {
            msgEl.textContent = 'Error de red: ' + err.message;
            msgEl.classList.remove('hidden');
            submitBtn.disabled = false;
            submitBtn.textContent = 'Confirmar pago';
        });
    });
})();
</script>

<?php $this->load->view('sisvent/layouts/footer'); ?>
</body>
</html>
