<?php $role = $this->session->userdata('user_data')['role']; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Garantías y Devoluciones</title>
    <?php $this->load->view('sisvent/layouts/meta_header'); ?>
</head>
<body>
<div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">

    <?php $this->load->view('sisvent/layouts/sidebar', ['thisFile' => $_ci_view, 'role' => $role]); ?>

    <div class="flex flex-col flex-1 w-full">
        <?php $this->load->view('sisvent/layouts/navbar'); ?>

        <main class="h-full overflow-y-auto">
            <div class="px-6 mx-auto grid">

                <div class="flex items-center justify-between mb-4 mt-2 flex-wrap gap-3">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-700">Garantías y Devoluciones</h2>
                        <p class="text-xs text-gray-500 mt-1">
                            Tickets de casos por el canal WhatsApp dedicado.
                            La conversación de cada cliente está en
                            <a href="<?= base_url() ?>sisvent/admin/bots" class="underline hover:text-mam-blue">Bots → Chat</a>
                            (selecciona "Ledxury Garantías" en el dropdown).
                        </p>
                    </div>
                    <a href="<?= base_url() ?>sisvent/admin/garantias/add"
                       class="flex items-center px-4 py-2 text-sm font-medium leading-5 text-white bg-mam-blue-petroleo rounded-lg hover:opacity-90">
                        <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span>Nuevo ticket</span>
                    </a>
                </div>

                <!-- Stats / contadores -->
                <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-5">
                    <a href="?status=abierto" class="bg-white rounded-lg shadow-sm border border-amber-200 p-3 text-center hover:shadow-md">
                        <div class="text-2xl font-extrabold text-amber-700"><?= (int)$counts['abierto'] ?></div>
                        <div class="text-[11px] text-amber-600 font-semibold mt-1">⚠ Abiertos</div>
                    </a>
                    <a href="?status=en_revision" class="bg-white rounded-lg shadow-sm border border-blue-200 p-3 text-center hover:shadow-md">
                        <div class="text-2xl font-extrabold text-blue-700"><?= (int)$counts['en_revision'] ?></div>
                        <div class="text-[11px] text-blue-600 font-semibold mt-1">⟳ En revisión</div>
                    </a>
                    <a href="?status=resuelto" class="bg-white rounded-lg shadow-sm border border-emerald-200 p-3 text-center hover:shadow-md">
                        <div class="text-2xl font-extrabold text-emerald-700"><?= (int)$counts['resuelto'] ?></div>
                        <div class="text-[11px] text-emerald-600 font-semibold mt-1">✓ Resueltos</div>
                    </a>
                    <a href="?status=cerrado" class="bg-white rounded-lg shadow-sm border border-slate-200 p-3 text-center hover:shadow-md">
                        <div class="text-2xl font-extrabold text-slate-700"><?= (int)$counts['cerrado'] ?></div>
                        <div class="text-[11px] text-slate-600 font-semibold mt-1">▣ Cerrados</div>
                    </a>
                    <a href="?status=cancelado" class="bg-white rounded-lg shadow-sm border border-red-200 p-3 text-center hover:shadow-md">
                        <div class="text-2xl font-extrabold text-red-700"><?= (int)$counts['cancelado'] ?></div>
                        <div class="text-[11px] text-red-600 font-semibold mt-1">✕ Cancelados</div>
                    </a>
                </div>

                <!-- Filtros -->
                <form method="get" class="bg-white rounded-lg shadow-md p-4 mb-4 flex flex-wrap items-end gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Estado</label>
                        <select name="status" onchange="this.form.submit()" class="text-sm border border-gray-300 rounded px-2 py-1.5">
                            <option value="all"           <?= $filters['status'] === 'all'           ? 'selected' : '' ?>>Todos</option>
                            <option value="abierto"       <?= $filters['status'] === 'abierto'       ? 'selected' : '' ?>>Abierto</option>
                            <option value="en_revision"   <?= $filters['status'] === 'en_revision'   ? 'selected' : '' ?>>En revisión</option>
                            <option value="resuelto"      <?= $filters['status'] === 'resuelto'      ? 'selected' : '' ?>>Resuelto</option>
                            <option value="cerrado"       <?= $filters['status'] === 'cerrado'       ? 'selected' : '' ?>>Cerrado</option>
                            <option value="cancelado"     <?= $filters['status'] === 'cancelado'     ? 'selected' : '' ?>>Cancelado</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Tipo</label>
                        <select name="type" onchange="this.form.submit()" class="text-sm border border-gray-300 rounded px-2 py-1.5">
                            <option value="all"        <?= $filters['case_type'] === 'all'        ? 'selected' : '' ?>>Todos</option>
                            <option value="garantia"   <?= $filters['case_type'] === 'garantia'   ? 'selected' : '' ?>>Garantía</option>
                            <option value="devolucion" <?= $filters['case_type'] === 'devolucion' ? 'selected' : '' ?>>Devolución</option>
                            <option value="reclamo"    <?= $filters['case_type'] === 'reclamo'    ? 'selected' : '' ?>>Reclamo</option>
                            <option value="otro"       <?= $filters['case_type'] === 'otro'       ? 'selected' : '' ?>>Otro</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Prioridad</label>
                        <select name="prio" onchange="this.form.submit()" class="text-sm border border-gray-300 rounded px-2 py-1.5">
                            <option value="all"      <?= $filters['priority'] === 'all'      ? 'selected' : '' ?>>Todas</option>
                            <option value="urgente"  <?= $filters['priority'] === 'urgente'  ? 'selected' : '' ?>>Urgente</option>
                            <option value="alta"     <?= $filters['priority'] === 'alta'     ? 'selected' : '' ?>>Alta</option>
                            <option value="media"    <?= $filters['priority'] === 'media'    ? 'selected' : '' ?>>Media</option>
                            <option value="baja"     <?= $filters['priority'] === 'baja'     ? 'selected' : '' ?>>Baja</option>
                        </select>
                    </div>
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Buscar</label>
                        <input type="text" name="q" value="<?= htmlspecialchars($filters['search'] ?? '') ?>" placeholder="ticket, teléfono, nombre, descripción..."
                               class="text-sm border border-gray-300 rounded px-2 py-1.5 w-full"/>
                    </div>
                    <button type="submit" class="px-3 py-1.5 text-sm font-medium text-white bg-mam-blue-petroleo rounded">Filtrar</button>
                </form>

                <!-- Tabla -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <table class="w-full whitespace-no-wrap text-sm">
                        <thead>
                            <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                                <th class="px-3 py-2">Ticket</th>
                                <th class="px-3 py-2">Cliente</th>
                                <th class="px-3 py-2">Tipo</th>
                                <th class="px-3 py-2 text-center">Prioridad</th>
                                <th class="px-3 py-2 text-center">Estado</th>
                                <th class="px-3 py-2">Asignado</th>
                                <th class="px-3 py-2">Abierto</th>
                                <th class="px-3 py-2 text-right">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y">
                            <?php if (empty($tickets)): ?>
                                <tr><td colspan="8" class="px-4 py-8 text-center text-gray-500">No hay tickets con los filtros actuales.</td></tr>
                            <?php else: foreach ($tickets as $t):
                                $prio_color = ['urgente'=>'red','alta'=>'orange','media'=>'amber','baja'=>'slate'][$t->priority] ?? 'slate';
                                $st_color   = ['abierto'=>'amber','en_revision'=>'blue','resuelto'=>'emerald','cerrado'=>'slate','cancelado'=>'red'][$t->status] ?? 'gray';
                                $opened = $t->opened_at ? (new DateTime($t->opened_at, new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('America/Bogota'))->format('d M H:i') : '-';
                            ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-3 py-2 font-semibold text-gray-700"><?= htmlspecialchars($t->ticket_number) ?></td>
                                <td class="px-3 py-2">
                                    <div class="font-medium text-gray-700"><?= htmlspecialchars($t->client_name ?: ($t->client_name_full ?? '?')) ?></div>
                                    <div class="text-xs text-gray-400"><?= htmlspecialchars($t->client_phone) ?></div>
                                </td>
                                <td class="px-3 py-2 text-xs"><?= htmlspecialchars(ucfirst($t->case_type)) ?></td>
                                <td class="px-3 py-2 text-center">
                                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-<?= $prio_color ?>-100 text-<?= $prio_color ?>-800 uppercase">
                                        <?= htmlspecialchars($t->priority) ?>
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-center">
                                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-<?= $st_color ?>-100 text-<?= $st_color ?>-800">
                                        <?= htmlspecialchars(str_replace('_', ' ', $t->status)) ?>
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-xs text-gray-600"><?= htmlspecialchars($t->assigned_to ?: '-') ?></td>
                                <td class="px-3 py-2 text-xs text-gray-500 whitespace-nowrap"><?= $opened ?></td>
                                <td class="px-3 py-2 text-right">
                                    <a href="<?= base_url() ?>sisvent/admin/garantias/view/<?= (int)$t->id ?>"
                                       class="px-3 py-1 text-[11px] font-semibold text-white bg-mam-blue-petroleo rounded hover:opacity-90">
                                        Ver
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </main>
    </div>
</div>

<?php $this->load->view('sisvent/layouts/footer'); ?>
</body>
</html>
