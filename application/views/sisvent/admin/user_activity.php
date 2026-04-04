<?php $role = $this->session->userdata('user_data')['role']; ?>
<!DOCTYPE html>
<html>
<head>
<title>Actividad de Usuarios - Ledxury</title>
<?php $this->load->view('sisvent/layouts/meta_header'); ?>
</head>
<body>
<div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
  <?php $this->load->view('sisvent/layouts/sidebar', array('thisFile' => $_ci_view, 'role' => $role)); ?>

  <div class="flex flex-col flex-1 w-full">
    <?php $this->load->view('sisvent/layouts/navbar'); ?>

    <main class="h-full overflow-y-auto">
      <div class="container px-6 mx-auto py-6">

        <div class="flex items-center justify-between mb-6">
          <h2 class="text-xl font-semibold text-gray-700">Actividad de Usuarios</h2>
          <form method="GET" class="flex items-center space-x-2">
            <input type="date" name="date" value="<?= $date ?>" class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
            <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">Filtrar</button>
          </form>
        </div>

        <!-- Usuarios conectados -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
          <div class="px-5 py-3 border-b bg-gray-50">
            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider">Estado de Usuarios</h3>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="text-xs font-medium text-gray-400 uppercase tracking-wider border-b">
                  <th class="px-5 py-3 text-left">Usuario</th>
                  <th class="px-3 py-3 text-left">Rol</th>
                  <th class="px-3 py-3 text-center">Estado</th>
                  <th class="px-3 py-3 text-center">Entrada</th>
                  <th class="px-3 py-3 text-center">Salida</th>
                  <th class="px-3 py-3 text-center">Tiempo conectado</th>
                  <th class="px-3 py-3 text-left">Ultima actividad</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <?php foreach ($users as $u):
                  $isOnline = !empty($u->last_activity) && strtotime($u->last_activity) > strtotime('-5 minutes');
                  $lastSeen = !empty($u->last_activity) ? date('d/m/Y H:i', strtotime($u->last_activity)) : 'Nunca';
                  $relativo = '';
                  if (!empty($u->last_activity)) {
                    $diff = time() - strtotime($u->last_activity);
                    if ($diff < 60) $relativo = 'Hace un momento';
                    elseif ($diff < 3600) $relativo = 'Hace ' . floor($diff/60) . ' min';
                    elseif ($diff < 86400) $relativo = 'Hace ' . floor($diff/3600) . 'h';
                    else $relativo = 'Hace ' . floor($diff/86400) . 'd';
                  }

                  // Datos del día
                  $ds = isset($day_summary[$u->idUser]) ? $day_summary[$u->idUser] : null;
                  $entrada = $ds && $ds->first_login ? date('H:i', strtotime($ds->first_login)) : '-';
                  $salida = $ds && $ds->last_logout ? date('H:i', strtotime($ds->last_logout)) : ($isOnline ? 'Activo' : '-');

                  // Calcular tiempo conectado
                  $tiempoConectado = '-';
                  if ($ds && $ds->first_login) {
                    $fin = $ds->last_logout ? strtotime($ds->last_logout) : ($isOnline ? time() : strtotime($u->last_activity));
                    $inicio = strtotime($ds->first_login);
                    if ($fin > $inicio) {
                      $mins = round(($fin - $inicio) / 60);
                      $h = floor($mins / 60);
                      $m = $mins % 60;
                      $tiempoConectado = ($h > 0 ? $h . 'h ' : '') . $m . 'min';
                    }
                  }

                  // Colores de entrada (tarde = después de 8:00 AM)
                  $entradaClass = 'text-gray-600';
                  if ($ds && $ds->first_login) {
                    $horaEntrada = (int)date('H', strtotime($ds->first_login));
                    $minEntrada = (int)date('i', strtotime($ds->first_login));
                    if ($horaEntrada > 8 || ($horaEntrada == 8 && $minEntrada > 15)) $entradaClass = 'text-red-600 font-semibold';
                    elseif ($horaEntrada == 8) $entradaClass = 'text-yellow-600 font-semibold';
                    else $entradaClass = 'text-green-600 font-semibold';
                  }

                  // Color salida (temprano = antes de 5:00 PM)
                  $salidaClass = 'text-gray-600';
                  if ($ds && $ds->last_logout) {
                    $horaSalida = (int)date('H', strtotime($ds->last_logout));
                    if ($horaSalida < 17) $salidaClass = 'text-yellow-600 font-semibold';
                    else $salidaClass = 'text-green-600 font-semibold';
                  }
                ?>
                <tr class="hover:bg-gray-50">
                  <td class="px-5 py-3 font-medium text-gray-700"><?= htmlspecialchars($u->name) ?></td>
                  <td class="px-3 py-3 text-gray-500 text-xs"><?= $u->role_name ?: '-' ?></td>
                  <td class="px-3 py-3 text-center">
                    <?php if ($isOnline): ?>
                    <span class="inline-flex items-center px-2 py-0.5 text-xs font-semibold rounded-full bg-green-100 text-green-700">
                      <span class="w-2 h-2 rounded-full bg-green-500 mr-1"></span> En linea
                    </span>
                    <?php else: ?>
                    <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full bg-gray-100 text-gray-500">Desconectado</span>
                    <?php endif; ?>
                  </td>
                  <td class="px-3 py-3 text-center text-sm <?= $entradaClass ?>"><?= $entrada ?></td>
                  <td class="px-3 py-3 text-center text-sm <?= $salidaClass ?>"><?= $salida ?></td>
                  <td class="px-3 py-3 text-center text-xs text-gray-500"><?= $tiempoConectado ?></td>
                  <td class="px-3 py-3 text-gray-400 text-xs">
                    <?= $lastSeen ?>
                    <?php if ($relativo): ?><span class="ml-1">(<?= $relativo ?>)</span><?php endif; ?>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Log de actividad del día -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
          <div class="px-5 py-3 border-b bg-gray-50">
            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider">Registro del <?= date('d/m/Y', strtotime($date)) ?></h3>
          </div>
          <?php if (empty($logs)): ?>
          <div class="px-5 py-8 text-center text-sm text-gray-400">Sin actividad registrada para esta fecha</div>
          <?php else: ?>
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="text-xs font-medium text-gray-400 uppercase tracking-wider border-b">
                  <th class="px-5 py-3 text-left">Hora</th>
                  <th class="px-3 py-3 text-left">Usuario</th>
                  <th class="px-3 py-3 text-center">Accion</th>
                  <th class="px-3 py-3 text-left">IP</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <?php foreach ($logs as $log): ?>
                <tr class="hover:bg-gray-50">
                  <td class="px-5 py-3 text-gray-600 font-mono text-xs"><?= date('H:i:s', strtotime($log->created_at)) ?></td>
                  <td class="px-3 py-3 font-medium text-gray-700"><?= htmlspecialchars($log->user_name ?: $log->user_id) ?></td>
                  <td class="px-3 py-3 text-center">
                    <?php if ($log->action === 'login'): ?>
                    <span class="inline-flex items-center px-2 py-0.5 text-xs font-semibold rounded-full bg-green-100 text-green-700">Ingreso</span>
                    <?php elseif ($log->action === 'logout'): ?>
                    <span class="inline-flex items-center px-2 py-0.5 text-xs font-semibold rounded-full bg-red-100 text-red-700">Salio</span>
                    <?php else: ?>
                    <span class="inline-flex items-center px-2 py-0.5 text-xs font-semibold rounded-full bg-blue-100 text-blue-700">Activo</span>
                    <?php endif; ?>
                  </td>
                  <td class="px-3 py-3 text-gray-400 text-xs font-mono"><?= $log->ip_address ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
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
