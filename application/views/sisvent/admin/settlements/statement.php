<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$fmt = function ($n) { return number_format((float)$n, 0, ',', '.'); };
$typeBadge = array(
    'liquidacion'        => array('💰', 'Liquidación',     'bg-green-100 text-green-700'),
    'vale'               => array('📄', 'Vale',            'bg-gray-100 text-gray-700'),
    'anticipo'           => array('💸', 'Anticipo',        'bg-yellow-100 text-yellow-800'),
    'cruce_anticipo'     => array('✂️', 'Cruce anticipo',  'bg-orange-100 text-orange-700'),
    'abono_empleado'     => array('💵', 'Abono empleado',  'bg-blue-100 text-blue-700'),
);
?>
<!DOCTYPE html>
<html lang="es">
<title>Estado de cuenta — <?= htmlspecialchars($vendor->name) ?></title>
<?php $this->load->view('sisvent/layouts/meta_header'); ?>
<body>
<div id="bars" class="flex h-screen bg-gray-100" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
    <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => 'sisvent/admin/settlements/list', 'role' => $role)); ?>
    <div class="flex flex-col flex-1 w-full">
        <?php $this->load->view('sisvent/layouts/navbar'); ?>
        <main class="h-full overflow-y-auto">
            <div class="px-6 py-5 w-full max-w-screen-xl mx-auto">

                <!-- Header -->
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between mb-5">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-700">Estado de cuenta</h2>
                        <p class="text-sm text-gray-600"><?= htmlspecialchars($vendor->name) ?></p>
                        <p class="text-xs text-gray-400">id: <?= htmlspecialchars($vendor->idUser) ?></p>
                    </div>
                    <div class="flex items-center gap-2 mt-3 lg:mt-0">
                        <button onclick="window.print()" class="px-3 py-1.5 text-xs text-gray-600 border border-gray-300 hover:bg-gray-100 rounded">Imprimir</button>
                        <a href="<?= base_url() ?>sisvent/admin/advances/add"
                           class="px-3 py-1.5 text-xs font-medium text-white bg-yellow-600 hover:bg-yellow-700 rounded">+ Dar anticipo</a>
                        <a href="<?= base_url() ?>sisvent/admin/settlements"
                           class="px-3 py-1.5 text-xs text-gray-500 hover:text-gray-700">← Volver</a>
                    </div>
                </div>

                <!-- Filtros de fecha con presets -->
                <form method="GET" class="flex flex-wrap items-end gap-3 mb-4 p-3 bg-white rounded-lg shadow-xs">
                    <div>
                        <label class="block text-xxs text-gray-500 mb-1 uppercase">Desde</label>
                        <input type="date" name="from" value="<?= htmlspecialchars($from) ?>" class="px-2 py-1 border rounded text-sm">
                    </div>
                    <div>
                        <label class="block text-xxs text-gray-500 mb-1 uppercase">Hasta</label>
                        <input type="date" name="to" value="<?= htmlspecialchars($to) ?>" class="px-2 py-1 border rounded text-sm">
                    </div>
                    <button type="submit" class="px-3 py-1.5 text-xs font-bold text-white bg-mam-blue-petroleo rounded">Filtrar</button>
                    <span class="text-gray-300">|</span>
                    <?php
                        $today = date('Y-m-d');
                        $thisMonthFrom = date('Y-m-01');
                        $lastMonthFrom = date('Y-m-01', strtotime('first day of last month'));
                        $lastMonthTo   = date('Y-m-t', strtotime('last day of last month'));
                        $thisYearFrom  = date('Y-01-01');
                        $base = base_url() . 'sisvent/admin/settlements/statement/' . urlencode($vendor->idUser);
                    ?>
                    <a href="<?= $base ?>?from=<?= $thisMonthFrom ?>&to=<?= $today ?>" class="text-xs text-mam-blue-petroleo hover:underline">Este mes</a>
                    <a href="<?= $base ?>?from=<?= $lastMonthFrom ?>&to=<?= $lastMonthTo ?>" class="text-xs text-mam-blue-petroleo hover:underline">Mes anterior</a>
                    <a href="<?= $base ?>?from=<?= $thisYearFrom ?>&to=<?= $today ?>" class="text-xs text-mam-blue-petroleo hover:underline">Este año</a>
                </form>

                <!-- KPI cards: 3 indicadores del estado actual del vendedor (no per-período) -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-5">
                    <div class="p-4 bg-white rounded-lg shadow-xs">
                        <p class="text-xxs text-gray-400 uppercase">Comisión liquidable</p>
                        <p class="text-2xl font-bold text-green-700">$<?= $fmt($current_commission) ?></p>
                        <p class="text-xxs text-gray-400 mt-0.5">Acumulada por facturas pagadas</p>
                    </div>
                    <div class="p-4 bg-white rounded-lg shadow-xs">
                        <p class="text-xxs text-gray-400 uppercase">Anticipos pendientes</p>
                        <p class="text-2xl font-bold text-yellow-700">$<?= $fmt($current_advances) ?></p>
                        <p class="text-xxs text-gray-400 mt-0.5">Saldo a cruzar en próxima liquidación</p>
                    </div>
                    <div class="p-4 bg-white rounded-lg shadow-xs border-2 <?= $current_balance >= 0 ? 'border-green-400' : 'border-red-400' ?>">
                        <p class="text-xxs text-gray-400 uppercase">Saldo del vendedor</p>
                        <p class="text-2xl font-bold <?= $current_balance >= 0 ? 'text-green-700' : 'text-red-600' ?>">$<?= $fmt($current_balance) ?></p>
                        <p class="text-xxs text-gray-400 mt-0.5"><?= $current_balance >= 0 ? 'Empresa debe al vendedor' : 'Vendedor debe a empresa' ?></p>
                    </div>
                </div>

                <!-- Tabla cronológica -->
                <div class="bg-white rounded-lg shadow-xs overflow-hidden mb-5">
                    <div class="px-4 py-3 border-b">
                        <h3 class="text-sm font-semibold text-gray-700">Movimientos · <?= count($rows) ?></h3>
                        <p class="text-xs text-gray-400">Cronología completa del período <?= date('d/m/Y', strtotime($from)) ?> — <?= date('d/m/Y', strtotime($to)) ?></p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm whitespace-no-wrap">
                            <thead>
                                <tr class="text-xxs text-gray-400 uppercase border-b bg-gray-50">
                                    <th class="px-3 py-2 text-left">Fecha</th>
                                    <th class="px-3 py-2 text-left">Tipo</th>
                                    <th class="px-3 py-2 text-left">Código</th>
                                    <th class="px-3 py-2 text-left">Concepto</th>
                                    <th class="px-3 py-2 text-right">Débito</th>
                                    <th class="px-3 py-2 text-right">Crédito</th>
                                    <th class="px-3 py-2 text-right border-l">Saldo</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <?php if ($kpis['previous_balance'] != 0): ?>
                                    <tr class="bg-gray-50 italic">
                                        <td colspan="6" class="px-3 py-2 text-right text-xs text-gray-500">Saldo anterior al <?= date('d/m/Y', strtotime($from)) ?>:</td>
                                        <td class="px-3 py-2 text-right font-semibold border-l <?= $kpis['previous_balance'] >= 0 ? 'text-gray-700' : 'text-red-600' ?>">$<?= $fmt($kpis['previous_balance']) ?></td>
                                    </tr>
                                <?php endif; ?>

                                <?php if (empty($rows)): ?>
                                    <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">Sin movimientos en el período seleccionado.</td></tr>
                                <?php else: foreach ($rows as $r):
                                    list($icon, $label, $cls) = $typeBadge[$r->tipo] ?? array('•', $r->tipo, 'bg-gray-100 text-gray-600');
                                ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-3 py-2 text-xs text-gray-500"><?= date('d/m/Y', strtotime($r->fecha)) ?></td>
                                        <td class="px-3 py-2"><span class="px-2 py-0.5 text-xxs font-semibold rounded-full <?= $cls ?>"><?= $icon ?> <?= $label ?></span></td>
                                        <td class="px-3 py-2 font-mono text-xs text-gray-600"><?= htmlspecialchars($r->code) ?></td>
                                        <td class="px-3 py-2 text-gray-700"><?= htmlspecialchars($r->concepto) ?></td>
                                        <td class="px-3 py-2 text-right <?= $r->debito > 0 ? 'text-yellow-700 font-semibold' : 'text-gray-300' ?>">
                                            <?= $r->debito > 0 ? '$' . $fmt($r->debito) : '—' ?>
                                        </td>
                                        <td class="px-3 py-2 text-right <?= $r->credito > 0 ? 'text-green-700 font-semibold' : 'text-gray-300' ?>">
                                            <?= $r->credito > 0 ? '$' . $fmt($r->credito) : '—' ?>
                                        </td>
                                        <td class="px-3 py-2 text-right border-l font-semibold <?= $r->saldo >= 0 ? 'text-gray-700' : 'text-red-600' ?>">
                                            $<?= $fmt($r->saldo) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Anticipos activos al pie -->
                <?php if (!empty($active_advances)): ?>
                <div class="bg-white rounded-lg shadow-xs">
                    <div class="px-4 py-3 border-b">
                        <h3 class="text-sm font-semibold text-gray-700">Anticipos activos</h3>
                        <p class="text-xs text-gray-400">Saldos pendientes de cruce con futuras liquidaciones (FIFO).</p>
                    </div>
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-xxs text-gray-400 uppercase border-b">
                                <th class="px-3 py-2 text-left">Código</th>
                                <th class="px-3 py-2 text-left">Concepto</th>
                                <th class="px-3 py-2 text-left">Desembolsado</th>
                                <th class="px-3 py-2 text-right">Monto original</th>
                                <th class="px-3 py-2 text-right">Saldo</th>
                                <th class="px-3 py-2"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <?php foreach ($active_advances as $a): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-3 py-2 font-mono text-xs"><?= $a->code ?></td>
                                <td class="px-3 py-2 text-gray-700"><?= htmlspecialchars($a->purpose) ?></td>
                                <td class="px-3 py-2 text-gray-500 text-xs"><?= $a->disbursed_at ? date('d/m/Y', strtotime($a->disbursed_at)) : '—' ?></td>
                                <td class="px-3 py-2 text-right text-gray-600">$<?= $fmt($a->amount) ?></td>
                                <td class="px-3 py-2 text-right text-yellow-700 font-semibold">$<?= $fmt($a->outstanding_balance) ?></td>
                                <td class="px-3 py-2 text-right">
                                    <a href="<?= base_url() ?>sisvent/admin/advances/view/<?= $a->id ?>" class="text-xs text-mam-blue-petroleo hover:underline">Ver →</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>

            </div>
        </main>
    </div>
</div>
<?php $this->load->view('sisvent/layouts/footer'); ?>
</body>
</html>
