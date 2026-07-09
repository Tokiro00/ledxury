<?php
    $role = $this->session->userdata('user_data')['role'];
    $last = ($total > 0) ? ceil($total / $limit) : 1;

    // Lookup arrays para nombres legibles
    $cashboxNames = array();
    foreach($cashboxes as $cb) { $cashboxNames[$cb->idCashbox] = $cb->name; }
    $bankNames = array();
    foreach($bankAccounts as $ba) { $bankNames[$ba->idBankAccount] = $ba->bankName . ' - ****' . substr($ba->accountNumber, -4); }

    // Liquidez total (todas las cajas + bancos)
    $totalLiquidez = 0;
    foreach($cashboxes as $cb) { $totalLiquidez += (float)$cb->currentBalance; }
    foreach($bankAccounts as $ba) { $totalLiquidez += (float)$ba->currentBalance; }

    // Etiqueta del período filtrado
    $fFrom = isset($filters['from']) ? substr($filters['from'], 0, 10) : date('Y-m-01');
    $fTo   = isset($filters['to'])   ? substr($filters['to'], 0, 10)   : date('Y-m-d');
    $meses = array(1=>'ene',2=>'feb',3=>'mar',4=>'abr',5=>'may',6=>'jun',7=>'jul',8=>'ago',9=>'sep',10=>'oct',11=>'nov',12=>'dic');
    $lblPeriodo = (int)date('d', strtotime($fFrom)) . ' ' . $meses[(int)date('n', strtotime($fFrom))]
                . ' – ' . (int)date('d', strtotime($fTo)) . ' ' . $meses[(int)date('n', strtotime($fTo))] . ' ' . date('Y', strtotime($fTo));

    // Totales del período
    $sumIn  = isset($summary->totalIngresos) ? (float)$summary->totalIngresos : 0;
    $sumOut = isset($summary->totalEgresos)  ? (float)$summary->totalEgresos  : 0;
    $sumNet = $sumIn - $sumOut;

    // Saldo corrido cuando se filtra por un origen específico
    $showSaldo = (!empty($filters['sourceType']) && !empty($filters['sourceId']));
    $runningBalances = array();
    if ($showSaldo && !empty($movements)) {
        $bal = (isset($balanceBeforeFilter) ? (float)$balanceBeforeFilter : 0)
             + (isset($netPreviousPages) ? (float)$netPreviousPages : 0);
        foreach ($movements as $i => $m) {
            if (in_array($m->movementType, ['ingreso', 'apertura'])) {
                $bal += (float)$m->amount;
            } elseif (in_array($m->movementType, ['egreso', 'cierre', 'transferencia'])) {
                $bal -= (float)$m->amount;
            }
            $runningBalances[$i] = $bal;
        }
    }

    if (!function_exists('cm_money')) {
        function cm_money($n, $dec = 0) { return '$' . number_format($n, $dec, ',', '.'); }
    }
