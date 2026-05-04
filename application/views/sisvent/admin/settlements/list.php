<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$role = $this->session->userdata('user_data')['role'];
$fmt = function ($n) { return number_format((float)$n, 0, ',', '.'); };

// Totales agregados
$totComisiones = 0; $totAnticipos = 0; $totNeto = 0;
foreach ($settlements as $s) {
    $totComisiones += (float)($s->settlement ?? 0);
    $totAnticipos  += (float)($s->advanceBalance ?? 0);
    $totNeto       += (float)($s->netoPagar ?? 0);
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
                        <p class="text-xs text-gray-400">Saldo de cada vendedor: comisión liquidable menos anticipos pendientes.</p>
                    </div>
                    <a href="<?= base_url() ?>sisvent/admin/settlements/history"
                       class="px-3 py-1.5 text-xs font-medium text-mam-blue-petroleo bg-blue-50 hover:bg-blue-100 rounded">Historial &rarr;</a>
                </div>

                <!-- Totales agregados -->
                <div class="grid grid-cols-3 gap-3 mb-4">
                    <div class="p-3 bg-white rounded-lg shadow-xs">
                        <p class="text-xxs text-gray-400 uppercase">Total comisión liquidable</p>
                        <p class="text-lg font-semibold text-green-700">$<?= $fmt($totComisiones) ?></p>
                    </div>
                    <div class="p-3 bg-white rounded-lg shadow-xs">
                        <p class="text-xxs text-gray-400 uppercase">Total anticipos pendientes</p>
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
                                    <th class="px-4 py-3">Vendedor</th>
                                    <th class="px-4 py-3 text-right">Comisión liquidable</th>
                                    <th class="px-4 py-3 text-right">Anticipos pendientes</th>
                                    <th class="px-4 py-3 text-right">Saldo neto</th>
                                    <th class="px-4 py-3 text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <?php if (empty($settlements)): ?>
                                    <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400">No hay vendedores con saldo.</td></tr>
                                <?php else: foreach ($settlements as $s):
                                    $comm = (float)($s->settlement ?? 0);
                                    $adv  = (float)($s->advanceBalance ?? 0);
                                    $neto = (float)($s->netoPagar ?? 0);
                                    $hasComm = $comm != 0;
                                ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <p class="text-sm font-medium text-gray-700"><?= htmlspecialchars($s->name) ?></p>
                                        <?php if ($s->alert): ?>
                                            <p class="text-xxs text-red-500 mt-0.5">⚠ Precios bajo el base</p>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-right <?= $hasComm ? 'text-green-700 font-semibold' : 'text-gray-300' ?>">
                                        <?= $hasComm ? '$' . $fmt($comm) : '—' ?>
                                    </td>
                                    <td class="px-4 py-3 text-right <?= $adv > 0 ? 'text-yellow-700' : 'text-gray-300' ?>">
                                        <?= $adv > 0 ? '$' . $fmt($adv) : '—' ?>
                                    </td>
                                    <td class="px-4 py-3 text-right text-base font-bold <?= $neto > 0 ? 'text-green-700' : ($neto < 0 ? 'text-red-600' : 'text-gray-400') ?>">
                                        $<?= $fmt($neto) ?>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <!-- Estado de cuenta (vista principal) -->
                                            <a href="<?= base_url() ?>sisvent/admin/settlements/statement/<?= urlencode($s->idUser) ?>"
                                               class="px-2.5 py-1 text-xs font-medium text-mam-blue-petroleo border border-gray-200 hover:bg-blue-50 rounded"
                                               title="Estado de cuenta">📊 Estado</a>

                                            <!-- Calcular liquidación (cuando hay comisión por liquidar) -->
                                            <?php if ($hasComm): ?>
                                            <a href="<?= base_url() ?>sisvent/admin/settlements/calculate/<?= urlencode($s->idUser) ?>"
                                               class="px-2.5 py-1 text-xs font-bold text-white bg-mam-blue-petroleo hover:bg-blue-900 rounded"
                                               onclick="showSureModal(event,this,'Calcular la liquidación. Después podrás revisarla y pagar o descartar.')"
                                               title="Calcular">🧮 Calcular</a>
                                            <?php endif; ?>

                                            <!-- Dar anticipo rápido -->
                                            <a href="<?= base_url() ?>sisvent/admin/advances/add?employee_id=<?= urlencode($s->idUser) ?>"
                                               class="px-2.5 py-1 text-xs font-medium text-yellow-700 border border-yellow-300 hover:bg-yellow-50 rounded"
                                               title="Dar anticipo">💸 Anticipo</a>

                                            <!-- Menú para acciones secundarias / legacy -->
                                            <details class="relative inline-block">
                                                <summary class="cursor-pointer px-2 py-1 text-gray-400 hover:text-gray-700 list-none select-none" title="Más">⋯</summary>
                                                <div class="absolute right-0 z-10 mt-1 w-44 bg-white rounded shadow-md border border-gray-200 py-1 text-xs">
                                                    <?php if ($hasComm): ?>
                                                    <a href="<?= base_url() ?>sisvent/admin/settlements/approve/<?= urlencode($s->idUser) ?>"
                                                       class="block px-3 py-1.5 text-gray-700 hover:bg-gray-50"
                                                       onclick="showSureModal(event,this,'Liquidar y pagar directamente sin revisión previa.')"
                                                    >Liquidar directo</a>
                                                    <?php endif; ?>
                                                    <a href="<?= base_url() ?>sisvent/admin/settlements/history?vendor=<?= urlencode($s->idUser) ?>"
                                                       class="block px-3 py-1.5 text-gray-700 hover:bg-gray-50">Historial liquidaciones</a>
                                                    <?php if (in_array($role, [1])): ?>
                                                    <button type="button"
                                                            value="<?= htmlspecialchars($s->idUser) ?>"
                                                            class="btn-view-userlostinvoices block w-full text-left px-3 py-1.5 text-gray-700 hover:bg-gray-50">Facturas perdidas</button>
                                                    <?php endif; ?>
                                                </div>
                                            </details>
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
<?php $this->load->view('sisvent/layouts/footer'); ?>
</body>
</html>
