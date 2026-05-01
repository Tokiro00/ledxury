<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$fmt = function ($n) { return number_format((float)$n, 0, ',', '.'); };
$statusBadge = function ($s) {
    switch ($s) {
        case 'pagado':     return ['bg-green-100 text-green-700',  'Pagado'];
        case 'aprobado':   return ['bg-blue-100 text-blue-700',    'Aprobado'];
        case 'calculado':  return ['bg-yellow-100 text-yellow-700','Calculado'];
        case 'reversado':  return ['bg-red-100 text-red-700',      'Reversado'];
    }
    return ['bg-gray-100 text-gray-500', ucfirst($s)];
};
?>
<!DOCTYPE html>
<html lang="es">
<title>Historial de liquidaciones - Ledxury</title>
<?php $this->load->view('sisvent/layouts/meta_header'); ?>
<body>
<div id="bars" class="flex h-screen bg-gray-100" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
    <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => 'sisvent/admin/settlements/list', 'role' => $role)); ?>
    <div class="flex flex-col flex-1 w-full">
        <?php $this->load->view('sisvent/layouts/navbar'); ?>
        <main class="h-full overflow-y-auto">
            <div class="px-6 py-5 w-full max-w-screen-xl mx-auto">

                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-700">Historial de liquidaciones</h2>
                        <p class="text-xs text-gray-400 mt-0.5">Liquidaciones de vendedor con detalle estructurado por factura.</p>
                    </div>
                    <a href="<?= base_url() ?>sisvent/admin/settlements" class="px-4 py-2 text-xs text-gray-500 hover:text-gray-700">&larr; Liquidaciones pendientes</a>
                </div>

                <!-- Filtros -->
                <form method="GET" class="flex flex-wrap items-end gap-3 mb-5 p-4 bg-white rounded-lg shadow-xs">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Vendedor (id)</label>
                        <input type="text" name="vendor" value="<?= htmlspecialchars($filter_vendor ?? '') ?>" class="px-2 py-1 border rounded text-sm" placeholder="cualquiera">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Desde</label>
                        <input type="date" name="from" value="<?= htmlspecialchars($filter_from ?? '') ?>" class="px-2 py-1 border rounded text-sm">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Hasta</label>
                        <input type="date" name="to" value="<?= htmlspecialchars($filter_to ?? '') ?>" class="px-2 py-1 border rounded text-sm">
                    </div>
                    <button type="submit" class="px-4 py-1.5 text-xs font-bold text-white bg-mam-blue-petroleo rounded">Filtrar</button>
                    <a href="<?= base_url() ?>sisvent/admin/settlements/history" class="text-xs text-gray-400 hover:text-gray-700">limpiar</a>
                </form>

                <!-- KPIs -->
                <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-5">
                    <div class="p-3 bg-white rounded-lg shadow-xs">
                        <p class="text-xxs text-gray-400 uppercase">Liquidaciones</p>
                        <p class="text-xl font-semibold text-gray-700"><?= (int)$totals['count'] ?></p>
                    </div>
                    <div class="p-3 bg-white rounded-lg shadow-xs">
                        <p class="text-xxs text-gray-400 uppercase">Recaudado</p>
                        <p class="text-xl font-semibold text-gray-700">$<?= $fmt($totals['recaudado']) ?></p>
                    </div>
                    <div class="p-3 bg-white rounded-lg shadow-xs">
                        <p class="text-xxs text-gray-400 uppercase">Comisión</p>
                        <p class="text-xl font-semibold text-green-700">$<?= $fmt($totals['comision']) ?></p>
                    </div>
                    <div class="p-3 bg-white rounded-lg shadow-xs">
                        <p class="text-xxs text-gray-400 uppercase">Descuentos</p>
                        <p class="text-xl font-semibold text-red-600">$<?= $fmt($totals['descuentos']) ?></p>
                    </div>
                    <div class="p-3 bg-white rounded-lg shadow-xs">
                        <p class="text-xxs text-gray-400 uppercase">Neto</p>
                        <p class="text-xl font-semibold text-gray-800">$<?= $fmt($totals['neto']) ?></p>
                    </div>
                </div>

                <!-- Tabla -->
                <div class="w-full overflow-hidden rounded-lg shadow-xs bg-white">
                    <div class="w-full overflow-x-auto">
                        <table class="w-full whitespace-no-wrap text-sm">
                            <thead>
                                <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                                    <th class="px-3 py-2">#</th>
                                    <th class="px-3 py-2">Fecha</th>
                                    <th class="px-3 py-2">Vendedor</th>
                                    <th class="px-3 py-2 text-right">Facturas</th>
                                    <th class="px-3 py-2 text-right">Recaudado</th>
                                    <th class="px-3 py-2 text-right">Comisión</th>
                                    <th class="px-3 py-2 text-right">Vales</th>
                                    <th class="px-3 py-2 text-right">Neto</th>
                                    <th class="px-3 py-2">Estado</th>
                                    <th class="px-3 py-2"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <?php if (empty($settlements)): ?>
                                    <tr><td colspan="10" class="px-4 py-8 text-center text-gray-400">No hay liquidaciones registradas con esos filtros.</td></tr>
                                <?php else: foreach ($settlements as $s): list($cls,$lbl) = $statusBadge($s->status); ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-3 py-2 text-gray-500">#<?= $s->id ?></td>
                                        <td class="px-3 py-2 text-gray-600"><?= date('d/m/Y', strtotime($s->created_at)) ?></td>
                                        <td class="px-3 py-2 text-gray-700">
                                            <?= htmlspecialchars($s->vendor_full_name ?: $s->vendor_name ?: $s->vendor_id) ?>
                                            <?php if (!empty($s->notes)): ?>
                                                <span class="ml-1 text-xs text-orange-500" title="<?= htmlspecialchars($s->notes) ?>">⚠</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-3 py-2 text-right text-gray-600"><?= (int)$s->invoice_count ?></td>
                                        <td class="px-3 py-2 text-right text-gray-600">$<?= $fmt($s->total_recaudado) ?></td>
                                        <td class="px-3 py-2 text-right text-green-700">$<?= $fmt($s->total_comision) ?></td>
                                        <td class="px-3 py-2 text-right text-gray-500">$<?= $fmt($s->total_vouchers) ?></td>
                                        <td class="px-3 py-2 text-right font-semibold text-gray-800">$<?= $fmt($s->total_neto) ?></td>
                                        <td class="px-3 py-2"><span class="px-2 py-0.5 text-xxs font-bold rounded-full <?= $cls ?>"><?= $lbl ?></span></td>
                                        <td class="px-3 py-2 text-right">
                                            <a href="<?= base_url() ?>sisvent/admin/settlements/detail/<?= $s->id ?>" class="text-mam-blue-petroleo hover:underline text-xs">Ver detalle &rarr;</a>
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
