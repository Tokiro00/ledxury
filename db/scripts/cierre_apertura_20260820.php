<?php
/**
 * CIERRE Y APERTURA AL 20/08/2026 — aquí arranca la contabilidad de Ledxury.
 *
 *   php cierre_apertura_20260820.php            (simulación)
 *   php cierre_apertura_20260820.php --apply
 *
 * POR QUÉ
 * Los libros venían de abril de 2024 y estaban materialmente equivocados: la
 * cartera contable decía $246.512.826 contra $16.565.810 reales, porque las
 * facturas debitaban cartera (3.652 asientos, $355.292.388) pero los cobros casi
 * nunca se asentaron (18 asientos de pago, 8 de contrapago). El asiento de
 * apertura del 01/07/2026 había conectado caja y banco, pero no cartera. Además
 * el "resultado" acumulaba dos años y medio y hay 2.725 asientos sin fecha.
 *
 * QUÉ HACE, todo con fecha 20/08/2026 y contra 370501 utilidades acumuladas:
 *
 *  1. Cierra el estado de resultados: lleva a cero TODAS las cuentas de
 *     ingresos, costos y gastos (clases 4, 5 y 6). El P&L arranca de cero.
 *  2. Ajusta las cuentas de balance a los saldos reales confirmados por Alex:
 *       · Caja 110505      -> $115.000
 *       · Banco 111005     -> $8.108.664,94
 *       · Cartera 130505   -> el saldo operativo real (facturas abiertas menos
 *                             sus pagos), calculado en el momento de correr
 *       · CxC vinculados 132505 -> 0  (se llevan a cero MAM y MAM-Online)
 *       · Inventario 143501 -> el valor del stock del sistema (inventory.stock
 *                              por cost_cop), calculado al correr
 *     Se dejan como están, porque ya se revisaron y cuadran:
 *       · 136525 Anticipos a vendedores
 *       · 220505 Proveedores (deuda con MAM, confirmada como real)
 *       · 223005 CxP a compañías vinculadas
 *       · 233525 Comisiones de bot por pagar
 *  3. Deja tesorería igual a contabilidad: movimientos de tipo 'ajuste' en el
 *     banco y en la caja por la diferencia, con la convención de siempre (el
 *     delta va firmado en amount y no tiene contrapartida contable propia,
 *     porque el lado contable es el asiento de cierre).
 *
 * QUÉ NO HACE
 *  · No borra ni anula ningún asiento histórico: quedan en el libro con su
 *    fecha. Los reportes por rango de fechas del pasado siguen mostrando lo de
 *    entonces; lo que queda en cero es el acumulado a hoy.
 *  · No anula facturas de clientes. Las 2 que pasan de 90 días (#3549 y #3694,
 *    $235.845 juntas) quedan abiertas para que Alex las cobre o las anule por
 *    la interfaz: son datos de clientes reales y esa decisión no es de un
 *    script.
 *
 * Idempotente: si ya existen asientos de tipo 'cierre_20260820', aborta.
 */
mysqli_report(MYSQLI_REPORT_OFF);
define('BASEPATH', 'x'); define('ENVIRONMENT', 'production');
$db = array(); include '/var/www/html/application/config/database.php';
$c = $db['default'];
$m = new mysqli($c['hostname'], $c['username'], $c['password'], $c['database']);
if ($m->connect_error) { fwrite(STDERR, "CONNECT ERROR\n"); exit(1); }
$m->set_charset('utf8mb4');
$APPLY = in_array('--apply', $argv, true);

$FECHA     = '2026-08-20';
$TIPO      = 'cierre_20260820';
$USER      = '71339095';
$STORE     = 1;
$ACC_PATRI = 45;             // 370501 Utilidades acumuladas
$ACC_COMIS = 65;             // 233525 Comisiones bots por pagar
$ACC_ANTIC = 63;             // 136525 Anticipos a vendedores
$OBJ_CAJA  = 115000.00;
$OBJ_BANCO = 8108664.94;
$ID_BANCO  = 1;
$ID_CAJA   = 1;

echo $APPLY ? "=== MODO APLICAR ===\n" : "=== SIMULACION (sin --apply no escribe nada) ===\n";
echo "Fecha de cierre y apertura: {$FECHA}\n\n";

