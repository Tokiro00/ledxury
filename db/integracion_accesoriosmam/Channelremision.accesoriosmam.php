<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Channelremision — emisión de remisiones a canales internos (MAM-Online,
 * Dropshipping). Sale inventario al costo y se factura al canal a precio
 * = costo + margen (default 10%, editable por línea): genera cartera
 * (132510) + margen (417005), NO venta (413506).
 *
 * Creación tipo factura/presupuesto: buscar artículo + cantidad + precio
 * autocalculado (costo × (1+margen%)) y editable.
 *
 * Ruta: /sisvent/admin/channelremision
 */
class Channelremision extends CI_Controller
{
    const DEFAULT_MARGIN = 10; // %

    // Puente a Ledxury (v2026-08-19): al guardar una remisión del canal
    // MAM-Online se avisa al importador de Ledxury, que la crea allá como
    // factura de proveedor "En Tránsito" (por recibir). La llave debe
    // coincidir con config/mamsync.php en Ledxury.
    const LEDXURY_CHANNEL_CLIENT = 3377;
    const LEDXURY_NOTIFY_URL = 'https://ledxury.com/cronmamsync/run?key=mamsync_cron_2026';

    public function __construct()
    {
        parent::__construct();
        // v2026-08-18: de roles hardcoded [1,2,5,9] a la matriz de permisos —
        // el acceso se administra desde Roles sin tocar codigo.
        $this->backend_lib->controlModule('canal_interno');
        $this->load->library('accounting_lib');
        $this->load->model('stores_model');
    }

    public function index()
    {
        $channels = $this->db->select('idClient, name')
            ->where('canal_interno', 1)->where('deleted', 0)
            ->order_by('name')->get('clients')->result();

        // Filtros de búsqueda (GET). Vacíos = sin filtro.
        $fFrom  = $this->input->get('from');
        $fTo    = $this->input->get('to');
        $fChan  = $this->input->get('channel');
        $fStore = $this->input->get('store');

        $this->db->select('cr.*, c.name AS channel_name, s.name AS store_name, '
                . '(SELECT COUNT(*) FROM channel_remision_items ci WHERE ci.remision_id = cr.id) AS n_items, '
                . '(SELECT GROUP_CONCAT(ci.product_id ORDER BY ci.id SEPARATOR ", ") FROM channel_remision_items ci WHERE ci.remision_id = cr.id) AS products_list', false)
            ->from('channel_remisions cr')
            ->join('clients c', 'c.idClient = cr.channel_client_id', 'left')
            ->join('stores s', 's.idStore = cr.origin_store', 'left')
            ->where('cr.deleted', 0);
        if (!empty($fFrom))  $this->db->where('DATE(cr.created_at) >=', $fFrom);
        if (!empty($fTo))    $this->db->where('DATE(cr.created_at) <=', $fTo);
        if (!empty($fChan))  $this->db->where('cr.channel_client_id', (int)$fChan);
        if (!empty($fStore)) $this->db->where('cr.origin_store', (int)$fStore);
        $recent = $this->db->order_by('cr.id', 'DESC')->limit(500)->get()->result();

        $this->load->view('sisvent/admin/channelremision/index', [
            'role'          => $this->session->userdata('user_data')['role'],
            'channels'      => $channels,
            'stores'        => $this->stores_model->getStores(),
            'recent'        => $recent,
            'defaultMargin' => self::DEFAULT_MARGIN,
            'filters'       => ['from' => $fFrom, 'to' => $fTo, 'channel' => $fChan, 'store' => $fStore],
        ]);
    }

