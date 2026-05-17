<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$estadoMap = [
    '0' => ['label' => 'borrador',    'tone' => 'neutral'],
    '1' => ['label' => 'aprobado',    'tone' => 'info'],
    '2' => ['label' => 'con guía',    'tone' => 'neutral'],
    '3' => ['label' => 'en tránsito', 'tone' => 'info'],
    '4' => ['label' => 'entregado',   'tone' => 'success'],
    '5' => ['label' => 'incidencia',  'tone' => 'danger'],
];
$stMeta = $estadoMap[(string) $budget->state] ?? ['label' => 'desconocido', 'tone' => 'neutral'];

$fmtMoney = fn($v) => '$' . number_format((float) $v, 0, ',', '.');

// Slot derecho del topbar — todos los CTAs son links a v1 (read-only)
$topbarRight = '';
ob_start();
$this->load->view('sisvent/v2/_components/button', [
    'variant' => 'ghost', 'size' => 'md',
    'label' => 'Imprimir',
    'href' => base_url('sisvent/commercial/budgets/print/' . $budget->idBudget),
    'title' => 'Imprimir desde v1',
]);
$this->load->view('sisvent/v2/_components/button', [
    'variant' => 'secondary', 'size' => 'md',
    'label' => 'Editar',
    'href' => base_url('sisvent/commercial/budgets/edit/' . $budget->idBudget),
    'title' => 'Editar en v1',
]);
$this->load->view('sisvent/v2/_components/button', [
    'variant' => 'primary', 'size' => 'md',
    'label' => 'Aprobar y facturar',
    'href' => base_url('sisvent/commercial/budgets/approve/' . $budget->idBudget),
    'title' => 'Aprobar en v1',
]);
$topbarRight = ob_get_clean();

// Título con número + pill de estado
ob_start();
?>
<span style="display:flex;align-items:center;gap:14px;">
  <a href="<?= base_url('sisvent/v2/presupuestos') ?>" style="
    font-family:inherit; font-size:13px; font-weight:500;
    color:var(--lx-ink3); text-decoration:none;
    display:inline-flex;align-items:center;gap:4px;
  ">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
    Volver
  </a>
  <span>Presupuesto <span class="mono" style="font-weight:500;font-size:22px;">#<?= (int) $budget->idBudget ?></span></span>
  <?php $this->load->view('sisvent/v2/_components/pill', ['tone' => $stMeta['tone'], 'dot' => true, 'label' => $stMeta['label']]); ?>
</span>
<?php
$titleHtml = ob_get_clean();
?>

<?php $this->load->view('sisvent/v2/layouts/meta_header'); ?>

