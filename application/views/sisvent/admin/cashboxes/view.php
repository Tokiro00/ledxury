<?php
    $role = $this->session->userdata('user_data')['role'];
?>
<!DOCTYPE html>
<html lang="en">
    <title><?php echo $cashbox->name; ?></title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<body>
    <div id="bars" class="flex h-screen bg-gray-50"
         v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">

        <?php $this->load->view('sisvent/layouts/sidebar',
            array('thisFile' => $_ci_view, 'role' => $role)); ?>

        <div class="flex flex-col flex-1 w-full">
            <?php $this->load->view('sisvent/layouts/navbar'); ?>

            <main class="h-full overflow-y-auto">
                <div class="px-6 mx-auto grid">

                    <!-- ENCABEZADO -->
                    <div class="flex items-center justify-between mb-4 mt-2">
                        <h2 class="text-lg font-semibold text-gray-600"><?php echo $cashbox->name; ?></h2>
                        <div class="flex items-center space-x-3">
                            <!-- Botones según estado -->
                            <?php if($cashbox->status == 'cerrada'): ?>
                                <button id="btn-open-cashbox" type="button"
                                        class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700">
                                    Abrir Caja
                                </button>
                            <?php elseif($cashbox->status == 'abierta'): ?>
                                <button id="btn-close-cashbox" type="button"
                                        class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">
                                    Cerrar Caja
                                </button>
                            <?php endif; ?>
                            <a href="<?php echo base_url(); ?>sisvent/admin/cashboxes/reporte_diario/<?php echo $cashbox->idCashbox; ?>"
                               class="px-4 py-2 text-sm font-medium text-mam-blue-dark border border-mam-blue-dark rounded-lg hover:bg-mam-blue-dark hover:text-white">
                                Reporte
                            </a>
                            <a href="<?php echo base_url(); ?>sisvent/admin/cashboxes"
                               class="text-sm text-mam-blue-dark hover:underline">
                                ← Volver
                            </a>
                        </div>
                    </div>

                    <!-- TARJETAS DE RESUMEN -->
                    <div class="grid grid-cols-4 gap-4 mb-6">
                        <!-- Estado -->
                        <div class="bg-white rounded-lg shadow-sm p-4">
                            <p class="text-xs text-gray-500 uppercase">Estado</p>
                            <?php
                                switch ($cashbox->status) {
                                    case 'abierta':
                                        $statusClass = 'text-green-700 bg-green-100';
                                        break;
                                    case 'cerrada':
                                        $statusClass = 'text-gray-600 bg-gray-100';
                                        break;
                                    case 'arqueo':
                                        $statusClass = 'text-yellow-700 bg-yellow-100';
                                        break;
                                    case 'bloqueada':
                                        $statusClass = 'text-red-700 bg-red-100';
                                        break;
                                    default:
                                        $statusClass = 'text-gray-600 bg-gray-100';
                                        break;
                                }
                            ?>
                            <span class="inline-block mt-1 px-2 py-1 text-sm font-semibold rounded-full <?php echo $statusClass; ?>">
                                <?php echo ucfirst($cashbox->status); ?>
                            </span>
                        </div>

                        <!-- Saldo Actual -->
                        <div class="bg-white rounded-lg shadow-sm p-4">
                            <p class="text-xs text-gray-500 uppercase">Saldo Actual</p>
                            <p class="text-xl font-bold text-gray-800 mt-1">
                                $<?php echo number_format($cashbox->currentBalance, 2); ?>
                            </p>
                        </div>

                        <!-- Tipo -->
                        <div class="bg-white rounded-lg shadow-sm p-4">
                            <p class="text-xs text-gray-500 uppercase">Tipo</p>
                            <p class="text-sm font-semibold text-gray-700 mt-1 capitalize"><?php echo $cashbox->type; ?></p>
                        </div>

                        <!-- Código -->
                        <div class="bg-white rounded-lg shadow-sm p-4">
                            <p class="text-xs text-gray-500 uppercase">Código</p>
                            <p class="text-sm font-semibold text-gray-700 mt-1"><?php echo $cashbox->code; ?></p>
                        </div>
                    </div>

                    <!-- INFO DE APERTURA (si está abierta) -->
                    <?php if($cashbox->status == 'abierta'): ?>
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                            <p class="text-sm text-green-700">
                                <strong>Abierta por:</strong> <?php echo $cashbox->openedBy; ?> |
                                <strong>Fecha:</strong> <?php echo date('d/m/Y H:i', strtotime($cashbox->openedAt)); ?> |
                                <strong>Saldo inicial:</strong> $<?php echo number_format($cashbox->initialBalance, 2); ?>
                            </p>
                        </div>
                    <?php endif; ?>

                    <!-- ÚLTIMO CIERRE -->
                    <?php if($lastClosure): ?>
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6">
                            <p class="text-xs font-semibold text-gray-500 uppercase mb-2">Último Cierre</p>
                            <div class="grid grid-cols-4 gap-4 text-sm">
                                <div>
                                    <p class="text-gray-500">Fecha</p>
                                    <p class="font-semibold text-gray-700"><?php echo date('d/m/Y H:i', strtotime($lastClosure->closureDate)); ?></p>
                                </div>
                                <div>
                                    <p class="text-gray-500">Saldo Esperado</p>
                                    <p class="font-semibold text-gray-700">$<?php echo number_format($lastClosure->expectedBalance, 2); ?></p>
                                </div>
                                <div>
                                    <p class="text-gray-500">Saldo Real</p>
                                    <p class="font-semibold text-gray-700">$<?php echo number_format($lastClosure->actualBalance, 2); ?></p>
                                </div>
                                <div>
                                    <p class="text-gray-500">Diferencia</p>
                                    <p class="font-semibold <?php echo ($lastClosure->difference >= 0) ? 'text-green-600' : 'text-red-600'; ?>">
                                        <?php echo ($lastClosure->difference >= 0) ? '+' : ''; ?>$<?php echo number_format($lastClosure->difference, 2); ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- MOVIMIENTOS RECIENTES -->
                    <div class="w-full overflow-hidden rounded-lg shadow-xs">
                        <div class="px-4 py-3 bg-gray-50 border-b">
                            <p class="text-xs font-semibold tracking-wide text-gray-500 uppercase">Movimientos</p>
                        </div>
                        <div class="w-full overflow-x-auto">
                            <table class="w-full whitespace-no-wrap">
                                <thead>
                                    <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                                        <th class="px-4 py-3">Fecha</th>
                                        <th class="px-4 py-3">Tipo</th>
                                        <th class="px-4 py-3">Concepto</th>
                                        <th class="px-4 py-3">Categoría</th>
                                        <th class="px-4 py-3">Monto</th>
                                        <th class="px-4 py-3">Estado</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y">
                                    <?php if(!empty($movements)): ?>
                                        <?php foreach($movements as $mov): ?>
                                            <tr class="text-gray-700">
                                                <td class="px-4 py-3 text-sm">
                                                    <?php echo date('d/m/Y H:i', strtotime($mov->movementDate)); ?>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <?php
                                                        switch ($mov->movementType) {
                                                            case 'ingreso':
                                                            case 'apertura':
                                                                $typeClass = 'text-green-700 bg-green-100';
                                                                break;
                                                            case 'egreso':
                                                            case 'cierre':
                                                                $typeClass = 'text-red-700 bg-red-100';
                                                                break;
                                                            case 'transferencia':
                                                                $typeClass = 'text-blue-700 bg-blue-100';
                                                                break;
                                                            case 'ajuste':
                                                                $typeClass = 'text-yellow-700 bg-yellow-100';
                                                                break;
                                                            default:
                                                                $typeClass = 'text-gray-600 bg-gray-100';
                                                                break;
                                                        }
                                                    ?>
                                                    <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $typeClass; ?>">
                                                        <?php echo ucfirst($mov->movementType); ?>
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3 text-sm"><?php echo $mov->concept; ?></td>
                                                <td class="px-4 py-3 text-sm capitalize"><?php echo str_replace('_', ' ', $mov->category); ?></td>
                                                <td class="px-4 py-3 text-sm">
                                                    <?php
                                                        $isNegative = in_array($mov->movementType, ['egreso', 'cierre']);
                                                        $color = $isNegative ? 'text-red-600' : 'text-green-600';
                                                    ?>
                                                    <span class="<?php echo $color; ?>">
                                                        <?php echo $isNegative ? '-' : '+'; ?>$<?php echo number_format($mov->amount, 2); ?>
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <span class="px-2 py-1 text-xs font-semibold rounded-full
                                                        <?php echo ($mov->status == 'anulado') ? 'text-red-700 bg-red-100' : 'text-gray-600 bg-gray-100'; ?>">
                                                        <?php echo ucfirst($mov->status); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="px-4 py-3 text-sm text-center text-gray-500">
                                                No hay movimientos
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </main>
        </div>
    </div>

    <?php $this->load->view('sisvent/layouts/footer'); ?>

    <!-- ========================================================================
         MODAL: APERTURA DE CAJA
         ======================================================================== -->
    <div id="modal-open-cashbox" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden z-50">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4">
            <div class="flex items-center justify-between p-4 border-b">
                <h3 class="text-lg font-semibold text-gray-700">Abrir Caja</h3>
                <button id="btn-close-modal-open" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="p-4">
                <p class="text-sm text-gray-600 mb-4">
                    Ingrese el saldo inicial en efectivo para abrir la caja <strong><?php echo $cashbox->name; ?></strong>.
                </p>
                <label class="block text-sm">
                    <span class="text-gray-700">Saldo Inicial</span>
                    <div class="flex items-center mt-1">
                        <span class="px-3 py-2 bg-gray-100 border border-r-0 border-gray-300 rounded-l-lg text-gray-600">$</span>
                        <input class="form-input rounded-l-none" type="number" id="input-initial-balance"
                               step="0.01" min="0" value="0" placeholder="0.00"/>
                    </div>
                </label>
            </div>
            <div class="flex items-center justify-end p-4 border-t space-x-3">
                <button id="btn-cancel-open" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">
                    Cancelar
                </button>
                <button id="btn-confirm-open"
                        class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700">
                    Abrir Caja
                </button>
            </div>
            <div id="modal-open-message" class="px-4 pb-3 hidden"></div>
        </div>
    </div>

    <!-- ========================================================================
         MODAL: CIERRE DE CAJA (ARQUEO)
         ======================================================================== -->
    <div id="modal-close-cashbox" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden z-50">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-xl mx-4" style="max-height:90vh; overflow-y:auto">
            <div class="flex items-center justify-between p-4 border-b bg-white sticky top-0">
                <h3 class="text-lg font-semibold text-gray-700">Cierre de Caja — Arqueo</h3>
                <button id="btn-close-modal-close" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="p-4">
                <!-- Resumen -->
                <div class="bg-gray-50 rounded-lg p-3 mb-4">
                    <div class="grid grid-cols-3 gap-3 text-sm">
                        <div>
                            <p class="text-gray-500">Saldo Apertura</p>
                            <p class="font-semibold text-gray-700">$<?php echo number_format($cashbox->initialBalance, 2); ?></p>
                        </div>
                        <div>
                            <p class="text-gray-500">Saldo Esperado</p>
                            <p class="font-semibold text-gray-700" id="expected-balance">
                                <?php if($expectedInfo): ?>$<?php echo number_format($expectedInfo['expectedBalance'], 2); ?><?php else: ?>-<?php endif; ?>
                            </p>
                        </div>
                        <div>
                            <p class="text-gray-500">Diferencia</p>
                            <p class="font-semibold" id="difference-display">-</p>
                        </div>
                    </div>
                </div>

                <!-- Conteo de billetes y monedas -->
                <p class="text-sm font-semibold text-gray-600 mb-2">Conteo de Efectivo</p>
                <div class="border rounded-lg overflow-hidden mb-4">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="text-left px-3 py-2 text-gray-600 font-semibold">Denominación</th>
                                <th class="text-center px-3 py-2 text-gray-600 font-semibold" style="width:80px">Cantidad</th>
                                <th class="text-right px-3 py-2 text-gray-600 font-semibold" style="width:100px">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr class="bg-blue-50">
                                <td colspan="3" class="px-3 py-1 text-xs font-semibold text-blue-600 uppercase tracking-wide">Billetes</td>
                            </tr>
                            <?php
                                $bills = array(100000, 50000, 20000, 10000, 5000, 2000, 1000, 500);
                                foreach ($bills as $denom):
                            ?>
                            <tr>
                                <td class="px-3 py-1 text-gray-700">$<?php echo number_format($denom); ?></td>
                                <td class="px-3 py-1">
                                    <input type="number" class="arqueo-qty form-input text-center p-1" data-type="bill" data-denom="<?php echo $denom; ?>" min="0" value="0" style="width:70px"/>
                                </td>
                                <td class="px-3 py-1 text-right text-gray-700" id="sub-bill-<?php echo $denom; ?>">$0.00</td>
                            </tr>
                            <?php endforeach; ?>

                            <tr class="bg-yellow-50">
                                <td colspan="3" class="px-3 py-1 text-xs font-semibold text-yellow-600 uppercase tracking-wide">Monedas</td>
                            </tr>
                            <?php
                                $coins = array(500, 200, 100, 50, 20, 10);
                                foreach ($coins as $denom):
                            ?>
                            <tr>
                                <td class="px-3 py-1 text-gray-700">$<?php echo number_format($denom); ?></td>
                                <td class="px-3 py-1">
                                    <input type="number" class="arqueo-qty form-input text-center p-1" data-type="coin" data-denom="<?php echo $denom; ?>" min="0" value="0" style="width:70px"/>
                                </td>
                                <td class="px-3 py-1 text-right text-gray-700" id="sub-coin-<?php echo $denom; ?>">$0.00</td>
                            </tr>
                            <?php endforeach; ?>

                            <tr class="bg-gray-100 font-semibold">
                                <td class="px-3 py-2 text-gray-700">Total Contado</td>
                                <td></td>
                                <td class="px-3 py-2 text-right text-gray-800" id="arqueo-total">$0.00</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Saldo real contado -->
                <label class="block text-sm">
                    <span class="text-gray-700">Saldo Real Contado <span class="text-gray-400">(se actualiza desde el arqueo)</span></span>
                    <div class="flex items-center mt-1">
                        <span class="px-3 py-2 bg-gray-100 border border-r-0 border-gray-300 rounded-l-lg text-gray-600">$</span>
                        <input class="form-input rounded-l-none" type="number" id="input-actual-balance"
                               step="0.01" min="0" value="0" placeholder="0.00"/>
                    </div>
                </label>

                <!-- Notas -->
                <label class="block text-sm mt-4">
                    <span class="text-gray-700">Observaciones</span>
                    <textarea class="form-input" id="input-closure-notes" rows="2"
                              placeholder="Observaciones opcionales..."></textarea>
                </label>
            </div>
            <div class="flex items-center justify-end p-4 border-t space-x-3">
                <button id="btn-cancel-close" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">
                    Cancelar
                </button>
                <button id="btn-confirm-close"
                        class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">
                    Cerrar Caja
                </button>
            </div>
            <div id="modal-close-message" class="px-4 pb-3 hidden"></div>
        </div>
    </div>

    <script>
    (function() {
        var cashboxId = <?php echo $cashbox->idCashbox; ?>;
        var baseUrl = '<?php echo base_url(); ?>';
        var expectedBalance = <?php echo $expectedInfo ? $expectedInfo['expectedBalance'] : 0; ?>;

        function formatMoney(val) {
            return '$' + val.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        }

        function updateDifference() {
            var actual = parseFloat($('#input-actual-balance').val()) || 0;
            var diff = actual - expectedBalance;
            var $el = $('#difference-display');
            $el.text((diff >= 0 ? '+' : '') + formatMoney(diff));
            $el.removeClass('text-green-600 text-red-600');
            $el.addClass(diff >= 0 ? 'text-green-600' : 'text-red-600');
        }

        // ====================================================================
        // MODAL APERTURA
        // ====================================================================
        $('#btn-open-cashbox').on('click', function() {
            $('#modal-open-message').addClass('hidden').empty();
            $('#modal-open-cashbox').removeClass('hidden');
        });

        $('#btn-close-modal-open, #btn-cancel-open').on('click', function() {
            $('#modal-open-cashbox').addClass('hidden');
        });

        $('#btn-confirm-open').on('click', function() {
            var initialBalance = $('#input-initial-balance').val();

            $.ajax({
                url: baseUrl + 'sisvent/admin/cashboxes/open/' + cashboxId,
                type: 'POST',
                data: { initialBalance: initialBalance },
                success: function(response) {
                    var parts = response.split(':');
                    var type = parts[0];
                    var message = parts.slice(1).join(':');

                    if (type === 'success') {
                        $('#modal-open-message')
                            .removeClass('hidden')
                            .addClass('text-green-600')
                            .html('<p class="text-sm">' + message + '</p>');
                        setTimeout(function() { location.reload(); }, 1500);
                    } else {
                        $('#modal-open-message')
                            .removeClass('hidden')
                            .addClass('text-red-600')
                            .html('<p class="text-sm">' + message + '</p>');
                    }
                },
                error: function() {
                    $('#modal-open-message')
                        .removeClass('hidden')
                        .addClass('text-red-600')
                        .html('<p class="text-sm">Error de conexión</p>');
                }
            });
        });

        // ====================================================================
        // MODAL CIERRE (ARQUEO)
        // ====================================================================
        $('#btn-close-cashbox').on('click', function() {
            $('#modal-close-message').addClass('hidden').empty();
            $('#modal-close-cashbox').removeClass('hidden');
            $('.arqueo-qty').val('0');
            $('#arqueo-total').text('$0.00');
            $('.arqueo-qty').each(function() {
                $('#sub-' + $(this).data('type') + '-' + $(this).data('denom')).text('$0.00');
            });
            $('#input-actual-balance').val('0');
            $('#input-closure-notes').val('');
            $('#difference-display').text('-').removeClass('text-green-600 text-red-600');
        });

        $('#btn-close-modal-close, #btn-cancel-close').on('click', function() {
            $('#modal-close-cashbox').addClass('hidden');
        });

        // Arqueo: recalcular subtotales y total al cambiar cantidad
        $(document).on('input', '.arqueo-qty', function() {
            var denom = parseInt($(this).data('denom'));
            var type = $(this).data('type');
            var qty = parseInt($(this).val()) || 0;

            $('#sub-' + type + '-' + denom).text(formatMoney(denom * qty));

            var total = 0;
            $('.arqueo-qty').each(function() {
                total += parseInt($(this).data('denom')) * (parseInt($(this).val()) || 0);
            });

            $('#arqueo-total').text(formatMoney(total));
            $('#input-actual-balance').val(total.toFixed(2));
            updateDifference();
        });

        // Permitir editar saldo real manualmente
        $('#input-actual-balance').on('input', updateDifference);

        // Confirmar cierre
        $('#btn-confirm-close').on('click', function() {
            var actualBalance = $('#input-actual-balance').val();
            var notes = $('#input-closure-notes').val();

            var billCount = {};
            $('.arqueo-qty').each(function() {
                var qty = parseInt($(this).val()) || 0;
                if (qty > 0) {
                    billCount[$(this).data('type') + '_' + $(this).data('denom')] = qty;
                }
            });

            $.ajax({
                url: baseUrl + 'sisvent/admin/cashboxes/close/' + cashboxId,
                type: 'POST',
                data: {
                    actualBalance: actualBalance,
                    notes: notes,
                    billCount: JSON.stringify(billCount)
                },
                success: function(response) {
                    var parts = response.split(':');
                    var type = parts[0];
                    var message = parts.slice(1).join(':');

                    var colorClass = (type === 'success') ? 'text-green-600' : (type === 'warning') ? 'text-yellow-600' : 'text-red-600';

                    $('#modal-close-message')
                        .removeClass('hidden')
                        .addClass(colorClass)
                        .html('<p class="text-sm">' + message + '</p>');

                    if (type === 'success' || type === 'warning') {
                        setTimeout(function() { location.reload(); }, 2000);
                    }
                },
                error: function() {
                    $('#modal-close-message')
                        .removeClass('hidden')
                        .addClass('text-red-600')
                        .html('<p class="text-sm">Error de conexión</p>');
                }
            });
        });

    })();
    </script>
</body>
</html>
