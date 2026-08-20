<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Facturas de proveedor — listado.
 * Línea gráfica de Ledxury (Tailwind + azul petróleo), igual que Facturas y
 * Remisiones: título h2, botonera, tabla shadow-xs con thead gris.
 */
$money = function ($n) { return '$' . number_format((float)$n, 0, ',', '.'); };
$dateEs = function ($d) {
    if (!$d) return '—';
    $meses = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
    $ts = strtotime($d); if (!$ts) return $d;
    return date('d', $ts) . ' ' . $meses[(int)date('n', $ts)-1] . ' ' . date('Y', $ts);
};
$estados = [
    'en_transito'  => ['Por recibir',  'text-orange-700 bg-orange-100'],
    'open'         => ['Abierta',      'text-blue-700 bg-blue-100'],
    'paid_partial' => ['Pago parcial', 'text-yellow-700 bg-yellow-100'],
    'paid'         => ['Pagada',       'text-green-700 bg-green-100'],
    'cancelled'    => ['Anulada',      'text-gray-600 bg-gray-100'],
];

$importPayables = $import_payables ?? [];
$importPayTotal = $import_pay_total ?? 0;

$saldoAbierto = 0; $cntAbiertas = 0; $saldoTransito = 0; $cntTransito = 0; $vencido = 0;
foreach ($invoices as $inv) {
    if (in_array($inv->status, ['open','paid_partial'])) {
        $saldoAbierto += (float)$inv->balance; $cntAbiertas++;
        if (!empty($inv->days_overdue) && $inv->days_overdue > 0) $vencido += (float)$inv->balance;
    } elseif ($inv->status === 'en_transito') {
        $saldoTransito += (float)$inv->balance; $cntTransito++;
    }
}
$deudaTotal = $saldoAbierto + $saldoTransito;
?>
<!DOCTYPE html>
<html lang="es">
    <title>Facturas de Proveedor</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
