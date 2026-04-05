<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Shipping_model extends CI_Model {

    /**
     * Lista de envíos con filtros para el dashboard
     */
    public function getShipments($store = -1, $status = 'all', $from = null, $to = null, $search = '', $page = 1, $limit = 25) {
        $this->db->select('sg.*, i.clientId, c.name as client_name, c.cellphone as client_phone, c.city as client_city, s.name as store_name');
        $this->db->from('shipping_guides sg');
        $this->db->join('invoices i', 'i.idInvoice = sg.invoiceId', 'left');
        $this->db->join('clients c', 'c.idClient = i.clientId', 'left');
        $this->db->join('stores s', 's.idStore = sg.storeId', 'left');

        if ($store != -1) $this->db->where('sg.storeId', $store);
        if ($status != 'all') $this->db->where('sg.status', $status);
        if ($from) $this->db->where('sg.created_at >=', $from . ' 00:00:00');
        if ($to) $this->db->where('sg.created_at <=', $to . ' 23:59:59');
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('sg.numeroPreenvio', $search);
            $this->db->or_like('c.name', $search);
            $this->db->or_like('sg.ciudadDestinoNombre', $search);
            $this->db->group_end();
        }

        $this->db->order_by('sg.created_at', 'DESC');
        $this->db->limit($limit, ($page - 1) * $limit);
        return $this->db->get()->result();
    }

    /**
     * Contar envíos (para paginación)
     */
    public function countShipments($store = -1, $status = 'all', $from = null, $to = null, $search = '') {
        $this->db->from('shipping_guides sg');
        $this->db->join('invoices i', 'i.idInvoice = sg.invoiceId', 'left');
        $this->db->join('clients c', 'c.idClient = i.clientId', 'left');

        if ($store != -1) $this->db->where('sg.storeId', $store);
        if ($status != 'all') $this->db->where('sg.status', $status);
        if ($from) $this->db->where('sg.created_at >=', $from . ' 00:00:00');
        if ($to) $this->db->where('sg.created_at <=', $to . ' 23:59:59');
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('sg.numeroPreenvio', $search);
            $this->db->or_like('c.name', $search);
            $this->db->group_end();
        }

        return $this->db->count_all_results();
    }

    /**
     * Obtener guía por ID
     */
    public function getShipment($id) {
        $this->db->select('sg.*, i.clientId, i.total as invoice_total, i.date as invoice_date,
            c.name as client_name, c.cellphone as client_phone, c.city as client_city, c.address as client_address, c.idNum as client_doc,
            s.name as store_name');
        $this->db->from('shipping_guides sg');
        $this->db->join('invoices i', 'i.idInvoice = sg.invoiceId', 'left');
        $this->db->join('clients c', 'c.idClient = i.clientId', 'left');
        $this->db->join('stores s', 's.idStore = sg.storeId', 'left');
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

        // Obtener estado anterior para detectar cambio
        $guide = $this->db->where('id', $id)->get('shipping_guides')->row();
        $previousStatus = $guide ? $guide->estadoGuia : null;

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
        if ($statusCode == 15) {
            $data['status'] = 'anulado';
        }
        if (in_array($statusCode, array(2, 3, 4, 18))) {
            $data['status'] = 'en_transito';
        }
        if (in_array($statusCode, array(6, 31))) {
            $data['status'] = 'en_reparto';
        }
        if (in_array($statusCode, array(7, 8, 10))) {
            $data['status'] = 'novedad';
        }

        $this->db->where('id', $id);
        $result = $this->db->update('shipping_guides', $data);

        // Si el estado cambió, notificar al cliente por WhatsApp
        if ($result && $guide && $previousStatus != $statusCode) {
            $this->_notifyStatusChange($guide, $statusCode, $statusName);
        }

        return $result;
    }

    /**
     * Enviar WhatsApp al cliente cuando cambia el estado de la guía
     */
    private function _notifyStatusChange($guide, $statusCode, $statusName)
    {
        try {
            // Obtener datos del cliente desde la factura
            $row = $this->db->select('c.name, c.cellphone, c.idNum, i.total')
                ->from('invoices i')
                ->join('budgets b', 'b.idBudget = i.budgetId')
                ->join('clients c', 'c.idClient = b.clientId')
                ->where('i.idInvoice', $guide->invoiceId)
                ->get()->row();

            if (!$row || empty($row->cellphone)) return;

            // Formatear celular
            $phone = preg_replace('/\D/', '', $row->cellphone);
            if (substr($phone, 0, 2) !== '57' && substr($phone, 0, 1) === '3') {
                $phone = '57' . $phone;
            }

            // Mensajes según estado
            $statusMessages = array(
                2  => 'tu pedido ya fue recibido y esta en camino',
                3  => 'tu pedido esta en transito hacia tu ciudad',
                4  => 'tu pedido esta en transito',
                6  => 'tu pedido esta en reparto y sera entregado hoy',
                7  => 'hay una novedad con tu envio, por favor comunicate con nosotros',
                8  => 'hay una novedad con tu envio',
                10 => 'hay una novedad con tu envio, estamos gestionando la solucion',
                11 => 'tu pedido fue entregado exitosamente! Gracias por tu compra',
                15 => 'tu envio ha sido cancelado, por favor comunicate con nosotros',
                18 => 'tu pedido esta en camino',
                31 => 'tu pedido esta disponible para recoger en la oficina de Interrapidisimo',
            );

            $statusMsg = isset($statusMessages[$statusCode]) ? $statusMessages[$statusCode] : 'el estado de tu envio ha cambiado a: ' . $statusName;
            $nombre = explode(' ', trim($row->name))[0]; // Primer nombre

            $mensaje = "Hola " . $nombre . "!\n\n"
                . "Te informamos que " . $statusMsg . ".\n\n"
                . "Numero de guia: " . $guide->numeroPreenvio . "\n"
                . "Estado: " . $statusName . "\n";

            if ($guide->ciudadDestinoNombre) {
                $mensaje .= "Destino: " . $guide->ciudadDestinoNombre . "\n";
            }

            $mensaje .= "\nRastrear envio: https://www.interrapidisimo.com/rastreo/\n\n"
                . "Ledxury - Iluminacion LED";

            // Buscar bot configurado para enviar
            $this->load->library('builderbot_lib');
            $this->load->model('builderbot_model');
            $configs = $this->builderbot_model->getConfigs();

            foreach ($configs as $cfg) {
                $sendResult = $this->builderbot_lib->sendMessage($cfg, $phone, $mensaje);
                if ($sendResult['success']) {
                    log_message('info', "WhatsApp tracking enviado a {$phone} - Guia {$guide->numeroPreenvio} estado {$statusCode}");
                    return;
                }
            }
        } catch (Exception $e) {
            log_message('error', 'Error notificando cambio de estado guia: ' . $e->getMessage());
        }
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
