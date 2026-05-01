<?php
$role = $this->session->userdata('user_data')['role'];
$isEdit = !empty($rule);
$action = $isEdit ? base_url() . 'sisvent/admin/purchaserules/update' : base_url() . 'sisvent/admin/purchaserules/store';
$title  = $isEdit ? 'Editar regla de compra' : 'Nueva regla de compra';

// Defaults para el form (si edit, leer; si add, defaults razonables)
$cfg = $isEdit ? (json_decode((string)$rule->frequency_config, true) ?: []) : [];
$f_name           = $isEdit ? $rule->name : '';
$f_provider       = $isEdit ? (int)$rule->providerId : 0;
$f_store          = $isEdit ? (int)$rule->storeId : 1;
$f_freq_type      = $isEdit ? $rule->frequency_type : 'weekly';
$f_lookback       = $isEdit ? (int)$rule->lookback_days : 7;
$f_filter         = $isEdit ? $rule->product_filter : 'all_sold';
$f_skus           = $isEdit ? implode(', ', json_decode((string)$rule->product_list, true) ?: []) : '';
$f_excl_blocked   = $isEdit ? (int)$rule->exclude_blocked : 1;
$f_active         = $isEdit ? (int)$rule->active : 1;
$f_dow            = $cfg['day_of_week']  ?? 1;
$f_dom            = $cfg['day_of_month'] ?? 1;
$f_hour           = $cfg['hour']         ?? 6;
$f_cron           = $cfg['cron']         ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title><?= htmlspecialchars($title) ?></title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
</head>
<body>
<div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">

    <?php $this->load->view('sisvent/layouts/sidebar', ['thisFile' => $_ci_view, 'role' => $role]); ?>

    <div class="flex flex-col flex-1 w-full">
        <?php $this->load->view('sisvent/layouts/navbar'); ?>

        <main class="h-full overflow-y-auto">
            <div class="px-6 mx-auto grid max-w-4xl">

                <div class="flex items-center mb-4 mt-2">
                    <a href="<?= base_url() ?>sisvent/admin/purchaserules" class="mr-3 text-gray-500 hover:text-gray-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </a>
                    <h2 class="text-lg font-semibold text-gray-700"><?= htmlspecialchars($title) ?></h2>
                </div>

                <?php if($this->session->flashdata('error')): ?>
                    <div class="flex items-center p-4 mb-4 text-sm font-semibold text-white bg-red-600 rounded-lg shadow-md">
                        <p><?= htmlspecialchars($this->session->flashdata('error')) ?></p>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?= $action ?>" class="bg-white rounded-lg shadow p-6 space-y-5">
                    <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                    <?php if($isEdit): ?>
                        <input type="hidden" name="id" value="<?= (int)$rule->id ?>">
                    <?php endif; ?>

                    <!-- Nombre -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Nombre de la regla *</label>
                        <input type="text" name="name" required maxlength="120" value="<?= htmlspecialchars($f_name) ?>"
                               placeholder="Ej: Reposición semanal MAM"
                               class="form-input-lg w-full"/>
                    </div>

                    <!-- Proveedor + Tienda -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Proveedor *</label>
                            <select name="providerId" required class="form-input-lg w-full">
                                <option value="">— Seleccionar —</option>
                                <?php foreach($providers as $p): ?>
                                    <option value="<?= (int)$p->idProvider ?>" <?= $f_provider === (int)$p->idProvider ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($p->name) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Tienda destino *</label>
                            <select name="storeId" required class="form-input-lg w-full">
                                <?php foreach($stores as $s): ?>
                                    <option value="<?= (int)$s->idStore ?>" <?= $f_store === (int)$s->idStore ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($s->name) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Frecuencia -->
                    <div class="border-t pt-5">
                        <h3 class="text-sm font-semibold text-gray-700 mb-3">¿Cada cuánto se ejecuta?</h3>
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Tipo</label>
                                <select name="frequency_type" id="frequency_type" class="form-input-lg w-full">
                                    <option value="weekly"  <?= $f_freq_type === 'weekly'  ? 'selected' : '' ?>>Semanal</option>
                                    <option value="monthly" <?= $f_freq_type === 'monthly' ? 'selected' : '' ?>>Mensual</option>
                                    <option value="custom"  <?= $f_freq_type === 'custom'  ? 'selected' : '' ?>>Personalizada</option>
                                </select>
                            </div>

                            <div id="grp_weekly" style="<?= $f_freq_type === 'weekly' ? '' : 'display:none' ?>">
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Día de la semana</label>
                                <select name="day_of_week" class="form-input-lg w-full">
                                    <?php
                                    $dows = [1=>'Lunes',2=>'Martes',3=>'Miércoles',4=>'Jueves',5=>'Viernes',6=>'Sábado',7=>'Domingo'];
                                    foreach($dows as $k=>$v): ?>
                                        <option value="<?= $k ?>" <?= (int)$f_dow === $k ? 'selected' : '' ?>><?= $v ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div id="grp_monthly" style="<?= $f_freq_type === 'monthly' ? '' : 'display:none' ?>">
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Día del mes (1–28)</label>
                                <input type="number" name="day_of_month" min="1" max="28" value="<?= (int)$f_dom ?>" class="form-input-lg w-full"/>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Hora (Bogotá)</label>
                                <select name="hour" class="form-input-lg w-full">
                                    <?php for($h=0; $h<24; $h++): ?>
                                        <option value="<?= $h ?>" <?= (int)$f_hour === $h ? 'selected' : '' ?>>
                                            <?= sprintf('%02d:00', $h) ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>

                        <div id="grp_custom" style="<?= $f_freq_type === 'custom' ? 'margin-top:1rem' : 'display:none' ?>" class="mt-3">
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Cron expression (avanzado)</label>
                            <input type="text" name="cron_expr" value="<?= htmlspecialchars($f_cron) ?>" placeholder="0 6 * * 1" class="form-input-lg w-full"/>
                            <p class="text-xs text-gray-500 mt-1">Por ahora "personalizada" se ejecuta como semanal a la hora elegida. Soporte completo de cron-expr en versión futura.</p>
                        </div>
                    </div>

                    <!-- Filtro de productos -->
                    <div class="border-t pt-5">
                        <h3 class="text-sm font-semibold text-gray-700 mb-3">¿Qué productos incluir?</h3>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Estrategia</label>
                                <select name="product_filter" id="product_filter" class="form-input-lg w-full">
                                    <option value="all_sold"      <?= $f_filter === 'all_sold'      ? 'selected' : '' ?>>Lo que se vendió en el período</option>
                                    <option value="specific_list" <?= $f_filter === 'specific_list' ? 'selected' : '' ?>>Lista específica de SKUs</option>
                                    <option value="all_provider"  <?= $f_filter === 'all_provider'  ? 'selected' : '' ?>>Todos los productos del proveedor</option>
                                </select>
                            </div>

                            <div id="grp_lookback" style="<?= $f_filter === 'all_sold' ? '' : 'display:none' ?>">
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Días hacia atrás (lookback)</label>
                                <input type="number" name="lookback_days" min="1" max="90" value="<?= (int)$f_lookback ?>" class="form-input-lg w-32"/>
                                <p class="text-xs text-gray-500 mt-1">Suma de cantidades vendidas en los últimos N días. Para semanal típicamente 7.</p>
                            </div>

                            <div id="grp_skus" style="<?= $f_filter === 'specific_list' ? '' : 'display:none' ?>">
                                <label class="block text-xs font-semibold text-gray-600 mb-1">SKUs (separados por coma o salto de línea)</label>
                                <textarea name="product_list" rows="4" class="form-input-lg w-full font-mono text-xs" placeholder="3LED-12V-I, 6LED-24V-A, DISC-ALARM"><?= htmlspecialchars($f_skus) ?></textarea>
                            </div>

                            <label class="inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="exclude_blocked" value="1" <?= $f_excl_blocked ? 'checked' : '' ?> class="form-checkbox h-5 w-5 text-mam-blue-petroleo">
                                <span class="ml-2 text-sm">Excluir productos agotados (bot_blocked_products)</span>
                            </label>
                        </div>
                    </div>

                    <!-- Activa -->
                    <div class="border-t pt-5">
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="active" value="1" <?= $f_active ? 'checked' : '' ?> class="form-checkbox h-5 w-5 text-emerald-600">
                            <span class="ml-2 text-sm font-semibold">Regla activa</span>
                        </label>
                        <p class="text-xs text-gray-500 ml-7">Si la desactivas, el cron deja de generar órdenes de esta regla. Las órdenes ya creadas no se borran.</p>
                    </div>

                    <!-- Botones -->
                    <div class="border-t pt-5 flex justify-end gap-2">
                        <a href="<?= base_url() ?>sisvent/admin/purchaserules" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded hover:bg-gray-200">
                            Cancelar
                        </a>
                        <button type="submit" class="px-5 py-2 text-sm font-medium text-white bg-mam-blue-petroleo rounded hover:opacity-90">
                            <?= $isEdit ? 'Guardar cambios' : 'Crear regla' ?>
                        </button>
                    </div>
                </form>

            </div>
        </main>
    </div>
</div>

<?php $this->load->view('sisvent/layouts/footer'); ?>

<script>
$(document).on('change', '#frequency_type', function(){
    var v = $(this).val();
    $('#grp_weekly').toggle(v === 'weekly');
    $('#grp_monthly').toggle(v === 'monthly');
    $('#grp_custom').toggle(v === 'custom');
});

$(document).on('change', '#product_filter', function(){
    var v = $(this).val();
    $('#grp_lookback').toggle(v === 'all_sold');
    $('#grp_skus').toggle(v === 'specific_list');
});
</script>
</body>
</html>
