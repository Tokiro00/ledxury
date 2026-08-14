<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Ledxury v2 — sidebar
 *
 * Espera en $data['activeRoute'] el ID de la ruta activa (p.ej. 'presupuestos').
 * Items y submenús según el JSX de referencia (Pedidos.jsx → Shell). Children
 * expandibles via Alpine x-data.
 */
$activeRoute = $activeRoute ?? '';
$role        = (int) ($this->session->userdata('user_data')['role'] ?? 0);
$userName    = $this->session->userdata('user_data')['name'] ?? '';
$userInits   = strtoupper(substr($userName, 0, 1) . substr(strstr($userName, ' '), 1, 1));
$userPic     = $this->session->userdata('image');

// Grupos de navegación. Estructura: { title?, items: [{id, label, icon, count?, dot?, children?}] }
$groups = [
    [
        'items' => [
            ['id' => 'presupuestos', 'label' => 'Presupuestos', 'icon' => 'inbox', 'href' => base_url('sisvent/v2/presupuestos')],
            ['id' => 'facturas',     'label' => 'Facturas',     'icon' => 'cc',    'href' => base_url('sisvent/commercial/invoices'),       'disabled' => true, 'hint' => 'En v1'],
            ['id' => 'productos',    'label' => 'Productos',    'icon' => 'box',   'href' => base_url('sisvent/business/products'),         'disabled' => true, 'hint' => 'En v1'],
            ['id' => 'clientes',     'label' => 'Clientes',     'icon' => 'users', 'href' => base_url('sisvent/business/clients'),          'disabled' => true, 'hint' => 'En v1'],
            ['id' => 'envios',       'label' => 'Envíos',       'icon' => 'truck', 'href' => base_url('sisvent/admin/envios'),              'disabled' => true, 'hint' => 'En v1'],
        ],
    ],
    [
        'title' => 'Cartera',
        'items' => [
            ['id' => 'cartera', 'label' => 'Cuentas por cobrar', 'icon' => 'chart',    'href' => base_url('sisvent/admin/cartera'),       'disabled' => true],
            ['id' => 'recaudo', 'label' => 'Recaudo',            'icon' => 'download', 'href' => base_url('sisvent/admin/contrapagos'),   'disabled' => true],
        ],
    ],
    [
        'title' => 'Automatización',
        'items' => [
            ['id' => 'bot',      'label' => 'Bot WhatsApp', 'icon' => 'bot',   'href' => base_url('sisvent/admin/bots'),       'disabled' => true],
            ['id' => 'reportes', 'label' => 'Reportes',     'icon' => 'chart', 'href' => base_url('sisvent/admin/reports/v2'), 'disabled' => true],
        ],
    ],
    [
        'title' => 'Sistema',
        'items' => [
            ['id' => 'ajustes', 'label' => 'Ajustes', 'icon' => 'settings', 'href' => base_url('sisvent/dashboard'), 'disabled' => true],
        ],
    ],
];

// SVG icons inline (Lucide-style, stroke 1.6). Match con Icons del JSX.
$icons = [
    'inbox'    => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-6l-2 3h-4l-2-3H2"/><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11Z"/></svg>',
    'cc'       => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>',
    'box'      => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="M3.27 6.96 12 12.01l8.73-5.05"/><path d="M12 22.08V12"/></svg>',
    'users'    => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
    'truck'    => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/></svg>',
    'chart'    => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M7 16l4-4 4 4 6-6"/></svg>',
    'download' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M7 10l5 5 5-5"/><path d="M12 15V3"/></svg>',
    'bot'      => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="10" rx="2"/><circle cx="12" cy="5" r="2"/><path d="M12 7v4"/><path d="M8 16h.01"/><path d="M16 16h.01"/></svg>',
    'settings' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>',
    'chev'     => '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>',
];
?>
<aside style="
  width: var(--lx-sidebar-w);
  flex: 0 0 auto;
  background: var(--lx-surface);
  border-right: 1px solid var(--lx-line);
  display: flex;
  flex-direction: column;
  position: sticky; top: 0;
  height: 100vh;
