<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Configuración del almacenamiento de imágenes de productos.
 *
 * Modos disponibles:
 *   - 'local'  → public/images/products/{idProduct}.{ext}  (default actual)
 *   - 's3'     → bucket S3, público, URL https://{bucket}.s3.{region}.amazonaws.com/products/{idProduct}.{ext}
 *
 * Para activar S3:
 *   1) Crear bucket S3 público con CORS abierto a tu dominio
 *   2) Crear IAM user con permisos s3:PutObject + s3:GetObject sobre el bucket
 *   3) Llenar las credenciales en application/config/secrets.php
 *      (NO en este archivo — secrets.php está gitignored)
 *   4) Cambiar 'driver' a 's3' aquí
 *   5) Subir las imágenes existentes con scripts/migrate_images_to_s3.php
 */

$config['image_storage'] = array(
    'driver' => 'local', // 'local' | 's3'

    'local' => array(
        'dir'      => 'public/images/products/', // relativo a FCPATH
        'url_path' => 'public/images/products/', // relativo a base_url()
    ),

    's3' => array(
        // Estos valores los lee primero de secrets.php; si no, los toma de aquí.
        // En producción usar SOLO secrets.php para que no queden en git.
        'bucket'   => '',  // ej: 'ledxury-products'
        'region'   => 'us-east-1',
        'prefix'   => 'products/', // path dentro del bucket
        'public_base_url' => '', // ej: 'https://ledxury-products.s3.us-east-1.amazonaws.com/'
                                // si vacío se construye automáticamente
        // Las credenciales viven en secrets.php:
        //   $config['s3_access_key_id']     = '...'
        //   $config['s3_secret_access_key'] = '...'
    ),
);