    /** AJAX: busca productos por código/descripción + stock y costo en la bodega. */
    public function searchProducts()
    {
        header('Content-Type: application/json');
        $term  = trim((string) $this->input->get('q'));
        $store = (int) $this->input->get('store');
        if (mb_strlen($term) < 2) { echo json_encode([]); return; }

        $esc = $this->db->escape_like_str($term);
        $rows = $this->db->query("
            SELECT p.idProduct, p.description, COALESCE(p.cost_cop,0) AS cost_cop,
                   COALESCE(i.stock,0) AS stock
            FROM products p
            LEFT JOIN inventory i ON i.idProduct = p.idProduct AND i.idStore = ?
            WHERE p.deleted = 0
              AND (p.idProduct LIKE '%{$esc}%' OR LOWER(p.description) LIKE LOWER('%{$esc}%'))
            ORDER BY p.idProduct ASC
            LIMIT 20
        ", [$store])->result();

        echo json_encode(array_map(function($r) {
            return [
                'code'  => $r->idProduct,
                'desc'  => $r->description,
                'cost'  => (float) $r->cost_cop,
                'stock' => (int) $r->stock,
            ];
        }, $rows));
    }

    public function store()
    {
        $this->outh_model->CSRFVerify();
        if ($this->input->method() !== 'post') { redirect('sisvent/admin/channelremision'); return; }

        $channel  = (int) $this->input->post('channel_client_id');
        $store    = (int) $this->input->post('origin_store');
        $codes    = $this->input->post('product');   // arrays paralelos
        $qtys     = $this->input->post('quantity');
        $prices   = $this->input->post('price');
        $uname    = $this->session->userdata('user_data')['uname'];

        $ch = $this->db->where('idClient', $channel)->where('canal_interno', 1)->get('clients')->row();
        if (!$ch || $store <= 0 || empty($codes)) {
            $this->session->set_flashdata('error', 'Faltan datos (canal, bodega o líneas).');
            redirect('sisvent/admin/channelremision'); return;
        }

        // Validar + armar líneas
        $items = []; $errors = [];
        for ($i = 0; $i < count($codes); $i++) {
            $code = trim((string) $codes[$i]);
            $qty  = (int) ($qtys[$i] ?? 0);
            $price = (float) ($prices[$i] ?? 0);
            if ($code === '') continue;
            if ($qty <= 0) { $errors[] = "Cantidad inválida en $code"; continue; }
            $prod = $this->db->select('idProduct, cost_cop')->where('idProduct', $code)->where('deleted', 0)->get('products')->row();
            if (!$prod) { $errors[] = "Producto no existe: $code"; continue; }
            $cost = (float) $prod->cost_cop;
            if ($cost <= 0) { $errors[] = "Producto sin costo: $code"; continue; }
            if ($price <= 0) $price = round($cost * (1 + self::DEFAULT_MARGIN / 100));
            $items[] = ['code' => $prod->idProduct, 'qty' => $qty, 'unit_cost' => $cost, 'unit_price' => $price];
        }
        if (!empty($errors)) { $this->session->set_flashdata('error', implode(' · ', array_slice($errors, 0, 10))); redirect('sisvent/admin/channelremision'); return; }
        if (empty($items)) { $this->session->set_flashdata('error', 'No hay líneas válidas.'); redirect('sisvent/admin/channelremision'); return; }

        $totalCost = 0.0; $totalAr = 0.0;
        foreach ($items as $it) { $totalCost += $it['qty'] * $it['unit_cost']; $totalAr += $it['qty'] * $it['unit_price']; }
        $totalMargin = $totalAr - $totalCost;
        $marginPct   = $totalCost > 0 ? ($totalMargin / $totalCost) : 0;

        $date = $this->input->post('date');
        $date = $date ? substr((string)$date, 0, 10) : date('Y-m-d');
        $createdAt = $date . ' ' . date('H:i:s');

        $this->db->trans_start();
        $this->db->insert('channel_remisions', [
            'channel_client_id' => $channel, 'origin_store' => $store,
            'total_cost' => round($totalCost), 'margin_pct' => round($marginPct, 4),
            'total_ar' => round($totalAr), 'total_margin' => round($totalMargin),
            'comments' => trim((string) $this->input->post('comments')) ?: null,
            'created_by' => $uname, 'created_at' => $createdAt, 'deleted' => 0,
        ]);
        $remId = (int) $this->db->insert_id();

        foreach ($items as $it) {
            $this->db->insert('channel_remision_items', [
                'remision_id' => $remId, 'product_id' => $it['code'], 'qty' => $it['qty'],
                'unit_cost' => round($it['unit_cost']), 'unit_price' => round($it['unit_price']),
            ]);
            $this->db->where('idStore', $store)->where('idProduct', $it['code'])
                     ->set('stock', 'stock - ' . (int) $it['qty'], false)->update('inventory');
        }
        $this->db->trans_complete();

        // Asiento: cartera 132510 + margen 417005 (margen efectivo según precios), sin venta. Fecha = la elegida.
        $ok = $this->accounting_lib->recordInternalChannelRemision($remId, $channel, $store, $totalCost, $uname, $marginPct, $date);
        if (!$ok) {
            $this->session->set_flashdata('error', 'Remisión #' . $remId . ' guardada pero el asiento contable falló (revisar 132510/143501/417005).');
        } else {
            $this->session->set_flashdata('ok', 'Remisión #' . $remId . ' a ' . $ch->name . ': cartera $' . number_format($totalAr, 0, ',', '.') . ' (costo $' . number_format($totalCost, 0, ',', '.') . ' + margen $' . number_format($totalMargin, 0, ',', '.') . ').');
        }

        // Puente: en Ledxury esta remisión aparece como factura por recibir.
        // A la deriva y con tiempo corto: si Ledxury no responde, la remisión
        // queda igual y el importador la recoge en el próximo aviso.
        if ($channel === self::LEDXURY_CHANNEL_CLIENT) {
            $this->_notifyLedxury();
        }
        redirect('sisvent/admin/channelremision');
    }

    /** Dispara el importador de Ledxury (fire-and-forget; nunca rompe el flujo). */
    private function _notifyLedxury()
    {
        try {
            $ch = curl_init(self::LEDXURY_NOTIFY_URL);
            curl_setopt_array($ch, array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 3,
                CURLOPT_TIMEOUT        => 10,
            ));
            $out  = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            log_message('info', 'Channelremision: aviso a Ledxury HTTP ' . $code . ' — ' . substr((string) $out, 0, 150));
        } catch (\Throwable $e) {
            log_message('error', 'Channelremision: aviso a Ledxury falló: ' . $e->getMessage());
        }
    }