$errores = 0;
function rows($m, $sql) {
    $r = $m->query($sql);
    if ($r === false) { echo "  ERROR: {$m->error}\n"; return false; }
    $o = array(); while ($x = $r->fetch_assoc()) $o[] = $x; return $o;
}
function one($m, $sql) { $r = rows($m, $sql); return $r ? $r[0] : null; }
function mo($v) { return number_format((float)$v, 2, ',', '.'); }
function exec_sql($m, $APPLY, $sql, &$errores) {
    if (!$APPLY) return true;
    if ($m->query($sql) === false) { echo "     ERROR: {$m->error}\n"; $errores++; return false; }
    return true;
}
/** Asiento de una línea. $val siempre positivo. */
function asiento($m, $APPLY, &$errores, $desc, $dr, $cr, $val, $tipo, $fecha, $user, $store) {
    global $nAsientos;
    $val = round((float)$val, 2);
    if ($val <= 0) return;
    $nAsientos++;
    $d = $m->real_escape_string($desc);
    $v = number_format($val, 2, '.', '');
    exec_sql($m, $APPLY, "INSERT INTO entries (userID, entryDescription, entryType,
            entryDebitAccount, entryDebitBalance, entryCreditAccount, entryCreditBalance,
            entryStatus, created_by, entryCreateDate, deleted, entryStoreId,
            entryTransactionType, entryTransactionId, entryDate)
        VALUES ('{$user}', '{$d}', 1, {$dr}, '{$v}', {$cr}, '{$v}',
            1, '{$user}', NOW(), 0, {$store}, '{$tipo}', 0, '{$fecha}')", $errores);
}
$nAsientos = 0;

// Saldo neto (débito − crédito) de una subcuenta, desde los asientos.
$NETO = "(SELECT COALESCE(SUM(CAST(e.entryDebitBalance AS DECIMAL(18,2))),0)
          FROM entries e WHERE e.deleted=0 AND e.entryDebitAccount = s.id)
       - (SELECT COALESCE(SUM(CAST(e.entryCreditBalance AS DECIMAL(18,2))),0)
          FROM entries e WHERE e.deleted=0 AND e.entryCreditAccount = s.id)";

// ── Idempotencia ───────────────────────────────────────────────────────────
$ya = one($m, "SELECT COUNT(*) n FROM entries WHERE deleted = 0 AND entryTransactionType = '{$TIPO}'");
if ((int)$ya['n'] > 0) {
    echo "Ya existen {$ya['n']} asientos de tipo '{$TIPO}'. El cierre ya se hizo; no se repite.\n";
    exit(0);
}