">
  <!-- Brand -->
  <div style="padding: 22px 22px 18px; display:flex; align-items:center; gap:10px; border-bottom: 1px solid var(--lx-line);">
    <div style="width:30px;height:30px;border-radius:8px;background:var(--lx-ink);color:#fff;display:grid;place-items:center;font-weight:700;font-size:18px;letter-spacing:-0.03em;">L</div>
    <div>
      <div style="font-weight:600;font-size:14px;letter-spacing:-0.02em;color:var(--lx-ink);">Ledxury</div>
      <div style="font-size:11px;color:var(--lx-ink3);margin-top:1px;">MAM · v2</div>
    </div>
  </div>

  <!-- Nav -->
  <nav style="padding:14px 10px; flex:1; overflow-y:auto;">
    <?php foreach ($groups as $g): ?>
    <div style="margin-bottom:18px;">
      <?php if (!empty($g['title'])): ?>
        <div style="font-size:10.5px;font-weight:500;text-transform:uppercase;letter-spacing:0.08em;color:var(--lx-ink3);padding:10px 12px 6px;">
          <?= htmlspecialchars($g['title']) ?>
        </div>
      <?php endif; ?>
      <?php foreach ($g['items'] as $it):
        $active   = ($activeRoute === $it['id']);
        $disabled = !empty($it['disabled']);
        $href     = $it['href'] ?? '#';
      ?>
        <a href="<?= htmlspecialchars($href) ?>"
           <?= $disabled ? 'title="' . htmlspecialchars($it['hint'] ?? 'Disponible en v1') . '"' : '' ?>
           style="
             display:flex; align-items:center; gap:10px;
             font-size:13px; font-weight:<?= $active ? '500' : '400' ?>;
             padding:8px 12px; border-radius:7px;
             color: <?= $active ? 'var(--lx-ink)' : 'var(--lx-ink2)' ?>;
             background: <?= $active ? 'var(--lx-accent-soft)' : 'transparent' ?>;
             <?= $disabled ? 'opacity:0.6;' : '' ?>
             text-decoration:none; transition: background .12s;
             margin-bottom:1px;
           "
           onmouseover="if(!<?= $active ? 'true' : 'false' ?>) this.style.background='var(--lx-bg)'"
           onmouseout="if(!<?= $active ? 'true' : 'false' ?>) this.style.background='transparent'">
          <span style="color: <?= $active ? 'var(--lx-accent)' : 'var(--lx-ink3)' ?>; display:inline-flex;">
            <?= $icons[$it['icon']] ?? '' ?>
          </span>
          <span style="flex:1;"><?= htmlspecialchars($it['label']) ?></span>
          <?php if (!empty($it['count'])): ?>
            <span style="font-size:11px;font-weight:500;color: <?= $active ? 'var(--lx-accent)' : 'var(--lx-ink3)' ?>;">
              <?= (int) $it['count'] ?>
            </span>
          <?php endif; ?>
          <?php if (!empty($it['dot'])): ?>
            <span style="width:6px;height:6px;border-radius:99px;background:var(--lx-orange);"></span>
          <?php endif; ?>
          <?php if ($disabled): ?>
            <span style="font-size:9px;color:var(--lx-ink3);text-transform:uppercase;letter-spacing:0.05em;">v1</span>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>
    </div>
    <?php endforeach; ?>
  </nav>

  <!-- User menu (footer) -->
  <div style="padding:14px; border-top:1px solid var(--lx-line);"
       x-data="{ open: false }" @click.outside="open = false">
    <button @click="open = !open" style="
      width:100%; display:flex; align-items:center; gap:10px;
      padding:4px; border-radius:8px; border:none; background:transparent;
      cursor:pointer; font-family:inherit; color:inherit; text-align:left;
    ">
      <div style="
        width:32px;height:32px;border-radius:99px;flex:0 0 auto;
        background: linear-gradient(135deg, var(--lx-accent), var(--lx-navy));
        color:#fff; display:inline-flex; align-items:center; justify-content:center;
        font-size:11.5px;font-weight:600;letter-spacing:-0.02em;
        background-image: <?= $userPic ? "url('" . get_images_path($userPic) . "')" : 'none' ?>;
        background-size: cover; background-position: center;
      "><?= !$userPic ? htmlspecialchars($userInits) : '' ?></div>
      <div style="flex:1; min-width:0;">
        <div style="font-size:12.5px;font-weight:500;letter-spacing:-0.01em;color:var(--lx-ink); overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
          <?= htmlspecialchars($userName) ?>
        </div>
        <div style="font-size:11px;color:var(--lx-ink3);">
          <?php
            $roles = [1=>'Superadmin', 2=>'Gerente', 3=>'Vendedor', 4=>'Contador'];
            echo htmlspecialchars($roles[$role] ?? 'Usuario');
          ?>
        </div>
      </div>
      <span style="color:var(--lx-ink3);display:inline-flex;"><?= $icons['chev'] ?></span>
    </button>

    <div x-show="open" x-cloak x-transition.opacity.duration.150ms style="
      position:absolute; bottom: 60px; left: 14px;
      width: 212px;
      background: var(--lx-surface); border:1px solid var(--lx-line);
      border-radius: var(--lx-radius); box-shadow: var(--lx-shadow-lg);
      padding: 6px; z-index: 50;
    ">
      <a href="<?= base_url('sisvent/dashboard/profile') ?>" class="lx-menu-item">Mi perfil</a>
      <a href="<?= base_url('sisvent') ?>" class="lx-menu-item">↩ Volver a versión clásica</a>
      <div style="height:1px;background:var(--lx-line);margin:6px 4px;"></div>
      <a href="<?= base_url('sisvent/login/logout') ?>" class="lx-menu-item" style="color: var(--lx-danger);">Cerrar sesión</a>
    </div>
  </div>
</aside>

<style>
  [x-cloak] { display: none !important; }
  .lx-menu-item {
    display: block; padding: 7px 10px; font-size: 12.5px;
    color: var(--lx-ink2); border-radius: 5px; cursor: pointer;
    text-decoration: none; transition: background .12s;
  }
  .lx-menu-item:hover { background: var(--lx-bg); color: var(--lx-ink); }
</style>
