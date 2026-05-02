<?php $this->load->view('tienda/_layout_head', array('pageTitle' => 'Pedido confirmado')); ?>

<main class="max-w-2xl mx-auto px-4 sm:px-6 py-12 text-center">
  <div class="bg-white rounded-2xl border border-slate-200 p-8 sm:p-10">
    <div class="w-20 h-20 mx-auto rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mb-5">
      <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
    </div>
    <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 mb-2">¡Pedido recibido!</h1>
    <p class="text-slate-600 mb-1">Pedido #<?= str_pad($budget->idBudget, 6, '0', STR_PAD_LEFT) ?></p>
    <?php if ($client): ?>
    <p class="text-sm text-slate-500 mb-6">Gracias <?= htmlspecialchars(explode(' ', $client->name)[0]) ?>. Te contactaremos por WhatsApp en breve.</p>
    <?php endif; ?>

    <div class="bg-slate-50 rounded-xl p-4 mb-4 text-left text-sm">
      <div class="flex justify-between mb-2"><span class="text-slate-600">Pedido:</span><span class="font-bold">#<?= $budget->idBudget ?></span></div>
      <div class="flex justify-between mb-2"><span class="text-slate-600">Fecha:</span><span><?= date('d/m/Y H:i', strtotime($budget->date)) ?></span></div>
      <div class="flex justify-between mb-2"><span class="text-slate-600">Estado:</span><span class="text-amber-700 font-semibold">Pendiente de confirmación</span></div>
      <div class="flex justify-between mb-2"><span class="text-slate-600">Pago:</span><span class="font-semibold">Contra entrega</span></div>
      <div class="flex justify-between mb-2"><span class="text-slate-600">Envío:</span><span class="font-semibold">Interrapidísimo</span></div>
      <div class="flex justify-between pt-2 border-t border-slate-200 mt-2"><span class="font-bold">Total:</span><span class="font-extrabold price">$<?= number_format($budget->total, 0, ',', '.') ?></span></div>
    </div>

    <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-3 mb-4 text-left text-xs text-emerald-800">
      <b>📱 Próximos pasos:</b> Acabamos de enviarte un mensaje de WhatsApp con el resumen de tu pedido. Te contactaremos en las próximas horas para confirmar el envío con Interrapidísimo. Pagas al recibir el paquete.
    </div>

    <div class="space-y-3">
      <a href="<?= base_url() ?>tienda/mis-pedidos<?= $client && !empty($client->cellphone) ? '?phone=' . urlencode($client->cellphone) : '' ?>" class="inline-flex items-center justify-center gap-2 w-full px-6 py-3 text-base font-bold text-white rounded-lg" style="background:#1B365D;">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
        Ver estado de mi pedido
      </a>
      <a href="<?= base_url() ?>tienda" class="block px-6 py-3 text-sm font-semibold text-slate-700 border border-slate-300 rounded-lg hover:bg-slate-50">Volver al catálogo</a>
    </div>
  </div>
</main>

<?php $this->load->view('tienda/_layout_foot'); ?>
