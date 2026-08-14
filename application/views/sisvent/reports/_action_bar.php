<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Action bar: botones de descarga (PDF/XLSX/CSV) + envío (email/whatsapp) +
 * audit + (futuro) schedule.
 *
 * @var ReportInterface $report
 * @var array $params
 * @var array $meta
 */

$baseUrl = base_url() . 'sisvent/admin/reports/v2/' . $report->id();
$qs = !empty($params) ? '?' . http_build_query(array_filter($params, fn($v) => $v !== null && $v !== '')) : '';
$canEmail = in_array('email', $report->availableChannels(), true);
$canWa = in_array('whatsapp', $report->availableChannels(), true);
?>
<div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:18px;">
    <!-- Formatos -->
    <div style="display:inline-flex;border:1px solid var(--border-default,#DDDFE8);border-radius:8px;overflow:hidden;background:var(--bg-surface,#fff);box-shadow:0 1px 2px rgba(27,54,93,.04);">
        <?php foreach ($report->availableFormats() as $f):
            $isCurrent = ($params['format'] ?? $report->defaultFormat()) === $f;
            $bg = $isCurrent ? 'var(--mam-blue-petroleo,#4487A0)' : 'transparent';
            $color = $isCurrent ? '#fff' : 'var(--mam-blue-dark,#2B3164)';
            $borderL = $f === $report->availableFormats()[0] ? '0' : '1px solid var(--border-default,#DDDFE8)';
            $href = $baseUrl . '?format=' . $f . (!empty($params) ? '&' . http_build_query(array_filter($params, fn($v) => $v !== null && $v !== '')) : '');
        ?>
            <a href="<?= $href ?>" style="display:inline-flex;align-items:center;gap:6px;padding:8px 14px;font-size:13px;font-weight:600;color:<?= $color ?>;background:<?= $bg ?>;border-left:<?= $borderL ?>;text-decoration:none;text-transform:uppercase;letter-spacing:0.5px;">
                <?= strtoupper($f) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <div style="flex:1;"></div>

    <?php if ($canEmail): ?>
    <button type="button" onclick="document.getElementById('rep-modal-email').style.display='flex'" style="display:inline-flex;align-items:center;gap:6px;padding:8px 14px;font-size:13px;font-weight:600;color:var(--mam-blue-dark,#2B3164);background:var(--bg-surface,#fff);border:1px solid var(--border-default,#DDDFE8);border-radius:6px;cursor:pointer;box-shadow:0 1px 2px rgba(27,54,93,.04);">
        <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
        <span>Enviar email</span>
    </button>
    <?php endif; ?>

    <?php if ($canWa): ?>
    <button type="button" onclick="document.getElementById('rep-modal-wa').style.display='flex'" style="display:inline-flex;align-items:center;gap:6px;padding:8px 14px;font-size:13px;font-weight:600;color:#fff;background:#25D366;border:0;border-radius:6px;cursor:pointer;box-shadow:0 1px 3px rgba(37,211,102,.25);">
        <svg style="width:14px;height:14px;" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413"/></svg>
        <span>Enviar WhatsApp</span>
    </button>
    <?php endif; ?>

    <a href="<?= $baseUrl ?>/audit" style="display:inline-flex;align-items:center;gap:6px;padding:8px 14px;font-size:13px;font-weight:500;color:var(--fg-2,#575964);background:transparent;border:1px solid transparent;border-radius:6px;text-decoration:none;" title="Historial de envíos">
        <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span>Historial</span>
    </a>
</div>

