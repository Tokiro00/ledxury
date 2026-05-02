<?php $this->load->view('tienda/_layout_head', array('pageTitle' => 'Mis pedidos')); ?>
<?php
/**
 * Mapeo de estados Interrapidísimo (shipping_guides.estadoNombre) → etiqueta amigable.
 * Valores reales observados en producción 2026-04-28:
 *   Pre: Creado, Admitida, Digitalizada, Centro acopio, Transito nacional, Reenvio,
 *   Intento de entrega, Reclame en oficina, Conciliado, Archivada, Devuelto, Anulada.
 */
function tienda_estado_label($state, $invoice_id, $tracking_number, $tracking_status) {
    if (!empty($tracking_status)) {
        $ts = strtolower((string)$tracking_status);
        // ENTREGADO (estados finales positivos)
        if (strpos($ts, 'entregado') !== false || strpos($ts, 'conciliado') !== false || strpos($ts, 'archivada') !== false) {
            return array('Entregado ✓', 'emerald');
        }
        // DEVUELTO
        if (strpos($ts, 'devuelto') !== false) return array('Devuelto', 'rose');
        // ANULADO
        if (strpos($ts, 'anulada') !== false || strpos($ts, 'no encontrada') !== false) return array('Anulado', 'slate');
        // EN REPARTO (mensajero intentando entregar)
        if (strpos($ts, 'intento de entrega') !== false) return array('En reparto — Intento de entrega', 'orange');
        // LISTO PARA RECOGER en oficina
        if (strpos($ts, 'reclame en oficina') !== false) return array('Listo para recoger en oficina', 'orange');
        // EN CAMINO (estados intermedios de transporte)
        if (strpos($ts, 'transito') !== false || strpos($ts, 'tránsito') !== false) return array('En tránsito nacional', 'blue');
        if (strpos($ts, 'centro acopio') !== false) return array('En centro de acopio', 'blue');
        if (strpos($ts, 'digitalizada') !== false || strpos($ts, 'admitida') !== false) return array('Recibido por la transportadora', 'blue');
        if (strpos($ts, 'reenvio') !== false || strpos($ts, 'reenvío') !== false) return array('Reenviando', 'blue');
        // Pre-creado: guía generada, esperando recolección
        if (strpos($ts, 'pre:') !== false || strpos($ts, 'creado') !== false) return array('Despachado — esperando transportadora', 'blue');
        // Cualquier otro estado raro: mostrar tal cual
        return array($tracking_status, 'blue');
    }
    if (!empty($tracking_number)) return array('Despachado', 'blue');
    if (!empty($invoice_id))      return array('Confirmado y facturado', 'blue');
    if ((int)$state === 1)        return array('Confirmado',  'blue');
    if ((int)$state === 2)        return array('Pagado',      'emerald');
    if ((int)$state === 3)        return array('Anulado',     'slate');
    return array('Pendiente de confirmación', 'amber');
}
$phase = isset($phase) ? $phase : 'phone';
?>

