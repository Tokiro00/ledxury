<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once __DIR__ . '/../AbstractReport.php';

/**
 * Dispatches — Reporte de envios / despachos del periodo (v1.31.35).
 *
 * Replica el panel de `/sisvent/admin/envios/despachos` como reporte v2 para
 * exportar a Excel/PDF/CSV con los filtros de bodega + vendedor + rango +
 * transportadora.
 *
 * KPIs:
 *   - Cantidad de envios
 *   - Valor total facturado
 *   - Cajas y peso total
 *   - Flete a pagar (segun reglas de contrapago Interrapidisimo)
 *   - Ticket promedio
 *
 * Tabla por factura: cliente, destino, transportadora, guia/preenvio,
 * cajas, peso, valor factura, flete, vendedor, separado_por, despachado_por,
 * bodega, fecha.
 *
 * Reusa Shipping_model::getDespachosByCarrier + getFleteAPagar.
 */
class Dispatches extends AbstractReport
{
    public function id(): string { return 'dispatches'; }
    public function title(): string { return 'Despachos / Envios'; }
    public function description(): string
    {
        return 'Listado de envios del periodo con KPIs de valor, cajas, peso y flete. Filtrable por bodega, vendedor y transportadora. Exportable a Excel.';
    }

    public function requiredRoles(): array
    {
        // 2=admin, 5=tesoreria, 9=logistica
        return [2, 5, 9];
    }

    public function filterDefinitions(): array
    {
        return [
            ['name' => 'date_range', 'type' => 'date_range'],
            [
                'name'    => 'desde',
                'label'   => 'Desde',
                'type'    => 'date',
                'default' => date('Y-m-01'),
            ],
            [
                'name'    => 'hasta',
                'label'   => 'Hasta',
                'type'    => 'date',
                'default' => date('Y-m-d'),
            ],
            $this->storeFilterDefinition(),
            [
                'name'  => 'vendor_id',
                'label' => 'Vendedor',
                'type'  => 'vendor',
            ],
            [
                'name'    => 'transportadora',
                'label'   => 'Transportadora',
                'type'    => 'select',
                'options' => [
                    'all'             => 'Todas',
                    'interrapidisimo' => 'Interrapidisimo',
                    'carro_mam'       => 'Carro MAM',
                    'moto_mam'        => 'Moto MAM',
                    'estelar'         => 'Estelar',
                    'coordinadora'    => 'Coordinadora',
                    'particular'      => 'Particular',
                    'recoge_cliente'  => 'Recoge cliente',
                ],
                'default' => 'all',
            ],
        ];
    }

    public function availableChannels(): array { return ['email', 'schedule']; }