<?php if ($canEmail): ?>
<!-- Modal email (oculto por default) -->
<div id="rep-modal-email" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(43,49,100,0.5);z-index:50;align-items:center;justify-content:center;">
    <div style="background:#fff;padding:24px;border-radius:8px;width:420px;max-width:90%;box-shadow:0 12px 32px rgba(27,54,93,.12);">
        <h3 style="font-size:16px;font-weight:700;color:var(--mam-blue-dark,#2B3164);margin:0 0 12px;">Enviar reporte por email</h3>
        <form method="POST" action="<?= $baseUrl ?>/email">
            <?php foreach (array_filter($params, fn($v) => $v !== null && $v !== '') as $k => $v): ?>
                <input type="hidden" name="<?= htmlspecialchars($k) ?>" value="<?= htmlspecialchars((string)$v) ?>">
            <?php endforeach; ?>
            <label style="display:block;font-size:12px;font-weight:600;color:var(--fg-2,#575964);margin-bottom:4px;">Email del destinatario</label>
            <input type="email" name="recipient" required style="width:100%;padding:8px 12px;font-size:13px;border:1px solid var(--border-default,#DDDFE8);border-radius:6px;margin-bottom:12px;">
            <label style="display:block;font-size:12px;font-weight:600;color:var(--fg-2,#575964);margin-bottom:4px;">Formato</label>
            <select name="format" style="width:100%;padding:8px 12px;font-size:13px;border:1px solid var(--border-default,#DDDFE8);border-radius:6px;margin-bottom:18px;">
                <?php foreach (array_intersect($report->availableFormats(), ['pdf','xlsx','csv']) as $f): ?>
                    <option value="<?= $f ?>"><?= strtoupper($f) ?></option>
                <?php endforeach; ?>
            </select>
            <div style="display:flex;gap:8px;justify-content:flex-end;">
                <button type="button" onclick="document.getElementById('rep-modal-email').style.display='none'" style="padding:8px 14px;font-size:13px;font-weight:500;color:var(--fg-2,#575964);background:transparent;border:0;cursor:pointer;border-radius:6px;">Cancelar</button>
                <button type="submit" style="padding:8px 16px;font-size:13px;font-weight:600;color:#fff;background:var(--mam-blue-petroleo,#4487A0);border:0;border-radius:6px;cursor:pointer;">Enviar</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php if ($canWa): ?>
<div id="rep-modal-wa" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(43,49,100,0.5);z-index:50;align-items:center;justify-content:center;">
    <div style="background:#fff;padding:24px;border-radius:8px;width:420px;max-width:90%;box-shadow:0 12px 32px rgba(27,54,93,.12);">
        <h3 style="font-size:16px;font-weight:700;color:var(--mam-blue-dark,#2B3164);margin:0 0 12px;">Enviar por WhatsApp</h3>
        <p style="font-size:12px;color:var(--fg-3,#AEAAA6);margin:0 0 12px;">Phone con código país (ej. +573001234567).</p>
        <form method="POST" action="<?= $baseUrl ?>/whatsapp">
            <?php foreach (array_filter($params, fn($v) => $v !== null && $v !== '') as $k => $v): ?>
                <input type="hidden" name="<?= htmlspecialchars($k) ?>" value="<?= htmlspecialchars((string)$v) ?>">
            <?php endforeach; ?>
            <label style="display:block;font-size:12px;font-weight:600;color:var(--fg-2,#575964);margin-bottom:4px;">Phone</label>
            <input type="text" name="recipient" pattern="^\+?\d{10,15}$" required placeholder="+573001234567" style="width:100%;padding:8px 12px;font-size:13px;border:1px solid var(--border-default,#DDDFE8);border-radius:6px;margin-bottom:12px;">
            <label style="display:block;font-size:12px;font-weight:600;color:var(--fg-2,#575964);margin-bottom:4px;">Formato</label>
            <select name="format" style="width:100%;padding:8px 12px;font-size:13px;border:1px solid var(--border-default,#DDDFE8);border-radius:6px;margin-bottom:18px;">
                <?php foreach (array_intersect($report->availableFormats(), ['pdf','xlsx']) as $f): ?>
                    <option value="<?= $f ?>"><?= strtoupper($f) ?></option>
                <?php endforeach; ?>
            </select>
            <div style="display:flex;gap:8px;justify-content:flex-end;">
                <button type="button" onclick="document.getElementById('rep-modal-wa').style.display='none'" style="padding:8px 14px;font-size:13px;font-weight:500;color:var(--fg-2,#575964);background:transparent;border:0;cursor:pointer;border-radius:6px;">Cancelar</button>
                <button type="submit" style="padding:8px 16px;font-size:13px;font-weight:600;color:#fff;background:#25D366;border:0;border-radius:6px;cursor:pointer;">Enviar</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>
