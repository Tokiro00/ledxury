<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Recuperación de guías — mesa de trabajo tras la pérdida de la instancia
 * (23/08/2026). Las guías que aparecen en los lotes de contrapago y en las
 * facturas de corte pero NO existen en shipping_guides (se perdieron con la
 * base) se consultan en lote contra el API de Interrapidísimo
 * (ConsultarEstadosGuiasCliente) y lo recuperado queda en guide_recovery.
 *
 * URL: /sisvent/admin/recuperacionguias
 */
class Recuperacionguias extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->backend_lib->control([1]);
    }

    public function index() {
        $role = $this->session->userdata('user_data')['role'];

        $tot = $this->db->query("
            SELECT COUNT(DISTINCT g) n FROM (
                SELECT cp.numeroGuia g FROM contrapago_payments cp WHERE cp.shipping_guide_id IS NULL
                  AND (cp.fechaVenta IS NULL OR cp.fechaVenta >= '2026-07-01')
                UNION
                SELECT cii.numero_guia FROM contrapago_invoice_items cii WHERE cii.shipping_guide_id IS NULL
                  AND (cii.fecha_grabacion IS NULL OR cii.fecha_grabacion >= '2026-07-01')
            ) t")->row();
        $cons = $this->db->query("SELECT COUNT(*) n FROM guide_recovery WHERE consultada_at IS NOT NULL")->row();

        $data = array(
            'role' => $role,
            'total_huerfanas' => (int)$tot->n,
            'total_consultadas' => (int)$cons->n,
        );
        $this->load->view('sisvent/admin/recuperacion_guias/index', $data);
    }

    /**
     * AJAX GET: listado completo de guías huérfanas con lo que sabemos de
     * cada una (contrapagos + cortes) y lo ya recuperado del API.
     */
    public function listado() {
        header('Content-Type: application/json');

        // Lo que sabemos por los lotes de pago
        $pagos = $this->db->query("
            SELECT cp.numeroGuia AS guia,
                   MAX(cp.fechaVenta) AS fecha_venta,
                   MAX(cp.valorTotal) AS valor_cobrado,
                   MAX(cp.nombreDestinatario) AS destinatario,
                   MAX(cp.company) AS company,
                   MAX(b.fecha_pago) AS fecha_pago,
                   MAX(b.sheet_name) AS lote
            FROM contrapago_payments cp
            JOIN contrapago_batches b ON b.id = cp.batch_id
            WHERE cp.shipping_guide_id IS NULL
              AND (cp.fechaVenta IS NULL OR cp.fechaVenta >= '2026-07-01')
            GROUP BY cp.numeroGuia")->result();

        // Lo que sabemos por las facturas de corte (flete)
        $cortes = $this->db->query("
            SELECT cii.numero_guia AS guia,
                   MAX(cii.fecha_grabacion) AS fecha_grabacion,
                   MAX(cii.ciudad_destino) AS ciudad_destino,
                   MAX(cii.valor_transporte) AS flete,
                   MAX(cii.valor_comercial) AS valor_declarado,
                   MAX(COALESCE(cii.company, 'sin_revisar')) AS company,
                   MAX(ci.numero_factura) AS factura
            FROM contrapago_invoice_items cii
            JOIN contrapago_invoices ci ON ci.id = cii.invoice_id
            WHERE cii.shipping_guide_id IS NULL
              AND (cii.fecha_grabacion IS NULL OR cii.fecha_grabacion >= '2026-07-01')
            GROUP BY cii.numero_guia")->result();

        // Facturas del sistema que tienen la guía como número de rastreo:
        // dan el vendedor y el cliente (p. ej. las de Barranquilla).
        $facts = $this->db->query("
            SELECT i.idInvoice, i.tracking_number, i.total,
                   u.name AS vendedor, c.name AS cliente
            FROM invoices i
            LEFT JOIN users u ON u.idUser = i.vendorId
            LEFT JOIN clients c ON c.idClient = i.clientId
            WHERE i.tracking_number IS NOT NULL AND i.tracking_number <> ''
              AND (i.deleted IS NULL OR i.deleted = 0)")->result();
        $factPor = array();
        foreach ($facts as $f) {
            $k = preg_replace('/[^0-9]/', '', $f->tracking_number);
            if ($k !== '') $factPor[$k] = $f;
        }

        // TODOS los pagos del histórico (17 hojas del archivo de Interrapidísimo,
        // ya importadas): si la guía aparece en CUALQUIER lote, ya fue pagada,
        // aunque ese pago esté cruzado con una guía del sistema y no sea huérfano.
        $pagosTodos = $this->db->query("
            SELECT REGEXP_REPLACE(cp.numeroGuia,'[^0-9]','') g, MAX(b.fecha_pago) fp
            FROM contrapago_payments cp
            JOIN contrapago_batches b ON b.id = cp.batch_id
            GROUP BY REGEXP_REPLACE(cp.numeroGuia,'[^0-9]','')")->result();
        $pagadaPor = array();
        foreach ($pagosTodos as $p) if ($p->g !== '') $pagadaPor[$p->g] = $p->fp;

        // Lo recuperado del API
        $rec = $this->db->query("SELECT * FROM guide_recovery")->result();
        $recPor = array();
        foreach ($rec as $r) $recPor[preg_replace('/[^0-9]/', '', $r->numero_guia)] = $r;

        $rows = array();
        foreach ($pagos as $p) {
            $k = preg_replace('/[^0-9]/', '', $p->guia);
            if ($k === '') continue;
            $rows[$k] = array(
                'guia' => $k,
                'fuentes' => array('pago: ' . $p->lote),
                'fecha_venta' => $p->fecha_venta,
                'destinatario' => $p->destinatario,
                'valor_cobrado' => (float)$p->valor_cobrado,
                'fecha_pago' => $p->fecha_pago,
                'valor_declarado' => null,
                'flete' => null,
                'company' => $p->company,
            );
        }
        foreach ($cortes as $c) {
            $k = preg_replace('/[^0-9]/', '', $c->guia);
            if ($k === '') continue;
            if (!isset($rows[$k])) {
                $rows[$k] = array(
                    'guia' => $k, 'fuentes' => array(),
                    'fecha_venta' => $c->fecha_grabacion,
                    'destinatario' => null, 'valor_cobrado' => null,
                    'fecha_pago' => null, 'valor_declarado' => null,
                    'flete' => null, 'company' => $c->company,
                );
            }
            $rows[$k]['fuentes'][] = 'corte: ' . $c->factura;
            $rows[$k]['flete'] = (float)$c->flete;
            $rows[$k]['valor_declarado'] = (float)$c->valor_declarado;
            if (empty($rows[$k]['destinatario']) && !empty($c->ciudad_destino)) {
                $rows[$k]['destinatario'] = $c->ciudad_destino;
            }
        }
        foreach ($rows as $k => &$row) {
            $row['pagada'] = isset($pagadaPor[$k]);
            if ($row['pagada'] && empty($row['fecha_pago'])) $row['fecha_pago'] = $pagadaPor[$k];
            if (isset($factPor[$k])) {
                $row['factura_erp'] = (int)$factPor[$k]->idInvoice;
                $row['vendedor'] = $factPor[$k]->vendedor;
                $row['cliente'] = $factPor[$k]->cliente;
            } else {
                $row['factura_erp'] = null;
                $row['vendedor'] = null;
                $row['cliente'] = null;
            }
            if (isset($recPor[$k])) {
                $g = $recPor[$k];
                $row['rec'] = array(
                    'estado' => $g->estado_actual,
                    'fecha_primer' => $g->fecha_primer_estado,
                    'fecha_ultimo' => $g->fecha_ultimo_estado,
                    'origen' => $g->ciudad_origen,
                    'destino' => $g->ciudad_destino,
                    'motivo' => $g->motivo_devolucion,
                    'consultada_at' => $g->consultada_at,
                );
            } else {
                $row['rec'] = null;
            }
        }
        unset($row);

        echo json_encode(array('success' => true, 'data' => array_values($rows)), JSON_UNESCAPED_UNICODE);
    }

    /**
     * AJAX POST: consulta un lote de guías (máx 30) en Interrapidísimo y
     * guarda el resultado en guide_recovery. Body: guias[] (números).
     */
    public function consultar() {
        header('Content-Type: application/json');
        $guias = $this->input->post('guias');
        if (!is_array($guias) || empty($guias)) {
            echo json_encode(array('success' => false, 'message' => 'Sin guías'));
            return;
        }
        $guias = array_slice(array_values(array_unique(array_filter(array_map(
            function ($g) { return preg_replace('/[^0-9]/', '', (string)$g); }, $guias
        )))), 0, 30);

        $this->load->library('interrapidisimo_lib');
        $res = $this->interrapidisimo_lib->consultarEstados($guias);
        // El API limita el ritmo: si falla, esperar y reintentar una vez.
        if (!is_object($res) || !isset($res->listadoGuias)) {
            sleep(6);
            $res = $this->interrapidisimo_lib->consultarEstados($guias);
        }

        if (!is_object($res) || !isset($res->listadoGuias)) {
            $msg = is_string($res) ? $res : 'Respuesta inesperada del API de Interrapidísimo';
            echo json_encode(array('success' => false, 'message' => $msg));
            return;
        }

        date_default_timezone_set('America/Bogota');
        $ahora = date('Y-m-d H:i:s');
        $out = array();
        foreach ($res->listadoGuias as $g) {
            $num = preg_replace('/[^0-9]/', '', (string)$g->numeroGuia);
            $estados = isset($g->estadosGuia) && is_array($g->estadosGuia) ? $g->estadosGuia : array();
            $primer = null; $ultimo = null; $actual = null; $origen = null; $destino = null;
            foreach ($estados as $e) {
                $f = isset($e->fechaEstado) ? $e->fechaEstado : null;
                if ($f !== null) {
                    if ($primer === null || $f < $primer) $primer = $f;
                    if ($ultimo === null || $f > $ultimo) { $ultimo = $f; $actual = $e; }
                }
                if (!$origen && !empty($e->nombreCiudadOrigen)) $origen = $e->nombreCiudadOrigen;
                if (!$destino && !empty($e->nombreCiudadDestino)) $destino = $e->nombreCiudadDestino;
            }
            // La devolución manda sobre el "Archivada" final: si el historial
            // trae un estado de devolución, ese es el que se muestra (con su fecha).
            foreach ($estados as $e) {
                if (stripos($e->nombreEstado, "evol") !== false || stripos($e->nombreEstado, "evuel") !== false) {
                    if (!$actual || stripos($actual->nombreEstado, "evol") === false || $e->fechaEstado > $actual->fechaEstado) {
                        $actual = $e; $ultimo = $e->fechaEstado;
                    }
                }
            }
            $motivo = isset($g->detalleMotivoDevolucion) && $g->detalleMotivoDevolucion !== null
                ? (is_scalar($g->detalleMotivoDevolucion) ? (string)$g->detalleMotivoDevolucion : json_encode($g->detalleMotivoDevolucion, JSON_UNESCAPED_UNICODE))
                : null;

            $fila = array(
                'numero_guia' => $num,
                'estado_actual' => $actual ? $actual->nombreEstado : null,
                'id_estado' => $actual ? (int)$actual->idEstadoGuia : null,
                'fecha_primer_estado' => $primer ? date('Y-m-d H:i:s', strtotime($primer)) : null,
                'fecha_ultimo_estado' => $ultimo ? date('Y-m-d H:i:s', strtotime($ultimo)) : null,
                'ciudad_origen' => $origen,
                'ciudad_destino' => $destino,
                'motivo_devolucion' => $motivo ? substr($motivo, 0, 255) : null,
                'raw_json' => json_encode($g, JSON_UNESCAPED_UNICODE),
                'consultada_at' => $ahora,
            );

            $ex = $this->db->query("SELECT id FROM guide_recovery WHERE numero_guia = ?", array($num))->row();
            if ($ex) $this->db->update('guide_recovery', $fila, array('id' => $ex->id));
            else $this->db->insert('guide_recovery', $fila);

            $out[] = array(
                'guia' => $num,
                'estado' => $fila['estado_actual'],
                'fecha_primer' => $fila['fecha_primer_estado'],
                'fecha_ultimo' => $fila['fecha_ultimo_estado'],
                'origen' => $origen,
                'destino' => $destino,
                'motivo' => $fila['motivo_devolucion'],
                'consultada_at' => $ahora,
            );
        }

        // Las pedidas que el API no devolvió también se marcan consultadas
        // (sin estado) para que el barrido no se atasque en ellas.
        $vistas = array_map(function ($o) { return $o['guia']; }, $out);
        foreach ($guias as $num) {
            if (in_array($num, $vistas, true)) continue;
            $ex = $this->db->query("SELECT id FROM guide_recovery WHERE numero_guia = ?", array($num))->row();
            $fila = array('numero_guia' => $num, 'estado_actual' => 'SIN RESPUESTA', 'consultada_at' => $ahora);
            if ($ex) $this->db->update('guide_recovery', $fila, array('id' => $ex->id));
            else $this->db->insert('guide_recovery', $fila);
            $out[] = array('guia' => $num, 'estado' => 'SIN RESPUESTA', 'consultada_at' => $ahora,
                'fecha_primer' => null, 'fecha_ultimo' => null, 'origen' => null, 'destino' => null, 'motivo' => null);
        }

        echo json_encode(array('success' => true, 'data' => $out), JSON_UNESCAPED_UNICODE);
    }
}
