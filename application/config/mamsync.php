<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| Puente accesoriosmam -> Ledxury (remisiones del canal MAM-Online).
|
| accesoriosmam.com es el ERP de MAM; allá Ledxury es el cliente 3377
| "MAM-Online" y cada envío de mercancía queda como una remisión de canal
| (channel_remisions). Este puente las trae como facturas de proveedor MAM
| pendientes por recibir. La llave debe coincidir con la del controlador
| Channelsync desplegado en accesoriosmam.
*/
$config['mamsync'] = array(
    // Endpoint de solo lectura en accesoriosmam
    'remote_url'   => 'https://accesoriosmam.com/api/channelsync',
    'api_key'      => 'cmx_37ab7732e85ff543a1d9dbcb79a5f5d402ab41db40c4fc93',

    // Llave local para disparar el cron (mismo patrón que /cron/update_shipping_guides)
    'cron_key'     => 'mamsync_cron_2026',

    // Todo lo anterior al arranque quedó cubierto por el saldo inicial del
    // 01/08/2026 (factura SALDO-INICIAL-MAMONLINE-20260801): las remisiones
    // con fecha anterior se registran como omitidas, no como facturas.
    'start_date'   => '2026-08-01',

    'provider_id'  => 12,   // proveedor MAM en Ledxury
    'store_id'     => 1,    // bodega que recibe
);
