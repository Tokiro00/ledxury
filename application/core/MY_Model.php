<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * MY_Model — Base class para modelos tenant-aware.
 *
 * Provee helpers para aislar queries por tenant_id automáticamente.
 * Los modelos que tocan tablas transaccionales DEBEN extender de esta clase
 * (en lugar de CI_Model) y usar los helpers tenantXxx() o llamar
 * applyTenantFilter() antes de cada query.
 *
 * Ejemplo:
 *   class Invoices_model extends MY_Model {
 *       public function getAll() {
 *           $this->applyTenantFilter();
 *           return $this->db->get('invoices')->result();
 *       }
 *       public function create($data) {
 *           return $this->tenantInsert('invoices', $data);
 *       }
 *   }
 *
 * Para queries cross-tenant (platform admin, reportes globales):
 *   $this->tenantBypassNext = true;
 *   $this->applyTenantFilter(); // no-op porque bypass está activo
 *   $rows = $this->db->get('invoices')->result(); // ve todo
 */
class MY_Model extends CI_Model {

	/** @var bool Si true, el próximo applyTenantFilter() no aplica filtro */
	protected $tenantBypassNext = false;

	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Devuelve el tenant_id activo. Prioridad:
	 *   1. Override explícito vía set_tenant_context() — APIs JWT, bots, CLI
	 *   2. Sesión web
	 *   3. null (caller debe inyectar)
	 */
	public function tenantId()
	{
		if (isset($GLOBALS['__PULSO_TENANT_OVERRIDE__'])) {
			return (int)$GLOBALS['__PULSO_TENANT_OVERRIDE__'];
		}
		if (isset($this->session)) {
			$tid = $this->session->userdata('tenant_id');
			if ($tid) return (int)$tid;
		}
		return null;
	}

	/**
	 * Aplica WHERE tenant_id al query builder activo.
	 * Si tenantBypassNext está activo, no aplica y resetea el flag.
	 *
	 * @param string|null $tableAlias Alias de tabla para queries con JOIN (ej: "i" en "invoices i")
	 */
	public function applyTenantFilter($tableAlias = null)
	{
		if ($this->tenantBypassNext) {
			$this->tenantBypassNext = false;
			return;
		}
		$tid = $this->tenantId();
		if ($tid === null) return;
		$col = $tableAlias ? $tableAlias . '.tenant_id' : 'tenant_id';
		$this->db->where($col, $tid);
	}

	/**
	 * INSERT que inyecta tenant_id automáticamente.
	 */
	public function tenantInsert($table, $data)
	{
		if (is_object($data)) $data = (array)$data;
		if (!isset($data['tenant_id'])) {
			$tid = $this->tenantId();
			if ($tid !== null) $data['tenant_id'] = $tid;
		}
		$this->db->insert($table, $data);
		return $this->db->insert_id();
	}

	/**
	 * INSERT BATCH que inyecta tenant_id en cada row.
	 */
	public function tenantInsertBatch($table, $rows)
	{
		if (empty($rows)) return 0;
		$tid = $this->tenantId();
		if ($tid !== null) {
			foreach ($rows as $i => $r) {
				if (is_object($r)) $r = (array)$r;
				if (!isset($r['tenant_id'])) $r['tenant_id'] = $tid;
				$rows[$i] = $r;
			}
		}
		return $this->db->insert_batch($table, $rows);
	}

	/**
	 * Activa bypass para la siguiente query (uso: platform admin / reportes globales).
	 * Solo permitido si el usuario es platform admin.
	 *
	 * @return $this
	 */
	public function withAllTenants()
	{
		$userData = $this->session->userdata('user_data');
		if (!empty($userData['is_platform_admin'])) {
			$this->tenantBypassNext = true;
		}
		return $this;
	}

	/**
	 * Devuelve el siguiente número de documento para el tenant activo.
	 * Usa tenant_invoice_counters como contador atómico (incrementa y devuelve).
	 *
	 * Compatibilidad: si el tenant es Ledxury (id=1) y la tabla está vacía,
	 * inicializa el contador con MAX() de la columna real para no romper la
	 * secuencia histórica. Para tenant 2+ siempre arranca desde 1.
	 *
	 * @param string $docType  'invoice', 'budget', 'credit_note', 'refund'
	 * @param int    $tenantId Override opcional (default: sesión)
	 * @return int  El siguiente número
	 */
	public function nextNumber($docType, $tenantId = null)
	{
		if ($tenantId === null) $tenantId = $this->tenantId();
		if ($tenantId === null) $tenantId = 1;
		$tenantId = (int)$tenantId;

		$this->db->trans_start();

		// Asegurar que el contador existe (lazy init).
		$existing = $this->db->select('last_number')
			->where('tenant_id', $tenantId)
			->where('doc_type', $docType)
			->get('tenant_invoice_counters')->row();

		if (!$existing) {
			// Inicializar desde MAX() de la tabla real (solo para tenant 1 legacy).
			$seed = 0;
			if ($tenantId === 1) {
				$colMap = array(
					'invoice'     => array('invoices', 'if_id'),
					'budget'      => array('budgets', 'idBudget'),
					'credit_note' => array('credit_notes', 'idCreditNote'),
					'refund'      => array('refunds', 'idRefund'),
				);
				if (isset($colMap[$docType])) {
					list($tbl, $col) = $colMap[$docType];
					$row = $this->db->select_max($col, 'mx')->get($tbl)->row();
					$seed = (int)($row->mx ?? 0);
				}
			}
			$this->db->insert('tenant_invoice_counters', array(
				'tenant_id'   => $tenantId,
				'doc_type'    => $docType,
				'last_number' => $seed,
				'updated_at'  => date('Y-m-d H:i:s'),
			));
			$next = $seed + 1;
		} else {
			$next = (int)$existing->last_number + 1;
		}

		$this->db->where('tenant_id', $tenantId)
			->where('doc_type', $docType)
			->update('tenant_invoice_counters', array(
				'last_number' => $next,
				'updated_at'  => date('Y-m-d H:i:s'),
			));

		$this->db->trans_complete();
		return $next;
	}
}
