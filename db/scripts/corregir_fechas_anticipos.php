<?php
/**
 * Pone la fecha que digitó el usuario en los anticipos que quedaron fechados el
 * día en que se registraron.
 *
 *   php corregir_fechas_anticipos.php            (simulación)
 *   php corregir_fechas_anticipos.php --apply
 *
 * EL PROBLEMA
 * Advances::store() leía advance_date del formulario y la guardaba bien en
 * employee_advances, pero al desembolsar en el mismo paso le pasaba date('Y-m-d')
 * a _processDisbursement(). Resultado: el movimiento de tesorería, el asiento
 * contable y disbursed_at quedaban con la fecha de digitación.
 * (El código ya quedó corregido; esto arregla los registros ya creados.)
 *
 * ALCANCE
 * Solo los anticipos listados abajo, verificados uno por uno: son los que se
 * crearon con "desembolsar ahora" y por eso arrastran la fecha equivocada.
 * ANT0001 a ANT0004 NO entran: esos se desembolsaron después, por el flujo de
 * desembolso, que sí pasa su propia fecha — ahí solicitada y desembolsada
 * difieren de verdad y está bien.
 *
 * Idempotente: cada UPDATE trae la fecha vieja en el WHERE.
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

// Anticipos a corregir y la fecha equivocada que hay que reemplazar. La fecha
// buena sale de employee_advances.advance_date, no está hardcodeada.
$SCOPE = array(
    5 => '2026-08-20',
    6 => '2026-08-20',
    7 => '2026-08-20',
);

function rows($m, $sql) {
    $r = $m->query($sql);
    if ($r === false) { echo "  ERROR: {$m->error}\n"; return false; }
    $o = array(); while ($x = $r->fetch_assoc()) $o[] = $x; return $o;
}
function one($m, $sql) { $r = rows($m, $sql); return $r ? $r[0] : null; }
function money($v) { return '$' . number_format((float)$v, 2, ',', '.'); }

$errores = 0;
function exec_sql($m, $APPLY, $sql, &$errores) {
    if (!$APPLY) { echo "       [sim] " . preg_replace('/\s+/', ' ', substr($sql, 0, 100)) . "\n"; return; }
    if ($m->query($sql) === false) { echo "       ERROR: {$m->error}\n"; $errores++; return; }
    echo "       -> {$m->affected_rows} fila" . ($m->affected_rows == 1 ? '' : 's') . "\n";
}

if ($APPLY) $m->begin_transaction();

foreach ($SCOPE as $advId => $fechaMala) {
    $a = one($m, "SELECT id, code, amount, advance_date, disbursed_at, cash_movement_id, entry_id
                  FROM employee_advances WHERE id = " . (int)$advId);
    if (!$a) { echo "── anticipo {$advId}: no existe, se salta\n"; continue; }

    $buena = $a['advance_date'];
    if (!$buena || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $buena)) {
        echo "── {$a['code']}: advance_date inválida ('{$buena}'), se salta\n";
        continue;
    }
    if (substr((string)$a['disbursed_at'], 0, 10) === $buena) {
        echo "── {$a['code']}: ya está en {$buena}, no se repite\n";
        continue;
    }

    echo "── {$a['code']} " . money($a['amount']) . "   {$fechaMala} → {$buena}\n";

    echo "     disbursed_at\n";
    exec_sql($m, $APPLY, "UPDATE employee_advances
        SET disbursed_at = CONCAT('{$buena}', ' ', TIME(disbursed_at)), updated_at = NOW()
        WHERE id = {$a['id']} AND DATE(disbursed_at) = '{$fechaMala}'", $errores);

    if ($a['cash_movement_id']) {
        echo "     movimiento {$a['cash_movement_id']}\n";
        exec_sql($m, $APPLY, "UPDATE cash_movements
            SET movementDate = CONCAT('{$buena}', ' ', TIME(movementDate)), updated_at = NOW()
            WHERE idMovement = " . (int)$a['cash_movement_id'] . " AND DATE(movementDate) = '{$fechaMala}'", $errores);
    }

    if ($a['entry_id']) {
        echo "     asiento {$a['entry_id']}\n";
        exec_sql($m, $APPLY, "UPDATE entries
            SET entryDate = '{$buena}', updated_at = NOW()
            WHERE entryID = " . (int)$a['entry_id'] . " AND entryDate = '{$fechaMala}'", $errores);
    }
}

if ($APPLY) {
    if ($errores) { echo "\n{$errores} error(es): ROLLBACK, no se cambió nada.\n"; $m->rollback(); exit(1); }
    $m->commit();
}

echo "\n=== " . ($APPLY ? "APLICADO — VERIFICACION" : "FIN SIMULACION — nada se escribió") . " ===\n";
if (!$APPLY) exit(0);

foreach (rows($m, "SELECT a.id, a.code, a.amount, a.advance_date, a.disbursed_at,
                          c.movementDate, e.entryDate
                   FROM employee_advances a
                   LEFT JOIN cash_movements c ON c.idMovement = a.cash_movement_id
                   LEFT JOIN entries e ON e.entryID = a.entry_id
                   ORDER BY a.id") as $r)
    printf("  %-9s %14s  solicitada %-11s desembolsada %-11s mov %-11s asiento %s\n",
        $r['code'], number_format($r['amount'], 2, ',', '.'), $r['advance_date'],
        substr((string)$r['disbursed_at'], 0, 10), substr((string)$r['movementDate'], 0, 10), $r['entryDate']);

$d = one($m, "SELECT COALESCE(SUM(CAST(entryDebitBalance AS DECIMAL(18,2)))
                            - SUM(CAST(entryCreditBalance AS DECIMAL(18,2))), 0) t
              FROM entries WHERE deleted = 0");
echo "  partida doble global: " . money($d['t']) . " (debe ser 0)\n";
