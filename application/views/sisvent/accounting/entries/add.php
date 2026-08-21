<?php
    $role = isset($role) ? $role : $this->session->userdata('user_data')['role'];
?>
<!DOCTYPE html>
<html lang="es">
    <title>Nuevo asiento de diario — Ledxury</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
    <style>
        /* Grid de líneas: se busca que capturar sea rápido con el teclado. */
        table.gl { width: 100%; border-collapse: collapse; min-width: 900px; }
        table.gl th { font-size: 10px; text-transform: uppercase; letter-spacing: .05em;
                      color: #6b7280; background: #f9fafb; padding: 8px 10px; text-align: left;
                      border-bottom: 1px solid #e5e7eb; white-space: nowrap; }
        table.gl th.num, table.gl td.num { text-align: right; }
        table.gl td { padding: 4px 6px; border-bottom: 1px solid #f3f4f6; }
        table.gl input, table.gl select { width: 100%; height: 34px; padding: 0 8px; font-size: 13px;
                      border: 1px solid transparent; border-radius: 5px; background: transparent; }
        table.gl input:hover, table.gl select:hover { border-color: #e5e7eb; }
        table.gl input:focus, table.gl select:focus { border-color: #17505c; background: #fff; outline: none; }
        table.gl input.amt { text-align: right; font-variant-numeric: tabular-nums; }
        table.gl input.amt.filled { font-weight: 700; }
        table.gl td.rm { width: 34px; text-align: center; }
        .rm-btn { border: 0; background: none; color: #9ca3af; cursor: pointer; font-size: 16px;
                  line-height: 1; padding: 5px 7px; border-radius: 5px; }
        .rm-btn:hover { color: #dc2626; background: #fef2f2; }
        .gl-add { display: block; width: 100%; text-align: left; padding: 9px 12px; font-size: 13px;
                  font-weight: 600; color: #17505c; background: none; border: 0; cursor: pointer; }
        .gl-add:hover { background: #f9fafb; }
        .tot-box { display: flex; justify-content: flex-end; gap: 28px; padding: 12px 16px;
                   background: #f9fafb; border-top: 1px solid #e5e7eb; flex-wrap: wrap; }
        .tot-box .t { text-align: right; }
        .tot-box .t small { display: block; font-size: 10px; text-transform: uppercase;
                            letter-spacing: .05em; color: #6b7280; }
        .tot-box .t b { font-size: 15px; font-variant-numeric: tabular-nums; }
        .tot-box .t.dif b { color: #dc2626; }
        .tot-box .t.dif.ok b { color: #15803d; }
        @media (max-width: 767px) { .gl-scroll { overflow-x: auto; } }
    </style>
<body>
    <div id="bars" class="flex h-screen bg-gray-50"
         v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">

        <?php $this->load->view('sisvent/layouts/sidebar',
            array('thisFile' => $_ci_view, 'role' => $role)); ?>

        <div class="flex flex-col flex-1 w-full">
            <?php $this->load->view('sisvent/layouts/navbar'); ?>

            <main class="h-full overflow-y-auto">
                <div class="px-6 mx-auto grid" style="max-width: 1200px;">

                    <div class="flex items-center justify-between mb-4 mt-2">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-600">Nuevo asiento de diario</h2>
                            <p class="text-xs text-gray-500 mt-1">
                                Agrega las líneas que necesites. El asiento solo se puede guardar cuando Debe = Haber.
                            </p>
                        </div>
                        <a href="<?php echo base_url(); ?>sisvent/accounting/entries"
                           class="text-sm text-mam-blue-petroleo hover:underline">← Volver al libro</a>
                    </div>

                    <?php if ($this->session->flashdata('diario_error')): ?>
                        <div class="p-4 mb-4 text-sm text-red-700 bg-red-50 border border-red-200 rounded-lg">
                            <?php echo htmlspecialchars($this->session->flashdata('diario_error')); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="<?php echo base_url(); ?>sisvent/accounting/entries/save"
                          id="form-asiento" onsubmit="return validarAsiento();">
                        <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>"
                               value="<?= $this->security->get_csrf_hash() ?>">

                        <div class="bg-white rounded-lg shadow-sm p-5 mb-4">
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-5">
                                <label class="block text-sm">
                                    <span class="text-gray-700">Fecha <span class="text-red-500">*</span></span>
                                    <input class="form-input" type="date" name="entryDate" id="f-fecha"
                                           value="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d'); ?>" required/>
                                </label>
                                <label class="block text-sm">
                                    <span class="text-gray-700">Bodega</span>
                                    <select class="form-input form-select" name="storeId">
                                        <?php foreach ($stores as $st): ?>
                                            <option value="<?php echo $st->idStore; ?>"><?php echo $st->name; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <label class="block text-sm md:col-span-2">
                                    <span class="text-gray-700">Concepto del asiento <span class="text-red-500">*</span></span>
                                    <input class="form-input" type="text" name="description" id="f-concepto"
                                           placeholder="Ej: Reclasificación de gastos de agosto" required/>
                                </label>
                            </div>

                            <div class="border border-gray-200 rounded-lg overflow-hidden">
                                <div class="gl-scroll">
                                    <table class="gl" id="gl-table">
                                        <thead>
                                            <tr>
                                                <th style="width:30px;">#</th>
                                                <th style="min-width:240px;">Cuenta <span class="text-red-500">*</span></th>
                                                <th style="min-width:160px;">Auxiliar <span class="normal-case font-normal">(opcional)</span></th>
                                                <th style="min-width:170px;">Detalle <span class="normal-case font-normal">(opcional)</span></th>
                                                <th class="num" style="width:140px;">Debe</th>
                                                <th class="num" style="width:140px;">Haber</th>
                                                <th style="width:34px;"></th>
                                            </tr>
                                        </thead>
                                        <tbody id="gl-body"></tbody>
                                    </table>
                                </div>
                                <button type="button" class="gl-add" id="gl-add">
                                    + Agregar línea
                                    <span class="font-normal text-gray-400">(o Enter en la última fila)</span>
                                </button>
                                <div class="tot-box">
                                    <div class="t"><small>Total Debe</small><b id="tot-debe">0,00</b></div>
                                    <div class="t"><small>Total Haber</small><b id="tot-haber">0,00</b></div>
                                    <div class="t dif" id="tot-dif-box"><small>Diferencia</small><b id="tot-dif">0,00</b></div>
                                </div>
                            </div>

                            <div id="estado" class="mt-3 text-sm px-3 py-2 rounded-lg bg-red-50 text-red-700 border border-red-200">
                                Agrega al menos 2 líneas con montos.
                            </div>
                            <p class="text-xs text-gray-500 mt-2">
                                Doble clic en un campo Debe o Haber vacío lo completa con el monto que cuadra el asiento.
                            </p>

                            <div class="flex items-center justify-end space-x-3 mt-5">
                                <a href="<?php echo base_url(); ?>sisvent/accounting/entries"
                                   class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Cancelar</a>
                                <button type="submit" id="btn-guardar" disabled
                                        class="px-4 py-2 text-sm font-medium text-white bg-mam-blue-petroleo rounded-lg hover:bg-mam-blue disabled:bg-gray-300 disabled:cursor-not-allowed">
                                    Registrar asiento
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>

    <?php $this->load->view('sisvent/layouts/footer'); ?>

    <script>
    (function () {
        // Cuentas y auxiliares se arman una sola vez y se clonan por fila.
        var OPT_CUENTAS = '<option value="">— Cuenta —</option>' +
            <?php
            $o = '';
            foreach ($subaccounts as $sa) {
                $o .= '<option value="' . (int)$sa->id . '">'
                    . htmlspecialchars($sa->pucCode . ' · ' . $sa->accountName, ENT_QUOTES) . '</option>';
            }
            echo json_encode($o);
            ?>;
        var OPT_AUX = '<option value="">— sin auxiliar —</option>' +
            <?php
            $o = '';
            foreach ($auxaccounts as $ax) {
                $o .= '<option value="' . (int)$ax->id . '">'
                    . htmlspecialchars($ax->accountID . ' · ' . $ax->accountName, ENT_QUOTES) . '</option>';
            }
            echo json_encode($o);
            ?>;

        var body = document.getElementById('gl-body');

        function fmt(cents) {
            var s = (Math.abs(cents) / 100).toFixed(2).split('.');
            return (cents < 0 ? '-' : '') +
                s[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.') + ',' + s[1];
        }
        // Acepta "1.234,56" y "1234.56": se quitan los puntos de miles y la
        // coma pasa a punto decimal.
        function cents(v) {
            v = String(v == null ? '' : v).replace(/[.\s]/g, '').replace(',', '.');
            var n = parseFloat(v);
            return isNaN(n) ? 0 : Math.round(n * 100);
        }

        function nuevaFila() {
            var tr = document.createElement('tr');
            tr.innerHTML =
                '<td class="text-center text-xs text-gray-400 rownum"></td>' +
                '<td><select name="cuenta[]" class="form-select">' + OPT_CUENTAS + '</select></td>' +
                '<td><select name="aux[]" class="form-select">' + OPT_AUX + '</select></td>' +
                '<td><input type="text" name="lconc[]" placeholder="Detalle"></td>' +
                '<td class="num"><input type="text" name="debe[]" class="amt" inputmode="decimal" placeholder="0,00"></td>' +
                '<td class="num"><input type="text" name="haber[]" class="amt" inputmode="decimal" placeholder="0,00"></td>' +
                '<td class="rm"><button type="button" class="rm-btn" title="Quitar línea">&times;</button></td>';
            body.appendChild(tr);
            renumerar();
            return tr;
        }

        function renumerar() {
            var filas = body.querySelectorAll('tr');
            for (var i = 0; i < filas.length; i++) {
                filas[i].querySelector('.rownum').textContent = (i + 1);
            }
        }

        function recalcular() {
            var d = 0, h = 0;
            body.querySelectorAll('input[name="debe[]"]').forEach(function (el) {
                var c = cents(el.value); d += c;
                el.classList.toggle('filled', c > 0);
            });
            body.querySelectorAll('input[name="haber[]"]').forEach(function (el) {
                var c = cents(el.value); h += c;
                el.classList.toggle('filled', c > 0);
            });
            document.getElementById('tot-debe').textContent = fmt(d);
            document.getElementById('tot-haber').textContent = fmt(h);
            document.getElementById('tot-dif').textContent = fmt(d - h);

            var difBox = document.getElementById('tot-dif-box');
            var cuadra = (d === h && d > 0);
            difBox.classList.toggle('ok', cuadra);

            // Cuántas líneas están realmente completas
            var completas = 0, problema = '';
            body.querySelectorAll('tr').forEach(function (tr) {
                var cta = tr.querySelector('select[name="cuenta[]"]').value;
                var cd = cents(tr.querySelector('input[name="debe[]"]').value);
                var ch = cents(tr.querySelector('input[name="haber[]"]').value);
                if (!cta && !cd && !ch) return;                       // fila vacía, se ignora
                if (!cta) { problema = 'Hay una línea con monto pero sin cuenta.'; return; }
                if (cd > 0 && ch > 0) { problema = 'Una línea no puede tener Debe y Haber a la vez.'; return; }
                if (cd === 0 && ch === 0) { problema = 'Hay una línea con cuenta pero sin monto.'; return; }
                completas++;
            });

            var est = document.getElementById('estado');
            var btn = document.getElementById('btn-guardar');
            var concepto = document.getElementById('f-concepto').value.trim();
            var ok = false, msg;

            if (problema)            { msg = problema; }
            else if (completas < 2)  { msg = 'Agrega al menos 2 líneas con montos.'; }
            else if (d === 0)        { msg = 'El asiento no puede ser de $0.'; }
            else if (d !== h)        { msg = 'Descuadrado por $' + fmt(Math.abs(d - h)) + '. Ajusta antes de guardar.'; }
            else if (!concepto)      { msg = 'Falta el concepto del asiento.'; }
            else { ok = true; msg = 'Asiento cuadrado: ' + completas + ' líneas por $' + fmt(d) + '.'; }

            est.textContent = msg;
            est.className = 'mt-3 text-sm px-3 py-2 rounded-lg border ' +
                (ok ? 'bg-green-50 text-green-700 border-green-200' : 'bg-red-50 text-red-700 border-red-200');
            btn.disabled = !ok;
            return ok;
        }

        window.validarAsiento = function () {
            if (!recalcular()) return false;
            document.getElementById('btn-guardar').disabled = true;
            return true;
        };

        // Delegado: las filas nacen después de que carga el script.
        document.addEventListener('input', function (e) {
            if (e.target.closest('#gl-body') || e.target.id === 'f-concepto') recalcular();
        });
        document.addEventListener('change', function (e) {
            if (e.target.closest('#gl-body')) recalcular();
        });
        document.addEventListener('click', function (e) {
            if (e.target.id === 'gl-add') { nuevaFila(); return; }
            var rm = e.target.closest('#gl-body .rm-btn');
            if (rm) {
                if (body.querySelectorAll('tr').length <= 2) { recalcular(); return; } // nunca dejar menos de 2
                rm.closest('tr').remove();
                renumerar(); recalcular();
            }
        });
        // Doble clic en un monto vacío: completa lo que falta para cuadrar.
        document.addEventListener('dblclick', function (e) {
            if (!e.target.classList.contains('amt')) return;
            if (cents(e.target.value) !== 0) return;
            var d = 0, h = 0;
            body.querySelectorAll('input[name="debe[]"]').forEach(function (el) { d += cents(el.value); });
            body.querySelectorAll('input[name="haber[]"]').forEach(function (el) { h += cents(el.value); });
            var falta = (e.target.name === 'debe[]') ? (h - d) : (d - h);
            if (falta > 0) { e.target.value = fmt(falta); recalcular(); }
        });
        // Enter en la última fila agrega otra
        document.addEventListener('keydown', function (e) {
            if (e.key !== 'Enter') return;
            var tr = e.target.closest('#gl-body tr');
            if (!tr) return;
            e.preventDefault();
            if (tr === body.lastElementChild) nuevaFila();
        });

        // Arranca con dos líneas, que es el mínimo de un asiento
        nuevaFila(); nuevaFila();
        recalcular();
    })();
    </script>
</body>
</html>
