<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Ledxury v2 — Avatar
 *
 * Uso:
 *   $this->load->view('sisvent/v2/_components/avatar', [
 *       'name' => 'Carolina Restrepo',  // derivamos iniciales automáticamente
 *       'size' => 32,                    // px
 *       'tone' => 'brand',               // brand|navy|orange|soft
 *       'src'  => '/path/to/img.jpg',    // opcional, sobreescribe iniciales
 *   ]);
 */
$name = $name ?? '';
$size = (int) ($size ?? 32);
$tone = $tone ?? 'brand';
$src  = $src  ?? null;

// Derivar iniciales: primera letra + primera letra del segundo término
$parts = preg_split('/\s+/', trim($name));
$initials = '';
if (!empty($parts[0])) $initials .= mb_strtoupper(mb_substr($parts[0], 0, 1));
if (!empty($parts[1])) $initials .= mb_strtoupper(mb_substr($parts[1], 0, 1));
if ($initials === '') $initials = '·';

$gradients = [
    'brand'  => 'linear-gradient(135deg, var(--lx-accent), var(--lx-navy))',
    'navy'   => 'linear-gradient(135deg, var(--lx-navy), var(--lx-ink))',
    'orange' => 'linear-gradient(135deg, var(--lx-orange), #B85B05)',
    'soft'   => 'var(--lx-accent-soft)',
];
$bg = $gradients[$tone] ?? $gradients['brand'];
$color = $tone === 'soft' ? 'var(--lx-accent-ink)' : '#fff';
$fontSize = max(10, $size * 0.36);
?>
<div style="
  width: <?= $size ?>px; height: <?= $size ?>px; border-radius:99px;
  background: <?= $src ? '#eee' : $bg ?>;
  color: <?= $color ?>;
  display:inline-flex; align-items:center; justify-content:center;
  font-size: <?= $fontSize ?>px; font-weight:600; flex:0 0 auto;
  <?= $src ? "background-image:url('" . htmlspecialchars($src) . "'); background-size:cover; background-position:center;" : '' ?>
  letter-spacing:-0.02em;
">
  <?= !$src ? htmlspecialchars($initials) : '' ?>
</div>
