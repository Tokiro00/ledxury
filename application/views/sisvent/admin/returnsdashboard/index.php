<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$role = $this->session->userdata('user_data')['role'];

function fmtMoney($v) {
    if ($v >= 1000000) return '$' . number_format($v/1000000, 1, '.', '') . 'M';
    if ($v >= 1000)    return '$' . number_format($v/1000, 0, '.', '') . 'K';
    return '$' . number_format((float)$v, 0, ',', '.');
}

// Color del % de tasa: rojo > 15%, naranja > 8%, verde menos
function tasaColor($pct) {
    if ($pct >= 15) return 'text-red-600';
    if ($pct >= 8)  return 'text-orange-500';
    return 'text-green-600';
}
function tasaBg($pct) {
    if ($pct >= 15) return 'bg-red-100 border-red-300';
    if ($pct >= 8)  return 'bg-orange-100 border-orange-300';
    return 'bg-green-100 border-green-300';
}

// Tendencia: calcular máximo para escalar barras
$maxTrend = 1;
foreach ($tendencia as $t) { if ($t->total > $maxTrend) $maxTrend = (int)$t->total; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Dashboard Devoluciones</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
</head>
<body>
<div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
    <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => $thisFile, 'role' => $role)); ?>
    <div class="flex flex-col flex-1 w-full">
        <?php $this->load->view('sisvent/layouts/navbar'); ?>
        <main class="h-full overflow-y-auto">
            <div class="px-4 py-4 w-full">

                <!-- Header -->
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between mb-4">
                    <div>
                        <h2 class="text-2xl font-black text-gray-800 tracking-tight">Dashboard de Devoluciones</h2>
                        <p class="text-xs text-gray-500">Tasa de devolución por SKU, ciudad y vendedor — datos de Interrapidísimo</p>
                    </div>
                    <form method="get" class="flex gap-2 mt-2 lg:mt-0 items-end">
                        <div>
                            <label class="text-xs text-gray-500 block">Desde</label>
                            <input type="date" name="desde" value="<?= htmlspecialchars($desde) ?>" class="form-input text-sm">
                        </div>
                        <div>
                            <label class="text-xs text-gray-500 block">Hasta</label>
                            <input type="date" name="hasta" value="<?= htmlspecialchars($hasta) ?>" class="form-input text-sm">
                        </div>
                        <div>
                            <label class="text-xs text-gray-500 block">Tienda</label>
                            <select name="store" class="form-input text-sm">
                                <option value="all" <?= $store == 'all' ? 'selected' : '' ?>>Todas</option>
                                <?php foreach ($stores as $s): ?>
                                <option value="<?= $s->idStore ?>" <?= (string)$store === (string)$s->idStore ? 'selected' : '' ?>><?= htmlspecialchars($s->name) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="px-4 py-2 text-sm font-bold text-white rounded-lg" style="background:#1B365D;">Filtrar</button>
                    </form>
                </div>

                <!-- KPIs -->
                <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-6">
                    <div class="bg-white rounded-lg shadow p-4 border-l-4 border-blue-500">
                        <p class="text-xs uppercase text-gray-500">Total guías</p>
                        <p class="text-3xl font-black text-gray-900"><?= number_format((int)$kpis->total_guias) ?></p>
                    </div>
                    <div class="bg-white rounded-lg shadow p-4 border-l-4 border-red-500">
                        <p class="text-xs uppercase text-gray-500">Devueltas</p>
                        <p class="text-3xl font-black text-red-600"><?= number_format((int)$kpis->devueltas) ?></p>
                    </div>
                    <div class="bg-white rounded-lg shadow p-4 border-l-4 <?= $kpis->tasa_pct >= 15 ? 'border-red-500' : ($kpis->tasa_pct >= 8 ? 'border-orange-500' : 'border-green-500') ?>">
                        <p class="text-xs uppercase text-gray-500">Tasa devolución</p>
                        <p class="text-3xl font-black <?= tasaColor($kpis->tasa_pct) ?>"><?= $kpis->tasa_pct ?>%</p>
                    </div>
                    <div class="bg-white rounded-lg shadow p-4 border-l-4 border-green-500">
                        <p class="text-xs uppercase text-gray-500">Entregadas</p>
                        <p class="text-3xl font-black text-green-600"><?= number_format((int)$kpis->entregadas) ?></p>
                        <p class="text-xs text-gray-500"><?= $kpis->tasa_entregadas_pct ?>%</p>
                    </div>
                    <div class="bg-white rounded-lg shadow p-4 border-l-4 border-gray-400">
                        <p class="text-xs uppercase text-gray-500">Valor devuelto (contrapago)</p>
                        <p class="text-2xl font-black text-gray-900"><?= fmtMoney($kpis->valor_devuelto) ?></p>
                    </div>
                </div>

                <!-- Tendencia diaria -->
                <?php if (!empty($tendencia) && count($tendencia) > 1): ?>
                <div class="bg-white rounded-lg shadow p-4 mb-6">
                    <h3 class="text-sm font-bold text-gray-700 mb-3">Tendencia diaria <span class="text-xs text-gray-400 font-normal">(barras grises = total despachado, rojas = devueltas)</span></h3>
                    <div class="flex items-end gap-1 h-32 overflow-x-auto">
                        <?php foreach ($tendencia as $t):
                            $hTotal = max(2, ($t->total / $maxTrend) * 120);
                            $hDev   = max(0, ($t->dev / $maxTrend) * 120);
                            $pct    = $t->total > 0 ? round(100 * $t->dev / $t->total, 1) : 0;
                        ?>
                        <div class="flex flex-col items-center relative group" style="min-width: 28px;">
                            <div class="absolute -top-8 hidden group-hover:block bg-gray-900 text-white text-xs rounded px-2 py-1 whitespace-nowrap z-10">
                                <?= $t->dia ?>: <?= $t->dev ?>/<?= $t->total ?> (<?= $pct ?>%)
                            </div>
                            <div class="w-5 bg-gray-300" style="height: <?= $hTotal ?>px;"></div>
                            <div class="w-5 bg-red-500 -mt-px" style="height: <?= $hDev ?>px; margin-top: -<?= $hTotal ?>px;"></div>
                            <span class="text-[10px] text-gray-400 mt-1"><?= substr($t->dia, 5) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Tablas: SKUs + Ciudades en grid 2x -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">

                    <!-- Top SKUs -->
                    <div class="bg-white rounded-lg shadow">
                        <div class="px-4 py-3 border-b">
                            <h3 class="font-bold text-gray-700">SKUs con más devolución</h3>
                            <p class="text-xs text-gray-400">Top 15 productos en pedidos devueltos</p>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                                    <tr>
                                        <th class="px-3 py-2 text-left">SKU</th>
                                        <th class="px-3 py-2 text-left">Descripción</th>
                                        <th class="px-3 py-2 text-right">Dev. guías</th>
                                        <th class="px-3 py-2 text-right">Tasa</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($top_skus)): ?>
                                        <tr><td colspan="4" class="px-3 py-6 text-center text-gray-400">Sin devoluciones en este rango.</td></tr>
                                    <?php else: foreach ($top_skus as $r): ?>
                                    <tr class="border-t hover:bg-gray-50">
                                        <td class="px-3 py-2 font-mono font-bold text-xs"><?= htmlspecialchars($r->productId) ?></td>
                                        <td class="px-3 py-2 text-xs text-gray-600"><?= htmlspecialchars(substr($r->description ?? '', 0, 45)) ?></td>
                                        <td class="px-3 py-2 text-right font-bold"><?= (int)$r->dev_guias ?></td>
                                        <td class="px-3 py-2 text-right font-bold <?= tasaColor($r->tasa_pct) ?>"><?= $r->tasa_pct ?>%</td>
                                    </tr>
                                    <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Top Ciudades -->
                    <div class="bg-white rounded-lg shadow">
                        <div class="px-4 py-3 border-b">
                            <h3 class="font-bold text-gray-700">Ciudades con más devolución</h3>
                            <p class="text-xs text-gray-400">Top 15 destinos problemáticos</p>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                                    <tr>
                                        <th class="px-3 py-2 text-left">Ciudad</th>
                                        <th class="px-3 py-2 text-right">Dev.</th>
                                        <th class="px-3 py-2 text-right">Total envíos</th>
                                        <th class="px-3 py-2 text-right">Tasa</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($top_ciudades)): ?>
                                        <tr><td colspan="4" class="px-3 py-6 text-center text-gray-400">Sin devoluciones en este rango.</td></tr>
                                    <?php else: foreach ($top_ciudades as $r): ?>
                                    <tr class="border-t hover:bg-gray-50">
                                        <td class="px-3 py-2 text-xs"><?= htmlspecialchars($r->ciudadDestinoNombre ?? '?') ?></td>
                                        <td class="px-3 py-2 text-right font-bold"><?= (int)$r->dev_guias ?></td>
                                        <td class="px-3 py-2 text-right text-gray-500"><?= (int)$r->total_envios_ciudad ?></td>
                                        <td class="px-3 py-2 text-right font-bold <?= tasaColor($r->tasa_pct) ?>"><?= $r->tasa_pct ?>%</td>
                                    </tr>
                                    <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Vendor + Detalle -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">

                    <!-- Vendedores -->
                    <div class="bg-white rounded-lg shadow">
                        <div class="px-4 py-3 border-b">
                            <h3 class="font-bold text-gray-700">Tasa por vendedor</h3>
                            <p class="text-xs text-gray-400">Mínimo 3 guías en el período</p>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                                    <tr>
                                        <th class="px-3 py-2 text-left">Vendedor</th>
                                        <th class="px-3 py-2 text-right">Total</th>
                                        <th class="px-3 py-2 text-right">Dev.</th>
                                        <th class="px-3 py-2 text-right">Tasa</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($top_vendors)): ?>
                                        <tr><td colspan="4" class="px-3 py-6 text-center text-gray-400">Sin datos.</td></tr>
                                    <?php else: foreach ($top_vendors as $r): ?>
                                    <tr class="border-t hover:bg-gray-50">
                                        <td class="px-3 py-2 text-xs"><?= htmlspecialchars($r->vendor_name ?? '?') ?></td>
                                        <td class="px-3 py-2 text-right text-gray-500"><?= (int)$r->total_guias ?></td>
                                        <td class="px-3 py-2 text-right font-bold"><?= (int)$r->dev_guias ?></td>
                                        <td class="px-3 py-2 text-right font-bold <?= tasaColor($r->tasa_pct) ?>"><?= $r->tasa_pct ?>%</td>
                                    </tr>
                                    <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Recomendaciones automáticas -->
                    <div class="bg-white rounded-lg shadow p-4">
                        <h3 class="font-bold text-gray-700 mb-3">📊 Insights</h3>
                        <ul class="text-sm space-y-2">
                            <?php
                            $insights = array();
                            if (!empty($top_skus) && $top_skus[0]->tasa_pct >= 20) {
                                $insights[] = "<strong>{$top_skus[0]->productId}</strong> tiene tasa de devolución del <strong class='text-red-600'>{$top_skus[0]->tasa_pct}%</strong>. Considera revisar foto/descripción o sacarlo del catálogo.";
                            }
                            if (!empty($top_ciudades) && $top_ciudades[0]->tasa_pct >= 25) {
                                $insights[] = "Las entregas a <strong>" . htmlspecialchars($top_ciudades[0]->ciudadDestinoNombre) . "</strong> fallan al <strong class='text-red-600'>{$top_ciudades[0]->tasa_pct}%</strong>. Posible problema de cobertura/zona.";
                            }
                            if (!empty($top_vendors)) {
                                foreach ($top_vendors as $v) {
                                    if ($v->tasa_pct >= 20 && $v->total_guias >= 10) {
                                        $insights[] = "El vendedor <strong>" . htmlspecialchars($v->vendor_name) . "</strong> tiene <strong class='text-red-600'>{$v->tasa_pct}%</strong> de devolución. Revisar capacitación o cuello de botella.";
                                        break;
                                    }
                                }
                            }
                            if ($kpis->tasa_pct < 10) {
                                $insights[] = "Tasa del <strong class='text-green-600'>{$kpis->tasa_pct}%</strong> está dentro de rango aceptable (industria 5-10%).";
                            } elseif ($kpis->tasa_pct < 15) {
                                $insights[] = "Tasa del <strong class='text-orange-500'>{$kpis->tasa_pct}%</strong> es elevada. Objetivo: bajar a menos de 10%.";
                            } else {
                                $insights[] = "🚨 Tasa del <strong class='text-red-600'>{$kpis->tasa_pct}%</strong> es <strong>crítica</strong>. Acción inmediata: revisar SKU/ciudad top y aplicar confirmación detallada antes de despachar.";
                            }

                            if (empty($insights)) $insights[] = "Sin patrones críticos detectados en este rango.";
                            foreach ($insights as $ins) echo "<li class='flex gap-2'><span class='text-gray-400'>•</span><span>$ins</span></li>";
                            ?>
                        </ul>
                    </div>
                </div>

                <!-- Lista de devoluciones recientes -->
                <div class="bg-white rounded-lg shadow mb-8">
                    <div class="px-4 py-3 border-b">
                        <h3 class="font-bold text-gray-700">Últimas 50 devoluciones</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                                <tr>
                                    <th class="px-3 py-2 text-left">Fecha</th>
                                    <th class="px-3 py-2 text-left">Factura</th>
                                    <th class="px-3 py-2 text-left">Guía</th>
                                    <th class="px-3 py-2 text-left">Cliente</th>
                                    <th class="px-3 py-2 text-left">Ciudad</th>
                                    <th class="px-3 py-2 text-left">Vendedor</th>
                                    <th class="px-3 py-2 text-right">Contrapago</th>
                                    <th class="px-3 py-2 text-left">Observaciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($detalle)): ?>
                                    <tr><td colspan="8" class="px-3 py-6 text-center text-gray-400">Sin devoluciones en este rango.</td></tr>
                                <?php else: foreach ($detalle as $d): ?>
                                <tr class="border-t hover:bg-gray-50">
                                    <td class="px-3 py-2 text-xs whitespace-nowrap"><?= date('Y-m-d', strtotime($d->created_at)) ?></td>
                                    <td class="px-3 py-2 text-xs"><a class="text-mam-blue underline" href="<?= base_url() ?>sisvent/commercial/invoices/view/<?= $d->idInvoice ?>">#<?= $d->idInvoice ?></a></td>
                                    <td class="px-3 py-2 text-xs font-mono"><?= htmlspecialchars($d->numeroPreenvio) ?></td>
                                    <td class="px-3 py-2 text-xs"><?= htmlspecialchars(substr($d->client_name ?? '', 0, 25)) ?></td>
                                    <td class="px-3 py-2 text-xs"><?= htmlspecialchars(substr($d->ciudadDestinoNombre ?? '', 0, 20)) ?></td>
                                    <td class="px-3 py-2 text-xs"><?= htmlspecialchars(substr($d->vendor_name ?? '?', 0, 20)) ?></td>
                                    <td class="px-3 py-2 text-xs text-right"><?= $d->contrapagoCost > 0 ? fmtMoney($d->contrapagoCost) : '-' ?></td>
                                    <td class="px-3 py-2 text-xs text-gray-500"><?= htmlspecialchars(substr($d->observations ?? '', 0, 60)) ?></td>
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