<head></head>
<body>
    <div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">

        <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => $_ci_view, 'role' => $role)); ?>

        <div class="flex flex-col flex-1 w-full">
            <?php $this->load->view('sisvent/layouts/navbar'); ?>

            <main class="h-full overflow-y-auto">
                <div class="px-6 mx-auto grid">

                    <h2 class="mb-4 text-lg font-semibold text-gray-600 mt-2">Facturas de Proveedor</h2>

                    <?php if ($this->session->flashdata('success')): ?>
                        <div class="px-4 py-3 mb-4 text-sm text-green-800 bg-green-100 border border-green-200 rounded-lg"><?= $this->session->flashdata('success') ?></div>
                    <?php endif; ?>
                    <?php if ($this->session->flashdata('error')): ?>
                        <div class="px-4 py-3 mb-4 text-sm text-red-800 bg-red-100 border border-red-200 rounded-lg"><?= $this->session->flashdata('error') ?></div>
                    <?php endif; ?>
                    <?php if ($this->session->flashdata('warning')): ?>
                        <div class="px-4 py-3 mb-4 text-sm text-yellow-800 bg-yellow-100 border border-yellow-200 rounded-lg"><?= $this->session->flashdata('warning') ?></div>
                    <?php endif; ?>

                    <!-- BOTONERA -->
                    <div class="flex flex-col flex-wrap mb-6 space-y-4 md:flex-row md:items-end md:space-x-4 md:space-y-0">
                        <a href="<?= base_url() ?>sisvent/purchases/provider_invoices/add"
                           class="flex items-center px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-petroleo border border-transparent rounded-lg hover:bg-mam-blue focus:outline-none">
                            Cargar Factura
                            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        </a>
                        <a href="<?= base_url() ?>sisvent/purchases/provider_invoices/import<?= $selected_provider ? '?provider_id=' . $selected_provider->idProvider : '' ?>"
                           class="flex items-center px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-mam-blue-dark border border-transparent rounded-lg hover:bg-mam-blue focus:outline-none">
                            Importar Packing List
                            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                        </a>
                        <a href="<?= base_url() ?>sisvent/purchases/cxp"
                           class="flex items-center px-4 py-2 text-sm font-medium leading-5 text-gray-700 transition-colors duration-150 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 focus:outline-none">
                            Panel de Cuentas por Pagar
                        </a>
                        <?php if ($selected_provider): ?>
                        <a href="<?= base_url() ?>sisvent/purchases/provider_invoices/statement/<?= (int)$selected_provider->idProvider ?>"
                           class="flex items-center px-4 py-2 text-sm font-medium leading-5 text-gray-700 transition-colors duration-150 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 focus:outline-none">
                            Estado de Cuenta · <?= htmlspecialchars($selected_provider->name) ?>
                        </a>
                        <?php endif; ?>
                    </div>

                    <!-- RESUMEN -->
                    <div class="grid gap-4 mb-6 md:grid-cols-4">
                        <div class="bg-white rounded-lg shadow-sm p-4">
                            <p class="text-xs text-gray-500 uppercase">Deuda total</p>
                            <p class="text-lg font-bold text-gray-800 mt-1"><?= $money($deudaTotal) ?></p>
                            <p class="text-xs text-gray-500 mt-1"><?= count($invoices) ?> factura(s)</p>
                        </div>
                        <div class="bg-blue-50 rounded-lg shadow-sm p-4">
                            <p class="text-xs text-blue-600 uppercase">Por pagar</p>
                            <p class="text-lg font-bold text-blue-700 mt-1"><?= $money($saldoAbierto) ?></p>
                            <p class="text-xs text-blue-600 mt-1"><?= $cntAbiertas ?> abierta(s)</p>
                        </div>
                        <div class="bg-orange-50 rounded-lg shadow-sm p-4">
                            <p class="text-xs text-orange-600 uppercase">Por recibir</p>
                            <p class="text-lg font-bold text-orange-700 mt-1"><?= $money($saldoTransito) ?></p>
                            <p class="text-xs text-orange-600 mt-1"><?= $cntTransito ?> en tránsito</p>
                        </div>
                        <div class="<?= $vencido > 0 ? 'bg-red-50' : 'bg-white' ?> rounded-lg shadow-sm p-4">
                            <p class="text-xs <?= $vencido > 0 ? 'text-red-600' : 'text-gray-500' ?> uppercase">Vencido</p>
                            <p class="text-lg font-bold <?= $vencido > 0 ? 'text-red-700' : 'text-gray-800' ?> mt-1"><?= $money($vencido) ?></p>
                            <p class="text-xs <?= $vencido > 0 ? 'text-red-600' : 'text-gray-500' ?> mt-1"><?= $vencido > 0 ? 'revisar pagos' : 'sin vencidos' ?></p>
                        </div>
                    </div>

                    <!-- FILTROS -->
                    <form method="get" action="<?= base_url() ?>sisvent/purchases/provider_invoices"
                          class="bg-white rounded-lg shadow-sm p-4 mb-6 flex flex-wrap items-end gap-4">
                        <label class="flex flex-col text-sm">
                            <span class="text-gray-600 mb-1">Proveedor</span>
                            <select name="provider_id" class="form-input">
                                <option value="">Todos</option>
                                <?php foreach ($providers as $p): ?>
                                    <option value="<?= (int)$p->idProvider ?>" <?= (!empty($filters['provider_id']) && (int)$filters['provider_id'] === (int)$p->idProvider) ? 'selected' : '' ?>><?= htmlspecialchars($p->name) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="flex flex-col text-sm">
                            <span class="text-gray-600 mb-1">Estado</span>
                            <select name="status" class="form-input">
                                <option value="">Todos</option>
                                <?php foreach ($estados as $k => $e): ?>
                                    <option value="<?= $k ?>" <?= (!empty($filters['status']) && $filters['status'] === $k) ? 'selected' : '' ?>><?= $e[0] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-mam-blue-petroleo rounded-lg hover:bg-mam-blue">Filtrar</button>
                        <?php if (!empty($filters['provider_id']) || !empty($filters['status'])): ?>
                            <a href="<?= base_url() ?>sisvent/purchases/provider_invoices" class="px-4 py-2 text-sm font-medium text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-100">Limpiar</a>
                        <?php endif; ?>
                    </form>

                    <!-- GASTOS DE IMPORTACIÓN POR PAGAR -->
                    <?php if (!empty($importPayables)): ?>
                    <div class="w-full overflow-hidden rounded-lg shadow-xs mb-6">
                        <div class="px-4 py-3 bg-yellow-50 border-b flex items-center justify-between">
                            <p class="text-xs font-semibold tracking-wide text-yellow-800 uppercase">Gastos de importación por pagar (<?= count($importPayables) ?>)</p>
                            <p class="text-sm font-bold text-yellow-800"><?= $money($importPayTotal) ?></p>
                        </div>
                        <div class="w-full overflow-x-auto">
                            <table class="w-full whitespace-no-wrap">
                                <thead>
                                    <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                                        <th class="px-4 py-3">Concepto</th>
                                        <th class="px-4 py-3">Factura</th>
                                        <th class="px-4 py-3">Proveedor</th>
                                        <th class="px-4 py-3 text-right">Saldo</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y">
                                    <?php foreach ($importPayables as $ip): ?>
                                    <tr class="text-gray-700">
                                        <td class="px-4 py-3 text-sm capitalize"><?= htmlspecialchars($ip->concept) ?><?= $ip->description ? ' · ' . htmlspecialchars($ip->description) : '' ?></td>
                                        <td class="px-4 py-3 text-sm">
                                            <a class="text-mam-blue-petroleo hover:underline font-medium" href="<?= base_url() ?>sisvent/purchases/provider_invoices/view/<?= (int)$ip->invoice_id ?>"><?= htmlspecialchars($ip->inv_code) ?></a>
                                        </td>
                                        <td class="px-4 py-3 text-sm"><?= htmlspecialchars($ip->provider_name) ?></td>
                                        <td class="px-4 py-3 text-sm text-right font-semibold text-yellow-700"><?= $money($ip->outstanding) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- TABLA DE FACTURAS -->
                    <div class="w-full overflow-hidden rounded-lg shadow-xs">
                        <div class="px-4 py-3 bg-gray-50 border-b flex items-center justify-between">
                            <p class="text-xs font-semibold tracking-wide text-gray-500 uppercase">
                                Facturas (<?= count($invoices) ?>)
                            </p>
                            <p class="text-xs text-gray-500">de la más antigua a la más reciente</p>
                        </div>
                        <div class="w-full overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                                        <th class="px-4 py-3">Nº Factura</th>
                                        <th class="px-4 py-3">Proveedor</th>
                                        <th class="px-4 py-3 whitespace-no-wrap">Emisión</th>
                                        <th class="px-4 py-3 whitespace-no-wrap">Vence</th>
                                        <th class="px-4 py-3">Estado</th>
                                        <th class="px-4 py-3 text-right whitespace-no-wrap">Total</th>
                                        <th class="px-4 py-3 text-right whitespace-no-wrap">Pagado</th>
                                        <th class="px-4 py-3 text-right whitespace-no-wrap">Saldo</th>
                                        <th class="px-4 py-3 text-right">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y">
                                    <?php if (empty($invoices)): ?>
                                        <tr><td colspan="9" class="px-4 py-6 text-sm text-center text-gray-500">No hay facturas de proveedor con estos filtros</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($invoices as $inv):
                                            $est = $estados[$inv->status] ?? [ucfirst($inv->status), 'text-gray-600 bg-gray-100'];
                                            $vence = !empty($inv->days_overdue) && $inv->days_overdue > 0;
                                        ?>
                                        <tr class="text-gray-700 hover:bg-gray-50">
                                            <td class="px-4 py-3 text-sm">
                                                <a class="font-medium text-mam-blue-petroleo hover:underline" href="<?= base_url() ?>sisvent/purchases/provider_invoices/view/<?= (int)$inv->id ?>"><?= htmlspecialchars($inv->inv_code) ?></a>
                                                <?php if ($inv->currency !== 'COP'): ?>
                                                    <div class="text-xs text-gray-500"><?= htmlspecialchars($inv->currency) ?> · tasa <?= number_format((float)$inv->exchange_rate, 2, ',', '.') ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-4 py-3 text-sm"><?= htmlspecialchars($inv->provider_name) ?></td>
                                            <td class="px-4 py-3 text-sm whitespace-no-wrap"><?= $dateEs($inv->issue_date) ?></td>
                                            <td class="px-4 py-3 text-sm whitespace-no-wrap">
                                                <?= $dateEs($inv->due_date) ?>
                                                <?php if ($vence): ?><div class="text-xs font-semibold text-red-600"><?= (int)$inv->days_overdue ?> días vencida</div><?php endif; ?>
                                            </td>
                                            <td class="px-4 py-3">
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full whitespace-no-wrap <?= $est[1] ?>"><?= $est[0] ?></span>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-right whitespace-no-wrap"><?= $money($inv->total) ?></td>
                                            <td class="px-4 py-3 text-sm text-right whitespace-no-wrap <?= (float)$inv->paid > 0 ? 'text-green-600' : 'text-gray-400' ?>"><?= (float)$inv->paid > 0 ? $money($inv->paid) : '—' ?></td>
                                            <td class="px-4 py-3 text-sm text-right font-semibold whitespace-no-wrap <?= $vence ? 'text-red-600' : 'text-gray-800' ?>"><?= $money($inv->balance) ?></td>
                                            <td class="px-4 py-3 text-sm text-right whitespace-no-wrap">
                                                <a class="px-2 py-1 text-xs font-medium text-white bg-mam-blue-petroleo rounded hover:bg-mam-blue" href="<?= base_url() ?>sisvent/purchases/provider_invoices/view/<?= (int)$inv->id ?>">Ver</a>
                                                <?php if ((int)($inv->cash_payments ?? 0) === 0 && empty($inv->received_at)): ?>
                                                    <a class="px-2 py-1 text-xs font-medium text-gray-700 border border-gray-300 rounded hover:bg-gray-100" href="<?= base_url() ?>sisvent/purchases/provider_invoices/edit/<?= (int)$inv->id ?>">Editar</a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                                <?php if (!empty($invoices)): ?>
                                <tfoot>
                                    <tr class="bg-gray-100 border-t-2 border-gray-300 text-gray-700">
                                        <td class="px-4 py-3 text-sm font-bold uppercase text-right" colspan="5">Totales</td>
                                        <td class="px-4 py-3 text-sm text-right font-bold whitespace-no-wrap"><?= $money(array_sum(array_map(function($i){ return (float)$i->total; }, $invoices))) ?></td>
                                        <td class="px-4 py-3 text-sm text-right font-bold whitespace-no-wrap"><?= $money(array_sum(array_map(function($i){ return (float)$i->paid; }, $invoices))) ?></td>
                                        <td class="px-4 py-3 text-sm text-right font-bold whitespace-no-wrap"><?= $money($deudaTotal) ?></td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                                <?php endif; ?>
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
