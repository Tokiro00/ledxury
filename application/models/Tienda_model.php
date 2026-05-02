<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Tienda_model — queries para la tienda pública (e-commerce).
 *
 * Ledxury opera vendiendo contra el inventario de MAM (no tiene stock
 * propio), por eso el catálogo muestra TODOS los productos con imagen y
 * precio, sin importar el stock local. Los productos en `blocked_products`
 * se muestran marcados como AGOTADO (no comprables) en lugar de ocultarse,
 * para que el cliente sepa qué está sin existencias en MAM.
 */
class Tienda_model extends CI_Model {

    const STORE_ID = 1; // Medellín — solo se usa para informar stock local
    const VIRTUAL_STOCK = 999; // capacidad asumida cuando se vende contra MAM

    /**
     * Devuelve set asociativo idProduct => extension (png/jpg) de productos
     * para los que existe archivo de imagen en disco.
     */
    public function get_available_images() {
        $dir = FCPATH . 'public/images/products/';
        $set = array();
        if (!is_dir($dir)) return $set;
        foreach (scandir($dir) as $f) {
            if ($f === '.' || $f === '..' || $f === 'no_image.png') continue;
            $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
            if (!in_array($ext, ['png', 'jpg', 'jpeg', 'webp'])) continue;
            $code = pathinfo($f, PATHINFO_FILENAME);
            // Si ya hay otro con mejor extensión (png > jpg) no sobrescribir
            if (!isset($set[$code])) $set[$code] = $ext;
        }
        return $set;
    }

    /**
     * Lista de códigos agotados — fuente de verdad: tabla `blocked_products`
     * (la misma que muestra/edita /sisvent/admin/bots/agotados).
     */
    public function get_blocked_codes() {
        $rows = $this->db->select('product_code')->get('blocked_products')->result();
        $codes = array();
        foreach ($rows as $r) $codes[] = strtoupper($r->product_code);
        return $codes;
    }

    /**
     * Catálogo: productos con imagen + precio>0, sin filtrar por stock.
     * Los productos en blocked_products quedan marcados con is_blocked=true
     * (la vista los pinta como AGOTADO sin botón de agregar). El stock de
     * la tabla inventory queda solo informativo.
     */
    public function get_catalog() {
        $available = $this->get_available_images();
        if (empty($available)) return array('families' => array());

        $codes = array_keys($available);
        // Set de bloqueados para marcar; NO se filtran de la query.
        $blocked_set = array();
        foreach ($this->get_blocked_codes() as $bc) $blocked_set[$bc] = true;

        // LEFT JOIN inventory porque puede no haber fila en stock=0 reset.
        $this->db->select('p.idProduct, p.description, p.price, p.family, f.name AS family_name, COALESCE(inv.stock,0) AS stock', false);
        $this->db->from('products p');
        $this->db->join('inventory inv', 'inv.idProduct = p.idProduct AND inv.idStore = ' . self::STORE_ID, 'left');
        $this->db->join('product_families f', 'f.idFamily = p.family', 'left');
        $this->db->where('p.deleted', 0);
        $this->db->where('p.price >', 0);
        $this->db->where_in('p.idProduct', $codes);
        $this->db->order_by('f.name', 'ASC');
        $this->db->order_by('p.description', 'ASC');
        $rows = $this->db->get()->result();

        $byFamily = array();
        foreach ($rows as $r) {
            $fid = (int)($r->family ?: 0);
            $fname = $r->family_name ?: 'Otros';
            if (!isset($byFamily[$fid])) {
                $byFamily[$fid] = array('id' => $fid, 'name' => $fname, 'products' => array());
            }
            $ext = $available[$r->idProduct] ?? 'png';
            $code_upper = strtoupper((string)$r->idProduct);
            $is_blocked = isset($blocked_set[$code_upper]);
            $byFamily[$fid]['products'][] = array(
                'id'         => $r->idProduct,
                'name'       => $r->description,
                'price'      => (int)$r->price,
                'stock'      => $is_blocked ? 0 : self::VIRTUAL_STOCK,
                'is_blocked' => $is_blocked,
                'image'      => 'public/images/products/' . $r->idProduct . '.' . $ext,
            );
        }

        // Ordenar familias: 7 (MODULOS - CINTAS LED) primero, después por # de productos desc
        $families = array_values($byFamily);
        usort($families, function($a, $b) {
            if ($a['id'] === 7 && $b['id'] !== 7) return -1;
            if ($b['id'] === 7 && $a['id'] !== 7) return 1;
            return count($b['products']) - count($a['products']);
        });

        return array('families' => $families);
    }

    /**
     * Obtener un producto por idProduct (sin filtrar por stock).
     * Los productos bloqueados se devuelven igual con is_blocked=true para
     * que la vista pueda mostrarlos como AGOTADO en lugar de 404.
     */
    public function get_product($id) {
        $available = $this->get_available_images();
        if (!isset($available[$id])) return null;
        $is_blocked = in_array(strtoupper((string)$id), $this->get_blocked_codes(), true);

        $row = $this->db->select('p.idProduct, p.description, p.price, p.family, f.name AS family_name, COALESCE(inv.stock,0) AS stock', false)
            ->from('products p')
            ->join('inventory inv', 'inv.idProduct = p.idProduct AND inv.idStore = ' . self::STORE_ID, 'left')
            ->join('product_families f', 'f.idFamily = p.family', 'left')
            ->where('p.idProduct', $id)
            ->where('p.deleted', 0)
            ->where('p.price >', 0)
            ->get()->row();
        if (!$row) return null;
        return array(
            'id'          => $row->idProduct,
            'name'        => $row->description,
            'price'       => (int)$row->price,
            'stock'       => $is_blocked ? 0 : self::VIRTUAL_STOCK,
            'is_blocked'  => $is_blocked,
            'family_name' => $row->family_name,
            'image'       => 'public/images/products/' . $row->idProduct . '.' . $available[$row->idProduct],
        );
    }

    /**
     * Validar lote de productos del carrito y devolverlos enriquecidos con precio actual.
     */
    public function validate_cart($cartItems) {
        if (empty($cartItems)) return array();
        $blocked = $this->get_blocked_codes();
        $codes = array_map(function($i) { return $i['id']; }, $cartItems);
        // Trae info actual de precio + descripción (sin filtrar por stock).
        $rows = $this->db->select('p.idProduct, p.description, p.price')
            ->from('products p')
            ->where('p.deleted', 0)
            ->where('p.price >', 0)
            ->where_in('p.idProduct', $codes)
            ->get()->result();
        $byCode = array();
        foreach ($rows as $r) $byCode[$r->idProduct] = $r;

        $result = array();
        foreach ($cartItems as $i) {
            $code = $i['id'];
            if (!isset($byCode[$code])) continue;
            if (in_array(strtoupper($code), $blocked, true)) continue; // agotado en MAM

            $r = $byCode[$code];
            // Sin límite de stock local: vendemos contra MAM. Cap superior 999
            // por seguridad anti-abuso y para evitar overflows.
            $qty = max(1, min((int)$i['qty'], self::VIRTUAL_STOCK));
            $result[] = array(
                'id'       => $code,
                'name'     => $r->description,
                'price'    => (int)$r->price,
                'qty'      => $qty,
                'subtotal' => (int)$r->price * $qty,
            );
        }
        return $result;
    }
}