<main class="max-w-3xl mx-auto px-4 sm:px-6 py-8">
  <h1 class="text-2xl font-extrabold text-slate-900 mb-2">Mis pedidos</h1>

  <?php if ($phase === 'phone'): ?>
    <p class="text-sm text-slate-500 mb-5">Ingresa tu número de WhatsApp para recibir un código y consultar tus pedidos.</p>

    <?php if (!empty($error)): ?>
      <div class="bg-red-50 border border-red-200 text-red-800 text-sm rounded-lg p-3 mb-3"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" class="bg-white rounded-2xl border border-slate-200 p-4 mb-5">
      <input type="hidden" name="action" value="send">
      <label class="block text-xs font-semibold text-slate-700 mb-1">Tu WhatsApp (Colombia)</label>
      <div class="flex gap-2">
        <span class="inline-flex items-center px-3 py-2.5 text-sm font-bold text-slate-700 bg-slate-100 border border-slate-300 rounded-lg">+57</span>
        <input type="tel" name="phone" required minlength="10" maxlength="10" inputmode="numeric" pattern="[0-9]{10}"
               value="<?= htmlspecialchars($phone ?? '') ?>"
               placeholder="3001234567"
               class="flex-1 px-3 py-2.5 text-sm border border-slate-300 rounded-lg focus:outline-none focus:border-red-500 focus:ring-2 focus:ring-red-100">
        <button class="px-5 py-2.5 text-sm font-bold text-white rounded-lg" style="background:#E63946;">Enviar código</button>
      </div>
      <p class="text-[11px] text-slate-500 mt-2">📱 Te enviaremos un código de 6 dígitos por WhatsApp. Por privacidad, solo el dueño del número puede ver sus pedidos.</p>
    </form>

  <?php elseif ($phase === 'verify'): ?>
    <p class="text-sm text-slate-500 mb-5">Revisa tu WhatsApp y escribe el código de 6 dígitos.</p>

    <?php if (!empty($info)): ?>
      <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm rounded-lg p-3 mb-3"><?= htmlspecialchars($info) ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
      <div class="bg-red-50 border border-red-200 text-red-800 text-sm rounded-lg p-3 mb-3"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" class="bg-white rounded-2xl border border-slate-200 p-4 mb-3">
      <input type="hidden" name="action" value="verify">
      <input type="hidden" name="phone" value="<?= htmlspecialchars($phone ?? '') ?>">
      <label class="block text-xs font-semibold text-slate-700 mb-1">Código recibido por WhatsApp</label>
      <div class="flex gap-2">
        <input type="text" name="code" required maxlength="6" minlength="6" inputmode="numeric" pattern="[0-9]{6}"
               autofocus autocomplete="one-time-code"
               placeholder="123456"
               class="flex-1 px-3 py-3 text-2xl font-bold tracking-[0.4em] text-center border border-slate-300 rounded-lg focus:outline-none focus:border-red-500 focus:ring-2 focus:ring-red-100">
        <button class="px-5 py-3 text-sm font-bold text-white rounded-lg" style="background:#E63946;">Verificar</button>
      </div>
    </form>

    <form method="post" class="text-center">
      <input type="hidden" name="action" value="send">
      <input type="hidden" name="phone" value="<?= htmlspecialchars($phone ?? '') ?>">
      <button type="submit" class="text-xs text-slate-500 hover:text-slate-700 underline">¿No recibiste el código? Reenviar</button>
    </form>
    <div class="text-center mt-2">
      <a href="<?= base_url() ?>tienda/mis-pedidos?logout=1" class="text-xs text-slate-400 hover:text-slate-700">Cambiar número</a>
    </div>

  <?php elseif ($phase === 'orders'): ?>

    <?php if (empty($results['client']) || empty($results['orders'])): ?>
      <div class="bg-amber-50 border border-amber-200 rounded-xl p-5 text-center text-sm text-amber-900">
        <b>No encontramos pedidos</b> asociados a este número.
        <div class="mt-2 text-xs">Si crees que es un error, escríbenos por WhatsApp.</div>
        <div class="mt-3"><a href="<?= base_url() ?>tienda/mis-pedidos?logout=1" class="text-xs underline">Probar con otro número</a></div>
      </div>
    <?php else: ?>
      <div class="flex items-center justify-between mb-4">
        <div class="text-sm text-slate-600">
          Hola <b><?= htmlspecialchars(explode(' ', $results['client']->name)[0] ?: 'cliente') ?></b>, encontramos <b><?= count($results['orders']) ?></b> pedido<?= count($results['orders']) === 1 ? '' : 's' ?>.
        </div>
        <a href="<?= base_url() ?>tienda/mis-pedidos?logout=1" class="text-xs text-slate-500 hover:text-slate-900 underline">Salir</a>
      </div>

      <div class="space-y-4">
        <?php foreach ($results['orders'] as $o): ?>
          <?php list($label, $color) = tienda_estado_label($o->state, $o->invoice_id, $o->tracking_number, $o->tracking_status); ?>
          <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-5">
            <div class="flex items-start justify-between gap-3 mb-3">
              <div>
                <div class="text-xs text-slate-500"><?= date('d/m/Y · H:i', strtotime($o->date)) ?></div>
                <div class="font-extrabold text-slate-900 text-base">Pedido #<?= str_pad($o->idBudget, 6, '0', STR_PAD_LEFT) ?></div>
              </div>
              <span class="text-[11px] font-semibold px-2.5 py-1 rounded-full whitespace-nowrap bg-<?= $color ?>-50 text-<?= $color ?>-700 border border-<?= $color ?>-200">
                <?= htmlspecialchars($label) ?>
              </span>
            </div>

            <?php if (!empty($o->lines)): ?>
              <div class="border-t border-slate-100 pt-3 mb-3">
                <div class="text-[11px] uppercase tracking-wider text-slate-400 font-semibold mb-2">Productos</div>
                <div class="space-y-2">
                  <?php foreach ($o->lines as $ln): ?>
                    <div class="flex items-start justify-between gap-3 text-sm">
                      <div class="flex-1 min-w-0">
                        <div class="font-semibold text-slate-900 truncate">
                          <?= (int) $ln->quantity ?>× <?= htmlspecialchars($ln->description ?: $ln->productId) ?>
                        </div>
                        <div class="text-[11px] text-slate-400">Código: <?= htmlspecialchars($ln->productId) ?> · $<?= number_format((int)$ln->unit, 0, ',', '.') ?> c/u</div>
                      </div>
                      <div class="font-bold price text-slate-900">$<?= number_format((int)$ln->line_total, 0, ',', '.') ?></div>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php endif; ?>

            <?php if (!empty($o->tracking_number)): ?>
              <div class="text-xs bg-blue-50 border border-blue-200 rounded-lg p-2 mb-3">
                <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                  <span>📦 Guía: <b><?= htmlspecialchars($o->tracking_number) ?></b></span>
                  <a href="https://interrapidisimo.com/sigue-tu-envio/?guia=<?= urlencode($o->tracking_number) ?>" target="_blank" rel="noopener" class="text-blue-700 underline">Rastrear en Interrapidísimo →</a>
                </div>
                <?php if (!empty($o->tracking_updated_at)): ?>
                  <div class="text-[10px] text-blue-700 mt-1">Última actualización: <?= date('d/m/Y H:i', strtotime($o->tracking_updated_at)) ?></div>
                <?php endif; ?>
              </div>
            <?php endif; ?>

            <div class="flex justify-between items-center pt-3 border-t border-slate-100">
              <span class="text-xs text-slate-500">Total · Pago contra entrega</span>
              <span class="font-extrabold price">$<?= number_format($o->total, 0, ',', '.') ?></span>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="mt-6 text-center">
        <a href="<?= base_url() ?>tienda" class="inline-block text-sm text-slate-600 hover:text-slate-900">← Volver al catálogo</a>
      </div>
    <?php endif; ?>

  <?php endif; ?>
</main>

<?php $this->load->view('tienda/_layout_foot'); ?>