// ── Saldos objetivo que se calculan del sistema ────────────────────────────
$cartRow = one($m, "
    SELECT COALESCE(SUM(i.total - COALESCE(p.pagado,0)),0) saldo, COUNT(*) n
    FROM invoices i
    LEFT JOIN (SELECT invoiceId, SUM(payment) pagado FROM payments WHERE deleted=0 GROUP BY invoiceId) p
           ON p.invoiceId = i.idInvoice
    WHERE i.state = 0 AND i.total > 0 AND (i.deleted IS NULL OR i.deleted = 0)");
$OBJ_CARTERA = round((float)$cartRow['saldo'], 2);

$invRow = one($m, "
    SELECT COALESCE(SUM(iv.stock * COALESCE(NULLIF(p.cost_cop,0), NULLIF(p.cost,0), 0)),0) valor,
           COUNT(*) refs, COALESCE(SUM(iv.stock),0) unidades
    FROM inventory iv JOIN products p ON p.idProduct = iv.idProduct
    WHERE iv.stock > 0 AND (p.deleted IS NULL OR p.deleted = 0)");
$OBJ_INVENT = round((float)$invRow['valor'], 2);

echo "Saldos objetivo:\n";
printf("  110505 Caja                     %18s\n", mo($OBJ_CAJA));
printf("  111005 Banco                    %18s\n", mo($OBJ_BANCO));
printf("  130505 Cartera  (%3s facturas)  %18s\n", $cartRow['n'], mo($OBJ_CARTERA));
printf("  132505 CxC vinculados            %18s\n", mo(0));
printf("  143501 Inventario (%s refs, %s und) %s\n", $invRow['refs'],
    number_format((float)$invRow['unidades'], 0, ',', '.'), mo($OBJ_INVENT));

// Objetivos por código PUC, en saldo PRESENTADO (positivo = naturaleza de la
// cuenta). Las que no aparecen aquí no se tocan: 220505 (deuda con MAM,
// confirmada real), 233525 (comisiones, que se ajusta solo con el cruce de
// anticipos) y 136525 (que queda en cero por ese mismo cruce).
$OBJETIVOS = array(
    '110505' => $OBJ_CAJA,
    '111005' => $OBJ_BANCO,
    '130505' => $OBJ_CARTERA,
    '132505' => 0.00,   // se llevan a cero las CxC a MAM y MAM-Online
    '143501' => $OBJ_INVENT,
    '223005' => 0.00,   // se llevan a cero las CxP a compañías vinculadas
);

if ($APPLY) $m->begin_transaction();

// ── 1. Cerrar el estado de resultados ──────────────────────────────────────
echo "\n" . str_repeat('─', 96) . "\n1. CIERRE DEL ESTADO DE RESULTADOS (clases 4, 5 y 6) contra 370501\n" . str_repeat('─', 96) . "\n";
$resultado = 0;
foreach (rows($m, "SELECT s.id, s.pucCode, s.accountName, ({$NETO}) neto
                   FROM subaccounts s
                   WHERE s.deleted = 0 AND (s.pucCode LIKE '4%' OR s.pucCode LIKE '5%' OR s.pucCode LIKE '6%')
                   ORDER BY s.pucCode") as $x) {
    $neto = round((float)$x['neto'], 2);
    if (abs($neto) < 0.005) continue;
    // El resultado en presentación: ingresos suman, costos y gastos restan.
    $resultado += -$neto;
    if ($neto > 0) {
        // saldo débito (gasto, costo, devoluciones): se acredita para cerrarlo
        printf("  %-9s %-46s CR %-4s %16s\n", $x['pucCode'], substr($x['accountName'], 0, 46), $x['id'], mo($neto));
        asiento($m, $APPLY, $errores,
            "Cierre al {$FECHA}: se lleva a cero {$x['pucCode']} {$x['accountName']} contra utilidades acumuladas. Aquí arranca la contabilidad de Ledxury.",
            $ACC_PATRI, (int)$x['id'], $neto, $TIPO, $FECHA, $USER, $STORE);
    } else {
        // saldo crédito (ingresos): se debita para cerrarlo
        printf("  %-9s %-46s DR %-4s %16s\n", $x['pucCode'], substr($x['accountName'], 0, 46), $x['id'], mo(-$neto));
        asiento($m, $APPLY, $errores,
            "Cierre al {$FECHA}: se lleva a cero {$x['pucCode']} {$x['accountName']} contra utilidades acumuladas. Aquí arranca la contabilidad de Ledxury.",
            (int)$x['id'], $ACC_PATRI, -$neto, $TIPO, $FECHA, $USER, $STORE);
    }
}
printf("  %-56s RESULTADO CERRADO %16s\n", '', mo($resultado));

// ── 2. Ajustar las cuentas de balance a los saldos reales ──────────────────
echo "\n" . str_repeat('─', 96) . "\n2. AJUSTE DE LAS CUENTAS DE BALANCE A LOS SALDOS REALES\n" . str_repeat('─', 96) . "\n";
$ajusteTotal = 0;
foreach ($OBJETIVOS as $puc => $objetivo) {
    $s = one($m, "SELECT s.id, s.pucCode, s.accountName, s.accountSide, ({$NETO}) neto
                  FROM subaccounts s WHERE s.pucCode = '{$puc}' AND s.deleted = 0 LIMIT 1");
    if (!$s) { echo "  {$puc}: no existe la subcuenta, se salta\n"; continue; }

    // Saldo PRESENTADO según la naturaleza: las de débito (activo) van con el
    // neto tal cual; las de crédito (pasivo, patrimonio) con el neto invertido.
    $esDebito = ($s['accountSide'] === '1');
    $actual   = round($esDebito ? (float)$s['neto'] : -(float)$s['neto'], 2);
    $delta    = round((float)$objetivo - $actual, 2);
    printf("  %-9s %-38s %s actual %16s -> %14s  ajuste %16s\n",
        $s['pucCode'], substr($s['accountName'], 0, 38), $esDebito ? 'DB' : 'CR',
        mo($actual), mo($objetivo), mo($delta));
    if (abs($delta) < 0.005) continue;

    // Efecto en el patrimonio: subir un activo o bajar un pasivo lo suben.
    $ajusteTotal += $esDebito ? $delta : -$delta;

    $motivo = array(
        '110505' => 'saldo real de la caja al ' . $FECHA,
        '111005' => 'saldo real del banco al ' . $FECHA . ' según extracto',
        '130505' => 'cartera real al ' . $FECHA . ': los cobros de 2024 y 2025 nunca se asentaron y la cuenta quedó inflada',
        '132505' => 'se llevan a cero las cuentas por cobrar a MAM y MAM-Online',
        '143501' => 'inventario del sistema al ' . $FECHA . ', que queda como inventario inicial',
        '223005' => 'se llevan a cero las cuentas por pagar a compañías vinculadas',
    );
    $desc = "Apertura al {$FECHA}: ajuste de {$s['pucCode']} {$s['accountName']} — "
          . (isset($motivo[$puc]) ? $motivo[$puc] : 'saldo real confirmado')
          . '. Contrapartida utilidades acumuladas.';

    // Subir el saldo presentado de una cuenta de débito se debita; de una de
    // crédito se acredita. Y al revés para bajarlo.
    $subir = ($delta > 0);
    if ($esDebito === $subir) asiento($m, $APPLY, $errores, $desc, (int)$s['id'], $ACC_PATRI, abs($delta), $TIPO, $FECHA, $USER, $STORE);
    else                      asiento($m, $APPLY, $errores, $desc, $ACC_PATRI, (int)$s['id'], abs($delta), $TIPO, $FECHA, $USER, $STORE);
}
printf("  %-56s EFECTO EN PATRIMONIO %16s\n", '', mo($ajusteTotal));

// ── 2b. Anticipos dentro de comisiones por pagar: una sola cuenta ──────────
echo "\n" . str_repeat('─', 96) . "\n2b. CRUCE DE ANTICIPOS CONTRA COMISIONES — una sola cuenta con el neto a pagar\n" . str_repeat('─', 96) . "\n";
echo "     Los anticipos (136525, activo) y las comisiones (233525, pasivo) son las dos caras\n";
echo "     de lo mismo con las mismas personas. Se cruzan por auxiliar para que 233525 quede\n";
echo "     con el neto a girar a cada uno y 136525 en cero. No toca el patrimonio.\n\n";
$cruces = rows($m, "
    SELECT axA.id aux_anticipo, axC.id aux_comision, axA.accountAccount uid, u.name,
           (axA.accountDebit - axA.accountCredit) anticipo,
           (axC.accountCredit - axC.accountDebit) comision
    FROM auxiliary_subaccounts axA
    JOIN auxiliary_subaccounts axC ON axC.accountAccount = axA.accountAccount
                                  AND axC.accountType = 'bot_commission' AND axC.deleted = 0
    LEFT JOIN users u ON u.idUser = axA.accountAccount
    WHERE axA.accountID = 136525 AND axA.deleted = 0
      AND (axA.accountDebit - axA.accountCredit) > 0
    ORDER BY (axA.accountDebit - axA.accountCredit) DESC");
$totCruce = 0;
foreach ($cruces as $x) {
    $ant = round((float)$x['anticipo'], 2);
    $com = round((float)$x['comision'], 2);
    if ($ant <= 0) continue;
    if ($ant > $com + 0.01) {
        printf("  %-30s anticipo %13s MAYOR que comisión %13s  <<< SE SALTA, revisar a mano\n",
            substr((string)$x['name'], 0, 30), mo($ant), mo($com));
        continue;
    }
    $totCruce += $ant;
    printf("  %-30s comisión %13s - anticipo %13s = neto %13s\n",
        substr((string)$x['name'], 0, 30), mo($com), mo($ant), mo($com - $ant));
    $desc = "Apertura al {$FECHA}: se cruza el anticipo de " . ($x['name'] ?: $x['uid'])
          . ' contra su comisión de bots, para manejar una sola cuenta con el neto a pagar.';
    $d = $m->real_escape_string($desc);
    $v = number_format($ant, 2, '.', '');
    $nAsientos++;
    exec_sql($m, $APPLY, "INSERT INTO entries (userID, entryDescription, entryType,
            entryDebitAccount, entryDebitAuxaccount, entryDebitBalance,
            entryCreditAccount, entryCreditAuxaccount, entryCreditBalance,
            entryStatus, created_by, entryCreateDate, deleted, entryStoreId,
            entryTransactionType, entryTransactionId, entryDate)
        VALUES ('{$USER}', '{$d}', 1,
            {$ACC_COMIS}, " . (int)$x['aux_comision'] . ", '{$v}',
            {$ACC_ANTIC}, " . (int)$x['aux_anticipo'] . ", '{$v}',
            1, '{$USER}', NOW(), 0, {$STORE}, '{$TIPO}', 0, '{$FECHA}')", $errores);
}
printf("  %-56s TOTAL CRUZADO %16s\n", '', mo($totCruce));

// Y en el módulo: el anticipo cruzado deja de estar pendiente, para que
// Liquidaciones no lo vuelva a restar del neto.
if ($totCruce > 0) {
    echo "\n     marcando los anticipos como cruzados en el módulo\n";
    exec_sql($m, $APPLY, "UPDATE employee_advances
        SET outstanding_balance = 0, status = 'pagado',
            observations = CONCAT(COALESCE(observations,''), ' | Cruzado contra la comisión de bots en el cierre del {$FECHA}.'),
            updated_at = NOW()
        WHERE status = 'desembolsado' AND (deleted IS NULL OR deleted = 0)
          AND outstanding_balance > 0", $errores);
}

// ── 3. Tesorería al mismo saldo ────────────────────────────────────────────
echo "\n" . str_repeat('─', 96) . "\n3. TESORERIA: movimientos de ajuste para igualar banco y caja\n" . str_repeat('─', 96) . "\n";
$LEDGER = function ($tipo, $id) {
    return "(SELECT COALESCE(SUM(CASE
        WHEN c.movementType IN ('ingreso','apertura') AND c.sourceType='{$tipo}' AND c.sourceId={$id} THEN c.amount
        WHEN c.movementType IN ('egreso','cierre')    AND c.sourceType='{$tipo}' AND c.sourceId={$id} THEN -c.amount
        WHEN c.movementType='transferencia'           AND c.sourceType='{$tipo}' AND c.sourceId={$id} THEN -c.amount
        WHEN c.movementType='transferencia' AND c.destinationType='{$tipo}' AND c.destinationId={$id} THEN c.amount
        WHEN c.movementType='ajuste'                  AND c.sourceType='{$tipo}' AND c.sourceId={$id} THEN c.amount
        ELSE 0 END),0)
      FROM cash_movements c WHERE c.deleted=0 AND c.status<>'anulado'
        AND ((c.sourceType='{$tipo}' AND c.sourceId={$id})
          OR (c.destinationType='{$tipo}' AND c.destinationId={$id} AND c.movementType='transferencia')))";
};
$destinos = array(
    array('tipo' => 'banco', 'id' => $ID_BANCO, 'tabla' => 'bank_accounts', 'pk' => 'idBankAccount',
          'obj' => $OBJ_BANCO, 'nombre' => 'banco'),
    array('tipo' => 'caja', 'id' => $ID_CAJA, 'tabla' => 'cashboxes', 'pk' => 'idCashbox',
          'obj' => $OBJ_CAJA, 'nombre' => 'caja'),
);
foreach ($destinos as $d) {
    $ini = one($m, "SELECT initialBalance ib FROM {$d['tabla']} WHERE {$d['pk']} = {$d['id']}");
    $mv  = one($m, "SELECT " . $LEDGER($d['tipo'], $d['id']) . " t");
    $actual = round((float)$ini['ib'] + (float)$mv['t'], 2);
    $delta  = round($d['obj'] - $actual, 2);
    printf("  %-6s tesorería %16s -> %16s   ajuste %16s\n", $d['nombre'], mo($actual), mo($d['obj']), mo($delta));
    if (abs($delta) < 0.005) continue;
    $conc = "Ajuste de apertura al {$FECHA}: el saldo de la {$d['nombre']} queda en "
          . '$' . number_format($d['obj'], 2, ',', '.')
          . ', que es el saldo real con el que arranca la contabilidad. El lado contable va en el asiento de cierre.';
    exec_sql($m, $APPLY, "INSERT INTO cash_movements
        (sourceType, sourceId, movementType, amount, concept, category, documentNumber,
         movementDate, status, created_at, updated_at)
        VALUES ('{$d['tipo']}', {$d['id']}, 'ajuste', " . number_format($delta, 2, '.', '') . ",
         '" . $m->real_escape_string($conc) . "', 'ajuste', 'APERTURA-{$FECHA}',
         '{$FECHA} 23:00:00', 'activo', NOW(), NOW())", $errores);
    exec_sql($m, $APPLY, "UPDATE {$d['tabla']} SET currentBalance = " . number_format($d['obj'], 2, '.', '') . ",
        updated_at = NOW() WHERE {$d['pk']} = {$d['id']}", $errores);
}

// ── 4. Recalcular saldos ───────────────────────────────────────────────────
foreach (array(
    "UPDATE subaccounts s SET
       s.accountDebit  = (SELECT COALESCE(SUM(CAST(e.entryDebitBalance  AS DECIMAL(18,2))),0) FROM entries e WHERE e.deleted=0 AND e.entryDebitAccount  = s.id),
       s.accountCredit = (SELECT COALESCE(SUM(CAST(e.entryCreditBalance AS DECIMAL(18,2))),0) FROM entries e WHERE e.deleted=0 AND e.entryCreditAccount = s.id)
     WHERE s.deleted = 0",
    "UPDATE subaccounts SET accountBalance = CASE WHEN accountSide = '1'
       THEN accountDebit - accountCredit ELSE accountCredit - accountDebit END WHERE deleted = 0",
    "UPDATE auxiliary_subaccounts a SET
       a.accountDebit  = (SELECT COALESCE(SUM(CAST(e.entryDebitBalance  AS DECIMAL(18,2))),0) FROM entries e WHERE e.deleted=0 AND e.entryDebitAuxaccount  = a.id),
       a.accountCredit = (SELECT COALESCE(SUM(CAST(e.entryCreditBalance AS DECIMAL(18,2))),0) FROM entries e WHERE e.deleted=0 AND e.entryCreditAuxaccount = a.id)
     WHERE a.deleted = 0",
    "UPDATE auxiliary_subaccounts SET accountBalance = CASE WHEN accountSide = '1'
       THEN accountDebit - accountCredit ELSE accountCredit - accountDebit END WHERE deleted = 0",
) as $sql) exec_sql($m, $APPLY, $sql, $errores);

echo "\nAsientos a crear: {$nAsientos}\n";

if ($APPLY) {
    if ($errores) { echo "\n{$errores} error(es): ROLLBACK, no se cambió nada.\n"; $m->rollback(); exit(1); }
    $m->commit();
}

// ── Verificación ───────────────────────────────────────────────────────────
echo "\n" . str_repeat('=', 96) . "\n" . ($APPLY ? "APLICADO — BALANCE DE APERTURA" : "FIN SIMULACION — nada se escribió") . "\n" . str_repeat('=', 96) . "\n";
if (!$APPLY) exit(0);

$tot = array();
foreach (array('1' => 'ACTIVO', '2' => 'PASIVO', '3' => 'PATRIMONIO', '4' => 'INGRESOS', '5' => 'GASTOS', '6' => 'COSTOS') as $pref => $nombre) {
    $sub = 0; $lineas = array();
    foreach (rows($m, "SELECT s.id, s.pucCode, s.accountName, ({$NETO}) neto FROM subaccounts s
                       WHERE s.deleted = 0 AND s.pucCode LIKE '{$pref}%' ORDER BY s.pucCode") as $x) {
        $val = in_array($pref, array('1','5','6')) ? (float)$x['neto'] : -(float)$x['neto'];
        if (abs($val) < 0.005) continue;
        $sub += $val;
        $lineas[] = sprintf("  %-9s %-50s %18s", $x['pucCode'], substr($x['accountName'], 0, 50), mo($val));
    }
    $tot[$pref] = $sub;
    if (!$lineas) { printf("\n%s\n  (todo en cero)\n", $nombre); continue; }
    echo "\n{$nombre}\n" . implode("\n", $lineas) . "\n";
    printf("  %-60s %18s\n", "TOTAL {$nombre}", mo($sub));
}
$a = isset($tot['1']) ? $tot['1'] : 0; $p = isset($tot['2']) ? $tot['2'] : 0; $q = isset($tot['3']) ? $tot['3'] : 0;
printf("\n  Activo %s = Pasivo %s + Patrimonio %s   ->  diferencia %s\n",
    mo($a), mo($p), mo($q), mo($a - ($p + $q)));
$d = one($m, "SELECT COALESCE(SUM(CAST(entryDebitBalance AS DECIMAL(18,2)))
                            - SUM(CAST(entryCreditBalance AS DECIMAL(18,2))),0) t
              FROM entries WHERE deleted = 0");
echo "  Partida doble global: " . mo($d['t']) . " (debe ser 0)\n";
$b = one($m, "SELECT currentBalance cb FROM bank_accounts WHERE idBankAccount = {$ID_BANCO}");
$k = one($m, "SELECT currentBalance cb FROM cashboxes WHERE idCashbox = {$ID_CAJA}");
echo "  Tesorería banco: " . mo($b['cb']) . "   caja: " . mo($k['cb']) . "\n";
