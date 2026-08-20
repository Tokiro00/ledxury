<?php
/**
 * Separa el canal MAM-Online de la persona que lo maneja.
 *
 *   php separar_vendedor_mam_online.php            (simulación)
 *   php separar_vendedor_mam_online.php --apply
 *
 * SITUACIÓN
 * El canal MAM-Online (builderbot_configs id 5) apuntaba al usuario de Christina
 * Morales, así que sus ventas y su 7% se mezclaban con la persona: no se podía
 * revisar ni pagar el canal por separado, y su histórico como vendedora (desde
 * 2024) quedaba en el mismo cubo.
 *
 * QUÉ HACE
 *  1. Crea el usuario vendedor "MAM-Online" con la misma forma que los otros
 *     vendedores de canal (rol 3, bodega 1, por comisión al 7%, acceso solo al
 *     bot 5). Contraseña aleatoria e inservible: es una cuenta de canal, no de
 *     una persona; nadie debe entrar con ella.
 *  2. Apunta el canal a ese vendedor. De aquí en adelante todo lo que entre por
 *     el bot nace con él.
 *  3. Mueve la comisión del 7% del canal: se desactiva la configuración de
 *     Christina y se crea la misma para el vendedor nuevo. Christina se queda
 *     solo con su 1% de todos los canales, que es lo que se pidió.
 *  4. Reasigna al vendedor nuevo las facturas del canal QUE AÚN NO SE HAN
 *     COBRADO (estado 0). Si se quedaran con Christina con su 7% apagado, al
 *     cobrarlas no generarían comisión para nadie y se perderían ~$172.000.
 *
 * QUÉ NO TOCA, a propósito
 *  · Las 6 facturas de 2026 ya cobradas (estado 2, $1.940.000). Su comisión ya
 *    está causada en el auxiliar de Christina y en parte pagada; moverlas
 *    desfiguraría su histórico. Se quedan con ella, igual que su saldo
 *    pendiente de $280.537.
 *  · Sus facturas anteriores a 2026 ni los 941 presupuestos históricos del bot.
 *  · commission_perc = 7 en el usuario de Christina (es la comisión clásica de
 *    vendedor, otra cosa distinta de la comisión de bots).
 *
 * Idempotente: cada paso comprueba si ya está hecho.
 */
mysqli_report(MYSQLI_REPORT_OFF);
define('BASEPATH', 'x'); define('ENVIRONMENT', 'production');
$db = array(); include '/var/www/html/application/config/database.php';
$c = $db['default'];
$m = new mysqli($c['hostname'], $c['username'], $c['password'], $c['database']);
if ($m->connect_error) { fwrite(STDERR, "CONNECT ERROR\n"); exit(1); }
$m->set_charset('utf8mb4');
$APPLY = in_array('--apply', $argv, true);
echo $APPLY ? "=== MODO APLICAR ===\n\n" : "=== SIMULACION (sin --apply no escribe nada) ===\n\n";

$BOT_ID      = 5;            // builderbot_configs.id del canal MAM-Online
$NUEVO_ID    = '5000005';    // idUser del vendedor de canal
$NUEVO_NOMBRE = 'MAM-Online'; // igual que builderbot_configs.name
$CHRISTINA   = '5210750';
$STORE       = 1;

$errores = 0;
function rows($m, $sql) {
    $r = $m->query($sql);
    if ($r === false) { echo "  ERROR: {$m->error}\n"; return false; }
    $o = array(); while ($x = $r->fetch_assoc()) $o[] = $x; return $o;
}
function one($m, $sql) { $r = rows($m, $sql); return $r ? $r[0] : null; }
function money($v) { return '$' . number_format((float)$v, 2, ',', '.'); }
function exec_sql($m, $APPLY, $sql, &$errores) {
    if (!$APPLY) { echo "       [sim] " . preg_replace('/\s+/', ' ', substr($sql, 0, 105)) . "\n"; return true; }
    if ($m->query($sql) === false) { echo "       ERROR: {$m->error}\n"; $errores++; return false; }
    echo "       -> {$m->affected_rows} fila" . ($m->affected_rows == 1 ? '' : 's') . "\n";
    return true;
}

// Foto de partida
$bot = one($m, "SELECT id, name, company, default_vendor_id FROM builderbot_configs WHERE id = {$BOT_ID}");
if (!$bot) { echo "No existe el canal {$BOT_ID}\n"; exit(1); }
echo "canal {$bot['id']} '{$bot['name']}' (compañía {$bot['company']}) — vendedor actual: {$bot['default_vendor_id']}\n\n";

if ($APPLY) $m->begin_transaction();