    /** Detalle de una remisión (solo lectura, estilo factura). */
    public function view($id)
    {
        $r = $this->_getRemision($id);
        if (!$r) { $this->session->set_flashdata('error', 'Remisión no encontrada.'); redirect('sisvent/admin/channelremision'); return; }
        $this->load->view('sisvent/admin/channelremision/view', [
            'role'  => $this->session->userdata('user_data')['role'],
            'r'     => $r,
            'items' => $this->_getItems($id),
        ]);
    }

    /** Descarga la remisión como XLSX (tipo factura: cabecera + líneas + totales). */
    public function excel($id)
    {
        $r = $this->_getRemision($id);
        if (!$r) { $this->session->set_flashdata('error', 'Remisión no encontrada.'); redirect('sisvent/admin/channelremision'); return; }
        $items = $this->_getItems($id);

        $marginPct = round(((float)$r->margin_pct) * 100, 2);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Remision RC-' . str_pad((string)$r->id, 6, '0', STR_PAD_LEFT));

        $sheet->setCellValue('A1', 'REMISIÓN A CANAL INTERNO  ·  RC-' . str_pad((string)$r->id, 6, '0', STR_PAD_LEFT));
        $sheet->mergeCells('A1:F1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $info = array(
            array('Canal', (string)$r->channel_name),
            array('Bodega origen', (string)$r->store_name),
            array('Fecha', substr((string)$r->created_at, 0, 10)),
            array('Margen %', number_format($marginPct, 2, ',', '.') . ' %'),
            array('Creado por', (string)$r->created_by),
        );
        if (!empty($r->comments)) { $info[] = array('Comentario', (string)$r->comments); }
        $row = 3;
        foreach ($info as $line) {
            $sheet->setCellValue('A' . $row, $line[0]);
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $sheet->setCellValue('B' . $row, $line[1]);
            $row++;
        }

        $row += 1;
        $headerRow = $row;
        $cols = array('A' => 'Código', 'B' => 'Descripción', 'C' => 'Cantidad', 'D' => 'Costo Unit.', 'E' => 'Precio (costo+margen)', 'F' => 'Subtotal');
        foreach ($cols as $col => $label) { $sheet->setCellValue($col . $row, $label); }
        $sheet->getStyle("A{$row}:F{$row}")->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle("A{$row}:F{$row}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('2B3164');
        $row++;

        $grandQty = 0; $grandCost = 0; $grandAr = 0;
        foreach ($items as $it) {
            $qty = (int)$it->qty;
            $subtotal = $qty * (float)$it->unit_price;
            $sheet->setCellValue('A' . $row, $it->product_id);
            $sheet->setCellValue('B' . $row, (string)$it->description);
            $sheet->setCellValue('C' . $row, $qty);
            $sheet->setCellValue('D' . $row, round((float)$it->unit_cost));
            $sheet->setCellValue('E' . $row, round((float)$it->unit_price));
            $sheet->setCellValue('F' . $row, round($subtotal));
            $grandQty  += $qty;
            $grandCost += $qty * (float)$it->unit_cost;
            $grandAr   += $subtotal;
            $row++;
        }

        $sheet->setCellValue('B' . $row, 'TOTAL CARTERA');
        $sheet->setCellValue('C' . $row, $grandQty);
        $sheet->setCellValue('F' . $row, round($grandAr));
        $sheet->getStyle("A{$row}:F{$row}")->getFont()->setBold(true);
        $row++;
        $sheet->setCellValue('E' . $row, 'Costo');
        $sheet->setCellValue('F' . $row, round($grandCost));
        $row++;
        $sheet->setCellValue('E' . $row, 'Margen');
        $sheet->setCellValue('F' . $row, round($grandAr - $grandCost));

        $sheet->getStyle("D{$headerRow}:F{$row}")->getNumberFormat()->setFormatCode('#,##0');
        foreach (array('A' => 16, 'B' => 48, 'C' => 10, 'D' => 14, 'E' => 20, 'F' => 16) as $col => $w) {
            $sheet->getColumnDimension($col)->setWidth($w);
        }

        $filename = 'remision_canal_RC-' . str_pad((string)$r->id, 6, '0', STR_PAD_LEFT) . '.xlsx';
        if (ob_get_length()) { ob_end_clean(); }
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /** Formulario de edición (igual que crear, precargado). */
    public function edit($id)
    {
        $r = $this->_getRemision($id);
        if (!$r) { $this->session->set_flashdata('error', 'Remisión no encontrada.'); redirect('sisvent/admin/channelremision'); return; }
        // Items con stock actual en la bodega origen (para mostrar disponible).
        $items = $this->db->query("
            SELECT ci.*, p.description, COALESCE(inv.stock,0) AS stock
            FROM channel_remision_items ci
            LEFT JOIN products p ON p.idProduct = ci.product_id
            LEFT JOIN inventory inv ON inv.idProduct = ci.product_id AND inv.idStore = ?
            WHERE ci.remision_id = ?
        ", [(int)$r->origin_store, (int)$id])->result();

        $channels = $this->db->select('idClient, name')->where('canal_interno', 1)->where('deleted', 0)->order_by('name')->get('clients')->result();
        $this->load->view('sisvent/admin/channelremision/edit', [
            'role'          => $this->session->userdata('user_data')['role'],
            'r'             => $r,
            'items'         => $items,
            'channels'      => $channels,
            'stores'        => $this->stores_model->getStores(),
            'defaultMargin' => self::DEFAULT_MARGIN,
        ]);
    }

    /** Guarda la edición: revierte inventario+asiento viejos y reaplica. */
    public function update()
    {
        $this->outh_model->CSRFVerify();
        if ($this->input->method() !== 'post') { redirect('sisvent/admin/channelremision'); return; }

        $id  = (int) $this->input->post('remision_id');
        $old = $this->_getRemision($id);
        if (!$old) { $this->session->set_flashdata('error', 'Remisión no encontrada.'); redirect('sisvent/admin/channelremision'); return; }

        $channel = (int) $this->input->post('channel_client_id');
        $store   = (int) $this->input->post('origin_store');
        $uname   = $this->session->userdata('user_data')['uname'];

        $ch = $this->db->where('idClient', $channel)->where('canal_interno', 1)->get('clients')->row();
        if (!$ch || $store <= 0) { $this->session->set_flashdata('error', 'Faltan datos (canal o bodega).'); redirect('sisvent/admin/channelremision/edit/' . $id); return; }

        list($items, $errors) = $this->_buildItems($this->input->post('product'), $this->input->post('quantity'), $this->input->post('price'));
        if (!empty($errors)) { $this->session->set_flashdata('error', implode(' · ', array_slice($errors, 0, 10))); redirect('sisvent/admin/channelremision/edit/' . $id); return; }
        if (empty($items)) { $this->session->set_flashdata('error', 'No hay líneas válidas.'); redirect('sisvent/admin/channelremision/edit/' . $id); return; }

        $totalCost = 0.0; $totalAr = 0.0;
        foreach ($items as $it) { $totalCost += $it['qty'] * $it['unit_cost']; $totalAr += $it['qty'] * $it['unit_price']; }
        $totalMargin = $totalAr - $totalCost;
        $marginPct   = $totalCost > 0 ? ($totalMargin / $totalCost) : 0;

        $date = $this->input->post('date');
        $date = $date ? substr((string)$date, 0, 10) : substr((string)$old->created_at, 0, 10);
        $createdAt = $date . ' ' . date('H:i:s');
        $oldDate = substr((string)$old->created_at, 0, 10);

        $oldItems = $this->_getItems($id);

        $this->db->trans_start();
        // 1. Restituir inventario a la bodega VIEJA.
        foreach ($oldItems as $oi) {
            $this->db->where('idStore', (int)$old->origin_store)->where('idProduct', $oi->product_id)
                     ->set('stock', 'stock + ' . (int)$oi->qty, false)->update('inventory');
        }
        // 2. Borrar items viejos.
        $this->db->where('remision_id', $id)->delete('channel_remision_items');
        // 3. Insertar nuevos + descontar inventario de la bodega NUEVA.
        foreach ($items as $it) {
            $this->db->insert('channel_remision_items', [
                'remision_id' => $id, 'product_id' => $it['code'], 'qty' => $it['qty'],
                'unit_cost' => round($it['unit_cost']), 'unit_price' => round($it['unit_price']),
            ]);
            $this->db->where('idStore', $store)->where('idProduct', $it['code'])
                     ->set('stock', 'stock - ' . (int)$it['qty'], false)->update('inventory');
        }
        // 4. Actualizar cabecera (incluye fecha).
        $this->db->where('id', $id)->update('channel_remisions', [
            'channel_client_id' => $channel, 'origin_store' => $store,
            'total_cost' => round($totalCost), 'margin_pct' => round($marginPct, 4),
            'total_ar' => round($totalAr), 'total_margin' => round($totalMargin),
            'comments' => trim((string) $this->input->post('comments')) ?: null,
            'created_at' => $createdAt,
        ]);
        $this->db->trans_complete();

        // 5. Reversar asiento viejo (fecha original) + re-postear con la fecha nueva.
        $this->accounting_lib->reverseInternalChannelRemision($id, (int)$old->channel_client_id, (int)$old->origin_store, (float)$old->total_cost, $uname, (float)$old->margin_pct, $oldDate);
        $ok = $this->accounting_lib->recordInternalChannelRemision($id, $channel, $store, $totalCost, $uname, $marginPct, $date);

        if (!$ok) { $this->session->set_flashdata('error', 'Remisión #' . $id . ' actualizada pero el asiento falló (revisar 132510/143501/417005).'); }
        else { $this->session->set_flashdata('ok', 'Remisión #' . $id . ' actualizada. Cartera $' . number_format($totalAr, 0, ',', '.') . '.'); }
        redirect('sisvent/admin/channelremision');
    }

    /** Anula la remisión: revierte inventario + asiento y la marca borrada. */
    public function delete($id)
    {
        $this->outh_model->CSRFVerify();
        if ($this->input->method() !== 'post') { redirect('sisvent/admin/channelremision'); return; }

        $r = $this->_getRemision($id);
        if (!$r) { $this->session->set_flashdata('error', 'Remisión no encontrada.'); redirect('sisvent/admin/channelremision'); return; }
        $uname = $this->session->userdata('user_data')['uname'];
        $items = $this->_getItems($id);

        $this->db->trans_start();
        foreach ($items as $it) {
            $this->db->where('idStore', (int)$r->origin_store)->where('idProduct', $it->product_id)
                     ->set('stock', 'stock + ' . (int)$it->qty, false)->update('inventory');
        }
        $this->db->where('id', (int)$id)->update('channel_remisions', ['deleted' => 1]);
        $this->db->trans_complete();

        $this->accounting_lib->reverseInternalChannelRemision((int)$id, (int)$r->channel_client_id, (int)$r->origin_store, (float)$r->total_cost, $uname, (float)$r->margin_pct);

        $this->session->set_flashdata('ok', 'Remisión #' . (int)$id . ' anulada: inventario y asiento revertidos.');
        redirect('sisvent/admin/channelremision');
    }

    // ========================================================================
    // DEVOLUCIONES DE CANAL — el canal (MAM-Online, Dropshipping) devuelve
    // mercancía a MAM: reingresa inventario a la bodega destino y se acredita
    // la cartera del canal (132510) por costo+margen. Espejo de la remisión.
    // ========================================================================

    /** Listado + formulario de devoluciones de canal. */
    public function returns()
    {
        $channels = $this->db->select('idClient, name')
            ->where('canal_interno', 1)->where('deleted', 0)
            ->order_by('name')->get('clients')->result();

        $fFrom  = $this->input->get('from');
        $fTo    = $this->input->get('to');
        $fChan  = $this->input->get('channel');
        $fStore = $this->input->get('store');

        $this->db->select('cr.*, c.name AS channel_name, s.name AS store_name, '
                . '(SELECT COUNT(*) FROM channel_return_items ci WHERE ci.return_id = cr.id) AS n_items, '
                . '(SELECT GROUP_CONCAT(ci.product_id ORDER BY ci.id SEPARATOR ", ") FROM channel_return_items ci WHERE ci.return_id = cr.id) AS products_list', false)
            ->from('channel_returns cr')
            ->join('clients c', 'c.idClient = cr.channel_client_id', 'left')
            ->join('stores s', 's.idStore = cr.dest_store', 'left')
            ->where('cr.deleted', 0);
        if (!empty($fFrom))  $this->db->where('DATE(cr.created_at) >=', $fFrom);
        if (!empty($fTo))    $this->db->where('DATE(cr.created_at) <=', $fTo);
        if (!empty($fChan))  $this->db->where('cr.channel_client_id', (int)$fChan);
        if (!empty($fStore)) $this->db->where('cr.dest_store', (int)$fStore);
        $recent = $this->db->order_by('cr.id', 'DESC')->limit(500)->get()->result();

        $this->load->view('sisvent/admin/channelremision/returns', [
            'role'          => $this->session->userdata('user_data')['role'],
            'channels'      => $channels,
            'stores'        => $this->stores_model->getStores(),
            'recent'        => $recent,
            'defaultMargin' => self::DEFAULT_MARGIN,
            'filters'       => ['from' => $fFrom, 'to' => $fTo, 'channel' => $fChan, 'store' => $fStore],
        ]);
    }

    /** Registra la devolución: inventario ENTRA + crédito a cartera del canal. */
    public function storeReturn()
    {
        $this->outh_model->CSRFVerify();
        if ($this->input->method() !== 'post') { redirect('sisvent/admin/channelremision/returns'); return; }

        $channel = (int) $this->input->post('channel_client_id');
        $store   = (int) $this->input->post('origin_store'); // el _form usa este name; aquí es bodega DESTINO
        $uname   = $this->session->userdata('user_data')['uname'];

        $ch = $this->db->where('idClient', $channel)->where('canal_interno', 1)->get('clients')->row();
        if (!$ch || $store <= 0) {
            $this->session->set_flashdata('error', 'Faltan datos (canal o bodega).');
            redirect('sisvent/admin/channelremision/returns'); return;
        }

        list($items, $errors) = $this->_buildItems($this->input->post('product'), $this->input->post('quantity'), $this->input->post('price'));
        if (!empty($errors)) { $this->session->set_flashdata('error', implode(' · ', array_slice($errors, 0, 10))); redirect('sisvent/admin/channelremision/returns'); return; }
        if (empty($items)) { $this->session->set_flashdata('error', 'No hay líneas válidas.'); redirect('sisvent/admin/channelremision/returns'); return; }

        $totalCost = 0.0; $totalCr = 0.0;
        foreach ($items as $it) { $totalCost += $it['qty'] * $it['unit_cost']; $totalCr += $it['qty'] * $it['unit_price']; }
        $totalMargin = $totalCr - $totalCost;
        $marginPct   = $totalCost > 0 ? ($totalMargin / $totalCost) : 0;

        $date = $this->input->post('date');
        $date = $date ? substr((string)$date, 0, 10) : date('Y-m-d');
        $createdAt = $date . ' ' . date('H:i:s');

        $this->db->trans_start();
        $this->db->insert('channel_returns', [
            'channel_client_id' => $channel, 'dest_store' => $store,
            'total_cost' => round($totalCost), 'margin_pct' => round($marginPct, 4),
            'total_cr' => round($totalCr), 'total_margin' => round($totalMargin),
            'comments' => trim((string) $this->input->post('comments')) ?: null,
            'created_by' => $uname, 'created_at' => $createdAt, 'deleted' => 0,
        ]);
        $retId = (int) $this->db->insert_id();

        foreach ($items as $it) {
            $this->db->insert('channel_return_items', [
                'return_id' => $retId, 'product_id' => $it['code'], 'qty' => $it['qty'],
                'unit_cost' => round($it['unit_cost']), 'unit_price' => round($it['unit_price']),
            ]);
            // La mercancía REINGRESA (upsert por si el producto no tiene fila en la bodega)
            $exists = $this->db->where('idStore', $store)->where('idProduct', $it['code'])->get('inventory')->row();
            if ($exists) {
                $this->db->where('idStore', $store)->where('idProduct', $it['code'])
                         ->set('stock', 'stock + ' . (int) $it['qty'], false)->update('inventory');
            } else {
                $this->db->insert('inventory', ['idStore' => $store, 'idProduct' => $it['code'], 'stock' => (int) $it['qty']]);
            }
        }
        $this->db->trans_complete();

        $ok = $this->accounting_lib->recordInternalChannelReturn($retId, $channel, $store, $totalCost, $uname, $marginPct, $date);
        if (!$ok) {
            $this->session->set_flashdata('error', 'Devolución #' . $retId . ' guardada pero el asiento contable falló (revisar 132510/143501/417005).');
        } else {
            $this->session->set_flashdata('ok', 'Devolución #' . $retId . ' de ' . $ch->name . ': crédito a cartera $' . number_format($totalCr, 0, ',', '.') . ' (costo $' . number_format($totalCost, 0, ',', '.') . ' + margen $' . number_format($totalMargin, 0, ',', '.') . '). Inventario reingresado.');
        }
        redirect('sisvent/admin/channelremision/returns');
    }

    /** Detalle de una devolución (solo lectura). */
    public function viewReturn($id)
    {
        $r = $this->_getReturn($id);
        if (!$r) { $this->session->set_flashdata('error', 'Devolución no encontrada.'); redirect('sisvent/admin/channelremision/returns'); return; }
        $this->load->view('sisvent/admin/channelremision/return_view', [
            'role'  => $this->session->userdata('user_data')['role'],
            'r'     => $r,
            'items' => $this->_getReturnItems($id),
        ]);
    }

    /** Anula la devolución: saca el inventario reingresado + reversa el asiento. */
    public function deleteReturn($id)
    {
        $this->outh_model->CSRFVerify();
        if ($this->input->method() !== 'post') { redirect('sisvent/admin/channelremision/returns'); return; }

        $r = $this->_getReturn($id);
        if (!$r) { $this->session->set_flashdata('error', 'Devolución no encontrada.'); redirect('sisvent/admin/channelremision/returns'); return; }
        $uname = $this->session->userdata('user_data')['uname'];
        $items = $this->_getReturnItems($id);

        $this->db->trans_start();
        foreach ($items as $it) {
            $this->db->where('idStore', (int)$r->dest_store)->where('idProduct', $it->product_id)
                     ->set('stock', 'stock - ' . (int)$it->qty, false)->update('inventory');
        }
        $this->db->where('id', (int)$id)->update('channel_returns', ['deleted' => 1]);
        $this->db->trans_complete();

        $this->accounting_lib->reverseInternalChannelReturn((int)$id, (int)$r->channel_client_id, (int)$r->dest_store, (float)$r->total_cost, $uname, (float)$r->margin_pct);

        $this->session->set_flashdata('ok', 'Devolución #' . (int)$id . ' anulada: inventario y asiento revertidos.');
        redirect('sisvent/admin/channelremision/returns');
    }

    private function _getReturn($id)
    {
        return $this->db->select('cr.*, c.name AS channel_name, s.name AS store_name')
            ->from('channel_returns cr')
            ->join('clients c', 'c.idClient = cr.channel_client_id', 'left')
            ->join('stores s', 's.idStore = cr.dest_store', 'left')
            ->where('cr.id', (int)$id)->where('cr.deleted', 0)->get()->row();
    }

    private function _getReturnItems($id)
    {
        return $this->db->select('ci.*, p.description')
            ->from('channel_return_items ci')
            ->join('products p', 'p.idProduct = ci.product_id', 'left')
            ->where('ci.return_id', (int)$id)->get()->result();
    }

    // --- helpers ---
    private function _getRemision($id)
    {
        return $this->db->select('cr.*, c.name AS channel_name, s.name AS store_name')
            ->from('channel_remisions cr')
            ->join('clients c', 'c.idClient = cr.channel_client_id', 'left')
            ->join('stores s', 's.idStore = cr.origin_store', 'left')
            ->where('cr.id', (int)$id)->where('cr.deleted', 0)->get()->row();
    }

    private function _getItems($id)
    {
        return $this->db->select('ci.*, p.description')
            ->from('channel_remision_items ci')
            ->join('products p', 'p.idProduct = ci.product_id', 'left')
            ->where('ci.remision_id', (int)$id)->get()->result();
    }

    /** Valida líneas (code/qty/price) y devuelve [items, errors]. Compartido por store/update. */
    private function _buildItems($codes, $qtys, $prices)
    {
        $codes = is_array($codes) ? $codes : [];
        $qtys = is_array($qtys) ? $qtys : [];
        $prices = is_array($prices) ? $prices : [];
        $items = []; $errors = [];
        for ($i = 0; $i < count($codes); $i++) {
            $code = trim((string) $codes[$i]);
            $qty  = (int) ($qtys[$i] ?? 0);
            $price = (float) ($prices[$i] ?? 0);
            if ($code === '') continue;
            if ($qty <= 0) { $errors[] = "Cantidad inválida en $code"; continue; }
            $prod = $this->db->select('idProduct, cost_cop')->where('idProduct', $code)->where('deleted', 0)->get('products')->row();
            if (!$prod) { $errors[] = "Producto no existe: $code"; continue; }
            $cost = (float) $prod->cost_cop;
            if ($cost <= 0) { $errors[] = "Producto sin costo: $code"; continue; }
            if ($price <= 0) $price = round($cost * (1 + self::DEFAULT_MARGIN / 100));
            $items[] = ['code' => $prod->idProduct, 'qty' => $qty, 'unit_cost' => $cost, 'unit_price' => $price];
        }
        return [$items, $errors];
    }
}