<div style="display:flex;height:100vh;">
  <?php $this->load->view('sisvent/v2/layouts/sidebar', ['activeRoute' => $activeRoute]); ?>

  <div style="flex:1;display:flex;flex-direction:column;min-width:0;">
    <?php $this->load->view('sisvent/v2/layouts/topbar', [
        'pageTitle'   => $titleHtml,
        'breadcrumbs' => $breadcrumbs,
        'topbarRight' => $topbarRight,
        'v1Url'       => $v1Url,
    ]); ?>

    <div style="flex:1;overflow-y:auto;padding:24px 28px 40px;">
      <div style="display:grid;grid-template-columns: 1fr 380px;gap:20px;align-items:flex-start;">

        <!-- LEFT — items + totales -->
        <div style="display:flex;flex-direction:column;gap:16px;">

          <!-- Card: items -->
          <div style="background:var(--lx-surface);border:1px solid var(--lx-line);border-radius:12px;overflow:hidden;">
            <div style="padding:16px 18px;border-bottom:1px solid var(--lx-line);display:flex;justify-content:space-between;align-items:center;">
              <h3 style="font-size:14px;font-weight:600;">Items del presupuesto</h3>
              <span style="font-size:12px;color:var(--lx-ink3);"><?= count($details) ?> producto(s)</span>
            </div>
            <?php if (empty($details)): ?>
              <div style="padding:24px;text-align:center;font-size:13px;color:var(--lx-ink3);">
                Sin productos en este presupuesto.
              </div>
            <?php else: ?>
              <!-- Head -->
              <div style="
                display:grid; grid-template-columns: 120px 1fr 60px 110px 110px;
                padding:8px 18px; background:var(--lx-bg);
                font-size:10.5px;font-weight:500;text-transform:uppercase;letter-spacing:0.05em;color:var(--lx-ink3);
              ">
                <div>SKU</div><div>Producto</div><div style="text-align:right;">Cant.</div>
                <div style="text-align:right;">Unitario</div><div style="text-align:right;">Subtotal</div>
              </div>
              <?php foreach ($details as $d):
                $unit = (float) ($d->unit ?? 0);
                $qty  = (int) ($d->quantity ?? 0);
                $subtotal = isset($d->total) ? (float) $d->total : $qty * $unit;
              ?>
                <div style="
                  display:grid;grid-template-columns:120px 1fr 60px 110px 110px;
                  padding:12px 18px;border-top:1px solid var(--lx-line);
                  font-size:13px;align-items:center;
                ">
                  <div class="mono" style="font-size:12px;color:var(--lx-ink3);">
                    <?= htmlspecialchars($d->productId ?? '—') ?>
                  </div>
                  <div style="color:var(--lx-ink);">
                    <?= htmlspecialchars($d->description ?? '—') ?>
                  </div>
                  <div class="tabular" style="text-align:right;color:var(--lx-ink2);"><?= $qty ?></div>
                  <div class="tabular" style="text-align:right;color:var(--lx-ink2);"><?= $fmtMoney($unit) ?></div>
                  <div class="tabular" style="text-align:right;font-weight:600;color:var(--lx-ink);"><?= $fmtMoney($subtotal) ?></div>
                </div>
              <?php endforeach; ?>
              <!-- Total -->
              <div style="
                display:grid;grid-template-columns:1fr 110px;
                padding:14px 18px;border-top:2px solid var(--lx-line);background:var(--lx-bg);
              ">
                <div style="text-align:right;font-size:12px;color:var(--lx-ink3);text-transform:uppercase;letter-spacing:0.05em;font-weight:500;">Total</div>
                <div class="tabular" style="text-align:right;font-size:18px;font-weight:700;color:var(--lx-ink);"><?= $fmtMoney($budget->total) ?></div>
              </div>
            <?php endif; ?>
          </div>

          <!-- Card: observaciones -->
          <?php if (!empty($budget->comments)): ?>
          <div style="background:var(--lx-surface);border:1px solid var(--lx-line);border-radius:12px;padding:16px 18px;">
            <div class="eyebrow" style="margin-bottom:8px;">Observaciones</div>
            <p style="font-size:13px;color:var(--lx-ink);"><?= nl2br(htmlspecialchars($budget->comments)) ?></p>
          </div>
          <?php endif; ?>

        </div>

        <!-- RIGHT — cliente + meta -->
        <div style="display:flex;flex-direction:column;gap:16px;">

          <!-- Cliente -->
          <div style="background:var(--lx-surface);border:1px solid var(--lx-line);border-radius:12px;padding:18px;">
            <div class="eyebrow" style="margin-bottom:12px;">Cliente</div>
            <div style="display:flex;gap:12px;align-items:center;margin-bottom:14px;">
              <?php $this->load->view('sisvent/v2/_components/avatar', [
                  'name' => $budget->client_name ?? '?', 'size' => 40, 'tone' => 'brand',
              ]); ?>
              <div style="min-width:0;">
                <div style="font-weight:600;color:var(--lx-ink);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                  <?= htmlspecialchars($budget->client_name ?? '—') ?>
                </div>
                <div style="font-size:12px;color:var(--lx-ink3);">
                  <?= htmlspecialchars($budget->client_idNum ?? '—') ?>
                </div>
              </div>
            </div>
            <?php if (!empty($client)): ?>
              <div style="display:flex;flex-direction:column;gap:8px;font-size:12.5px;">
                <?php if (!empty($client->cellphone)): ?>
                  <div style="display:flex;justify-content:space-between;">
                    <span style="color:var(--lx-ink3);">Teléfono</span>
                    <span style="color:var(--lx-ink);"><?= htmlspecialchars($client->cellphone) ?></span>
                  </div>
                <?php endif; ?>
                <?php if (!empty($client->email)): ?>
                  <div style="display:flex;justify-content:space-between;">
                    <span style="color:var(--lx-ink3);">Email</span>
                    <span style="color:var(--lx-ink);overflow:hidden;text-overflow:ellipsis;max-width:200px;"><?= htmlspecialchars($client->email) ?></span>
                  </div>
                <?php endif; ?>
                <?php if (!empty($client->city)): ?>
                  <div style="display:flex;justify-content:space-between;">
                    <span style="color:var(--lx-ink3);">Ciudad</span>
                    <span style="color:var(--lx-ink);"><?= htmlspecialchars($client->city) ?></span>
                  </div>
                <?php endif; ?>
                <?php if (!empty($client->address)): ?>
                  <div style="display:flex;justify-content:space-between;align-items:flex-start;">
                    <span style="color:var(--lx-ink3);">Dirección</span>
                    <span style="color:var(--lx-ink);text-align:right;max-width:200px;"><?= htmlspecialchars($client->address) ?></span>
                  </div>
                <?php endif; ?>
              </div>
            <?php endif; ?>
          </div>

          <!-- Metadata -->
          <div style="background:var(--lx-surface);border:1px solid var(--lx-line);border-radius:12px;padding:18px;">
            <div class="eyebrow" style="margin-bottom:12px;">Detalle</div>
            <div style="display:flex;flex-direction:column;gap:8px;font-size:12.5px;">
              <div style="display:flex;justify-content:space-between;">
                <span style="color:var(--lx-ink3);">Fecha</span>
                <span style="color:var(--lx-ink);"><?= !empty($budget->date) ? date('d/m/Y H:i', strtotime($budget->date)) : '—' ?></span>
              </div>
              <div style="display:flex;justify-content:space-between;">
                <span style="color:var(--lx-ink3);">Vendedor</span>
                <span style="color:var(--lx-ink);"><?= htmlspecialchars($budget->vendor_name ?? '—') ?></span>
              </div>
              <div style="display:flex;justify-content:space-between;">
                <span style="color:var(--lx-ink3);">Bodega</span>
                <span style="color:var(--lx-ink);"><?= htmlspecialchars($budget->store_name ?? '—') ?></span>
              </div>
              <div style="display:flex;justify-content:space-between;">
                <span style="color:var(--lx-ink3);">IVA</span>
                <span style="color:var(--lx-ink);"><?= !empty($budget->hasIva) ? ((int) $budget->iva) . '%' : 'Sin IVA' ?></span>
              </div>
              <?php if (!empty($budget->e_commerce)): ?>
                <div style="display:flex;justify-content:space-between;">
                  <span style="color:var(--lx-ink3);">Canal</span>
                  <span style="color:var(--lx-ink);">E-commerce</span>
                </div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Aviso v2 -->
          <div style="background:var(--lx-accent-soft);border:1px solid var(--lx-line);border-radius:8px;padding:12px 14px;font-size:11.5px;color:var(--lx-accent-ink);line-height:1.5;">
            <strong>Vista v2 — solo lectura.</strong><br>
            Para editar este presupuesto o gestionar guías, usá los botones de arriba que te llevan a la versión clásica.
          </div>

        </div>

      </div>
    </div>
  </div>
</div>

<?php $this->load->view('sisvent/v2/layouts/footer'); ?>
