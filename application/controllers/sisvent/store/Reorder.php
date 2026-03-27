<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Reorder extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->backend_lib->controlModule('compras_reorden');
        $this->load->model('inventory_model');
        $this->load->model('supplierorders_model');
        $this->load->model('productproviders_model');
        $this->load->model('products_model');
        $this->load->model('providers_model');
        $this->load->model('stores_model');
    }

    /**
     * Dashboard ABC — clasificación de productos por tienda
     */
    public function index($storeId = -1, $filterAbc = 'all') {
        if ($storeId == -1) $storeId = $this->input->get('store') ?: -1;
        if ($filterAbc == 'all') $filterAbc = $this->input->get('abc') ?: 'all';

        $products = $this->inventory_model->getAbcData((int)$storeId);

        // Filtrar por tipo ABC si se seleccionó
        if ($filterAbc != 'all') {
            $products = array_filter($products, function($p) use ($filterAbc) {
                return $p->abc_type == $filterAbc;
            });
        }

        // Conteos por tipo
        $counts = array('A' => 0, 'B' => 0, 'C' => 0, 'N' => 0);
        $revenues = array('A' => 0, 'B' => 0, 'C' => 0, 'N' => 0);
        foreach ($products as $p) {
            $type = $p->abc_type ?: 'N';
            $counts[$type]++;
            $revenues[$type] += (float) $p->revenue_12m;
        }

        $data = array(
            'products' => $products,
            'counts' => $counts,
            'revenues' => $revenues,
            'stores' => $this->stores_model->getStores(),
            'selectedStore' => $storeId,
            'filterAbc' => $filterAbc,
            'totalProducts' => count($products)
        );

        $this->load->view('sisvent/store/reorder/abc', $data);
    }

    /**
     * Recalcular ABC — AJAX POST
     */
    public function recalculate() {
        $storeId = $this->input->post('store') ?: -1;
        $count = $this->inventory_model->calculateAndStoreAbc((int)$storeId);
        echo json_encode(array('success' => true, 'count' => $count));
    }

    /**
     * Agente de reorden — sugerencias por tienda
     */
    public function agent($storeId = null) {
        if (!$storeId) $storeId = $this->input->post('store') ?: ($this->input->get('store') ?: null);

        $suggestions = array();

        if ($storeId) {
            $raw = $this->inventory_model->getReorderSuggestions((int)$storeId);
            // Convertir arrays a objetos para la vista
            if (!empty($raw)) {
                foreach ($raw as $providerId => $group) {
                    $items = array();
                    if (isset($group['items']) && is_array($group['items'])) {
                        foreach ($group['items'] as $item) {
                            $items[] = (object) $item;
                        }
                    }
                    $suggestions[$providerId] = array(
                        'name' => isset($group['provider_name']) ? $group['provider_name'] : 'Sin nombre',
                        'items' => $items,
                        'total' => isset($group['total']) ? $group['total'] : 0
                    );
                }
            }
        }

        $data = array(
            'suggestions' => $suggestions,
            'stores' => $this->stores_model->getStores(),
            'selectedStore' => $storeId
        );

        $this->load->view('sisvent/store/reorder/agent', $data);
    }

    /**
     * Generar órdenes de compra desde las sugerencias del agente — POST
     */
    public function generateOrders() {
        $storeId = $this->input->post('storeId');
        $items = $this->input->post('items'); // array de {productId, quantity, unitCost, providerId}

        if (!$storeId || !$items || !is_array($items)) {
            $this->session->set_flashdata('error_reorder', 'Datos incompletos para generar órdenes.');
            redirect('sisvent/store/reorder/agent?store=' . $storeId);
            return;
        }

        // Agrupar items por proveedor
        $byProvider = array();
        foreach ($items as $item) {
            $qty = (int) $item['quantity'];
            if ($qty <= 0) continue;
            $pid = (int) $item['providerId'];
            if (!isset($byProvider[$pid])) $byProvider[$pid] = array();
            $byProvider[$pid][] = $item;
        }

        $ordersCreated = 0;
        $user = $this->session->userdata('user_data')['uname'];

        foreach ($byProvider as $providerId => $providerItems) {
            $orderNumber = $this->supplierorders_model->getNextOrderNumber();
            $total = 0;

            foreach ($providerItems as $item) {
                $total += (float) $item['quantity'] * (float) $item['unitCost'];
            }

            $orderId = $this->supplierorders_model->save(array(
                'orderNumber' => $orderNumber,
                'providerId' => $providerId,
                'storeId' => $storeId,
                'status' => 'borrador',
                'total' => $total,
                'orderDate' => date('Y-m-d'),
                'generatedBy' => 'agente',
                'created_by' => $user
            ));

            $details = array();
            foreach ($providerItems as $item) {
                $qty = (int) $item['quantity'];
                $cost = (float) $item['unitCost'];
                $details[] = array(
                    'orderId' => $orderId,
                    'productId' => $item['productId'],
                    'quantityOrdered' => $qty,
                    'quantityReceived' => 0,
                    'unitCost' => $cost,
                    'subtotal' => $qty * $cost,
                    'status' => 'pendiente'
                );
            }

            $this->supplierorders_model->saveBatch($details);
            $ordersCreated++;
        }

        $this->session->set_flashdata('success_reorder', "{$ordersCreated} orden(es) de compra generada(s) exitosamente.");
        redirect('sisvent/store/reorder/orders?store=' . $storeId);
    }

    /**
     * Lista de órdenes a proveedores
     */
    public function orders() {
        $storeId = $this->input->get('store') ?: -1;
        $status = $this->input->get('status') ?: 'all';
        $page = $this->input->get('page') ?: 1;

        $data = array(
            'orders' => $this->supplierorders_model->getOrders((int)$storeId, -1, $status, (int)$page, 25),
            'stores' => $this->stores_model->getStores(),
            'selectedStore' => $storeId,
            'selectedStatus' => $status,
            'page' => $page
        );

        $this->load->view('sisvent/store/reorder/orders', $data);
    }

    /**
     * Ver detalle de una orden
     */
    public function view($id) {
        $order = $this->supplierorders_model->getOrder($id);
        if (!$order) show_404();

        $data = array(
            'order' => $order,
            'details' => $this->supplierorders_model->getDetails($id)
        );

        $this->load->view('sisvent/store/reorder/view', $data);
    }

    /**
     * Aprobar una orden (borrador → pendiente)
     */
    public function approve($id) {
        $order = $this->supplierorders_model->getOrder($id);
        if (!$order || $order->status != 'borrador') {
            $this->session->set_flashdata('error_reorder', 'La orden no se puede aprobar.');
            redirect('sisvent/store/reorder/orders');
            return;
        }

        $this->supplierorders_model->update($id, array('status' => 'pendiente'));
        $this->session->set_flashdata('success_reorder', "Orden {$order->orderNumber} aprobada.");
        redirect('sisvent/store/reorder/view/' . $id);
    }

    /**
     * Marcar orden como enviada
     */
    public function markSent($id) {
        $expectedDate = $this->input->post('expectedDate');
        $this->supplierorders_model->update($id, array(
            'status' => 'enviada',
            'expectedDate' => $expectedDate ?: null
        ));
        redirect('sisvent/store/reorder/view/' . $id);
    }

    /**
     * Pantalla de recepción de mercancía
     */
    public function receive($id) {
        $order = $this->supplierorders_model->getOrder($id);
        if (!$order) show_404();

        $data = array(
            'order' => $order,
            'details' => $this->supplierorders_model->getDetails($id)
        );

        $this->load->view('sisvent/store/reorder/receive', $data);
    }

    /**
     * Procesar recepción — actualiza stock
     */
    public function processReceive($id) {
        $order = $this->supplierorders_model->getOrder($id);
        if (!$order) show_404();

        $quantities = $this->input->post('qty'); // array: detail_id => quantity_received_now

        if ($quantities && is_array($quantities)) {
            foreach ($quantities as $detailId => $qty) {
                $qty = (int) $qty;
                if ($qty <= 0) continue;

                $detail = $this->supplierorders_model->receiveDetail($detailId, $qty);
                if (!$detail) continue;

                // Aumentar stock en la tienda destino
                $existing = $this->db->where('idStore', $order->storeId)
                    ->where('idProduct', $detail->productId)
                    ->get('inventory')->row();

                if ($existing) {
                    $this->db->set('stock', 'stock + ' . $qty, FALSE);
                    $this->db->where('idStore', $order->storeId);
                    $this->db->where('idProduct', $detail->productId);
                    $this->db->update('inventory');
                } else {
                    $this->db->insert('inventory', array(
                        'idStore' => $order->storeId,
                        'idProduct' => $detail->productId,
                        'stock' => $qty,
                        'counted' => 0
                    ));
                }
            }
        }

        $this->session->set_flashdata('success_reorder', 'Recepción registrada. Stock actualizado.');
        redirect('sisvent/store/reorder/view/' . $id);
    }

    /**
     * Cancelar orden
     */
    public function cancel($id) {
        $this->supplierorders_model->update($id, array('status' => 'cancelada'));
        redirect('sisvent/store/reorder/view/' . $id);
    }

    /**
     * Vista para carga masiva de proveedores
     */
    public function uploadProviders() {
        $data = array(
            'result' => $this->session->flashdata('upload_result'),
            'error' => $this->session->flashdata('upload_error'),
            'thisFile' => 'sisvent/store/reorder/upload_providers',
            'role' => $this->session->userdata('user_data')['role']
        );
        $this->load->view('sisvent/store/reorder/upload_providers', $data);
    }

    /**
     * Descargar plantilla Excel para carga masiva
     */
    public function downloadTemplate() {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Proveedores');

        $sheet->setCellValue('A1', 'Codigo');
        $sheet->setCellValue('B1', 'Proveedor');
        $sheet->getStyle('A1:B1')->getFont()->setBold(true);

        // Ejemplo
        $sheet->setCellValue('A2', 'FW-12V');
        $sheet->setCellValue('B2', 'Nombre o ID del proveedor');

        // Segunda hoja con lista de proveedores para referencia
        $provSheet = $spreadsheet->createSheet();
        $provSheet->setTitle('Lista Proveedores');
        $provSheet->setCellValue('A1', 'ID');
        $provSheet->setCellValue('B1', 'Nombre');
        $provSheet->setCellValue('C1', 'NIT');
        $provSheet->getStyle('A1:C1')->getFont()->setBold(true);

        $providers = $this->providers_model->getProviders();
        $row = 2;
        foreach ($providers as $p) {
            $provSheet->setCellValue('A' . $row, $p->idProvider);
            $provSheet->setCellValue('B' . $row, $p->name);
            $provSheet->setCellValue('C' . $row, isset($p->idNum) ? $p->idNum : '');
            $row++;
        }

        foreach (range('A', 'C') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
            $provSheet->getColumnDimension($col)->setAutoSize(true);
        }

        $spreadsheet->setActiveSheetIndex(0);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="plantilla_proveedores.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * Procesar Excel de carga masiva de proveedores
     */
    public function processProviders() {
        if ($this->input->method() !== 'post') {
            redirect('sisvent/store/reorder/uploadProviders');
            return;
        }

        if (empty($_FILES['file']['name'])) {
            $this->session->set_flashdata('upload_error', 'Seleccione un archivo Excel.');
            redirect('sisvent/store/reorder/uploadProviders');
            return;
        }

        $filePath = $_FILES['file']['tmp_name'];

        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();
        } catch (\Exception $e) {
            $this->session->set_flashdata('upload_error', 'Error al leer el archivo: ' . $e->getMessage());
            redirect('sisvent/store/reorder/uploadProviders');
            return;
        }

        // Cargar mapa de proveedores por nombre (case-insensitive)
        $providers = $this->providers_model->getProviders();
        $provByName = array();
        $provById = array();
        foreach ($providers as $p) {
            $provByName[mb_strtolower(trim($p->name))] = $p->idProvider;
            $provById[$p->idProvider] = $p->name;
        }

        $updated = 0;
        $errors = array();
        $skipped = 0;

        foreach ($rows as $i => $row) {
            if ($i === 0) continue; // Skip header

            $productId = trim($row[0]);
            $providerVal = trim($row[1]);

            if (empty($productId) || empty($providerVal)) {
                $skipped++;
                continue;
            }

            // Verificar que el producto existe
            $product = $this->products_model->getProduct($productId);
            if (!$product) {
                $errors[] = "Fila " . ($i + 1) . ": Producto '{$productId}' no encontrado.";
                continue;
            }

            // Resolver proveedor: por ID numérico o por nombre
            $providerId = null;
            if (is_numeric($providerVal) && isset($provById[(int)$providerVal])) {
                $providerId = (int) $providerVal;
            } else {
                $key = mb_strtolower($providerVal);
                if (isset($provByName[$key])) {
                    $providerId = $provByName[$key];
                }
            }

            if (!$providerId) {
                $errors[] = "Fila " . ($i + 1) . ": Proveedor '{$providerVal}' no encontrado.";
                continue;
            }

            // Actualizar proveedor default del producto
            $this->db->where('idProduct', $productId);
            $this->db->update('products', array('provider' => $providerId));

            // Actualizar o crear en product_providers
            $existing = $this->db->where('productId', $productId)
                ->where('providerId', $providerId)
                ->get('product_providers')->row();

            if ($existing) {
                $this->productproviders_model->setDefault($productId, $providerId);
            } else {
                // Quitar default anterior
                $this->db->where('productId', $productId);
                $this->db->update('product_providers', array('isDefault' => 0));
                // Crear nuevo
                $this->productproviders_model->save(array(
                    'productId' => $productId,
                    'providerId' => $providerId,
                    'cost' => $product->cost_cop ?: 0,
                    'isDefault' => 1,
                    'priority' => 1
                ));
            }

            $updated++;
        }

        $result = "{$updated} producto(s) actualizado(s).";
        if ($skipped > 0) $result .= " {$skipped} fila(s) vacías omitidas.";
        if (!empty($errors)) $result .= " " . count($errors) . " error(es).";

        $this->session->set_flashdata('upload_result', array(
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors,
            'message' => $result
        ));
        redirect('sisvent/store/reorder/uploadProviders');
    }

    /** Exportar sugerencias a Excel */
    public function exportExcel($storeId = 1) {
        $months = $this->input->get('months') ?: null;
        $mA = $this->input->get('mA') ?: null;
        $mB = $this->input->get('mB') ?: null;
        $mC = $this->input->get('mC') ?: null;
        $monthsPerAbc = ($mA || $mB || $mC) ? array('A' => $mA, 'B' => $mB, 'C' => $mC) : null;
        $raw = $this->inventory_model->getReorderSuggestions((int)$storeId, $months ? (int)$months : null, $monthsPerAbc);
        $store = $this->stores_model->getStore($storeId);
        $storeName = $store ? $store->name : 'Tienda ' . $storeId;

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Sugerencias Reorden');

        // Header
        $sheet->setCellValue('A1', 'Sugerencias de Reorden - ' . $storeName);
        $sheet->setCellValue('A2', 'Generado: ' . date('Y-m-d H:i'));
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $row = 4;
        foreach ($raw as $providerId => $group) {
            $sheet->setCellValue('A' . $row, 'PROVEEDOR: ' . $group['provider_name']);
            $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(11);
            $row++;

            $headers = ['Codigo', 'Descripcion', 'ABC', 'Demanda/Mes', 'Objetivo', 'Stock', 'Transito', 'Pedir', 'Costo Unit', 'Subtotal'];
            foreach ($headers as $col => $h) {
                $sheet->setCellValueByColumnAndRow($col + 1, $row, $h);
            }
            $sheet->getStyle('A' . $row . ':J' . $row)->getFont()->setBold(true);
            $row++;

            foreach ($group['items'] as $item) {
                $sheet->setCellValue('A' . $row, $item['productId']);
                $sheet->setCellValue('B' . $row, $item['description']);
                $sheet->setCellValue('C' . $row, $item['abc_type']);
                $sheet->setCellValue('D' . $row, $item['demand_monthly']);
                $sheet->setCellValue('E' . $row, $item['stock_target']);
                $sheet->setCellValue('F' . $row, $item['stock_actual']);
                $sheet->setCellValue('G' . $row, $item['in_transit']);
                $sheet->setCellValue('H' . $row, $item['need']);
                $sheet->setCellValue('I' . $row, $item['unit_cost']);
                $sheet->setCellValue('J' . $row, $item['subtotal']);
                $row++;
            }

            $sheet->setCellValue('I' . $row, 'Total:');
            $sheet->setCellValue('J' . $row, $group['total']);
            $sheet->getStyle('I' . $row . ':J' . $row)->getFont()->setBold(true);
            $row += 2;
        }

        // Autosize columns
        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Format currency columns
        $sheet->getStyle('I5:J' . $row)->getNumberFormat()->setFormatCode('#,##0');

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="reorden_' . $storeName . '_' . date('Y-m-d') . '.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /** API JSON para sugerencias de reorden */
    public function debug($storeId = 1) {
        header('Content-Type: application/json');
        $months = $this->input->get('months') ?: null;
        $mA = $this->input->get('mA') ?: null;
        $mB = $this->input->get('mB') ?: null;
        $mC = $this->input->get('mC') ?: null;
        $monthsPerAbc = ($mA || $mB || $mC) ? array('A' => $mA, 'B' => $mB, 'C' => $mC) : null;
        $raw = $this->inventory_model->getReorderSuggestions((int)$storeId, $months ? (int)$months : null, $monthsPerAbc);

        $totalItems = 0;
        $totalCost = 0;
        $providerSummary = array();
        foreach ($raw as $pid => $group) {
            $itemCount = count($group['items']);
            $totalItems += $itemCount;
            $totalCost += $group['total'];
            $providerSummary[] = array(
                'id' => $pid,
                'name' => $group['provider_name'],
                'nit' => isset($group['provider_nit']) ? $group['provider_nit'] : '',
                'phone' => isset($group['provider_phone']) ? $group['provider_phone'] : '',
                'email' => isset($group['provider_email']) ? $group['provider_email'] : '',
                'items_count' => $itemCount,
                'total' => $group['total']
            );
        }

        // Ordenar proveedores por total descendente
        usort($providerSummary, function($a, $b) { return $b['total'] - $a['total']; });

        echo json_encode(array(
            'store' => $storeId,
            'months' => $months,
            'providers' => count($raw),
            'totalItems' => $totalItems,
            'totalCost' => $totalCost,
            'providerSummary' => $providerSummary,
            'data' => $raw
        ));
        exit;
    }

    /**
     * Exportar Excel de un solo proveedor
     */
    public function exportExcelProvider($storeId, $providerId) {
        $months = $this->input->get('months') ?: null;
        $mA = $this->input->get('mA') ?: null;
        $mB = $this->input->get('mB') ?: null;
        $mC = $this->input->get('mC') ?: null;
        $monthsPerAbc = ($mA || $mB || $mC) ? array('A' => $mA, 'B' => $mB, 'C' => $mC) : null;
        $raw = $this->inventory_model->getReorderSuggestions((int)$storeId, $months ? (int)$months : null, $monthsPerAbc);

        if (!isset($raw[$providerId])) {
            show_404();
            return;
        }

        $group = $raw[$providerId];
        $store = $this->stores_model->getStore($storeId);
        $storeName = $store ? $store->name : 'Tienda ' . $storeId;
        $cobertura = $months ? $months . ' meses' : 'ABC';

        require_once APPPATH . '../vendor/autoload.php';
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header
        $sheet->setCellValue('A1', 'Orden de Compra - ' . $group['provider_name']);
        $sheet->setCellValue('A2', 'Tienda: ' . $storeName . ' | Cobertura: ' . $cobertura . ' | Fecha: ' . date('d/m/Y'));
        $sheet->mergeCells('A1:J1');
        $sheet->mergeCells('A2:J2');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2')->getFont()->setSize(10);

        // Column headers
        $headers = array('Codigo', 'Descripcion', 'ABC', 'Demanda/Mes', 'Objetivo', 'Stock', 'Transito', 'Pedir', 'Costo Unit', 'Subtotal');
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col . '4', $h);
            $sheet->getStyle($col . '4')->getFont()->setBold(true);
            $col++;
        }

        // Data
        $row = 5;
        foreach ($group['items'] as $item) {
            $sheet->setCellValue('A' . $row, $item['productId']);
            $sheet->setCellValue('B' . $row, $item['description']);
            $sheet->setCellValue('C' . $row, $item['abc_type']);
            $sheet->setCellValue('D' . $row, $item['demand_monthly']);
            $sheet->setCellValue('E' . $row, $item['stock_target']);
            $sheet->setCellValue('F' . $row, $item['stock_actual']);
            $sheet->setCellValue('G' . $row, $item['in_transit']);
            $sheet->setCellValue('H' . $row, $item['need']);
            $sheet->setCellValue('I' . $row, $item['unit_cost']);
            $sheet->setCellValue('J' . $row, $item['subtotal']);
            $sheet->getStyle('I' . $row)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('J' . $row)->getNumberFormat()->setFormatCode('#,##0');
            $row++;
        }

        // Total
        $sheet->setCellValue('I' . $row, 'TOTAL:');
        $sheet->setCellValue('J' . $row, $group['total']);
        $sheet->getStyle('I' . $row . ':J' . $row)->getFont()->setBold(true);
        $sheet->getStyle('J' . $row)->getNumberFormat()->setFormatCode('#,##0');

        // Auto-size columns
        foreach (range('A', 'J') as $c) { $sheet->getColumnDimension($c)->setAutoSize(true); }

        $filename = 'OC_' . preg_replace('/[^a-zA-Z0-9]/', '_', $group['provider_name']) . '_' . date('Ymd') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}
