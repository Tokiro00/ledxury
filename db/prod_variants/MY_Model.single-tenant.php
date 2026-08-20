<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * MY_Model — VERSIÓN SINGLE-TENANT PARA ledxury.com
 * ==================================================
 *
 * ESTE ARCHIVO VIVE EN EL REPO EN db/prod_variants/ Y SE DESPLIEGA A
 * /var/www/html/application/core/MY_Model.php  (nombre distinto a propósito:
 * no debe pisar el application/core/MY_Model.php de la rama, que sí es
 * multi-tenant).
 *
 * POR QUÉ EXISTE
 * La rama de trabajo es multi-tenant: sus modelos declaran
 * `class X extends MY_Model` y llaman applyTenantFilter() / tenantInsert().
 * ledxury.com corre la línea single-tenant: **no tiene tabla `tenants` ni una
 * sola columna `tenant_id`** (verificado el 20/08/2026: 0 columnas tenant_id en
 * toda la base). Sin esta clase, cualquier modelo de la rama que se despliegue
 * revienta con "Class MY_Model not found" y tumba la página completa.
 *
 * Ya pasó dos veces:
 *   · `Supplierbills_model.php` llegó a producción extendiendo MY_Model en un
 *     deploy sin registrar, y dejó reventadas Cuentas por Pagar, el tablero
 *     financiero y Proveedores.
 *   · El 20/08/2026 se subió `Employeeadvances_model.php` de la rama y tumbó
 *     Liquidaciones y Anticipos.
 *
 * QUÉ HACE
 * Cumple la misma API pública que la MY_Model de la rama, pero **inerte**:
 * no filtra por tenant y no inyecta tenant_id, porque aquí no hay a qué. Así
 * los modelos de la rama corren tal cual, sin portarlos uno por uno.
 *
 * NO desplegar aquí la MY_Model de la rama: applyTenantFilter() agregaría
 * `WHERE tenant_id = 1` y rompería todas las consultas contra tablas que no
 * tienen esa columna.
 *
 * Si algún día se activa multi-tenant en producción, este archivo se reemplaza
 * por el de la rama DESPUÉS de correr la migración 060.
 */
class MY_Model extends CI_Model
{
    /** Bypass de tenant: aquí no aplica, se conserva por compatibilidad. */
    protected $tenantBypassNext = false;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Sin multi-tenant no hay tenant activo. Devolver null es lo correcto:
     * tenantInsert() lo interpreta como "no inyectes la columna".
     */
    public function tenantId()
    {
        return null;
    }

    /**
     * No-op. En la rama agrega `WHERE tenant_id = X`; aquí no existe la
     * columna, así que agregar algo rompería la consulta.
     *
     * @return $this  para poder encadenar igual que en la rama
     */
    public function applyTenantFilter($tableAlias = null)
    {
        $this->tenantBypassNext = false;
        return $this;
    }

    /**
     * Saldo real de una caja o banco desde cash_movements. Idéntica a la de la
     * rama: no tiene nada de multi-tenant. Los movimientos tipo 'ajuste' traen
     * el delta ya firmado en amount.
     *
     * @param string $sourceType 'banco' | 'caja' (literal fijo, no user input)
     * @param string $idRef      referencia SQL al id de la cuenta
     * @param string $initialRef referencia SQL al saldo inicial
     * @return string            expresión SQL (sin alias)
     */
    protected function realBalanceExpr($sourceType, $idRef, $initialRef)
    {
        $t = ($sourceType === 'caja') ? 'caja' : 'banco'; // whitelist
        return "($initialRef + COALESCE((
            SELECT SUM(CASE
                WHEN cm.movementType IN ('ingreso','apertura') AND cm.sourceType='$t' AND cm.sourceId = $idRef THEN cm.amount
                WHEN cm.movementType IN ('egreso','cierre')    AND cm.sourceType='$t' AND cm.sourceId = $idRef THEN -cm.amount
                WHEN cm.movementType = 'transferencia'         AND cm.sourceType='$t' AND cm.sourceId = $idRef THEN -cm.amount
                WHEN cm.movementType = 'transferencia'         AND cm.destinationType='$t' AND cm.destinationId = $idRef THEN cm.amount
                WHEN cm.movementType = 'ajuste'                AND cm.sourceType='$t' AND cm.sourceId = $idRef THEN cm.amount
                ELSE 0 END)
            FROM cash_movements cm
            WHERE cm.deleted = 0 AND cm.status != 'anulado'
              AND ( (cm.sourceType='$t' AND cm.sourceId = $idRef)
                 OR (cm.destinationType='$t' AND cm.destinationId = $idRef AND cm.movementType='transferencia') )
        ), 0))";
    }

    /**
     * INSERT normal: aquí no hay tenant_id que inyectar.
     * Devuelve el insert_id, igual que en la rama.
     */
    public function tenantInsert($table, $data)
    {
        if (is_object($data)) $data = (array)$data;
        unset($data['tenant_id']);   // por si un modelo de la rama la trae puesta
        $this->db->insert($table, $data);
        return $this->db->insert_id();
    }

    /** INSERT BATCH normal, sin tenant_id. */
    public function tenantInsertBatch($table, $rows)
    {
        if (empty($rows)) return 0;
        foreach ($rows as $i => $r) {
            if (is_object($r)) $r = (array)$r;
            unset($r['tenant_id']);
            $rows[$i] = $r;
        }
        return $this->db->insert_batch($table, $rows);
    }

    /** No-op: sin multi-tenant todas las consultas ya ven todo. */
    public function withAllTenants()
    {
        return $this;
    }

    /**
     * Siguiente número de documento. La rama usa el contador por tenant
     * (tenant_invoice_counters); aquí esa tabla no existe, así que se usa la
     * secuencia histórica: MAX() de la columna real + 1, que es exactamente lo
     * que hacía el código antes del multi-tenant.
     *
     * @param string $docType 'invoice' | 'budget' | 'credit_note' | 'refund'
     * @return int
     */
    public function nextNumber($docType, $tenantId = null)
    {
        $colMap = array(
            'invoice'     => array('invoices', 'if_id'),
            'budget'      => array('budgets', 'idBudget'),
            'credit_note' => array('credit_notes', 'idCreditNote'),
            'refund'      => array('refunds', 'idRefund'),
        );
        if (!isset($colMap[$docType])) {
            log_message('error', "MY_Model::nextNumber - tipo de documento desconocido: {$docType}");
            return 0;
        }
        list($tbl, $col) = $colMap[$docType];
        $row = $this->db->select_max($col, 'mx')->get($tbl)->row();
        return (int)(isset($row->mx) ? $row->mx : 0) + 1;
    }
}
