<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$totalPages = max(1, (int)ceil($total / $limit));
$qs = function($override = array()) use ($filters, $page) {
    $base = array(
        'vendedor' => $filters['vendedor'],
        'guia'     => $filters['guia'],
        'from'     => $filters['from'],
        'to'       => $filters['to'],
        'page'     => $page,
    );
    return http_build_query(array_filter(array_merge($base, $override), function($v) { return $v !== null && $v !== ''; }));
};
?>
<!DOCTYPE html>
<html lang="es">
<title>Despachos MAM — Ledxury</title>
<?php $this->load->view('sisvent/layouts/meta_header'); ?>
<body>
<div id="bars" class="flex h-screen bg-gray-100" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
    <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => 'sisvent/admin/contrapagos/index', 'role' => $role)); ?>
    <div class="flex flex-col flex-1 w-full">
        <?php $this->load->view('sisvent/layouts/navbar'); ?>
        <main class="h-full overflow-y-auto">
            <div class="px-6 py-5 w-full max-w-screen-xl mx-auto">

                <!-- Header -->
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between mb-4">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-700">Despachos MAM</h2>
                        <p class="text-xs text-gray-400 mt-0.5">Listado de envíos de MAM con guía Inter. Origen para auto-marcar items y pagos como intercompañía.</p>
                    </div>
                    <div class="mt-2 lg:mt-0 flex gap-3 text-xs">
                        <a href="<?= base_url() ?>sisvent/admin/contrapagos/invoices" class="text-mam-blue-petroleo hover:underline">Facturas Inter</a>
                        <a href="<?= base_url() ?>sisvent/admin/contrapagos" class="text-mam-blue-petroleo hover:underline">Pagos Contrapago</a>
                    </div>
                </div>

                <?php if ($this->session->flashdata('contrapago_error')): ?>
                <div class="flex items-center p-3 mb-4 text-sm text-red-700 bg-red-50 border border-red-200 rounded-lg">
                    <?= $this->session->flashdata('contrapago_error') ?>
                </div>
                <?php endif; ?>
                <?php if ($this->session->flashdata('contrapago_success')): ?>
                <div class="flex items-center p-3 mb-4 text-sm text-green-700 bg-green-50 border border-green-200 rounded-lg">
                    <?= $this->session->flashdata('contrapago_success') ?>
                </div>
                <?php endif; ?>

                <!-- KPIs -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
                    <div class="bg-white rounded-lg border p-3 border-l-4 border-mam-blue-petroleo">
                        <p class="text-xxs text-gray-400 uppercase tracking-wide">Despachos cargados</p>
                        <p class="text-xl font-bold text-gray-700 mt-1"><?= number_format((int)($stats->total ?? 0), 0, ',', '.') ?></p>
                        <?php if (!empty($stats->oldest) && !empty($stats->newest)): ?>
                        <p class="text-xxs text-gray-400 mt-0.5"><?= date('d/m/Y', strtotime($stats->oldest)) ?> → <?= date('d/m/Y', strtotime($stats->newest)) ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="bg-white rounded-lg border p-3 border-l-4 border-yellow-500">
                        <p class="text-xxs text-gray-400 uppercase tracking-wide">Flete total</p>
                        <p class="text-xl font-bold text-yellow-700 mt-1">$<?= number_format((float)($stats->total_flete ?? 0), 0, ',', '.') ?></p>
                        <p class="text-xxs text-gray-400 mt-0.5"><?= (int)($stats->vendedores ?? 0) ?> vendedores</p>
                    </div>
                    <div class="bg-white rounded-lg border p-3 border-l-4 border-green-500">
                        <p class="text-xxs text-gray-400 uppercase tracking-wide">Items facturas Inter = MAM</p>
                        <p class="text-xl font-bold text-green-700 mt-1"><?= number_format((int)$matchStats['items_mam'], 0, ',', '.') ?></p>
                        <p class="text-xxs text-gray-400 mt-0.5">guías intercompany ya marcadas</p>
                    </div>
                    <div class="bg-white rounded-lg border p-3 border-l-4 border-green-500">
                        <p class="text-xxs text-gray-400 uppercase tracking-wide">Pagos contrapago = MAM</p>
                        <p class="text-xl font-bold text-green-700 mt-1"><?= number_format((int)$matchStats['payments_mam'], 0, ',', '.') ?></p>
                        <p class="text-xxs text-gray-400 mt-0.5">cobros intercompany ya marcados</p>
                    </div>
                </div>

                <!-- Upload -->
                <div class="bg-white rounded-lg border p-5 mb-4">
                    <form action="<?= base_url() ?>sisvent/admin/contrapagos/uploadDespachosMam" method="POST" enctype="multipart/form-data" class="flex flex-col lg:flex-row items-start lg:items-end gap-4">
                        <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                        <div class="flex-1 w-full">
                            <label class="block text-xs text-gray-400 uppercase tracking-wide mb-1.5">Excel de despachos MAM (.xlsx)</label>
                            <input type="file" name="excel_file" accept=".xlsx,.xls" required
                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg bg-gray-50 focus:outline-none focus:bg-white">
                        </div>
                        <button type="submit" style="background:#4487A0;" class="inline-flex items-center flex-shrink-0 px-5 py-2.5 text-sm font-medium text-white rounded-lg hover:opacity-90 whitespace-nowrap">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                            Subir despachos
                        </button>
                    </form>
                    <p class="text-xs text-gray-400 mt-2">
                        Header en fila 1; columnas: A factura · B fecha · C cliente · D destino · E transp · <strong>F guía</strong> · G cajas · H peso · I valor · J flete · K vendedor · L separado · M despachado · N bodega.
                        Re-subir el mismo archivo NO duplica: actualiza por <code>numero_guia</code>.
                    </p>
                </div>

                <!-- Filtros -->
                <form method="GET" class="bg-white rounded-lg border p-3 mb-4 flex items-center gap-3 flex-wrap">
                    <span class="text-xxs font-bold text-gray-500 uppercase">Filtrar:</span>
                    <input type="text" name="vendedor" value="<?= htmlspecialchars((string)$filters['vendedor']) ?>" placeholder="Vendedor" class="px-2 py-1 border rounded text-xs">
                    <input type="text" name="guia" value="<?= htmlspecialchars((string)$filters['guia']) ?>" placeholder="Guía" class="px-2 py-1 border rounded text-xs">
                    <label class="flex items-center gap-1 text-xs text-gray-600">Desde
                        <input type="date" name="from" value="<?= htmlspecialchars((string)$filters['from']) ?>" class="px-2 py-1 border rounded text-xs">
                    </label>
                    <label class="flex items-center gap-1 text-xs text-gray-600">Hasta
                        <input type="date" name="to" value="<?= htmlspecialchars((string)$filters['to']) ?>" class="px-2 py-1 border rounded text-xs">
                    </label>
                    <button type="submit" class="px-3 py-1 text-xs font-bold text-white bg-mam-blue-petroleo rounded">Aplicar</button>
                    <a href="<?= base_url() ?>sisvent/admin/contrapagos/despachosMam" class="text-xs text-gray-500 hover:text-gray-700">Limpiar</a>
                    <span class="ml-auto text-xs text-gray-400"><?= number_format($total, 0, ',', '.') ?> resultados</span>
                </form>

                <!-- Tabla -->
                <div class="bg-white rounded-lg border overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs whitespace-no-wrap">
                            <thead>
                                <tr style="background:#1B365D;">
                                    <th class="px-3 py-2 text-left text-xs font-semibold text-white uppercase">Fecha</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold text-white uppercase">Guía</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold text-white uppercase">Factura MAM</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold text-white uppercase">Cliente</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold text-white uppercase">Destino</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold text-white uppercase">Vendedor</th>
                                    <th class="px-3 py-2 text-right text-xs font-semibold text-white uppercase">Cajas</th>
                                    <th class="px-3 py-2 text-right text-xs font-semibold text-white uppercase">Peso</th>
                                    <th class="px-3 py-2 text-right text-xs font-semibold text-white uppercase">Valor fact.</th>
                                    <th class="px-3 py-2 text-right text-xs font-semibold text-white uppercase">Flete</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y bg-white">
                                <?php if (empty($rows)): ?>
                                    <tr><td colspan="10" class="px-4 py-8 text-center text-gray-400">Sin despachos cargados aún. Sube el Excel arriba.</td></tr>
                                <?php else: foreach ($rows as $r): ?>
                                <tr class="text-gray-700 hover:bg-gray-50">
                                    <td class="px-3 py-1.5 text-gray-500"><?= !empty($r->fecha_despacho) ? date('d/m/Y', strtotime($r->fecha_despacho)) : '—' ?></td>
                                    <td class="px-3 py-1.5 font-mono text-mam-blue-petroleo"><?= htmlspecialchars((string)$r->numero_guia) ?></td>
                                    <td class="px-3 py-1.5 font-mono text-gray-600"><?= htmlspecialchars((string)$r->factura_mam) ?></td>
                                    <td class="px-3 py-1.5 text-gray-700" style="max-width:220px; overflow:hidden; text-overflow:ellipsis;"><?= htmlspecialchars((string)$r->cliente) ?></td>
                                    <td class="px-3 py-1.5 text-gray-500" style="max-width:160px; overflow:hidden; text-overflow:ellipsis;"><?= htmlspecialchars((string)$r->destino) ?></td>
                                    <td class="px-3 py-1.5 text-gray-600"><?= htmlspecialchars((string)$r->vendedor) ?></td>
                                    <td class="px-3 py-1.5 text-right"><?= (int)$r->cajas ?: '—' ?></td>
                                    <td class="px-3 py-1.5 text-right"><?= (float)$r->peso > 0 ? number_format($r->peso, 2, ',', '.') : '—' ?></td>
                                    <td class="px-3 py-1.5 text-right text-gray-600"><?= (float)$r->valor_factura > 0 ? '$' . number_format($r->valor_factura, 0, ',', '.') : '—' ?></td>
                                    <td class="px-3 py-1.5 text-right font-bold text-yellow-700"><?= (float)$r->flete > 0 ? '$' . number_format($r->flete, 0, ',', '.') : '—' ?></td>
                                </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($totalPages > 1): ?>
                    <div class="flex items-center justify-between p-3 text-xs text-gray-500 border-t">
                        <span>Página <?= $page ?> de <?= $totalPages ?></span>
                        <div class="flex gap-2">
                            <?php if ($page > 1): ?>
                                <a href="?<?= $qs(array('page' => $page - 1)) ?>" class="px-3 py-1 border rounded hover:bg-gray-50">← Anterior</a>
                            <?php endif; ?>
                            <?php if ($page < $totalPages): ?>
                                <a href="?<?= $qs(array('page' => $page + 1)) ?>" class="px-3 py-1 border rounded hover:bg-gray-50">Siguiente →</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

            </div>
        </main>
    </div>
</div>
<?php $this->load->view('sisvent/layouts/footer'); ?>
</body>
</html>
