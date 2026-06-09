<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="es">
<title>Tenants — Pulso</title>
<?php $this->load->view('sisvent/layouts/meta_header'); ?>
<body>
<div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
    <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => $_ci_view, 'role' => $role)); ?>
    <div class="flex flex-col flex-1 w-full">
        <?php $this->load->view('sisvent/layouts/navbar'); ?>
        <main class="h-full overflow-y-auto">
            <div class="px-6 py-4 mx-auto max-w-screen-xl">

                <div class="flex items-center justify-between mb-4">
                    <div>
                        <p class="text-xxs text-gray-400 uppercase tracking-wider">Plataforma Pulso</p>
                        <h2 class="text-2xl font-bold text-gray-800">Tenants</h2>
                        <p class="text-xs text-gray-500 mt-1">Empresas que operan bajo la plataforma. Solo platform admin.</p>
                    </div>
                    <a href="<?= base_url('sisvent/admin/tenants/edit') ?>"
                       class="px-4 py-2 text-sm font-bold text-white rounded-lg" style="background-color:#FF5A36;">
                        + Nuevo tenant
                    </a>
                </div>

                <?php if ($flash = $this->session->flashdata('success')): ?>
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-2 rounded mb-3 text-sm"><?= htmlspecialchars($flash) ?></div>
                <?php endif; ?>
                <?php if ($flash = $this->session->flashdata('error')): ?>
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-2 rounded mb-3 text-sm"><?= htmlspecialchars($flash) ?></div>
                <?php endif; ?>

                <div class="bg-white border rounded-lg overflow-hidden">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr class="text-xxs uppercase text-gray-500 font-bold">
                                <th class="px-3 py-2 text-left">Tenant</th>
                                <th class="px-3 py-2 text-left">Slug</th>
                                <th class="px-3 py-2 text-left">NIT</th>
                                <th class="px-3 py-2 text-left">Interrapidísimo sucursal</th>
                                <th class="px-3 py-2 text-right">Facturas</th>
                                <th class="px-3 py-2 text-right">Productos</th>
                                <th class="px-3 py-2 text-right">Clientes</th>
                                <th class="px-3 py-2 text-right">Users</th>
                                <th class="px-3 py-2 text-center">Estado</th>
                                <th class="px-3 py-2 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tenants as $t):
                                $c = $countsByTenant[$t->id] ?? array(); ?>
                            <tr class="border-t hover:bg-gray-50">
                                <td class="px-3 py-2">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-block w-3 h-3 rounded-full" style="background-color: <?= htmlspecialchars($t->brand_primary) ?>;"></span>
                                        <span class="font-bold text-gray-800"><?= htmlspecialchars($t->name) ?></span>
                                    </div>
                                </td>
                                <td class="px-3 py-2 font-mono text-xs text-gray-600"><?= htmlspecialchars($t->slug) ?></td>
                                <td class="px-3 py-2 text-gray-600"><?= htmlspecialchars($t->nit ?? '—') ?></td>
                                <td class="px-3 py-2 text-gray-600"><?= htmlspecialchars($t->inter_sucursal_id ?? '—') ?></td>
                                <td class="px-3 py-2 text-right text-gray-600"><?= number_format($c['invoices'] ?? 0, 0, ',', '.') ?></td>
                                <td class="px-3 py-2 text-right text-gray-600"><?= number_format($c['products'] ?? 0, 0, ',', '.') ?></td>
                                <td class="px-3 py-2 text-right text-gray-600"><?= number_format($c['clients'] ?? 0, 0, ',', '.') ?></td>
                                <td class="px-3 py-2 text-right text-gray-600"><?= number_format($c['users'] ?? 0, 0, ',', '.') ?></td>
                                <td class="px-3 py-2 text-center">
                                    <?php if ($t->active): ?>
                                    <span class="px-2 py-0.5 text-xxs bg-green-100 text-green-700 rounded-full">Activo</span>
                                    <?php else: ?>
                                    <span class="px-2 py-0.5 text-xxs bg-gray-100 text-gray-500 rounded-full">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-3 py-2 text-right">
                                    <a href="<?= base_url('sisvent/admin/tenants/edit/' . $t->id) ?>"
                                       class="text-xs text-blue-600 hover:underline">Editar</a>
                                    <span class="text-gray-300 mx-1">·</span>
                                    <a href="<?= base_url('sisvent/admin/tenants/switch_to/' . $t->id) ?>"
                                       class="text-xs text-purple-600 hover:underline">Ver como</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <p class="text-xs text-gray-400 mt-4">
                    Cada tenant es una empresa independiente con sus propios clientes, productos, cajas, bots y contabilidad.
                    Los subdominios <code class="bg-gray-100 px-1 rounded">{slug}.pulso.test</code> resuelven al tenant correspondiente.
                </p>

            </div>
        </main>
    </div>
</div>
<?php $this->load->view('sisvent/layouts/footer'); ?>
</body>
</html>
