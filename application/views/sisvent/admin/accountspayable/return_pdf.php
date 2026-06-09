<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$totalUnits = 0;
foreach ($items as $it) $totalUnits += (int)$it->qty;
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Acta Devolución <?= htmlspecialchars($return->return_code) ?></title>
<style>
  body { font-family: -apple-system, 'Segoe UI', Helvetica, Arial, sans-serif; color:#222; background:#f5f5f5; padding:20px; margin:0; }
  .sheet { max-width: 800px; margin: 0 auto; background:#fff; padding: 36px 40px; box-shadow: 0 1px 4px rgba(0,0,0,.08); }
  .head { display:flex; justify-content:space-between; align-items:flex-end; border-bottom: 2px solid #222; padding-bottom: 14px; margin-bottom: 22px; }
  h1 { margin:0; font-size: 22px; letter-spacing: -0.01em; }
  .meta { text-align: right; font-size: 12px; color: #555; }
  .meta strong { color:#222; }
  .section { margin: 20px 0; }
  .label { font-size: 10px; text-transform: uppercase; letter-spacing: 0.08em; color: #888; font-weight: 700; }
  .value { font-size: 13px; color: #222; margin-top: 2px; }
  table { width:100%; border-collapse: collapse; font-size: 12px; margin-top: 10px; }
  th, td { padding: 8px 10px; text-align: left; }
  th { background: #f0f0f0; font-size: 10px; text-transform: uppercase; letter-spacing: 0.05em; color: #555; border-bottom: 2px solid #ddd; }
  tr { border-bottom: 1px solid #eee; }
  td.num { text-align: right; font-variant-numeric: tabular-nums; }
  td.mono { font-family: 'Menlo', monospace; font-weight: 600; }
  tfoot td { border-top: 2px solid #ddd; background:#fafafa; font-weight: 700; font-size: 13px; padding-top: 10px; }
  .firmas { display:flex; justify-content: space-between; gap: 40px; margin-top: 60px; }
  .firma { flex: 1; text-align: center; }
  .firma .linea { border-top: 1px solid #222; padding-top: 6px; font-size: 11px; color: #444; }
  .firma .nombre { font-weight: 700; color: #222; font-size: 12px; }
  .print-btn { position: fixed; top: 20px; right: 20px; padding: 10px 20px; background: #1B365D; color: #fff; border: 0; border-radius: 6px; font-size: 13px; font-weight: 700; cursor: pointer; }
  @media print {
    body { background: #fff; padding: 0; }
    .sheet { box-shadow: none; }
    .print-btn { display: none; }
  }
</style>
</head>
<body>
<button class="print-btn" onclick="window.print()">🖨 Imprimir</button>

<div class="sheet">

  <div class="head">
    <div>
      <div class="label">Acta de devolución a proveedor</div>
      <h1><?= htmlspecialchars($return->return_code) ?></h1>
    </div>
    <div class="meta">
      <div class="label">Fecha</div>
      <div><strong><?= date('d \de F \de Y', strtotime($return->return_date)) ?></strong></div>
      <div style="margin-top:6px;" class="label">Hora entrega</div>
      <div><?= !empty($return->delivered_at) ? date('H:i', strtotime($return->delivered_at)) : '—' ?></div>
    </div>
  </div>

  <div class="section" style="display:grid; grid-template-columns: 1fr 1fr; gap: 24px;">
    <div>
      <div class="label">Entrega</div>
      <div class="value"><strong>Ledxury</strong> · MULTI ACCESORIOS MEDELLIN S.A.S.</div>
      <div class="value" style="color:#666; font-size: 12px;">NIT 901427578</div>
    </div>
    <div>
      <div class="label">Recibe</div>
      <div class="value"><strong><?= htmlspecialchars($provider->name ?? 'MAM') ?></strong></div>
      <?php if (!empty($provider->idNum)): ?>
      <div class="value" style="color:#666; font-size: 12px;">NIT <?= htmlspecialchars($provider->idNum) ?></div>
      <?php endif; ?>
      <?php if (!empty($return->delivered_to)): ?>
      <div class="value" style="margin-top:6px; color:#444;">Recibido por: <strong><?= htmlspecialchars($return->delivered_to) ?></strong></div>
      <?php endif; ?>
    </div>
  </div>

  <div class="section">
    <div class="label">Productos devueltos</div>
    <table>
      <thead>
        <tr>
          <th style="width:40px;">#</th>
          <th>SKU</th>
          <th>Descripción</th>
          <th>Bodega</th>
          <th style="text-align:right;">Cantidad</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($items as $i => $it): ?>
        <tr>
          <td><?= $i + 1 ?></td>
          <td class="mono"><?= htmlspecialchars($it->product_id) ?></td>
          <td><?= htmlspecialchars($it->description ?: '(sin descripción)') ?></td>
          <td><?= htmlspecialchars($it->store_name ?: $it->store_id) ?></td>
          <td class="num"><?= number_format($it->qty, 0, ',', '.') ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr>
          <td colspan="4" style="text-align:right;">TOTAL UNIDADES</td>
          <td class="num"><?= number_format($totalUnits, 0, ',', '.') ?></td>
        </tr>
      </tfoot>
    </table>
  </div>

  <?php if (!empty($return->notes)): ?>
  <div class="section" style="background:#f9f9f9; padding: 12px 14px; border-radius: 6px;">
    <div class="label">Observaciones</div>
    <div class="value" style="white-space:pre-wrap; font-size:12px;"><?= htmlspecialchars($return->notes) ?></div>
  </div>
  <?php endif; ?>

  <div class="firmas">
    <div class="firma">
      <div style="height: 50px;"></div>
      <div class="linea">
        <div class="nombre">Entrega — Ledxury</div>
        <div style="color:#666;"><?= htmlspecialchars($return->created_by ?: '') ?></div>
        <div style="color:#999; margin-top: 4px;">C.C. _______________________</div>
      </div>
    </div>
    <div class="firma">
      <div style="height: 50px;"></div>
      <div class="linea">
        <div class="nombre">Recibe — <?= htmlspecialchars($provider->name ?? 'MAM') ?></div>
        <div style="color:#666;"><?= htmlspecialchars($return->delivered_to ?: '_______________________') ?></div>
        <div style="color:#999; margin-top: 4px;">C.C. _______________________</div>
      </div>
    </div>
  </div>

</div>
</body>
</html>
