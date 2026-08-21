<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Libro Diario — listado de asientos y captura de asientos manuales.
 *
 * ASIENTOS COMPUESTOS (reescrito 21/08/2026)
 * La pantalla anterior solo permitía UNA línea de débito y UNA de crédito, y
 * además guardaba entryStatus = 'activo' en una columna int(1): el asiento
 * nacía con estado 0, o sea muerto. En toda la historia de la base no se creó
 * ni un asiento manual — no servía.
 *
 * Ahora se captura como lo entiende un contador: un encabezado y N líneas, cada
 * una con su cuenta, su auxiliar opcional y su monto en Debe o en Haber. Se
 * guarda tal cual en entry_groups / entry_group_lines y además se descompone en
 * pares balanceados hacia `entries`, así el mayor por cuenta queda idéntico y
 * todos los reportes que ya existen —que asumen un débito y un crédito por
 * fila— siguen funcionando sin tocarlos. Mismo modelo del ERP de
 * stockaccessories, más los auxiliares, que aquí son indispensables.
 *
 * El cuadre se valida en CENTAVOS enteros: con flotantes, dos montos que en
 * pantalla suman igual pueden diferir en una fracción y dejar pasar un asiento
 * descuadrado. Un asiento que no cuadra no se guarda nunca.
 */
