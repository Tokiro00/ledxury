<?php
/**
 * Recalcula el campo currentBalance de cajas y bancos desde cash_movements,
 * con la misma fórmula del libro (Cashmovements_model::getLedgerBySource).
 *
 *   php recalcular_saldos_tesoreria.php            (simulación)
 *   php recalcular_saldos_tesoreria.php --apply
 *
 * POR QUÉ HACE FALTA
 * currentBalance es un caché: se actualiza cuando el módulo que crea el
 * movimiento se acuerda de hacerlo. Cuando un movimiento entra por otra vía
 * (script, corrección, módulo que no lo actualiza) la columna se queda atrás.
 * Las vistas ya calculan el saldo desde los movimientos, así que el desfase no
 * se ve en pantalla — pero sí al comparar contra contabilidad.
 *
 * Correrlo es seguro y repetible: solo iguala la columna a la suma real.
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

function rows($m, $sql) {
    $r = $m->query($sql);
    if ($r === false) { echo "  ERROR: {$m->error}\n"; return array(); }
    $o = array(); while ($x = $r->fetch_assoc()) $o[] = $x; return $o;
}
function money($v) { return number_format((float)$v, 2, ',', '.'); }

// La fórmula del libro: los 'ajuste' traen el delta ya firmado en amount.
function saldoReal($tipo, $idCol) {
    return "
    (SELECT COALESCE(SUM(CASE
        WHEN c.movementType IN ('ingreso','apertura') AND c.sourceType='{$tipo}' AND c.sourceId = t.{$idCol} THEN c.amount
        WHEN c.movementType IN ('egreso','cierre')    AND c.sourceType='{$tipo}' AND c.sourceId = t.{$idCol} THEN -c.amount
        WHEN c.movementType = 'transferencia'         AND c.sourceType='{$tipo}' AND c.sourceId = t.{$idCol} THEN -c.amount
        WHEN c.movementType = 'transferencia' AND c.destinationType='{$tipo}' AND c.destinationId = t.{$idCol} THEN c.amount
        WHEN c.movementType = 'ajuste'                AND c.sourceType='{$tipo}' AND c.sourceId = t.{$idCol} THEN c.amount
        ELSE 0 END), 0)
     FROM cash_movements c
     WHERE c.deleted = 0 AND c.status <> 'anulado'
       AND ((c.sourceType='{$tipo}' AND c.sourceId = t.{$idCol})
         OR (c.destinationType='{$tipo}' AND c.destinationId = t.{$idCol} AND c.movementType='transferencia')))";
}

$destinos = array(
    array('tabla' => 'bank_accounts', 'id' => 'idBankAccount', 'tipo' => 'banco',
          'nombre' => "CONCAT(t.bankName, ' ', COALESCE(t.accountNumber,''))"),
    array('tabla' => 'cashboxes',     'id' => 'idCashbox',     'tipo' => 'caja',
          'nombre' => 't.name'),
);

$cambios = 0;
foreach ($destinos as $d) {
    if (!rows($m, "SHOW TABLES LIKE '{$d['tabla']}'")) { echo "── {$d['tabla']}: no existe, se salta\n\n"; continue; }
    echo "── {$d['tabla']}\n";
    $expr = saldoReal($d['tipo'], $d['id']);
    $lista = rows($m, "SELECT t.{$d['id']} id, {$d['nombre']} nombre, t.initialBalance ini,
                              t.currentBalance actual, (t.initialBalance + {$expr}) real_
                       FROM {$d['tabla']} t WHERE t.deleted = 0 ORDER BY t.{$d['id']}");
    foreach ($lista as $r) {
        $dif = round((float)$r['real_'] - (float)$r['actual'], 2);
        $marca = abs($dif) > 0.01 ? '  <<< desfasado' : '';
        printf("  %-4s %-40s guardado %18s   real %18s   dif %14s%s\n",
            $r['id'], substr($r['nombre'], 0, 40), money($r['actual']), money($r['real_']), money($dif), $marca);
        if (abs($dif) > 0.01) $cambios++;
    }
    if ($APPLY) {
        $sql = "UPDATE {$d['tabla']} t SET t.currentBalance = t.initialBalance + {$expr}, t.updated_at = NOW()
                WHERE t.deleted = 0";
        if ($m->query($sql) === false) echo "  ERROR: {$m->error}\n";
        else echo "  -> actualizadas {$m->affected_rows} fila(s)\n";
    }
    echo "\n";
}

echo $APPLY
    ? "=== APLICADO ({$cambios} estaban desfasados) ===\n"
    : "=== FIN SIMULACION ({$cambios} desfasado(s)) — nada se escribió ===\n";
