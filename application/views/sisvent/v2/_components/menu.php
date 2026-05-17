<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Ledxury v2 — Menu / popover (Alpine-based)
 *
 * Uso:
 *   $this->load->view('sisvent/v2/_components/menu', [
 *       'trigger' => '<button>...</button>',     // HTML del trigger
 *       'items'   => [                            // array de items
 *           ['label' => 'Editar',  'href' => '...'],
 *           ['label' => 'Eliminar','href' => '...', 'danger' => true],
 *           ['divider' => true],
 *           ['header' => 'Sesión'],
 *       ],
 *       'align'   => 'right',                     // right|left
 *       'width'   => 220,
 *   ]);
 *
 * Importante: el panel se posiciona con position:absolute respecto al
 * wrapper. El wrapper tiene position:relative.
 */
$trigger = $trigger ?? '';
$items   = $items   ?? [];
$align   = $align   ?? 'right';
$width   = (int) ($width ?? 220);
?>
<div x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false"
     style="position:relative; display:inline-block;">
  <div @click="open = !open" style="cursor:pointer;">
    <?= $trigger ?>
  </div>
  <div x-show="open" x-cloak x-transition.opacity.duration.150ms
       style="
         position:absolute; top: calc(100% + 6px);
         <?= $align === 'right' ? 'right:0;' : 'left:0;' ?>
         width: <?= $width ?>px;
         background: var(--lx-surface); border:1px solid var(--lx-line);
         border-radius: var(--lx-radius); box-shadow: var(--lx-shadow-lg);
         padding: 6px; z-index: 100;
       ">
    <?php foreach ($items as $it): ?>
      <?php if (!empty($it['divider'])): ?>
        <div style="height:1px;background:var(--lx-line);margin:6px 4px;"></div>
      <?php elseif (!empty($it['header'])): ?>
        <div style="padding:6px 10px 4px;font-size:10.5px;font-weight:500;text-transform:uppercase;letter-spacing:0.06em;color:var(--lx-ink3);">
          <?= htmlspecialchars($it['header']) ?>
        </div>
      <?php else:
        $color = !empty($it['danger']) ? 'var(--lx-danger)' : 'var(--lx-ink2)';
        $tag   = !empty($it['href']) ? 'a' : 'button';
        $hrefAttr = !empty($it['href']) ? 'href="' . htmlspecialchars($it['href']) . '"' : '';
        $onclickAttr = !empty($it['onclick']) ? 'onclick="' . htmlspecialchars($it['onclick']) . '"' : '';
      ?>
        <<?= $tag ?> <?= $hrefAttr ?> <?= $onclickAttr ?> style="
          display:flex; align-items:center; gap:8px;
          padding:7px 10px; font-size:12.5px;
          color: <?= $color ?>; border-radius:5px;
          text-decoration:none; cursor:pointer;
          background:transparent; border:none; width:100%;
          font-family:inherit; text-align:left;
          transition: background .12s;
        " onmouseover="this.style.background='var(--lx-bg)'"
           onmouseout="this.style.background='transparent'">
          <?php if (!empty($it['icon'])): ?>
            <span style="display:inline-flex;color:var(--lx-ink3);"><?= $it['icon'] ?></span>
          <?php endif; ?>
          <span style="flex:1;"><?= htmlspecialchars($it['label']) ?></span>
          <?php if (!empty($it['hint'])): ?>
            <span style="font-size:10.5px;color:var(--lx-ink3);"><?= htmlspecialchars($it['hint']) ?></span>
          <?php endif; ?>
        </<?= $tag ?>>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>
</div>