    public function data(array $params): array
    {
        $params = $this->applyFilterDefaults($params);
        $desde          = $params['desde'];
        $hasta          = $params['hasta'];
        $storeId        = (int)($params['store_id'] ?? 0);
        $vendorId       = (string)($params['vendor_id'] ?? '');
        $transportadora = (string)($params['transportadora'] ?? 'all');

        $CI =& get_instance();
        $CI->load->model('shipping_model');

        $rows = $CI->shipping_model->getDespachosByCarrier(
            $desde, $hasta,
            $storeId > 0 ? $storeId : -1,
            $transportadora,
            $vendorId !== '' ? $vendorId : 'all'
        );

        // Flete a pagar segun reglas de contrapago (mismo helper que la pantalla original)
        $flete = $CI->shipping_model->getFleteAPagar(
            $desde, $hasta,
            $storeId > 0 ? $storeId : -1,
            $transportadora
        );

        $totalValor = 0.0;
        $totalCajas = 0;
        $totalPeso  = 0.0;
        $tableRows  = [];

        $carrierLabels = [
            'interrapidisimo' => 'Interrapidisimo',
            'carro_mam'       => 'Carro MAM',
            'moto_mam'        => 'Moto MAM',
            'estelar'         => 'Estelar',
            'coordinadora'    => 'Coordinadora',
            'particular'      => 'Particular',
            'recoge_cliente'  => 'Recoge cliente',
        ];

        // Etiquetas del estado de la guía (alineadas con config/tracking.php)
        $statusLabels = [
            'cotizado'         => 'Cotizado',
            'pending'          => 'Pendiente',
            'in_transit'       => 'En tránsito',
            'out_for_delivery' => 'En reparto',
            'delivered'        => 'Entregado',
            'returned'         => 'Devuelto',
            'exception'        => 'Novedad',
            ''                 => 'Sin guía',
        ];

        foreach ($rows as $r) {
            $valorFactura = (float)$r->total - (float)($r->discount ?? 0);
            $cajas        = (int)($r->numeroPiezas ?? 0);
            $peso         = (float)($r->peso ?? 0);
            $fleteValor   = (float)($r->flete_valor ?? 0);

            $totalValor += $valorFactura;
            $totalCajas += $cajas;
            $totalPeso  += $peso;

            $statusKey = (string)($r->guide_status ?? '');
            $tableRows[] = [
                'invoice_id'       => (int)$r->idInvoice,
                'cliente'          => (string)($r->client_name ?? ''),
                'destino'          => (string)($r->despacho_destino ?: $r->client_city ?: ''),
                'transportadora'   => $carrierLabels[$r->transportadora] ?? (string)$r->transportadora,
                'guia'             => (string)($r->numeroPreenvio ?? ''),
                'estado_guia'      => $statusLabels[$statusKey] ?? ($r->estadoNombre ?? ($statusKey ?: '—')),
                'cajas'            => $cajas,
                'peso_kg'          => $peso,
                'valor_factura'    => $valorFactura,
                'flete'            => $fleteValor,
                'vendedor'         => (string)($r->vendor_name ?? ''),
                'separado_por'     => (string)($r->separado_by_name ?? ''),
                'despachado_por'   => (string)($r->despachado_by_name ?? ''),
                'bodega'           => (string)($r->store_name ?? ''),
                'fecha'            => $r->despachado_at ?: $r->date,
            ];
        }

        $count = count($tableRows);
        $kpis = [
            'envios'         => $count,
            'valor_total'    => $totalValor,
            'cajas'          => $totalCajas,
            'peso_kg'        => $totalPeso,
            'flete_a_pagar'  => (float)($flete->flete_a_pagar ?? 0),
            'flete_entregadas' => (float)($flete->flete_entregadas ?? 0),
            'flete_en_curso' => (float)($flete->flete_en_curso ?? 0),
            'ticket_promedio'=> $count > 0 ? $totalValor / $count : 0,
        ];

        $columns = [
            ['key' => 'invoice_id',     'label' => 'Factura',         'type' => 'number'],
            ['key' => 'fecha',          'label' => 'Fecha',           'type' => 'datetime'],
            ['key' => 'cliente',        'label' => 'Cliente',         'type' => 'text'],
            ['key' => 'destino',        'label' => 'Destino',         'type' => 'text'],
            ['key' => 'transportadora', 'label' => 'Transportadora',  'type' => 'text'],
            ['key' => 'guia',           'label' => 'Guia / Preenvio', 'type' => 'text'],
            ['key' => 'estado_guia',    'label' => 'Estado guía',     'type' => 'text'],
            ['key' => 'cajas',          'label' => 'Cajas',           'type' => 'number'],
            ['key' => 'peso_kg',        'label' => 'Peso (kg)',       'type' => 'number', 'decimals' => 1],
            ['key' => 'valor_factura',  'label' => 'Valor factura',   'type' => 'currency'],
            ['key' => 'flete',          'label' => 'Flete',           'type' => 'currency'],
            ['key' => 'vendedor',       'label' => 'Vendedor',        'type' => 'text'],
            ['key' => 'separado_por',   'label' => 'Separado por',    'type' => 'text'],
            ['key' => 'despachado_por', 'label' => 'Despachado por',  'type' => 'text'],
            ['key' => 'bodega',         'label' => 'Bodega',          'type' => 'text'],
        ];

        $totals = [
            'invoice_id'    => 'TOTAL',
            'cajas'         => $totalCajas,
            'peso_kg'       => $totalPeso,
            'valor_factura' => $totalValor,
            'flete'         => $kpis['flete_a_pagar'],
        ];

        return [
            'kpis'    => $kpis,
            'columns' => $columns,
            'rows'    => $tableRows,
            'totals'  => $totals,
        ];
    }

    public function meta(array $params): array
    {
        $desde = $params['desde'] ?? date('Y-m-01');
        $hasta = $params['hasta'] ?? date('Y-m-d');
        return [
            'filename'              => 'despachos_' . $desde . '_' . $hasta,
            'email_subject'         => 'Reporte de despachos — ' . date('d/m/Y', strtotime($desde)) . ' a ' . date('d/m/Y', strtotime($hasta)),
            'whatsapp_report_label' => 'reporte de despachos',
            'pdf_orientation'       => 'L',
        ];
    }
}
