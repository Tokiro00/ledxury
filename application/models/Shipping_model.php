<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Shipping_model extends CI_Model {

    /**
     * Lista de envíos con filtros para el dashboard
     */
    public function getShipments($store = -1, $status = 'all', $from = null, $to = null, $search = '', $page = 1, $limit = 25, $vendor = 'all') {
        $this->db->select('sg.*, i.clientId, i.budgetId, i.vendorId, c.name as client_name, c.idNum as client_doc, c.cellphone as client_phone, c.city as client_city, s.name as store_name, u.name as vendor_name');
        $this->db->from('shipping_guides sg');
        $this->db->join('invoices i', 'i.idInvoice = sg.invoiceId', 'left');
        $this->db->join('clients c', 'c.idClient = i.clientId', 'left');
        $this->db->join('stores s', 's.idStore = sg.storeId', 'left');
        $this->db->join('users u', 'u.idUser = i.vendorId', 'left');

        if ($store != -1) $this->db->where('sg.storeId', $store);
        if ($status != 'all') $this->db->where('sg.status', $status);
        if ($vendor != 'all' && !empty($vendor)) $this->db->where('i.vendorId', $vendor);
        if ($from) $this->db->where('sg.created_at >=', $from . ' 00:00:00');
        if ($to) $this->db->where('sg.created_at <=', $to . ' 23:59:59');
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('sg.numeroPreenvio', $search);
            $this->db->or_like('c.name', $search);
            $this->db->or_like('c.idNum', $search);
            $this->db->or_like('i.budgetId', $search);
            $this->db->or_like('sg.ciudadDestinoNombre', $search);
            $this->db->group_end();
        }

        $this->db->order_by('sg.created_at', 'DESC');
        if ($limit > 0) {
            $this->db->limit($limit, ($page - 1) * $limit);
        }
        return $this->db->get()->result();
    }

    /**
     * Contar envíos (para paginación)
     */
    public function countShipments($store = -1, $status = 'all', $from = null, $to = null, $search = '', $vendor = 'all') {
        $this->db->from('shipping_guides sg');
        $this->db->join('invoices i', 'i.idInvoice = sg.invoiceId', 'left');
        $this->db->join('clients c', 'c.idClient = i.clientId', 'left');

        if ($store != -1) $this->db->where('sg.storeId', $store);
        if ($status != 'all') $this->db->where('sg.status', $status);
        if ($vendor != 'all' && !empty($vendor)) $this->db->where('i.vendorId', $vendor);
        if ($from) $this->db->where('sg.created_at >=', $from . ' 00:00:00');
        if ($to) $this->db->where('sg.created_at <=', $to . ' 23:59:59');
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('sg.numeroPreenvio', $search);
            $this->db->or_like('c.name', $search);
            $this->db->or_like('c.idNum', $search);
            $this->db->or_like('i.budgetId', $search);
            $this->db->group_end();
        }

        return $this->db->count_all_results();
    }

    /**
     * Obtener guía por ID
     */
    public function getShipment($id) {
        $this->db->select('sg.*, i.clientId, i.vendorId, i.budgetId, i.total as invoice_total, i.date as invoice_date,
            c.name as client_name, c.cellphone as client_phone, c.city as client_city, c.address as client_address, c.idNum as client_doc,
            s.name as store_name, u.name as vendor_name');
        $this->db->from('shipping_guides sg');
        $this->db->join('invoices i', 'i.idInvoice = sg.invoiceId', 'left');
        $this->db->join('clients c', 'c.idClient = i.clientId', 'left');
        $this->db->join('stores s', 's.idStore = sg.storeId', 'left');
        $this->db->join('users u', 'u.idUser = i.vendorId', 'left');
        $this->db->where('sg.id', $id);
        return $this->db->get()->row();
    }

    /**
     * Obtener guía por número de preenvío
     */
    public function getByGuideNumber($numero) {
        return $this->db->where('numeroPreenvio', $numero)->get('shipping_guides')->row();
    }

    /**
     * Estadísticas para el dashboard
     */
    public function getStats($store = -1) {
        $where = ($store != -1) ? "AND storeId = {$store}" : '';

        $sql = "SELECT
            COUNT(*) as total,
            SUM(CASE WHEN status = 'creado' THEN 1 ELSE 0 END) as creados,
            SUM(CASE WHEN status IN ('recogida_solicitada') THEN 1 ELSE 0 END) as por_recoger,
            SUM(CASE WHEN estadoGuia IN (2,3,4,18) THEN 1 ELSE 0 END) as en_transito,
            SUM(CASE WHEN estadoGuia IN (6,31) THEN 1 ELSE 0 END) as en_reparto,
            SUM(CASE WHEN estadoGuia = 11 THEN 1 ELSE 0 END) as entregados,
            SUM(CASE WHEN estadoGuia IN (7,8,10) THEN 1 ELSE 0 END) as novedades,
            SUM(CASE WHEN estadoGuia = 15 THEN 1 ELSE 0 END) as anulados,
            SUM(valorTotal) as costo_total,
            SUM(CASE WHEN estadoGuia = 11 THEN valorTotal ELSE 0 END) as costo_entregados,
            SUM(CASE WHEN isContrapago = 1 THEN contrapagoCost ELSE 0 END) as contrapago_total,
            SUM(CASE WHEN isContrapago = 1 AND estadoGuia = 11 THEN contrapagoCost ELSE 0 END) as contrapago_entregado,
            SUM(CASE WHEN isContrapago = 1 AND estadoGuia NOT IN (11,15) THEN contrapagoCost ELSE 0 END) as contrapago_pendiente
        FROM shipping_guides
        WHERE 1=1 {$where}";

        return $this->db->query($sql)->row();
    }

    /**
     * Estadísticas por rango de fechas
     */
    public function getStatsByDate($from, $to, $store = -1) {
        $where = ($store != -1) ? "AND storeId = " . (int)$store : '';
        $sql = "SELECT
            COUNT(*) as total,
            SUM(CASE WHEN estadoGuia = 11 THEN 1 ELSE 0 END) as entregados,
            SUM(CASE WHEN estadoGuia IN (7,8,10) THEN 1 ELSE 0 END) as novedades,
            SUM(valorTotal) as costo_total
        FROM shipping_guides
        WHERE created_at >= ? AND created_at <= ? {$where}";

        return $this->db->query($sql, array($from . ' 00:00:00', $to . ' 23:59:59'))->row();
    }

    /**
     * Estado de cuenta financiero con Interrapidísimo
     * MAM paga: valorTotal de guías no-contrapago
     * Inter paga: contrapagoCost - valorTotal de guías contrapago
     */
    public function getFinancialStats($from, $to, $store = -1) {
        $where = ($store != -1) ? "AND storeId = " . (int)$store : '';
        $sql = "SELECT
            -- Totales generales
            COUNT(*) as total_guias,
            SUM(valorTotal) as total_fletes,

            -- Envío gratis (MAM paga el flete)
            SUM(CASE WHEN isContrapago = 0 THEN 1 ELSE 0 END) as guias_mam_paga,
            SUM(CASE WHEN isContrapago = 0 THEN valorTotal ELSE 0 END) as flete_mam_paga,

            -- Contrapago (cliente paga, Inter cobra y devuelve)
            SUM(CASE WHEN isContrapago = 1 THEN 1 ELSE 0 END) as guias_contrapago,
            SUM(CASE WHEN isContrapago = 1 THEN contrapagoCost ELSE 0 END) as contrapago_cobrado,
            SUM(CASE WHEN isContrapago = 1 THEN valorTotal ELSE 0 END) as flete_contrapago,

            -- Por estado
            SUM(CASE WHEN estadoGuia = 11 THEN valorTotal ELSE 0 END) as flete_entregados,
            SUM(CASE WHEN estadoGuia = 11 AND isContrapago = 1 THEN contrapagoCost ELSE 0 END) as contrapago_entregados,
            SUM(CASE WHEN estadoGuia NOT IN (11, 15) THEN valorTotal ELSE 0 END) as flete_en_curso,
            SUM(CASE WHEN estadoGuia = 15 THEN valorTotal ELSE 0 END) as flete_anulados

        FROM shipping_guides
        WHERE created_at >= ? AND created_at <= ? {$where}";

        return $this->db->query($sql, array($from . ' 00:00:00', $to . ' 23:59:59'))->row();
    }

    /**
     * Detalle de guías para estado de cuenta
     */
    public function getFinancialDetail($from, $to, $store = -1, $tipo = 'all') {
        $this->db->select('sg.*, c.name as client_name');
        $this->db->from('shipping_guides sg');
        $this->db->join('invoices i', 'i.idInvoice = sg.invoiceId', 'left');
        $this->db->join('clients c', 'c.idClient = i.clientId', 'left');

        $this->db->where('sg.created_at >=', $from . ' 00:00:00');
        $this->db->where('sg.created_at <=', $to . ' 23:59:59');
        if ($store != -1) $this->db->where('sg.storeId', $store);
        if ($tipo == 'mam') $this->db->where('sg.isContrapago', 0);
        if ($tipo == 'contrapago') $this->db->where('sg.isContrapago', 1);

        $this->db->order_by('sg.created_at', 'DESC');
        return $this->db->get()->result();
    }

    /**
     * Historial de tracking de una guía
     */
    public function getTrackingEvents($guideId) {
        return $this->db->where('guideId', $guideId)
            ->order_by('eventDate', 'DESC')
            ->get('shipping_tracking_events')->result();
    }

    /**
     * Agregar evento de tracking
     */
    public function addTrackingEvent($data) {
        date_default_timezone_set("America/Bogota");
        $data['created_at'] = date('Y-m-d H:i:s');
        return $this->db->insert('shipping_tracking_events', $data);
    }

    /**
     * Guías activas que necesitan actualización de tracking
     * (no entregadas, no anuladas, última verificación hace más de 30 min)
     */
    public function getActiveForTracking($limit = 15) {
        date_default_timezone_set("America/Bogota");
        $threshold = date('Y-m-d H:i:s', strtotime('-30 minutes'));

        return $this->db->select('id, numeroPreenvio, status, estadoGuia')
            ->from('shipping_guides')
            ->where('estadoGuia !=', 11)  // No entregados
            ->where('estadoGuia !=', 15)  // No anulados
            ->where('numeroPreenvio IS NOT NULL')
            ->where('numeroPreenvio !=', 0)
            ->group_start()
                ->where('lastTrackingCheck IS NULL')
                ->or_where('lastTrackingCheck <', $threshold)
            ->group_end()
            ->order_by('lastTrackingCheck', 'ASC')
            ->limit($limit)
            ->get()->result();
    }

    /**
     * Actualizar estado de una guía
     */
    public function updateStatus($id, $statusCode, $statusName) {
        date_default_timezone_set("America/Bogota");
        $data = array(
            'estadoGuia' => $statusCode,
            'estadoNombre' => $statusName,
            'fechaEstado' => date('Y-m-d H:i:s'),
            'lastTrackingCheck' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        );

        // Si es entregado, registrar fecha
        if ($statusCode == 11) {
            $data['actualDelivery'] = date('Y-m-d H:i:s');
            $data['status'] = 'entregado';
        }
        // Si es anulado
        if ($statusCode == 15) {
            $data['status'] = 'anulado';
        }
        // Si está en tránsito
        if (in_array($statusCode, array(2, 3, 4, 18))) {
            $data['status'] = 'en_transito';
        }
        // Si está en reparto
        if (in_array($statusCode, array(6, 31))) {
            $data['status'] = 'en_reparto';
        }
        // Si tiene novedad
        if (in_array($statusCode, array(7, 8, 10))) {
            $data['status'] = 'novedad';
        }
        // Recogido / en bodega Inter
        if (in_array($statusCode, array(1))) {
            $data['status'] = 'en_transito';
        }
        // Reclame en oficina
        if (in_array($statusCode, array(5))) {
            $data['status'] = 'en_reparto';
        }

        // Fallback por nombre cuando estadoGuia no matchea ningún código conocido.
        // Estados observados en producción 2026-04-29:
        //   "Conciliado" / "Archivada" → estado final positivo (entregado)
        //   "Devuelto" → anulado
        //   "Reenvio" → tránsito (segundo intento)
        if (!isset($data['status']) && !empty($statusName)) {
            $sn = mb_strtolower($statusName);
            if (strpos($sn, 'conciliado') !== false || strpos($sn, 'archivada') !== false || strpos($sn, 'archivado') !== false) {
                $data['status'] = 'entregado';
                $data['actualDelivery'] = date('Y-m-d H:i:s');
            } elseif (strpos($sn, 'devuelt') !== false || strpos($sn, 'no encontrada') !== false) {
                $data['status'] = 'anulado';
            } elseif (strpos($sn, 'reenvio') !== false || strpos($sn, 'reenvío') !== false) {
                $data['status'] = 'en_transito';
            } elseif (strpos($sn, 'transito') !== false || strpos($sn, 'tránsito') !== false || strpos($sn, 'centro acopio') !== false) {
                $data['status'] = 'en_transito';
            } elseif (strpos($sn, 'admitida') !== false || strpos($sn, 'digitalizada') !== false) {
                $data['status'] = 'en_transito';
            } elseif (strpos($sn, 'reparto') !== false || strpos($sn, 'reclame en oficina') !== false) {
                $data['status'] = 'en_reparto';
            }
        }

        $this->db->where('id', $id);
        return $this->db->update('shipping_guides', $data);
    }

    /**
     * Marcar como verificado (sin cambio de estado)
     */
    public function markChecked($id) {
        date_default_timezone_set("America/Bogota");
        $this->db->where('id', $id);
        return $this->db->update('shipping_guides', array('lastTrackingCheck' => date('Y-m-d H:i:s')));
    }

    /**
     * Buscar municipio DANE
     */
    public function searchMunicipality($term, $limit = 10) {
        return $this->db->like('shortName', $term, 'both')
            ->or_like('name', $term, 'both')
            ->limit($limit)
            ->get('dane_municipalities')->result();
    }

    /**
     * Poblar municipios DANE desde array
     */
    public function seedMunicipalities($data) {
        return $this->db->insert_batch('dane_municipalities', $data);
    }

    /**
     * Contar municipios
     */
    public function countMunicipalities() {
        return $this->db->count_all('dane_municipalities');
    }
}
