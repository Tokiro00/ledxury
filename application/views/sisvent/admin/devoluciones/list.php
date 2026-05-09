<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$statusMeta = [
    'detectada'             => ['label' => 'Detectada',          'class' => 'bg-amber-100 text-amber-700'],
    'en_camino'             => ['label' => 'En camino',          'class' => 'bg-blue-100 text-blue-700'],
    'recibida'              => ['label' => 'Recibida',           'class' => 'bg-indigo-100 text-indigo-700'],
    'nota_credito_emitida'  => ['label' => 'NC emitida',         'class' => 'bg-green-100 text-green-700'],
    'reembarcada'           => ['label' => 'Reembarcada',        'class' => 'bg-purple-100 text-purple-700'],
    'perdida'               => ['label' => 'Perdida (write-off)','class' => 'bg-red-100 text-red-700'],
];

$fmt = function ($n) { return number_format((float)$n, 0, ',', '.'); };
?>
<!DOCTYPE html>
<html lang="es">
<title>Devoluciones — Ledxury</title>
<?php $this->load->view('sisvent/layouts/meta_header'); ?>
<body>
<div id="bars" class="flex h-screen bg-gray-50">
    <?php $this->load->view('sisvent/layouts/sidebar', ['thisFile' => 'sisvent/admin/devoluciones/list', 'role' => $role]); ?>
    <div class="flex flex-col flex-1 w-full">
        <?php $this->load->view('sisvent/layouts/navbar'); ?>
        <main class="h-full overflow-y-auto">
            <div class="px-6 py-5 max-w-screen-2xl mx-auto">

                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between mb-5">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-700">Devoluciones de transportadora</h2>
                        <p class="text-xs text-gray-400 mt-0.5">Workflow: detectada → recibida → nota crédito (o reembarcada / perdida).
                        <?php if ($detected_now > 0): ?>
                            <span class="ml-2 text-amber-600 font-bold">⚠ <?= $detected_now ?> nueva<?= $detected_now == 1 ? '' : 's' ?> detectada<?= $detected_now == 1 ? '' : 's' ?> al cargar</span>
                        <?php endif; ?>
                        </p>
                    </div>
                </div>

                <?php if ($this->session->flashdata('devoluciones_msg')): ?>
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-2 rounded mb-4 text-sm"><?= htmlspecialchars($this->session->flashdata('devoluciones_msg')) ?></div>
                <?php endif; ?>
                <?php if ($this->session->flashdata('devoluciones_error')): ?>
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-2 rounded mb-4 text-sm"><?= htmlspecialchars($this->session->flashdata('devoluciones_error')) ?></div>
                <?php endif; ?>

                <!-- KPIs -->
                <div class="grid grid-cols-2 lg:grid-cols-7 gap-3 mb-5">
                    <div class="bg-white p-3 rounded-lg border-l-4 border-amber-400">
                        <p class="text-xxs text-gray-400 uppercase">Detectadas</p>
                        <p class="text-xl font-bold text-amber-700"><?= (int)($kpis->detectadas ?? 0) ?></p>
                    </div>
                    <div class="bg-white p-3 rounded-lg border-l-4 border-blue-400">
                        <p class="text-xxs text-gray-400 uppercase">En camino</p>
                        <p class="text-xl font-bold text-blue-700"><?= (int)($kpis->en_camino ?? 0) ?></p>
                    </div>
                    <div class="bg-white p-3 rounded-lg border-l-4 border-indigo-400">
                        <p class="text-xxs text-gray-400 uppercase">Recibidas</p>
                        <p class="text-xl font-bold text-indigo-700"><?= (int)($kpis->recibidas ?? 0) ?></p>
                    </div>
                    <div class="bg-white p-3 rounded-lg border-l-4 border-green-400">
                        <p class="text-xxs text-gray-400 uppercase">Con NC</p>
                        <p class="text-xl font-bold text-green-700"><?= (int)($kpis->con_nc ?? 0) ?></p>
                    </div>
                    <div class="bg-white p-3 rounded-lg border-l-4 border-purple-400">
                        <p class="text-xxs text-gray-400 uppercase">Reembarcadas</p>
                        <p class="text-xl font-bold text-purple-700"><?= (int)($kpis->reembarcadas ?? 0) ?></p>
                    </div>
                    <div class="bg-white p-3 rounded-lg border-l-4 border-red-400">
                        <p class="text-xxs text-gray-400 uppercase">Perdidas</p>
                        <p class="text-xl font-bold text-red-700"><?= (int)($kpis->perdidas ?? 0) ?></p>
                    </div>
                    <div class="bg-white p-3 rounded-lg border-l-4 border-gray-400">
                        <p class="text-xxs text-gray-400 uppercase">Flete perdido</p>
                        <p class="text-xl font-bold text-gray-700">$<?= $fmt($kpis->total_flete_perdido ?? 0) ?></p>
                    </div>
                </div>

                <!-- Filtros -->
                <form method="GET" class="bg-white rounded-lg border p-3 mb-4 flex flex-wrap items-end gap-3 text-xs">
                    <div>
                        <label class="block text-xxs text-gray-400 uppercase font-bold mb-1">Estado</label>
                        <select name="status" class="px-2 py-1.5 border rounded">
                            <?php foreach ([
                                'pendientes' => 'Pendientes (no cerradas)',
                                'todas'      => 'Todas',
                                'detectada'  => 'Detectada',
                                'recibida'   => 'Recibida',
                                'nota_credito_emitida' => 'NC emitida',
                                'reembarcada' => 'Reembarcada',
                                'perdida'    => 'Perdida',
                            ] as $k => $v): ?>
                            <option value="<?= $k ?>" <?= $filter_status === $k ? 'selected' : '' ?>><?= $v ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xxs text-gray-400 uppercase font-bold mb-1">Carrier</label>
                        <select name="carrier" class="px-2 py-1.5 border rounded">
                            <?php foreach (['all' => 'Todas', 'Interrapidisimo' => 'Interrapidísimo', 'Servientrega' => 'Servientrega', 'Coordinadora' => 'Coordinadora', 'Envia' => 'Envia', 'TCC' => 'TCC'] as $k => $v): ?>
                            <option value="<?= $k ?>" <?= $filter_carrier === $k ? 'selected' : '' ?>><?= $v ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xxs text-gray-400 uppercase font-bold mb-1">Desde</label>
                        <input type="date" name="from" value="<?= $filter_from ?>" class="px-2 py-1.5 border rounded">
                    </div>
                    <div>
                        <label class="block text-xxs text-gray-400 uppercase font-bold mb-1">Hasta</label>
                        <input type="date" name="to" value="<?= $filter_to ?>" class="px-2 py-1.5 border rounded">
                    </div>
                    <button type="submit" class="px-3 py-1.5 text-white rounded font-semibold" style="background:#2E7D91;">Filtrar</button>
                </form>

                <!-- Tabla -->
                <div class="bg-white rounded-lg border overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs">
                            <thead>
                                <tr style="background:#1B365D;" class="text-white">
                                    <th class="px-3 py-2.5 text-left font-semibold uppercase">Detectada</th>
                                    <th class="px-3 py-2.5 text-left font-semibold uppercase">Días</th>
                                    <th class="px-3 py-2.5 text-left font-semibold uppercase">Guía</th>
                                    <th class="px-3 py-2.5 text-left font-semibold uppercase">Carrier</th>
                                    <th class="px-3 py-2.5 text-left font-semibold uppercase">Factura</th>
                                    <th class="px-3 py-2.5 text-left font-semibold uppercase">Cliente</th>
                                    <th class="px-3 py-2.5 text-left font-semibold uppercase">Vendedor</th>
                                    <th class="px-3 py-2.5 text-right font-semibold uppercase">Valor</th>
                                    <th class="px-3 py-2.5 text-center font-semibold uppercase">Estado</th>
                                    <th class="px-3 py-2.5 text-center font-semibold uppercase">Acción</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <?php if (empty($returns)): ?>
                                <tr><td colspan="10" class="px-3 py-8 text-center text-gray-400">No hay devoluciones que coincidan con los filtros.</td></tr>
                                <?php else: foreach ($returns as $r):
                                    $sm = $statusMeta[$r->status] ?? ['label' => $r->status, 'class' => 'bg-gray-100 text-gray-600'];
                                    $diasUrgente = (int)$r->dias_desde_deteccion >= 7 && in_array($r->status, ['detectada', 'en_camino']);
                                ?>
                                <tr class="hover:bg-blue-50 <?= $diasUrgente ? 'bg-amber-50' : '' ?>">
                                    <td class="px-3 py-2 text-gray-600"><?= date('d/m/Y', strtotime($r->detected_at)) ?></td>
                                    <td class="px-3 py-2 font-bold <?= $diasUrgente ? 'text-amber-700' : 'text-gray-500' ?>"><?= (int)$r->dias_desde_deteccion ?>d</td>
                                    <td class="px-3 py-2 font-mono text-gray-700"><?= htmlspecialchars($r->numeroPreenvio ?? '-') ?></td>
                                    <td class="px-3 py-2"><?= htmlspecialchars($r->carrierName ?? '-') ?></td>
                                    <td class="px-3 py-2">
                                        <?php if ($r->factura_id): ?>
                                            <a href="<?= base_url() ?>sisvent/commercial/invoices/view/<?= $r->factura_id ?>" class="text-mam-blue-petroleo hover:underline">#<?= $r->factura_id ?></a>
                                            <div class="text-xxs text-gray-400">$<?= $fmt($r->factura_total) ?></div>
                                        <?php else: ?>—<?php endif; ?>
                                    </td>
                                    <td class="px-3 py-2"><?= htmlspecialchars($r->cliente_name ?? '-') ?>
                                        <?php if (!empty($r->cliente_city)): ?><div class="text-xxs text-gray-400"><?= htmlspecialchars($r->cliente_city) ?></div><?php endif; ?>
                                    </td>
                                    <td class="px-3 py-2 text-gray-500"><?= htmlspecialchars($r->vendor_name ?? '-') ?></td>
                                    <td class="px-3 py-2 text-right font-bold">$<?= $fmt($r->valorDeclarado ?: $r->factura_total) ?></td>
                                    <td class="px-3 py-2 text-center">
                                        <span class="px-2 py-0.5 text-xxs font-bold rounded-full <?= $sm['class'] ?>"><?= $sm['label'] ?></span>
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        <a href="<?= base_url() ?>sisvent/admin/devoluciones/detail/<?= $r->id ?>" class="px-3 py-1 text-xxs bg-mam-blue-petroleo text-white rounded font-semibold hover:opacity-80">Gestionar →</a>
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
<?php $this->load->view('sisvent/layouts/footer'); ?>
</body>
</html>
