<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#2E7D91">
    <title>Ventas con error - Ledxury</title>
    <link rel="icon" type="image/jpeg" href="<?= base_url() ?>public/images/logoLedxury.jpg?v=20260420"/>
    <style>
        *, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
        :root {
            --petrol:#2E7D91; --petrol-dark:#236470;
            --bg:#f4f6f8; --card:#fff;
            --text:#1a1a2e; --text-secondary:#64748b; --border:#e2e8f0;
            --danger:#ef4444; --warning:#f59e0b; --success:#10b981;
            --radius:12px; --radius-sm:8px;
            --shadow:0 1px 3px rgba(0,0,0,0.08), 0 1px 2px rgba(0,0,0,0.06);
            --tab-height:64px; --header-height:56px;
            --safe-bottom:env(safe-area-inset-bottom, 0px);
        }
        html, body { height:100%; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif; background:var(--bg); color:var(--text); -webkit-tap-highlight-color:transparent; }
        #app { display:flex; flex-direction:column; min-height:100%; max-width:480px; margin:0 auto; background:var(--bg); position:relative; }

        .header { height:var(--header-height); background:var(--petrol); color:#fff; display:flex; align-items:center; padding:0 16px; flex-shrink:0; z-index:10; box-shadow:0 2px 8px rgba(0,0,0,.12); }
        .header a { color:#fff; text-decoration:none; font-size:20px; margin-right:12px; }
        .header h1 { font-size:16px; font-weight:700; }

        .screen-container { flex:1; padding:16px; padding-bottom:calc(var(--tab-height) + var(--safe-bottom) + 16px); }

        .empty-state { text-align:center; padding:48px 20px; color:var(--text-secondary); }
        .empty-state svg { width:80px; height:80px; margin-bottom:12px; opacity:.4; }
        .empty-state p { font-size:14px; }

        .fail-card { background:var(--card); border-radius:var(--radius); box-shadow:var(--shadow); margin-bottom:12px; overflow:hidden; border-left:4px solid var(--danger); }
        .fail-summary { padding:14px 16px; cursor:pointer; display:flex; justify-content:space-between; align-items:flex-start; gap:10px; }
        .fail-summary-left { flex:1; min-width:0; }
        .fail-name { font-size:14px; font-weight:700; color:var(--text); }
        .fail-meta { font-size:11px; color:var(--text-secondary); margin-top:2px; }
        .fail-error { font-size:12px; color:var(--danger); margin-top:6px; word-break:break-word; line-height:1.4; }
        .fail-toggle { color:var(--petrol); font-size:13px; font-weight:700; flex-shrink:0; padding-top:2px; }

        .fail-detail { display:none; padding:14px 16px 18px; border-top:1px solid var(--border); background:#fafbfc; }
        .fail-detail.active { display:block; }

        .form-group { margin-bottom:12px; }
        .form-group label { display:block; font-size:11px; color:var(--text-secondary); font-weight:700; margin-bottom:4px; text-transform:uppercase; letter-spacing:.3px; }
        .form-group input, .form-group textarea { width:100%; padding:10px 12px; border:1px solid var(--border); border-radius:var(--radius-sm); font-size:14px; background:#fff; font-family:inherit; }
        .form-group input:focus, .form-group textarea:focus { border-color:var(--petrol); outline:none; box-shadow:0 0 0 3px rgba(46,125,145,.1); }

        .prod-row { display:grid; grid-template-columns:1fr 70px 80px 30px; gap:6px; align-items:center; margin-bottom:6px; }
        .prod-row input { padding:8px 10px; font-size:13px; border:1px solid var(--border); border-radius:6px; }
        .prod-row button { background:#fee2e2; color:#991b1b; border:none; border-radius:6px; font-size:18px; cursor:pointer; height:34px; }
        .prod-add { background:#e0f2fe; color:#075985; border:none; padding:8px 14px; border-radius:6px; font-size:12px; font-weight:700; cursor:pointer; margin-top:4px; }

        .actions { display:flex; gap:8px; flex-wrap:wrap; margin-top:14px; }
        .btn { flex:1; padding:12px 16px; border:none; border-radius:var(--radius-sm); font-size:13px; font-weight:700; cursor:pointer; min-width:120px; text-align:center; text-decoration:none; display:inline-flex; align-items:center; justify-content:center; gap:6px; }
        .btn-primary { background:var(--petrol); color:#fff; }
        .btn-primary:disabled { background:#94a3b8; cursor:wait; }
        .btn-wa { background:#25D366; color:#fff; }
        .btn-approve { background:var(--success); color:#fff; }
        .btn-secondary { background:#f1f5f9; color:var(--text); border:1px solid var(--border); }
        .btn-delete { background:#fee2e2; color:#991b1b; border:1px solid #fca5a5; }

        .alert-inline { padding:10px 12px; border-radius:var(--radius-sm); font-size:13px; margin-bottom:10px; }
        .alert-inline.success { background:#d1fae5; color:#065f46; border:1px solid #10b981; }
        .alert-inline.error { background:#fee2e2; color:#991b1b; border:1px solid #ef4444; }

        .tab-bar { position:fixed; bottom:0; left:50%; transform:translateX(-50%); width:100%; max-width:480px; background:var(--card); border-top:1px solid var(--border); display:flex; z-index:10; padding-bottom:var(--safe-bottom); height:var(--tab-height); }
        .tab-bar a { flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; font-size:10px; color:var(--text-secondary); text-decoration:none; font-weight:600; position:relative; }
        .tab-bar a svg { width:24px; height:24px; margin-bottom:2px; }
        .tab-bar a.active { color:var(--petrol); }
        .tab-bar a.active::before { content:''; position:absolute; top:0; left:25%; right:25%; height:3px; background:var(--petrol); border-radius:0 0 3px 3px; }

        .badge-agotado { background:#fef3c7; color:#92400e; font-size:10px; font-weight:700; padding:3px 8px; border-radius:10px; margin-top:6px; display:inline-block; }
    </style>
</head>
<body>
<div id="app">
    <div class="header">
        <a href="<?= base_url() ?>ventas/dashboard">&larr;</a>
        <h1>Ventas con error del bot</h1>
    </div>

    <div class="screen-container">
        <?php if (empty($items)): ?>
            <div class="empty-state">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <p>No tienes ventas fallidas.<br>Todos los pedidos del bot se procesaron correctamente.</p>
            </div>
        <?php else: ?>
            <?php foreach ($items as $item):
                $p = json_decode($item->payload, true);
                $nombre = $p['nombre'] ?? '(sin nombre)';
                $doc = $p['documento'] ?? '';
                $cel = $p['celular'] ?? '';
                $prods = $p['productos'] ?? [];
                $is_agotado = (stripos((string)$item->error_message, 'agotado') !== false);
            ?>
            <div class="fail-card" data-id="<?= $item->id ?>">
                <div class="fail-summary" onclick="toggleCard(<?= $item->id ?>)">
                    <div class="fail-summary-left">
                        <div class="fail-name"><?= htmlspecialchars($nombre) ?></div>
                        <div class="fail-meta">
                            <?= htmlspecialchars($doc ?: '—') ?> &middot; <?= htmlspecialchars($cel ?: '—') ?> &middot;
                            <?= date('d M H:i', strtotime($item->created_at)) ?>
                        </div>
                        <div class="fail-error"><?= htmlspecialchars($item->error_message ?: 'Error desconocido') ?></div>
                        <?php if ($is_agotado): ?><span class="badge-agotado">Producto agotado</span><?php endif; ?>
                    </div>
                    <div class="fail-toggle" id="toggle-<?= $item->id ?>">&#x25BC;</div>
                </div>

                <div class="fail-detail" id="detail-<?= $item->id ?>">
                    <div id="alert-<?= $item->id ?>"></div>

                    <?php if ($is_agotado): ?>
                    <div style="background:#fef3c7; border:1px solid #f59e0b; border-radius:8px; padding:10px 12px; margin-bottom:12px; font-size:12px; color:#92400e;">
                        El producto esta agotado. Puedes enviarle al cliente un mensaje por WhatsApp para ofrecer alternativa,
                        o editar los productos y reintentar.
                    </div>
                    <div class="actions" style="margin-top:0; margin-bottom:14px;">
                        <button class="btn btn-wa" onclick="sendAgotado(<?= $item->id ?>)">
                            <svg fill="#fff" viewBox="0 0 24 24" style="width:16px; height:16px;"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/></svg>
                            Enviar WhatsApp agotado
                        </button>
                    </div>
                    <?php endif; ?>

                    <form onsubmit="return retrySale(event, <?= $item->id ?>)">
                        <div class="form-group">
                            <label>Nombre</label>
                            <input type="text" name="nombre" value="<?= htmlspecialchars($nombre) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Documento</label>
                            <input type="text" name="documento" value="<?= htmlspecialchars($doc) ?>">
                        </div>
                        <div class="form-group">
                            <label>Celular</label>
                            <input type="text" name="celular" value="<?= htmlspecialchars($cel) ?>">
                        </div>
                        <div class="form-group">
                            <label>Direccion</label>
                            <input type="text" name="direccion" value="<?= htmlspecialchars($p['direccion'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label>Productos</label>
                            <div class="prod-row" style="font-size:10px; color:var(--text-secondary); font-weight:700;">
                                <span>CODIGO</span><span>CANT</span><span>PRECIO</span><span></span>
                            </div>
                            <div id="prods-<?= $item->id ?>">
                                <?php foreach ($prods as $idx => $pr): ?>
                                <div class="prod-row">
                                    <input type="text" name="cod[]" value="<?= htmlspecialchars($pr['codigo'] ?? '') ?>" placeholder="COD" required>
                                    <input type="number" name="cant[]" value="<?= (int)($pr['cantidad'] ?? 1) ?>" min="1" required>
                                    <input type="number" name="prec[]" value="<?= (float)($pr['precio'] ?? 0) ?>" step="0.01" min="0">
                                    <button type="button" onclick="this.parentElement.remove()">&times;</button>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" class="prod-add" onclick="addProd(<?= $item->id ?>)">+ Agregar producto</button>
                        </div>

                        <div class="actions">
                            <button type="submit" class="btn btn-primary" id="submit-<?= $item->id ?>">
                                Enviar a presupuestos
                            </button>
                            <button type="button" class="btn btn-delete" onclick="deleteFallido(<?= $item->id ?>)">
                                &#x1F5D1; Eliminar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="tab-bar">
        <a href="<?= base_url() ?>ventas/dashboard">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
            Inicio
        </a>
        <a href="<?= base_url() ?>ventas/pendientes">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
            Pendientes
        </a>
        <a href="<?= base_url() ?>ventas/chat">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
            Chat
        </a>
    </div>
</div>

<script>
var BASE = '<?= base_url() ?>';
var CSRF = { '<?= $this->security->get_csrf_token_name() ?>': '<?= $this->security->get_csrf_hash() ?>' };

function toggleCard(id) {
    var d = document.getElementById('detail-' + id);
    var t = document.getElementById('toggle-' + id);
    var open = d.classList.toggle('active');
    t.innerHTML = open ? '&#x25B2;' : '&#x25BC;';
}

function addProd(id) {
    var cont = document.getElementById('prods-' + id);
    var row = document.createElement('div');
    row.className = 'prod-row';
    row.innerHTML = '<input type="text" name="cod[]" placeholder="COD" required>'
                  + '<input type="number" name="cant[]" value="1" min="1" required>'
                  + '<input type="number" name="prec[]" value="0" step="0.01" min="0">'
                  + '<button type="button" onclick="this.parentElement.remove()">&times;</button>';
    cont.appendChild(row);
}

function showAlert(id, type, msg, html) {
    var el = document.getElementById('alert-' + id);
    el.className = 'alert-inline ' + type;
    el.innerHTML = msg + (html || '');
}

function sendAgotado(id) {
    if (!confirm('¿Enviar mensaje de producto agotado al cliente por WhatsApp?')) return;

    var data = new FormData();
    data.append('id', id);
    for (var k in CSRF) data.append(k, CSRF[k]);

    fetch(BASE + 'ventas/fallido_send_agotado', { method:'POST', body:data })
      .then(function(r) { return r.json(); })
      .then(function(r) {
          if (r.ok) showAlert(id, 'success', '&#x2705; ' + r.message);
          else showAlert(id, 'error', 'Error: ' + r.error);
      })
      .catch(function() { showAlert(id, 'error', 'Error de conexion'); });
}

function retrySale(e, id) {
    e.preventDefault();
    var form = e.target;
    var btn = document.getElementById('submit-' + id);
    btn.disabled = true;
    btn.innerText = 'Enviando...';

    var fd = new FormData(form);
    var payload = {
        nombre: fd.get('nombre'),
        documento: fd.get('documento'),
        celular: fd.get('celular'),
        direccion: fd.get('direccion'),
        productos: []
    };
    var cods = fd.getAll('cod[]');
    var cants = fd.getAll('cant[]');
    var precs = fd.getAll('prec[]');
    for (var i=0; i<cods.length; i++) {
        if (!cods[i]) continue;
        payload.productos.push({
            codigo: cods[i],
            cantidad: parseInt(cants[i]) || 1,
            precio: parseFloat(precs[i]) || 0
        });
    }

    var data = new FormData();
    data.append('id', id);
    data.append('payload', JSON.stringify(payload));
    for (var k in CSRF) data.append(k, CSRF[k]);

    fetch(BASE + 'ventas/fallido_retry', { method:'POST', body:data })
      .then(function(r) { return r.json(); })
      .then(function(r) {
          btn.disabled = false;
          btn.innerText = 'Enviar a presupuestos';
          if (r.ok) {
              var approveBtn = '<div style="margin-top:10px;">'
                  + '<button class="btn btn-approve" onclick="approveBudget(' + id + ', ' + r.budget_id + ')">'
                  + '&#x2714; Aprobar ahora</button></div>';
              showAlert(id, 'success', '&#x2705; ' + r.message, approveBtn);
              // Marcar card como resuelta
              var card = document.querySelector('.fail-card[data-id="' + id + '"]');
              if (card) card.style.borderLeftColor = 'var(--success)';
          } else {
              showAlert(id, 'error', '&#x26A0; ' + r.error);
          }
      })
      .catch(function() {
          btn.disabled = false;
          btn.innerText = 'Enviar a presupuestos';
          showAlert(id, 'error', 'Error de conexion');
      });
    return false;
}

function deleteFallido(id) {
    if (!confirm('¿Eliminar esta venta fallida de la cola? Esta accion no se puede deshacer.')) return;

    var data = new FormData();
    data.append('id', id);
    for (var k in CSRF) data.append(k, CSRF[k]);

    fetch(BASE + 'ventas/fallido_delete', { method:'POST', body:data })
      .then(function(r) { return r.json(); })
      .then(function(r) {
          if (r.ok) {
              var card = document.querySelector('.fail-card[data-id="' + id + '"]');
              if (card) {
                  card.style.transition = 'opacity .3s, transform .3s';
                  card.style.opacity = '0';
                  card.style.transform = 'translateX(100%)';
                  setTimeout(function() { card.remove(); }, 300);
              }
          } else {
              showAlert(id, 'error', 'Error: ' + r.error);
          }
      })
      .catch(function() { showAlert(id, 'error', 'Error de conexion'); });
}

function approveBudget(failId, budgetId) {
    var data = new FormData();
    data.append('id', budgetId);
    for (var k in CSRF) data.append(k, CSRF[k]);

    fetch(BASE + 'ventas/aprobar', { method:'POST', body:data })
      .then(function(r) { return r.json(); })
      .then(function(r) {
          if (r.success) {
              showAlert(failId, 'success', '&#x2705; ' + r.message + ' — recargando...');
              setTimeout(function() { location.reload(); }, 1500);
          } else {
              showAlert(failId, 'error', 'No se pudo aprobar: ' + r.error);
          }
      })
      .catch(function() { showAlert(failId, 'error', 'Error de conexion'); });
}
</script>
</body>
</html>
