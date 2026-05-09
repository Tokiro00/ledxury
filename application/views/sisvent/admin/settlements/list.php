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
