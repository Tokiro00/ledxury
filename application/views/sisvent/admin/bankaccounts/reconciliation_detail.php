<?php
    $role = $this->session->userdata('user_data')['role'];
?>
<!DOCTYPE html>
<html lang="en">
    <title>Conciliacion #<?php echo $reconciliation->idReconciliation; ?> — <?php echo $bankAccount->bankName; ?></title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<body>
    <div id="bars" class="flex h-screen bg-gray-50"
         v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">

        <?php $this->load->view('sisvent/layouts/sidebar',
            array('thisFile' => $_ci_view, 'role' => $role)); ?>

        <div class="flex flex-col flex-1 w-full">
            <?php $this->load->view('sisvent/layouts/navbar'); ?>

            <main class="h-full overflow-y-auto">
                <div class="px-4 py-4 mx-auto w-full">

                    <!-- Header -->
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-600">
                            Conciliacion Bancaria — <?php echo $bankAccount->bankName; ?> (***<?php echo substr($bankAccount->accountNumber, -4); ?>)
                        </h2>
                        <div class="flex items-center gap-3">
                            <a href="<?php echo base_url(); ?>sisvent/admin/bankaccounts/reconciliationReport/<?php echo $reconciliation->idReconciliation; ?>"
                               class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700">
                                Ver Reporte
                            </a>
                            <a href="<?php echo base_url(); ?>sisvent/admin/bankaccounts/view/<?php echo $bankAccount->idBankAccount; ?>"
                               class="text-sm text-mam-blue-petroleo hover:underline">&larr; Volver</a>
                        </div>
                    </div>

                    <!-- Flash Messages -->
                    <?php if($this->session->flashdata('error')): ?>
                        <div class="bg-red-50 border border-red-200 rounded-lg p-3 mb-4">
                            <p class="text-sm text-red-700"><?php echo $this->session->flashdata('error'); ?></p>
                        </div>
                    <?php endif; ?>
                    <?php if($this->session->flashdata('success')): ?>
                        <div class="bg-green-50 border border-green-200 rounded-lg p-3 mb-4">
                            <p class="text-sm text-green-700"><?php echo $this->session->flashdata('success'); ?></p>
                        </div>
                    <?php endif; ?>

                    <!-- Summary Stats -->
                    <div class="grid grid-cols-6 gap-3 mb-4">
                        <div class="bg-white rounded-lg shadow-sm p-3 border-l-4 border-blue-600">
                            <p class="text-xs text-gray-500 uppercase">Total Lineas</p>
                            <p class="text-xl font-bold text-blue-700"><?php echo $stats ? $stats->total_lines : 0; ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm p-3 border-l-4 border-green-500">
                            <p class="text-xs text-gray-500 uppercase">Conciliadas</p>
                            <p class="text-xl font-bold text-green-700"><?php echo $stats ? ((int)$stats->matched + (int)$stats->manual) : 0; ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm p-3 border-l-4 border-yellow-500">
                            <p class="text-xs text-gray-500 uppercase">Pendientes</p>
                            <p class="text-xl font-bold text-yellow-700"><?php echo $stats ? $stats->pending : 0; ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm p-3 border-l-4 border-gray-400">
                            <p class="text-xs text-gray-500 uppercase">Saldo Libros</p>
                            <p class="text-lg font-bold text-gray-700">$<?php echo number_format($reconciliation->bookBalance, 0, ',', '.'); ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm p-3 border-l-4 border-gray-400">
                            <p class="text-xs text-gray-500 uppercase">Saldo Banco</p>
                            <p class="text-lg font-bold text-gray-700">$<?php echo number_format($reconciliation->bankBalance, 0, ',', '.'); ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm p-3 border-l-4 <?php echo ($reconciliation->difference == 0) ? 'border-green-500' : 'border-red-500'; ?>">
                            <p class="text-xs text-gray-500 uppercase">Diferencia</p>
                            <p class="text-lg font-bold <?php echo ($reconciliation->difference == 0) ? 'text-green-700' : 'text-red-700'; ?>">
                                $<?php echo number_format($reconciliation->difference, 0, ',', '.'); ?>
                            </p>
                        </div>
                    </div>

                    <!-- Two-Panel Workspace -->
                    <div class="grid grid-cols-2 gap-4">

                        <!-- LEFT PANEL: Bank Statement Lines -->
                        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                            <div class="px-4 py-3 border-b" style="background:#1B365D;">
                                <h3 class="text-sm font-semibold text-white">Extracto Bancario</h3>
                            </div>
                            <div class="overflow-x-auto" style="max-height:600px; overflow-y:auto;">
                                <table class="w-full text-xs">
                                    <thead>
                                        <tr style="background:#1B365D; color:white;">
                                            <th class="px-2 py-2 text-left">#</th>
                                            <th class="px-2 py-2 text-left">Fecha</th>
                                            <th class="px-2 py-2 text-left">Descripcion</th>
                                            <th class="px-2 py-2 text-right">Debito</th>
                                            <th class="px-2 py-2 text-right">Credito</th>
                                            <th class="px-2 py-2 text-center">Estado</th>
                                            <th class="px-2 py-2 text-center">Accion</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y">
                                        <?php if(!empty($lines)): ?>
                                            <?php foreach($lines as $line): ?>
                                                <?php
                                                    $isMatched = in_array($line->matchStatus, ['matched', 'manual']);
                                                    $rowBg = $isMatched ? 'bg-green-50' : ($line->matchStatus === 'unmatched_bank' ? 'bg-blue-50' : 'bg-yellow-50');
                                                ?>
                                                <tr class="<?php echo $rowBg; ?> hover:bg-gray-100 bank-line"
                                                    data-line-id="<?php echo $line->idLine; ?>"
                                                    data-amount="<?php echo (float)$line->debit > 0 ? $line->debit : $line->credit; ?>"
                                                    data-type="<?php echo (float)$line->debit > 0 ? 'egreso' : 'ingreso'; ?>">
                                                    <td class="px-2 py-2"><?php echo $line->rowNumber; ?></td>
                                                    <td class="px-2 py-2"><?php echo date('d/m', strtotime($line->transactionDate)); ?></td>
                                                    <td class="px-2 py-2 truncate" style="max-width:150px;" title="<?php echo htmlspecialchars($line->description); ?>">
                                                        <?php echo htmlspecialchars(mb_substr($line->description, 0, 30)); ?>
                                                        <?php if($line->reference): ?>
                                                            <span class="text-gray-400">(<?php echo $line->reference; ?>)</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="px-2 py-2 text-right text-red-600"><?php echo (float)$line->debit > 0 ? '$' . number_format($line->debit, 0, ',', '.') : ''; ?></td>
                                                    <td class="px-2 py-2 text-right text-green-600"><?php echo (float)$line->credit > 0 ? '$' . number_format($line->credit, 0, ',', '.') : ''; ?></td>
                                                    <td class="px-2 py-2 text-center">
                                                        <?php if($isMatched): ?>
                                                            <span class="px-1 py-0.5 text-xs rounded bg-green-100 text-green-800">OK</span>
                                                        <?php else: ?>
                                                            <span class="px-1 py-0.5 text-xs rounded bg-yellow-100 text-yellow-800">Pend.</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="px-2 py-2 text-center">
                                                        <?php if($isMatched): ?>
                                                            <button class="btn-unmatch text-xs text-red-600 hover:underline" data-line-id="<?php echo $line->idLine; ?>">Deshacer</button>
                                                        <?php else: ?>
                                                            <button class="btn-select-line text-xs text-blue-600 hover:underline font-semibold" data-line-id="<?php echo $line->idLine; ?>">Seleccionar</button>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">No hay lineas</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- RIGHT PANEL: System Movements -->
                        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                            <div class="px-4 py-3 border-b" style="background:#1B365D;">
                                <h3 class="text-sm font-semibold text-white">Movimientos del Sistema</h3>
                            </div>
                            <div class="overflow-x-auto" style="max-height:600px; overflow-y:auto;">
                                <table class="w-full text-xs">
                                    <thead>
                                        <tr style="background:#1B365D; color:white;">
                                            <th class="px-2 py-2 text-left">ID</th>
                                            <th class="px-2 py-2 text-left">Fecha</th>
                                            <th class="px-2 py-2 text-left">Concepto</th>
                                            <th class="px-2 py-2 text-left">Tipo</th>
                                            <th class="px-2 py-2 text-right">Monto</th>
                                            <th class="px-2 py-2 text-center">Conc.</th>
                                            <th class="px-2 py-2 text-center">Accion</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y">
                                        <?php if(!empty($systemMovements)): ?>
                                            <?php foreach($systemMovements as $mov): ?>
                                                <?php
                                                    $isReconciled = !empty($mov->reconciled) && $mov->reconciled == 1;
                                                    $movBg = $isReconciled ? 'bg-green-50' : '';
                                                ?>
                                                <tr class="<?php echo $movBg; ?> hover:bg-gray-100 system-movement"
                                                    data-movement-id="<?php echo $mov->idMovement; ?>"
                                                    data-amount="<?php echo $mov->amount; ?>"
                                                    data-type="<?php echo $mov->movementType; ?>">
                                                    <td class="px-2 py-2 font-mono"><?php echo $mov->idMovement; ?></td>
                                                    <td class="px-2 py-2"><?php echo date('d/m', strtotime($mov->movementDate)); ?></td>
                                                    <td class="px-2 py-2 truncate" style="max-width:150px;" title="<?php echo htmlspecialchars($mov->concept); ?>">
                                                        <?php echo htmlspecialchars(mb_substr($mov->concept, 0, 30)); ?>
                                                    </td>
                                                    <td class="px-2 py-2">
                                                        <span class="px-1 py-0.5 text-xs rounded <?php echo ($mov->movementType === 'ingreso') ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                                            <?php echo ucfirst($mov->movementType); ?>
                                                        </span>
                                                    </td>
                                                    <td class="px-2 py-2 text-right font-semibold">$<?php echo number_format($mov->amount, 0, ',', '.'); ?></td>
                                                    <td class="px-2 py-2 text-center">
                                                        <?php if($isReconciled): ?>
                                                            <span class="text-green-600">&#10003;</span>
                                                        <?php else: ?>
                                                            <span class="text-gray-400">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="px-2 py-2 text-center">
                                                        <?php if(!$isReconciled): ?>
                                                            <button class="btn-match-movement text-xs text-blue-600 hover:underline font-semibold" data-movement-id="<?php echo $mov->idMovement; ?>">Conciliar</button>
                                                        <?php else: ?>
                                                            <span class="text-xs text-gray-400">Conciliado</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">No hay movimientos en este periodo</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div><!-- /grid -->

                    <!-- Match indicator -->
                    <div class="mt-4 p-3 bg-gray-100 rounded-lg text-sm text-center hidden" id="match-indicator">
                        <span id="match-indicator-text"></span>
                        <button class="ml-3 px-3 py-1 bg-red-100 text-red-700 rounded text-xs hover:bg-red-200" id="btn-cancel-selection">Cancelar</button>
                    </div>

                    <!-- Legend -->
                    <div class="mt-4 flex items-center gap-6 text-xs text-gray-500">
                        <span><span class="inline-block w-3 h-3 bg-green-100 border border-green-300 rounded mr-1"></span> Conciliado</span>
                        <span><span class="inline-block w-3 h-3 bg-yellow-100 border border-yellow-300 rounded mr-1"></span> Pendiente</span>
                        <span><span class="inline-block w-3 h-3 bg-blue-100 border border-blue-300 rounded mr-1"></span> Solo en banco</span>
                    </div>

                </div>
            </main>
        </div>
    </div>

    <?php $this->load->view('sisvent/layouts/footer'); ?>

    <script>
    (function() {
        var selectedLineId = null;
        var selectedMovementId = null;
        var bankAccountId = <?php echo $bankAccount->idBankAccount; ?>;
        var csrfKey = '<?php echo $this->security->get_csrf_token_name(); ?>';
        var csrfHash = '<?php echo $this->security->get_csrf_hash(); ?>';

        function showIndicator(text) {
            $('#match-indicator-text').text(text);
            $('#match-indicator').removeClass('hidden');
        }

        function hideIndicator() {
            $('#match-indicator').addClass('hidden');
            selectedLineId = null;
            selectedMovementId = null;
            $('.bank-line').removeClass('ring-2 ring-blue-500');
            $('.system-movement').removeClass('ring-2 ring-blue-500');
        }

        function doMatch(lineId, movementId) {
            var data = { lineId: lineId, movementId: movementId };
            data[csrfKey] = csrfHash;

            $.ajax({
                url: '<?php echo base_url(); ?>sisvent/admin/bankaccounts/matchItems',
                type: 'POST',
                data: data,
                dataType: 'json',
                headers: { 'Authkey': csrfHash },
                success: function(resp) {
                    if (resp.success) {
                        location.reload();
                    } else {
                        alert(resp.message || 'Error al conciliar');
                    }
                },
                error: function() { alert('Error de conexion'); }
            });
        }

        // Select bank line
        $(document).on('click', '.btn-select-line', function() {
            selectedLineId = $(this).data('line-id');
            $('.bank-line').removeClass('ring-2 ring-blue-500');
            $(this).closest('tr').addClass('ring-2 ring-blue-500');
            showIndicator('Linea seleccionada. Ahora haz clic en "Conciliar" en el movimiento correspondiente.');

            if (selectedMovementId) {
                doMatch(selectedLineId, selectedMovementId);
            }
        });

        // Match system movement
        $(document).on('click', '.btn-match-movement', function() {
            selectedMovementId = $(this).data('movement-id');
            $('.system-movement').removeClass('ring-2 ring-blue-500');
            $(this).closest('tr').addClass('ring-2 ring-blue-500');

            if (selectedLineId) {
                doMatch(selectedLineId, selectedMovementId);
            } else {
                showIndicator('Movimiento seleccionado. Ahora haz clic en "Seleccionar" en la linea del extracto.');
            }
        });

        // Cancel selection
        $(document).on('click', '#btn-cancel-selection', function() {
            hideIndicator();
        });

        // Unmatch
        $(document).on('click', '.btn-unmatch', function() {
            if (!confirm('Deshacer esta conciliacion?')) return;
            var lineId = $(this).data('line-id');
            var data = { lineId: lineId };
            data[csrfKey] = csrfHash;

            $.ajax({
                url: '<?php echo base_url(); ?>sisvent/admin/bankaccounts/unmatchItem',
                type: 'POST',
                data: data,
                dataType: 'json',
                headers: { 'Authkey': csrfHash },
                success: function(resp) {
                    if (resp.success) {
                        location.reload();
                    } else {
                        alert(resp.message || 'Error');
                    }
                },
                error: function() { alert('Error de conexion'); }
            });
        });
    })();
    </script>
</body>
</html>
