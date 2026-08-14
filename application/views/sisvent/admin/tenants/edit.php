<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$csrfName = $this->security->get_csrf_token_name();
$csrfHash = $this->security->get_csrf_hash();
$t = $tenant; // null si es nuevo
$isNew = !$t;
?>
<!DOCTYPE html>
<html lang="es">
<title><?= $isNew ? 'Nuevo tenant' : 'Editar tenant' ?> — Pulso</title>
<?php $this->load->view('sisvent/layouts/meta_header'); ?>
<body>
<div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
    <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => 'sisvent/admin/tenants/list', 'role' => $role)); ?>
    <div class="flex flex-col flex-1 w-full">
        <?php $this->load->view('sisvent/layouts/navbar'); ?>
        <main class="h-full overflow-y-auto">
            <div class="px-6 py-4 mx-auto max-w-3xl">

                <div class="mb-4">
                    <a href="<?= base_url('sisvent/admin/tenants') ?>" class="text-xs text-gray-500 hover:underline">← Volver a tenants</a>
                    <h2 class="text-2xl font-bold text-gray-800 mt-1">
                        <?= $isNew ? 'Nuevo tenant' : 'Editar: ' . htmlspecialchars($t->name) ?>
                    </h2>
                </div>

                <?php if ($flash = $this->session->flashdata('error')): ?>
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-2 rounded mb-3 text-sm"><?= htmlspecialchars($flash) ?></div>
                <?php endif; ?>

                <form method="post" action="<?= base_url('sisvent/admin/tenants/save') ?>" class="bg-white border rounded-lg p-5 space-y-4">
                    <input type="hidden" name="<?= $csrfName ?>" value="<?= $csrfHash ?>">
                    <input type="hidden" name="id" value="<?= $t ? (int)$t->id : '' ?>">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <label class="text-sm">
                            <span class="block text-xs font-bold text-gray-600">Slug (subdominio)</span>
                            <input type="text" name="slug" required pattern="[a-z0-9][a-z0-9-]*"
                                   value="<?= $t ? htmlspecialchars($t->slug) : '' ?>"
                                   class="mt-1 w-full px-3 py-2 border rounded text-sm font-mono"
                                   placeholder="ledxury">
                            <span class="block text-xxs text-gray-400 mt-1">URL: <span class="font-mono">{slug}.pulso.test</span></span>
                        </label>
                        <label class="text-sm">
                            <span class="block text-xs font-bold text-gray-600">Nombre comercial</span>
                            <input type="text" name="name" required
                                   value="<?= $t ? htmlspecialchars($t->name) : '' ?>"
                                   class="mt-1 w-full px-3 py-2 border rounded text-sm"
                                   placeholder="Ledxury">
                        </label>
                        <label class="text-sm">
                            <span class="block text-xs font-bold text-gray-600">NIT</span>
                            <input type="text" name="nit"
                                   value="<?= $t ? htmlspecialchars($t->nit ?? '') : '' ?>"
                                   class="mt-1 w-full px-3 py-2 border rounded text-sm"
                                   placeholder="901427578">
                        </label>
                        <label class="text-sm">
                            <span class="block text-xs font-bold text-gray-600">Razón social</span>
                            <input type="text" name="razon_social"
                                   value="<?= $t ? htmlspecialchars($t->razon_social ?? '') : '' ?>"
                                   class="mt-1 w-full px-3 py-2 border rounded text-sm">
                        </label>
                    </div>

                    <div class="border-t pt-4">
                        <p class="text-xs font-bold text-gray-500 uppercase mb-2">Integración Interrapidísimo</p>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <label class="text-sm">
                                <span class="block text-xs font-bold text-gray-600">CodigoConvenioRemitente</span>
                                <input type="number" name="inter_sucursal_id"
                                       value="<?= $t ? htmlspecialchars($t->inter_sucursal_id ?? '') : '' ?>"
                                       class="mt-1 w-full px-3 py-2 border rounded text-sm font-mono">
                                <span class="block text-xxs text-gray-400 mt-1">ID de sucursal registrada en Interrapidísimo</span>
                            </label>
                            <label class="text-sm">
                                <span class="block text-xs font-bold text-gray-600">Dirección recogida</span>
                                <input type="text" name="inter_pickup_address"
                                       value="<?= $t ? htmlspecialchars($t->inter_pickup_address ?? '') : '' ?>"
                                       class="mt-1 w-full px-3 py-2 border rounded text-sm">
                            </label>
                            <label class="text-sm">
                                <span class="block text-xs font-bold text-gray-600">Ciudad DANE</span>
                                <input type="text" name="inter_pickup_city"
                                       value="<?= $t ? htmlspecialchars($t->inter_pickup_city ?? '') : '' ?>"
                                       class="mt-1 w-full px-3 py-2 border rounded text-sm font-mono"
                                       placeholder="05088000">
                            </label>
                        </div>
                    </div>

                    <div class="border-t pt-4">
                        <p class="text-xs font-bold text-gray-500 uppercase mb-2">Marca visual</p>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <label class="text-sm">
                                <span class="block text-xs font-bold text-gray-600">Color primario</span>
                                <div class="flex gap-2 mt-1 items-center">
                                    <input type="color" name="brand_primary"
                                           value="<?= $t ? htmlspecialchars($t->brand_primary) : '#FF5A36' ?>"
                                           class="h-9 w-12 border rounded">
                                    <input type="text" name="brand_primary_text"
                                           value="<?= $t ? htmlspecialchars($t->brand_primary) : '#FF5A36' ?>"
                                           class="px-3 py-2 border rounded text-sm font-mono w-28" readonly>
                                </div>
                            </label>
                            <label class="text-sm">
                                <span class="block text-xs font-bold text-gray-600">Color secundario</span>
                                <div class="flex gap-2 mt-1 items-center">
                                    <input type="color" name="brand_secondary"
                                           value="<?= $t ? htmlspecialchars($t->brand_secondary) : '#FFF7EE' ?>"
                                           class="h-9 w-12 border rounded">
                                    <input type="text" name="brand_secondary_text"
                                           value="<?= $t ? htmlspecialchars($t->brand_secondary) : '#FFF7EE' ?>"
                                           class="px-3 py-2 border rounded text-sm font-mono w-28" readonly>
                                </div>
                            </label>
                            <label class="text-sm">
                                <span class="block text-xs font-bold text-gray-600">Logo URL</span>
                                <input type="text" name="logo_url"
                                       value="<?= $t ? htmlspecialchars($t->logo_url ?? '') : '' ?>"
                                       class="mt-1 w-full px-3 py-2 border rounded text-sm"
                                       placeholder="https://...">
                            </label>
                        </div>
                    </div>

                    <div class="border-t pt-4">
                        <p class="text-xs font-bold text-gray-500 uppercase mb-2">Plantilla y datos para facturas</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <label class="text-sm">
                                <span class="block text-xs font-bold text-gray-600">Plantilla de factura</span>
                                <select name="invoice_template" class="mt-1 w-full px-3 py-2 border rounded text-sm">
                                    <option value="pulso" <?= ($t && $t->invoice_template === 'pulso') ? 'selected' : '' ?>>Pulso (default)</option>
                                    <option value="mam_classic" <?= ($t && $t->invoice_template === 'mam_classic') ? 'selected' : '' ?>>MAM Classic</option>
                                </select>
                            </label>
                        </div>
                        <label class="text-sm block mt-3">
                            <span class="block text-xs font-bold text-gray-600">Texto de cuenta bancaria (pie de factura)</span>
                            <textarea name="invoice_account" rows="2" class="mt-1 w-full px-3 py-2 border rounded text-sm"><?= $t ? htmlspecialchars($t->invoice_account ?? '') : '' ?></textarea>
                        </label>
                        <label class="text-sm block mt-3">
                            <span class="block text-xs font-bold text-gray-600">Texto de soporte (pie de factura)</span>
                            <textarea name="invoice_support" rows="2" class="mt-1 w-full px-3 py-2 border rounded text-sm"><?= $t ? htmlspecialchars($t->invoice_support ?? '') : '' ?></textarea>
                        </label>
                    </div>

                    <div class="border-t pt-4 flex items-center gap-4">
                        <label class="text-sm inline-flex items-center gap-2">
                            <input type="checkbox" name="bot_enabled" value="1" <?= ($t && $t->bot_enabled) ? 'checked' : '' ?>>
                            <span class="text-gray-700">Bot habilitado</span>
                        </label>
                        <label class="text-sm inline-flex items-center gap-2">
                            <input type="checkbox" name="active" value="1" <?= (!$t || $t->active) ? 'checked' : '' ?>>
                            <span class="text-gray-700">Tenant activo</span>
                        </label>
                    </div>

                    <div class="border-t pt-4 flex justify-end gap-2">
                        <a href="<?= base_url('sisvent/admin/tenants') ?>" class="px-4 py-2 text-sm text-gray-600 border rounded hover:bg-gray-50">Cancelar</a>
                        <button type="submit" class="px-5 py-2 text-sm font-bold text-white rounded" style="background-color:#FF5A36;">
                            <?= $isNew ? 'Crear tenant' : 'Guardar cambios' ?>
                        </button>
                    </div>
                </form>

            </div>
        </main>
    </div>
</div>
<script>
// Sincronizar color picker con su input de texto adyacente
document.querySelectorAll('input[type=color]').forEach(function(c){
    c.addEventListener('input', function(){
        var txt = c.parentElement.querySelector('input[type=text]');
        if (txt) txt.value = c.value;
    });
});
</script>
<?php $this->load->view('sisvent/layouts/footer'); ?>
</body>
</html>
