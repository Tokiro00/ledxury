<?php defined('BASEPATH') OR exit('No direct script access allowed');
$fmt = function ($n) { return number_format((float)$n, 2, ',', '.'); };
// CNY se muestra como RMB (yuanes).
$curName = function ($cur) { $c = strtoupper((string) $cur); return $c === 'CNY' ? 'RMB' : $c; };
$fdate = function ($d) { return $d ? date('d/m/Y', strtotime($d)) : '—'; };
$statusLabel = ['en_transito'=>'En tránsito','open'=>'Abierta','paid_partial'=>'Parcial','paid'=>'Pagada','cancelled'=>'Anulada'];
?>
<!DOCTYPE html>
<html lang="es">
<title>Estado de cuenta <?= htmlspecialchars($provider->name ?? '') ?> · Ledxury</title>
<?php $this->load->view('sisvent/layouts/meta_header'); $this->load->view('sisvent/purchases/_vars'); ?>
<style>
.ps-page { max-width: 1100px; margin: 0 auto; padding: 22px; }
.ps-bc { font-family: var(--font-mono); font-size: 11px; color: var(--ink-500); text-transform: uppercase; letter-spacing:.06em; }
.ps-h1 { margin: 4px 0 0; font-size: 23px; font-weight: 800; color: var(--ink-900); }
.ps-sub { font-size: 13px; color: var(--ink-500); margin-top: 4px; }
.ps-strip { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:12px; margin:16px 0; }
.ps-tile { background:#fff; border:1px solid var(--ink-150); border-top:3px solid #1B365D; border-radius:8px; padding:14px 16px; }
.ps-tile .l { font-size:11px; text-transform:uppercase; letter-spacing:.5px; color:var(--ink-400); }
.ps-tile .v { font-size:20px; font-weight:800; color:var(--ink-900); font-variant-numeric:tabular-nums; }
.ps-card { background:#fff; border:1px solid var(--ink-150); border-radius:8px; overflow:hidden; margin-bottom:16px; }
.ps-card h3 { margin:0; padding:12px 16px; background:var(--mam-blue-dark,#0F0F14); color:#fff; font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.05em; }
table.ps { width:100%; border-collapse:collapse; font-size:12.5px; font-variant-numeric:tabular-nums; }
table.ps th { background:#F1F3F5; padding:8px 12px; text-align:left; font-size:10px; text-transform:uppercase; letter-spacing:.4px; color:#575964; font-weight:600; }
table.ps td { padding:7px 12px; border-top:1px solid #EEF0F3; }
.ps-r { text-align:right; font-family:var(--font-mono); }
.ps-badge { display:inline-block; padding:1px 7px; border-radius:99px; font-size:10px; font-weight:700; background:#EEF0F3; color:#575964; }
.ps-btn { height:34px; padding:0 14px; font-size:13px; font-weight:600; border-radius:6px; border:1px solid var(--ink-200); background:#fff; color:var(--ink-800); text-decoration:none; display:inline-flex; align-items:center; }
</style>
<body>
<div id="bars" class="flex h-screen bg-gray-50" v-bind:class="{ 'overflow-hidden': isSideMenuOpen }">
  <?php $this->load->view('sisvent/layouts/sidebar', ['thisFile' => $_ci_view, 'role' => $role]); ?>
  <div class="flex flex-col flex-1 w-full">
    <?php $this->load->view('sisvent/layouts/navbar'); ?>
    <main class="h-full overflow-y-auto">
      <div class="ps-page">
        <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px;">
          <div>
            <div class="ps-bc"><a href="<?= base_url() ?>sisvent/purchases/provider_invoices?provider_id=<?= (int)$provider->idProvider ?>" style="color:inherit;text-decoration:none;">Facturas de proveedor</a> · Estado de cuenta</div>
            <h1 class="ps-h1"><?= htmlspecialchars($provider->name) ?></h1>
            <div class="ps-sub">Cuenta en <strong><?= $curName($nativeCur) ?></strong> · concilia 1:1 con el estado que envía el proveedor</div>
          </div>
          <a class="ps-btn" href="<?= base_url() ?>sisvent/purchases/provider_invoices?provider_id=<?= (int)$provider->idProvider ?>">← Volver</a>
        </div>

        <div class="ps-strip">
          <div class="ps-tile"><div class="l">Total facturado (<?= $curName($nativeCur) ?>)</div><div class="v"><?= $fmt($totFact) ?></div></div>
          <div class="ps-tile"><div class="l">Total pagado (<?= $curName($nativeCur) ?>)</div><div class="v"><?= $fmt($totPag) ?></div></div>
          <div class="ps-tile" style="border-top-color:#C0392B;"><div class="l">Saldo (<?= $curName($nativeCur) ?>)</div><div class="v" style="color:<?= $balNativo>0?'#C0392B':'#1E874B' ?>;"><?= $fmt($balNativo) ?></div></div>
          <div class="ps-tile"><div class="l">Saldo equiv. COP</div><div class="v">$<?= $fmt($balBase) ?></div></div>
        </div>

        <?php
          $limit   = (float) ($provider->credit_limit ?? 0);
          $cupoCur = ($provider->credit_currency ?? null) ?: "COP";
          $used    = ($cupoCur === $nativeCur) ? $balNativo : $balBase; // saldo en la moneda del cupo
          $avail   = $limit - $used;
          $pct     = $limit > 0 ? ($used / $limit * 100) : 0;
          $over    = $limit > 0 && $used > $limit;
          $near    = $limit > 0 && !$over && $pct >= 80;
          $finDays = (int) ($provider->financing_days ?? 0);
          $finInt  = (float) ($provider->financing_interest_pct ?? 0);
        ?>
        <?php if ($limit > 0): ?>
        <div style="background:<?= $over?'#FDECEC':($near?'#FFF8E6':'#F2F7F4') ?>;border:1px solid <?= $over?'#F5B5B5':($near?'#E8C96A':'#BFE3CD') ?>;border-radius:8px;padding:14px 16px;margin-bottom:16px;">
          <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:10px;align-items:center;">
            <div>
              <strong style="color:<?= $over?'#9C151A':($near?'#7A5E12':'#1E874B') ?>;">
                <?= $over ? '⚠ Cupo EXCEDIDO' : ($near ? '⚠ Cupo casi lleno' : '✓ Cupo disponible') ?>
              </strong>
              <span style="color:var(--ink-600,#4A4D55);font-size:13px;margin-left:8px;">
                Cupo <?= htmlspecialchars($cupoCur) ?> <?= $fmt($limit) ?> · usado <?= $fmt($used) ?> (<?= number_format($pct,0) ?>%) · disponible <?= $fmt($avail) ?>
              </span>
            </div>
            <?php if ($finDays > 0 || $finInt > 0): ?>
            <div style="font-size:12px;color:var(--ink-500);">Financiación: <?= $finDays>0 ? $finDays.' días' : '' ?><?= ($finDays>0 && $finInt>0)?' · ':'' ?><?= $finInt>0 ? number_format($finInt,2).'%/mes' : '' ?></div>
            <?php endif; ?>
          </div>
          <div style="height:8px;border-radius:4px;background:#fff;overflow:hidden;margin-top:8px;border:1px solid rgba(0,0,0,.05);">
            <i style="display:block;height:100%;width:<?= min(100,max(0,$pct)) ?>%;background:<?= $over?'#C0392B':($near?'#E0A800':'#5EBA47') ?>;"></i>
          </div>
        </div>
        <?php endif; ?>

        <div class="ps-card">
          <h3>Facturas (cargos)</h3>
          <table class="ps">
            <thead><tr>
              <th>Fecha</th><th>Nº factura</th><th>Estado</th><th>Mon.</th>
              <th class="ps-r">Total</th><th class="ps-r">Pagado</th><th class="ps-r">Saldo</th><th class="ps-r">TRM</th>
            </tr></thead>
            <tbody>
              <?php foreach ($invoices as $i): ?>
              <tr>
                <td><?= $fdate($i->issue_date) ?></td>
                <td style="font-family:var(--font-mono);"><a href="<?= base_url() ?>sisvent/purchases/provider_invoices/view/<?= (int)$i->id ?>" style="color:var(--stock-red,#ED3237);text-decoration:none;"><?= htmlspecialchars($i->inv_code) ?></a></td>
                <td><span class="ps-badge"><?= $statusLabel[$i->status] ?? $i->status ?></span></td>
                <td><?= $curName($i->currency) ?></td>
                <td class="ps-r"><?= $fmt($i->total) ?></td>
                <td class="ps-r" style="color:#1E874B;"><?= $fmt($i->paid) ?></td>
                <td class="ps-r" style="font-weight:700;color:<?= $i->balance>0?'#C0392B':'#575964' ?>;"><?= $fmt($i->balance) ?></td>
                <td class="ps-r" style="color:#AEAAA6;"><?= $i->currency==="COP" ? "—" : $fmt((float)$i->exchange_rate) ?></td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($invoices)): ?><tr><td colspan="8" style="text-align:center;color:#AEAAA6;padding:20px;">Sin facturas.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>

        <div class="ps-card">
          <h3>Pagos (abonos)</h3>
          <table class="ps">
            <thead><tr>
              <th>Fecha</th><th>Comprobante</th><th>Factura</th><th>Mon. pago</th>
              <th class="ps-r">Monto pago</th><th class="ps-r">TRM</th><th class="ps-r">Aplicado (<?= $curName($nativeCur) ?>)</th><th class="ps-r">Dif. cambio</th>
            </tr></thead>
            <tbody>
              <?php foreach ($payments as $p): ?>
              <tr>
                <td><?= $fdate($p->pay_date) ?></td>
                <td style="font-family:var(--font-mono);"><?= htmlspecialchars($p->pay_code) ?></td>
                <td style="font-family:var(--font-mono);color:#575964;"><?= htmlspecialchars($p->inv_code) ?></td>
                <td><?= $curName($p->currency) ?></td>
                <td class="ps-r"><?= $fmt($p->amount) ?></td>
                <td class="ps-r" style="color:#AEAAA6;"><?= $p->currency==="COP" ? "—" : $fmt((float)$p->exchange_rate) ?></td>
                <td class="ps-r" style="color:#1E874B;font-weight:600;"><?= $fmt($p->amount_invoice_currency) ?></td>
                <td class="ps-r" style="color:<?= (float)$p->fx_diff<0?'#C0392B':'#575964' ?>;"><?= $fmt($p->fx_diff) ?></td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($payments)): ?><tr><td colspan="8" style="text-align:center;color:#AEAAA6;padding:20px;">Sin pagos.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>

        <div class="ps-sub">La <strong>diferencia en cambio</strong> se genera cuando pagas a una TRM distinta a la de la factura; va al P&amp;L. El <strong>saldo</strong> se muestra en la moneda del proveedor para conciliar con su estado, y su equivalente en pesos es el que aparece en el Balance.</div>
      </div>
    </main>
  </div>
</div>
<?php $this->load->view('sisvent/layouts/footer'); ?>
</body>
</html>
