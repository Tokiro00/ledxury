<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Configuración del servicio de rastreo de envíos
 *
 * 17TRACK API - https://www.17track.net/en/api
 */

// API Key de 17TRACK
$config['17track_api_key'] = '9CC005C21D9576202FCA5160E8E37502';

// URL base de la API de 17TRACK
$config['17track_api_url'] = 'https://api.17track.net/track/v2.2';

// Mapeo de transportadoras locales a códigos de 17TRACK
// Lista completa: https://api.17track.net/track/v2.2/getcarrier
$config['carrier_codes'] = [
    'interrapidisimo' => 190239,  // Inter Rapidísimo Colombia
    'servientrega'    => 190050,  // Servientrega Colombia
    'coordinadora'    => 190048,  // Coordinadora Colombia
    'envia'           => 190073,  // Envía Colvanes Colombia
    'tcc'             => 190052,  // TCC Colombia
    'otro'            => 0,       // Auto-detect
];

// Mapeo de estados de 17TRACK a estados internos del sistema
// Estados de 17TRACK: https://api.17track.net/track/v2.2/gettracklist
$config['status_mapping'] = [
    // 17TRACK status => Internal status
    'NotFound'       => 'pending',         // No encontrado
    'InfoReceived'   => 'pending',         // Información recibida
    'InTransit'      => 'in_transit',      // En tránsito
    'OutForDelivery' => 'out_for_delivery', // En reparto
    'Delivered'      => 'delivered',       // Entregado
    'AvailableForPickup' => 'out_for_delivery', // Listo para recoger
    'Exception'      => 'exception',       // Novedad/Excepción
    'Expired'        => 'exception',       // Expirado
    'Pending'        => 'pending',         // Pendiente
];

// Etiquetas de estado en español
$config['status_labels'] = [
    'pending'          => 'Pendiente',
    'in_transit'       => 'En tránsito',
    'out_for_delivery' => 'En reparto',
    'delivered'        => 'Entregado',
    'returned'         => 'Devuelto',
    'exception'        => 'Novedad',
];