class Entries extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->backend_lib->controlModule('contabilidad'); // Admin, Contador
        $this->load->model("entry_model");
        $this->load->model("stores_model");
        $this->load->model("subaccount_model");
        $this->load->model("auxsubaccount_model");
        $this->load->model("accountingperiods_model");
        $this->load->library("accounting_lib");
    }

    public function index()
    {
        $page = $this->input->get('p') ?: 1;
        $limit = 50;

        // Filtros
        $from = $this->input->get('from') ?: date('Y-m-01');
        $to = $this->input->get('to') ?: date('Y-m-d');
        $storeId = $this->input->get('store');
        $type = $this->input->get('type');

        // Construir filtros para la consulta
        $filters = array(
            'from' => $from,
            'to' => $to,
            'store' => $storeId,
            'type' => $type
        );

        $total = $this->entry_model->getTotalEntriesFiltered($filters);
        $last = ceil($total / $limit);

        if ($page > $last) $page = max($last, 1);
        if ($page <= 0) $page = 1;

        $entries = $this->entry_model->getEntriesFiltered($filters, $page, $limit);

        // Calcular totales del período
        $totals = $this->entry_model->getTotalsByDateRange($from, $to, $storeId);

        $data = array(
            'entries' => $entries,
            'page' => $page,
            'total' => $total,
            'limit' => $limit,
            'stores' => $this->stores_model->getStores(),
            'filter_from' => $from,
            'filter_to' => $to,
            'filter_store' => $storeId,
            'filter_type' => $type,
            'totalDebit' => $totals->totalDebit ?: 0,
            'totalCredit' => $totals->totalCredit ?: 0
        );
        $this->load->view("sisvent/accounting/entries/list", $data);
    }

    /**
     * Detalle de un asiento. Si viene de un asiento compuesto del libro diario,
     * la vista puede mostrar las líneas como se capturaron.
     */
    public function view($id)
    {
        $entry = $this->entry_model->getEntryWithDetails($id);

        if (!$entry) {
            redirect(base_url() . 'sisvent/accounting/entries');
        }

        $grupo = null; $lineas = array();
        if (!empty($entry->entryGroupId)) {
            $grupo = $this->db->where('id', (int)$entry->entryGroupId)->get('entry_groups')->row();
            $lineas = $this->db->select('l.*, s.pucCode, s.accountName, a.accountName AS auxName')
                ->from('entry_group_lines l')
                ->join('subaccounts s', 's.id = l.subaccount_id', 'left')
                ->join('auxiliary_subaccounts a', 'a.id = l.aux_id', 'left')
                ->where('l.group_id', (int)$entry->entryGroupId)
                ->order_by('l.ord', 'ASC')
                ->get()->result();
        }

        $this->load->view("sisvent/accounting/entries/view", array(
            'entry'  => $entry,
            'grupo'  => $grupo,
            'lineas' => $lineas,
        ));
    }

    /**
     * Formulario de asiento manual.
     *
     * Los auxiliares se pasan TODOS los que tienen tipo (proveedores,
     * comisiones de bot, anticipos): son 13 y son los que se usan en un asiento
     * manual. No se filtran por la cuenta elegida a propósito — el campo
     * `auxiliary_subaccounts.accountAccount` está sobrecargado en esta base (a
     * veces es el id de la subcuenta padre, a veces el id de un usuario o
     * proveedor) y `accountID` no siempre coincide con el pucCode del padre
     * (los de proveedor dicen 220501 y la subcuenta real es 220505), así que
     * filtrar por metadatos daría listas vacías. Los de cliente se excluyen:
     * son 3.572 y para cartera se usa su propio módulo.
     */
    public function add()
    {
        $subaccounts = $this->db->select('id, pucCode, accountName, accountSide')
            ->from('subaccounts')
            ->where('deleted', 0)
            ->where("COALESCE(pucCode,'') != ''", null, false)
            ->order_by('pucCode', 'ASC')
            ->get()->result();

        $auxaccounts = $this->db->select('id, accountID, accountName, accountType')
            ->from('auxiliary_subaccounts')
            ->where('deleted', 0)
            ->where("COALESCE(accountType,'') != ''", null, false)
            ->where_not_in('accountType', array('client'))
            ->order_by('accountID', 'ASC')
            ->order_by('accountName', 'ASC')
            ->get()->result();

        $this->load->view("sisvent/accounting/entries/add", array(
            'stores'      => $this->stores_model->getStores(),
            'subaccounts' => $subaccounts,
            'auxaccounts' => $auxaccounts,
            'role'        => $this->session->userdata('user_data')['role'],
        ));
    }

    /**
     * Guarda el asiento compuesto. Validación dura: mínimo 2 líneas, cada línea
     * con cuenta y con SOLO Debe o SOLO Haber, y suma Debe = suma Haber al
     * centavo. Si algo falla no se escribe nada.
     */
    public function save()
    {
        // No se llama outh_model->CSRFVerify(): ese método lee una cabecera
        // 'Authkey' y sirve para AJAX, no para un POST de formulario. La
        // protección CSRF de CI está en FALSE en esta instalación, así que el
        // campo oculto de la vista es el estándar a usar cuando se active.
        if ($_SERVER['REQUEST_METHOD'] != 'POST') { redirect(base_url() . 'sisvent/accounting/entries'); return; }

        $fecha = (string)$this->input->post('entryDate');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) || $fecha > date('Y-m-d')) {
            $this->_err('La fecha no es válida o es futura.'); return;
        }
        $desc    = trim((string)$this->input->post('description'));
        $storeId = (int)$this->input->post('storeId') ?: 1;
        $userId  = $this->session->userdata('user_data')['uname'];

        $cuentas = (array)$this->input->post('cuenta');
        $auxes   = (array)$this->input->post('aux');
        $lconc   = (array)$this->input->post('lconc');
        $debes   = (array)$this->input->post('debe');
        $haberes = (array)$this->input->post('haber');

        // Montos en CENTAVOS enteros: el cuadre tiene que ser exacto, y con
        // flotantes dos cifras que en pantalla suman igual pueden diferir.
        $toCents = function ($v) {
            $v = str_replace(array('.', ' '), '', (string)$v);   // separador de miles
            $v = str_replace(',', '.', $v);                       // coma decimal
            return (int)round(((float)$v) * 100);
        };

        $lines = array();
        foreach ($cuentas as $i => $cta) {
            $cta = (int)$cta;
            $d = $toCents(isset($debes[$i]) ? $debes[$i] : 0);
            $h = $toCents(isset($haberes[$i]) ? $haberes[$i] : 0);
            if ($cta <= 0 && $d === 0 && $h === 0) continue;   // fila vacía del grid
            if ($cta <= 0)          { $this->_err('Hay una línea con monto pero sin cuenta.'); return; }
            if ($d < 0 || $h < 0)   { $this->_err('Los montos no pueden ser negativos.'); return; }
            if ($d > 0 && $h > 0)   { $this->_err('Una línea no puede tener Debe y Haber a la vez.'); return; }
            if ($d === 0 && $h === 0) { $this->_err('Hay una línea con cuenta pero sin monto.'); return; }
            $lines[] = array(
                'cta'   => $cta,
                'aux'   => isset($auxes[$i]) && (int)$auxes[$i] > 0 ? (int)$auxes[$i] : null,
                'conc'  => trim((string)(isset($lconc[$i]) ? $lconc[$i] : '')),
                'debe'  => $d,
                'haber' => $h,
            );
        }

        if ($desc === '')        { $this->_err('El concepto del asiento es obligatorio.'); return; }
        if (count($lines) < 2)   { $this->_err('Un asiento necesita mínimo 2 líneas.'); return; }

        $sumD = 0; $sumH = 0;
        foreach ($lines as $l) { $sumD += $l['debe']; $sumH += $l['haber']; }
        if ($sumD !== $sumH) {
            $this->_err('Asiento DESCUADRADO: la diferencia entre Debe y Haber es $'
                . number_format(abs($sumD - $sumH) / 100, 2, ',', '.') . '. No se guardó nada.');
            return;
        }
        if ($sumD === 0) { $this->_err('El asiento no puede ser de $0.'); return; }

        // Las cuentas tienen que existir
        $ids = array_unique(array_map(function ($l) { return $l['cta']; }, $lines));
        $found = (int)$this->db->where_in('id', $ids)->where('deleted', 0)->count_all_results('subaccounts');
        if ($found !== count($ids)) { $this->_err('Hay una cuenta inválida en las líneas.'); return; }

        // Período cerrado
        if ($this->accounting_lib->isPeriodClosed($fecha, $storeId)) {
            $this->_err('No se pueden crear asientos en un período cerrado (' . date('m/Y', strtotime($fecha)) . ').');
            return;
        }

        $this->db->trans_start();

        $this->db->insert('entry_groups', array(
            'group_date'  => $fecha,
            'description' => $desc,
            'store_id'    => $storeId,
            'total'       => number_format($sumD / 100, 2, '.', ''),
            'created_by'  => $userId,
            'created_at'  => date('Y-m-d H:i:s'),
            'deleted'     => 0,
        ));
        $groupId = (int)$this->db->insert_id();

        foreach ($lines as $ord => $l) {
            $this->db->insert('entry_group_lines', array(
                'group_id'      => $groupId,
                'ord'           => $ord + 1,
                'subaccount_id' => $l['cta'],
                'aux_id'        => $l['aux'],
                'concepto'      => $l['conc'] !== '' ? $l['conc'] : null,
                'debe'          => number_format($l['debe'] / 100, 2, '.', ''),
                'haber'         => number_format($l['haber'] / 100, 2, '.', ''),
            ));
        }

        // Descomposición en pares: se empareja el débito mayor con el crédito
        // mayor y se va descontando. El total por cuenta queda exacto; el
        // emparejamiento es solo la forma de guardarlo en `entries`.
        $debits = array(); $credits = array();
        foreach ($lines as $l) {
            if ($l['debe'] > 0) $debits[]  = array('cta' => $l['cta'], 'aux' => $l['aux'], 'rem' => $l['debe']);
            else                $credits[] = array('cta' => $l['cta'], 'aux' => $l['aux'], 'rem' => $l['haber']);
        }
        $cmp = function ($a, $b) { return $b['rem'] - $a['rem']; };
        $primero = 0; $pares = 0; $fallo = false;
        while (!empty($debits) && !empty($credits)) {
            usort($debits, $cmp);
            usort($credits, $cmp);
            $monto = min($debits[0]['rem'], $credits[0]['rem']);
            $entryId = $this->accounting_lib->recordManualJournalPair(
                $groupId,
                $debits[0]['cta'],  $debits[0]['aux'],
                $credits[0]['cta'], $credits[0]['aux'],
                $monto / 100, $desc, $userId, $storeId, $fecha
            );
            if (!$entryId) { $fallo = true; break; }
            if (!$primero) $primero = (int)$entryId;
            $pares++;
            $debits[0]['rem']  -= $monto;
            $credits[0]['rem'] -= $monto;
            if ($debits[0]['rem'] === 0)  array_shift($debits);
            if ($credits[0]['rem'] === 0) array_shift($credits);
        }

        if ($fallo) {
            $this->db->trans_rollback();
            $this->_err('No se pudo asentar el par contable — no se guardó nada. Revisa que el período esté abierto.');
            return;
        }

        $this->db->trans_complete();

        if ($this->db->trans_status()) {
            $this->load->model('logs_model');
            $this->logs_model->logMessage("info", "Usuario {$userId} registró el asiento de diario #{$groupId} ("
                . count($lines) . " líneas, {$pares} pares)");
            $this->session->set_flashdata('diario_success', 'Asiento #' . $groupId . ' registrado: '
                . count($lines) . ' líneas por $' . number_format($sumD / 100, 2, ',', '.') . ', cuadrado.');
            redirect(base_url() . 'sisvent/accounting/entries/view/' . $primero);
        } else {
            $this->_err('Error al guardar el asiento — la transacción se revirtió.');
        }
    }

    private function _err($msg)
    {
        $this->session->set_flashdata('diario_error', $msg);
        redirect(base_url() . 'sisvent/accounting/entries/add');
    }

    /**
     * Auxiliares de una subcuenta (AJAX). Se conserva para quien lo use.
     */
    public function getAuxAccounts($subaccountId)
    {
        $auxAccounts = $this->auxsubaccount_model->getAuxsubaccountsBySubaccount($subaccountId);
        header('Content-Type: application/json');
        echo json_encode($auxAccounts);
    }
}