?>
<!DOCTYPE html>
<html lang="es">
    <title>Movimientos</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<body>
    <div id="bars" class="flex h-screen bg-gray-50"
         v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">

        <?php $this->load->view('sisvent/layouts/sidebar',
            array('thisFile' => $_ci_view, 'role' => $role)); ?>

        <div class="flex flex-col flex-1 w-full">
            <?php $this->load->view('sisvent/layouts/navbar'); ?>

            <main class="h-full overflow-y-auto">
                <div class="px-6 mx-auto grid max-w-screen-2xl">

                    <!-- ENCABEZADO -->
                    <div class="flex flex-wrap items-end justify-between gap-3 mt-4 mb-5">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-800">Movimientos</h2>
                            <p class="text-sm text-gray-400 mt-0.5">Ingresos y egresos de cajas y bancos · <?php echo $lblPeriodo; ?></p>
                        </div>
                        <div class="flex items-center gap-2">
                            <a href="<?php echo base_url(); ?>sisvent/admin/cashmovements/transfer"
                               class="inline-flex items-center gap-1.5 px-3.5 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 hover:text-gray-800 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                                Transferencia
                            </a>
                            <a href="<?php echo base_url(); ?>sisvent/admin/cashmovements/add"
                               class="inline-flex items-center gap-1.5 px-3.5 py-2 text-sm font-semibold text-white rounded-lg shadow-sm transition-colors"
                               style="background: var(--mam-blue-petroleo, #4487A0);">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                Nuevo Movimiento
                            </a>
                        </div>
                    </div>

                    <!-- SALDOS (hero) -->
                    <div class="flex gap-3 overflow-x-auto pb-1 mb-5">
                        <!-- Liquidez total -->
                        <div class="flex-shrink-0 min-w-[190px] rounded-xl p-4 text-white shadow-sm"
                             style="background: linear-gradient(135deg, var(--mam-blue-dark, #2B3164), var(--mam-blue-petroleo, #4487A0));">
                            <p class="text-xs font-medium uppercase tracking-wide opacity-80">Liquidez total</p>
                            <p class="text-2xl font-bold mt-1 tabular-nums"><?php echo cm_money($totalLiquidez); ?></p>
                            <p class="text-xs opacity-70 mt-1"><?php echo count($cashboxes); ?> caja(s) · <?php echo count($bankAccounts); ?> banco(s)</p>
                        </div>
                        <?php foreach($cashboxes as $cb): ?>
                        <div class="flex-shrink-0 min-w-[170px] bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                            <div class="flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                <p class="text-xs font-medium text-gray-500 truncate"><?php echo htmlspecialchars($cb->name); ?></p>
                            </div>
                            <p class="text-xl font-bold text-gray-800 mt-1 tabular-nums"><?php echo cm_money($cb->currentBalance); ?></p>
                            <p class="text-xs text-gray-400 mt-0.5">Caja</p>
                        </div>
                        <?php endforeach; ?>
                        <?php foreach($bankAccounts as $ba): ?>
                        <div class="flex-shrink-0 min-w-[170px] bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                            <div class="flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full" style="background: var(--mam-blue-petroleo, #4487A0);"></span>
                                <p class="text-xs font-medium text-gray-500 truncate"><?php echo htmlspecialchars($ba->bankName); ?></p>
                            </div>
                            <p class="text-xl font-bold text-gray-800 mt-1 tabular-nums"><?php echo cm_money($ba->currentBalance); ?></p>
                            <p class="text-xs text-gray-400 mt-0.5">****<?php echo substr($ba->accountNumber, -4); ?></p>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- MENSAJE FLASH -->
                    <?php if($this->session->flashdata('error')): ?>
                        <div class="flex items-center p-4 mb-4 text-sm font-semibold text-white bg-red-500 rounded-xl shadow-sm">
                            <p><?php echo $this->session->flashdata('error'); ?></p>
                        </div>
                    <?php endif; ?>

                    <!-- TOOLBAR: búsqueda + filtros -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-4">
                        <div class="flex flex-col lg:flex-row lg:items-end gap-3">
                            <!-- Búsqueda -->
                            <div class="flex-1">
                                <label class="block text-xs font-medium text-gray-500 mb-1">Buscar</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                    </span>
                                    <input type="text" id="movements-search"
                                           value="<?php echo isset($search_term) ? htmlspecialchars($search_term) : ''; ?>"
                                           placeholder="Concepto o número de comprobante…"
                                           class="w-full pl-9 pr-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-mam-blue-petroleo/30 focus:border-mam-blue-petroleo"/>
                                </div>
                            </div>
                            <!-- Origen -->
                            <div class="w-full lg:w-56">
                                <label class="block text-xs font-medium text-gray-500 mb-1">Origen</label>
                                <select id="filter-source" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-mam-blue-petroleo/30 focus:border-mam-blue-petroleo">
                                    <option value="">Todos</option>
                                    <optgroup label="Cajas">
                                        <?php foreach($cashboxes as $cb): ?>
                                            <option value="caja|<?php echo $cb->idCashbox; ?>"
                                                    <?php echo (isset($filters['sourceType']) && $filters['sourceType']=='caja' && $filters['sourceId']==$cb->idCashbox) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($cb->name); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                    <optgroup label="Bancos">
                                        <?php foreach($bankAccounts as $ba): ?>
                                            <option value="banco|<?php echo $ba->idBankAccount; ?>"
                                                    <?php echo (isset($filters['sourceType']) && $filters['sourceType']=='banco' && $filters['sourceId']==$ba->idBankAccount) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($ba->bankName) . ' ****' . substr($ba->accountNumber, -4); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                </select>
                            </div>
                            <!-- Tipo -->
                            <div class="w-full lg:w-40">
                                <label class="block text-xs font-medium text-gray-500 mb-1">Tipo</label>
                                <select id="filter-type" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-mam-blue-petroleo/30 focus:border-mam-blue-petroleo">
                                    <option value="">Todos</option>
                                    <option value="ingreso" <?php echo isset($filters['movementType']) && $filters['movementType']=='ingreso' ? 'selected' : ''; ?>>Ingreso</option>
                                    <option value="egreso" <?php echo isset($filters['movementType']) && $filters['movementType']=='egreso' ? 'selected' : ''; ?>>Egreso</option>
                                    <option value="transferencia" <?php echo isset($filters['movementType']) && $filters['movementType']=='transferencia' ? 'selected' : ''; ?>>Transferencia</option>
                                    <option value="ajuste" <?php echo isset($filters['movementType']) && $filters['movementType']=='ajuste' ? 'selected' : ''; ?>>Ajuste</option>
                                </select>
                            </div>
                            <!-- Desde -->
                            <div class="w-full lg:w-40">
                                <label class="block text-xs font-medium text-gray-500 mb-1">Desde</label>
                                <input type="date" id="filter-from" value="<?php echo $fFrom; ?>"
                                       class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-mam-blue-petroleo/30 focus:border-mam-blue-petroleo"/>
                            </div>
                            <!-- Hasta -->
                            <div class="w-full lg:w-40">
                                <label class="block text-xs font-medium text-gray-500 mb-1">Hasta</label>
                                <input type="date" id="filter-to" value="<?php echo $fTo; ?>"
                                       class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-mam-blue-petroleo/30 focus:border-mam-blue-petroleo"/>
                            </div>
                            <!-- Acciones -->
                            <div class="flex items-center gap-2">
                                <button id="btn-apply-filters" type="button"
                                        class="px-4 py-2 text-sm font-semibold text-white rounded-lg shadow-sm"
                                        style="background: var(--mam-blue-petroleo, #4487A0);">
                                    Aplicar
                                </button>
                                <a href="<?php echo base_url(); ?>sisvent/admin/cashmovements"
                                   class="px-3 py-2 text-sm text-gray-500 hover:text-gray-700">Limpiar</a>
                            </div>
                        </div>
                    </div>

                    <!-- RESUMEN DEL PERÍODO -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-5">
                        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                            <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">Ingresos</p>
                            <p class="text-lg font-bold text-emerald-600 mt-1 tabular-nums">+<?php echo cm_money($sumIn); ?></p>
                        </div>
                        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                            <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">Egresos</p>
                            <p class="text-lg font-bold text-rose-500 mt-1 tabular-nums">−<?php echo cm_money($sumOut); ?></p>
                        </div>
                        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                            <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">Neto del período</p>
                            <p class="text-lg font-bold mt-1 tabular-nums <?php echo $sumNet >= 0 ? 'text-gray-800' : 'text-rose-500'; ?>"><?php echo ($sumNet < 0 ? '−' : '') . cm_money(abs($sumNet)); ?></p>
                        </div>
                        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                            <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">Movimientos</p>
                            <p class="text-lg font-bold text-gray-800 mt-1 tabular-nums"><?php echo number_format($total, 0, ',', '.'); ?></p>
                        </div>
                    </div>

                    <!-- TABLA -->
                    <div class="w-full bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="w-full overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="text-xs font-semibold tracking-wide text-left text-gray-400 uppercase border-b border-gray-100 bg-gray-50/60">
                                        <th class="px-5 py-3 font-semibold">Fecha</th>
                                        <th class="px-5 py-3 font-semibold">Tipo</th>
                                        <th class="px-5 py-3 font-semibold">Concepto</th>
                                        <th class="px-5 py-3 font-semibold">Origen</th>
                                        <th class="px-5 py-3 font-semibold text-right">Monto</th>
                                        <?php if($showSaldo): ?>
                                        <th class="px-5 py-3 font-semibold text-right">Saldo</th>
                                        <?php endif; ?>
                                        <th class="px-5 py-3 font-semibold text-center">Estado</th>
                                        <th class="px-5 py-3 font-semibold text-right">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    <?php if(!empty($movements)): ?>
                                        <?php foreach($movements as $idx => $mov): ?>
                                            <?php
                                                if ($mov->sourceType == 'caja' && !empty($mov->cashboxName)) {
                                                    $sourceName = $mov->cashboxName;
                                                } elseif ($mov->sourceType == 'banco' && !empty($mov->bankName)) {
                                                    $sourceName = $mov->bankName . ' ****' . substr($mov->accountNumber, -4);
                                                } else {
                                                    $sourceName = ucfirst($mov->sourceType) . ' #' . $mov->sourceId;
                                                }
                                                switch ($mov->movementType) {
                                                    case 'ingreso': case 'apertura': $typeClass = 'text-emerald-700 bg-emerald-50'; break;
                                                    case 'egreso':  case 'cierre':   $typeClass = 'text-rose-700 bg-rose-50'; break;
                                                    case 'transferencia': $typeClass = 'text-blue-700 bg-blue-50'; break;
                                                    case 'ajuste':  $typeClass = 'text-amber-700 bg-amber-50'; break;
                                                    default: $typeClass = 'text-gray-600 bg-gray-100'; break;
                                                }
                                                $isNeg = in_array($mov->movementType, ['egreso', 'cierre']);
                                                $anulado = ($mov->status == 'anulado');
                                            ?>
                                            <tr class="text-gray-700 hover:bg-gray-50/70 transition-colors <?php echo $anulado ? 'opacity-50' : ''; ?>">
                                                <td class="px-5 py-3 text-sm whitespace-nowrap">
                                                    <span class="text-gray-700"><?php echo date('d/m/Y', strtotime($mov->movementDate)); ?></span>
                                                    <span class="text-gray-400 text-xs ml-1"><?php echo date('H:i', strtotime($mov->movementDate)); ?></span>
                                                </td>
                                                <td class="px-5 py-3">
                                                    <span class="px-2.5 py-1 text-xs font-medium rounded-full <?php echo $typeClass; ?>">
                                                        <?php echo ucfirst($mov->movementType); ?>
                                                    </span>
                                                </td>
                                                <td class="px-5 py-3 text-sm max-w-md">
                                                    <span class="block truncate" title="<?php echo htmlspecialchars((string)$mov->concept); ?>"><?php echo htmlspecialchars((string)$mov->concept); ?></span>
                                                </td>
                                                <td class="px-5 py-3 text-sm text-gray-500 whitespace-nowrap"><?php echo htmlspecialchars($sourceName); ?></td>
                                                <td class="px-5 py-3 text-sm text-right whitespace-nowrap tabular-nums font-medium <?php echo $isNeg ? 'text-rose-600' : 'text-emerald-600'; ?>">
                                                    <?php echo $isNeg ? '−' : '+'; ?><?php echo cm_money($mov->amount); ?>
                                                </td>
                                                <?php if($showSaldo): ?>
                                                <td class="px-5 py-3 text-sm text-right whitespace-nowrap tabular-nums font-semibold <?php echo (isset($runningBalances[$idx]) && $runningBalances[$idx] < 0) ? 'text-rose-600' : 'text-gray-700'; ?>">
                                                    <?php echo cm_money(isset($runningBalances[$idx]) ? $runningBalances[$idx] : 0); ?>
                                                </td>
                                                <?php endif; ?>
                                                <td class="px-5 py-3 text-center">
                                                    <?php if($anulado): ?>
                                                        <span class="px-2.5 py-1 text-xs font-medium rounded-full text-rose-700 bg-rose-50">Anulado</span>
                                                    <?php else: ?>
                                                        <span class="inline-flex items-center gap-1 text-xs text-gray-400">
                                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>Activo
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="px-5 py-3">
                                                    <div class="flex items-center justify-end gap-2">
                                                        <a href="<?php echo base_url(); ?>sisvent/admin/cashmovements/view/<?php echo $mov->idMovement; ?>"
                                                           class="text-gray-400 hover:text-mam-blue-petroleo" title="Ver">
                                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7s-8.268-2.943-9.542-7z"/></svg>
                                                        </a>
                                                        <?php if(!$anulado && !in_array($mov->movementType, ['apertura','cierre'])): ?>
                                                            <a href="<?php echo base_url(); ?>sisvent/admin/cashmovements/cancel/<?php echo $mov->idMovement; ?>"
                                                               class="text-gray-300 hover:text-rose-500" title="Anular"
                                                               onclick="showSureModal(event,this)">
                                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="<?php echo $showSaldo ? 8 : 7; ?>" class="px-5 py-16 text-center">
                                                <svg class="w-12 h-12 mx-auto text-gray-200 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                                <p class="text-sm text-gray-400">No hay movimientos en este período</p>
                                                <p class="text-xs text-gray-300 mt-1">Ajusta los filtros o registra un nuevo movimiento</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- PAGINACIÓN -->
                        <?php if($total > 0): ?>
                        <div class="flex flex-wrap items-center justify-between gap-2 px-5 py-3 text-xs font-medium text-gray-400 border-t border-gray-100 bg-gray-50/40">
                            <span>Mostrando <?php echo ((($page-1)*$limit)+1).'–'.(($last==$page)?$total:((($page-1)*$limit)+$limit)).' de '.$total; ?></span>
                            <span class="flex justify-end"><?php echo createLinks($page, $total, "", $limit); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>

                </div>
            </main>
        </div>
    </div>

    <?php $this->load->view('sisvent/layouts/footer'); ?>

    <script>
        // Búsqueda - delegated events
        $(document).on('click', '#btn-search-movements', function() {
            var term = $('#movements-search').val().trim();
            if (term.length > 0) {
                window.location.href = '<?php echo base_url(); ?>sisvent/admin/cashmovements/search/' + encodeURIComponent(term);
            }
        });
        $(document).on('keypress', '#movements-search', function(e) {
            if (e.which == 13) {
                var term = $('#movements-search').val().trim();
                if (term.length > 0) {
                    window.location.href = '<?php echo base_url(); ?>sisvent/admin/cashmovements/search/' + encodeURIComponent(term);
                }
            }
        });

        // Filtros - delegated events
        $(document).on('click', '#btn-apply-filters', function() {
            var source = $('#filter-source').val();
            var type = $('#filter-type').val();
            var from = $('#filter-from').val();
            var to = $('#filter-to').val();

            var params = [];
            if (source) {
                var parts = source.split('|');
                params.push('st=' + parts[0]);
                params.push('si=' + parts[1]);
            }
            if (type) params.push('mt=' + type);
            if (from) params.push('from=' + from);
            if (to) params.push('to=' + to);

            var url = '<?php echo base_url(); ?>sisvent/admin/cashmovements';
            if (params.length > 0) url += '?' + params.join('&');
            window.location.href = url;
        });
    </script>
</body>
</html>
