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
      <b>📦 Próximos pasos:</b> Te contactaremos por WhatsApp para confirmar la dirección y coordinar el envío con Interrapidísimo. Pagas al recibir el paquete.
    </div>

    <div class="space-y-3">
      <a href="https://wa.me/573226951481?text=<?= urlencode('Hola! Quiero confirmar mi pedido #' . $budget->idBudget . ' por $' . number_format($budget->total, 0, ',', '.')) ?>" target="_blank" rel="noopener" class="inline-flex items-center justify-center gap-2 w-full px-6 py-3 text-base font-bold text-white rounded-lg" style="background:#25D366;">
        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/></svg>
        Confirmar por WhatsApp
      </a>
      <a href="<?= base_url() ?>tienda" class="block px-6 py-3 text-sm font-semibold text-slate-700 border border-slate-300 rounded-lg hover:bg-slate-50">Volver al catálogo</a>
    </div>
  </div>
</main>

<?php $this->load->view('tienda/_layout_foot'); ?>