// ── 1. Usuario vendedor del canal ──────────────────────────────────────────
echo "── 1. Vendedor '{$NUEVO_NOMBRE}'\n";
$ya = one($m, "SELECT idUser, name FROM users WHERE idUser = '{$NUEVO_ID}'");
if ($ya) {
    echo "     ya existe ({$ya['name']}), no se repite\n";
} else {
    // Contraseña aleatoria: cuenta de canal, no de persona.
    $pwd = password_hash(bin2hex(random_bytes(24)), PASSWORD_BCRYPT);
    echo "     creando: rol 3, bodega {$STORE}, por comisión 7%, solo bot {$BOT_ID}\n";
    // El servidor está en STRICT_TRANS_TABLES y en `users` hay tres columnas
    // NOT NULL sin default: idUser, f_id y last_logout. Se les da el mismo
    // valor que trae el vendedor de canal más reciente (Sebastian): f_id = 0 y
    // last_logout = ''.
    exec_sql($m, $APPLY, "INSERT INTO users
        (idUser, f_id, last_logout, name, password, role, store, by_commission, commission_perc,
         picture_url, bots_access, allowed_bot_ids, user_status, is_vendor, company,
         archived, deleted, created_at, updated_at)
        VALUES ('{$NUEVO_ID}', 0, '', '" . $m->real_escape_string($NUEVO_NOMBRE) . "',
         '" . $m->real_escape_string($pwd) . "', 3, {$STORE}, 1, 7,
         'users/general_1.png', 1, '{$BOT_ID}', 'active', 1, 'ledxury',
         0, 0, NOW(), NOW())", $errores);
}

// ── 2. El canal apunta al vendedor nuevo ───────────────────────────────────
echo "\n── 2. Canal MAM-Online -> vendedor {$NUEVO_ID}\n";
if ($bot['default_vendor_id'] === $NUEVO_ID) {
    echo "     ya apunta ahí, no se repite\n";
} else {
    echo "     {$bot['default_vendor_id']} -> {$NUEVO_ID}\n";
    exec_sql($m, $APPLY, "UPDATE builderbot_configs
        SET default_vendor_id = '{$NUEVO_ID}', updated_at = NOW()
        WHERE id = {$BOT_ID} AND default_vendor_id = '" . $m->real_escape_string($bot['default_vendor_id']) . "'", $errores);
}

// ── 3. La comisión del 7% del canal pasa al vendedor ───────────────────────
echo "\n── 3. Comisión del canal (7% operator sobre recaudo)\n";
$cfgChris = one($m, "SELECT id, percentage, basis, is_active FROM bot_commission_config
                     WHERE user_id = '{$CHRISTINA}' AND commission_type = 'operator'
                       AND applies_to = '{$BOT_ID}'");
if ($cfgChris && (int)$cfgChris['is_active'] === 1) {
    echo "     desactivando la de Christina (config #{$cfgChris['id']}, {$cfgChris['percentage']}%)\n";
    exec_sql($m, $APPLY, "UPDATE bot_commission_config
        SET is_active = 0, valid_to = CURDATE(),
            description = CONCAT(description, ' — cerrada 20/08/2026: el canal pasó a su propio vendedor MAM-Online'),
            updated_at = NOW()
        WHERE id = " . (int)$cfgChris['id'], $errores);
} else {
    echo "     la de Christina ya está desactivada o no existe\n";
}

$cfgNuevo = one($m, "SELECT id FROM bot_commission_config
                     WHERE user_id = '{$NUEVO_ID}' AND commission_type = 'operator'
                       AND applies_to = '{$BOT_ID}'");
if ($cfgNuevo) {
    echo "     el vendedor nuevo ya la tiene (config #{$cfgNuevo['id']})\n";
} else {
    $pct = $cfgChris ? $cfgChris['percentage'] : '7.00';
    $bas = $cfgChris ? $cfgChris['basis'] : 'recaudo';
    echo "     creando para {$NUEVO_NOMBRE}: {$pct}% sobre {$bas} del canal {$BOT_ID}\n";
    exec_sql($m, $APPLY, "INSERT INTO bot_commission_config
        (user_id, description, commission_type, percentage, basis, applies_to,
         is_active, valid_from, created_at, updated_at)
        VALUES ('{$NUEVO_ID}', 'Ventas del canal MAM-Online (igual que los operadores)',
         'operator', {$pct}, '{$bas}', '{$BOT_ID}', 1, CURDATE(), NOW(), NOW())", $errores);
}

echo "\n     Christina conserva su 1% de todos los canales:\n";
foreach (rows($m, "SELECT id, commission_type, percentage, applies_to, is_active
                   FROM bot_commission_config WHERE user_id = '{$CHRISTINA}' ORDER BY id") as $x)
    printf("       #%-3s %-12s %5s%% de [%s]  activo=%s\n", $x['id'], $x['commission_type'],
        $x['percentage'], $x['applies_to'], $x['is_active']);

// ── 4. Facturas del canal aún sin cobrar ───────────────────────────────────
echo "\n── 4. Facturas del canal sin cobrar (estado 0) -> vendedor nuevo\n";
echo "     (las 6 ya cobradas se quedan con Christina: su comisión ya está causada)\n";
$pend = rows($m, "SELECT idInvoice, total, DATE(created_at) creada
                  FROM invoices
                  WHERE vendorId = '{$CHRISTINA}' AND state = 0
                    AND (deleted IS NULL OR deleted = 0)
                    AND created_at >= '2026-01-01'
                  ORDER BY created_at");
if (!$pend) {
    echo "     no hay facturas pendientes por mover\n";
} else {
    $t = 0;
    foreach ($pend as $p) { $t += (float)$p['total'];
        printf("       #%-7s %14s  creada %s\n", $p['idInvoice'], money($p['total']), $p['creada']); }
    echo "     total " . count($pend) . " factura(s), " . money($t)
       . "  (7% al cobrarse = " . money($t * 0.07) . ")\n";
    exec_sql($m, $APPLY, "UPDATE invoices
        SET vendorId = '{$NUEVO_ID}', updated_at = updated_at
        WHERE vendorId = '{$CHRISTINA}' AND state = 0
          AND (deleted IS NULL OR deleted = 0) AND created_at >= '2026-01-01'", $errores);
}

if ($APPLY) {
    if ($errores) { echo "\n{$errores} error(es): ROLLBACK, no se cambió nada.\n"; $m->rollback(); exit(1); }
    $m->commit();
}

// ── Verificación ───────────────────────────────────────────────────────────
echo "\n=== " . ($APPLY ? "APLICADO — VERIFICACION" : "FIN SIMULACION — nada se escribió") . " ===\n";
if (!$APPLY) exit(0);

echo "\n  canales y su vendedor:\n";
foreach (rows($m, "SELECT b.id, b.name, b.default_vendor_id vid, u.name vendedor, u.role
                   FROM builderbot_configs b LEFT JOIN users u ON u.idUser = b.default_vendor_id
                   WHERE b.is_active = 1 ORDER BY b.id") as $x)
    printf("    bot %-3s %-24s -> %-12s %-34s rol %s\n", $x['id'], substr($x['name'], 0, 24),
        $x['vid'], substr((string)$x['vendedor'], 0, 34), $x['role']);

echo "\n  configuraciones activas de comisión de bot:\n";
foreach (rows($m, "SELECT c.user_id, u.name, c.commission_type, c.percentage, c.applies_to
                   FROM bot_commission_config c LEFT JOIN users u ON u.idUser = c.user_id
                   WHERE c.is_active = 1 ORDER BY u.name, c.id") as $x)
    printf("    %-12s %-34s %-12s %5s%% de [%s]\n", $x['user_id'], substr((string)$x['name'], 0, 34),
        $x['commission_type'], $x['percentage'], $x['applies_to']);

echo "\n  facturas por vendedor en 2026 (canal MAM-Online y Christina):\n";
foreach (rows($m, "SELECT i.vendorId, u.name, i.state, COUNT(*) n, COALESCE(SUM(i.total),0) t
                   FROM invoices i LEFT JOIN users u ON u.idUser = i.vendorId
                   WHERE i.vendorId IN ('{$CHRISTINA}', '{$NUEVO_ID}')
                     AND (i.deleted IS NULL OR i.deleted = 0) AND i.created_at >= '2026-01-01'
                   GROUP BY i.vendorId, i.state ORDER BY u.name, i.state") as $x)
    printf("    %-12s %-24s estado %-3s %4s factura(s) %16s\n", $x['vendorId'],
        substr((string)$x['name'], 0, 24), $x['state'], $x['n'], money($x['t']));

echo "\n  saldo de comisión de bot por persona (auxiliar 233525):\n";
foreach (rows($m, "SELECT a.accountAccount uid, u.name, a.accountCredit gen, a.accountDebit pag,
                          a.accountBalance saldo
                   FROM auxiliary_subaccounts a LEFT JOIN users u ON u.idUser = a.accountAccount
                   WHERE a.accountType = 'bot_commission' AND a.deleted = 0
                   ORDER BY a.accountBalance DESC") as $x)
    printf("    %-12s %-30s generada %14s pagada %14s pendiente %14s\n", $x['uid'],
        substr((string)$x['name'], 0, 30), money($x['gen']), money($x['pag']), money($x['saldo']));
