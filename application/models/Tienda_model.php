<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Tienda_model — queries para la tienda pública (e-commerce).
 * Filtra solo productos con stock>0 en Medellín (storeId=1) Y con archivo
 * de imagen disponible en /public/images/products/{idProduct}.{png|jpg|jpeg}.
 */
class Tienda_model extends CI_Model {

    const STORE_ID = 1; // Medellín

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
     * Productos con stock>0 e imagen disponible, agrupados por familia.
     * Las familias de Módulos (id 7) van primero.
     */
    public function get_catalog() {
        $available = $this->get_available_images();
        if (empty($available)) return array('families' => array());

        $codes = array_keys($available);
        // Limitar tamaño del IN para evitar query enorme. 1000+ productos cabe en un IN.
        $this->db->select('p.idProduct, p.description, p.price, p.family, f.name AS family_name, inv.stock');
        $this->db->from('products p');
        $this->db->join('inventory inv', 'inv.idProduct = p.idProduct AND inv.idStore = ' . self::STORE_ID, 'inner');
        $this->db->join('product_families f', 'f.idFamily = p.family', 'left');
        $this->db->where('p.deleted', 0);
        $this->db->where('p.price >', 0);
        $this->db->where('inv.stock >', 0);
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
            $byFamily[$fid]['products'][] = array(
                'id'    => $r->idProduct,
                'name'  => $r->description,
                'price' => (int)$r->price,
                'stock' => (int)$r->stock,
                'image' => 'public/images/products/' . $r->idProduct . '.' . $ext,
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
     * Obtener un producto por idProduct (con stock e imagen verificada).
     */
    public function get_product($id) {
        $available = $this->get_available_images();
        if (!isset($available[$id])) return null;

        $row = $this->db->select('p.idProduct, p.description, p.price, p.family, f.name AS family_name, inv.stock')
            ->from('products p')
            ->join('inventory inv', 'inv.idProduct = p.idProduct AND inv.idStore = ' . self::STORE_ID, 'inner')
            ->join('product_families f', 'f.idFamily = p.family', 'left')
            ->where('p.idProduct', $id)
            ->where('p.deleted', 0)
            ->where('inv.stock >', 0)
            ->get()->row();
        if (!$row) return null;
        return array(
            'id'    => $row->idProduct,
            'name'  => $row->description,
            'price' => (int)$row->price,
            'stock' => (int)$row->stock,
            'family_name' => $row->family_name,
            'image' => 'public/images/products/' . $row->idProduct . '.' . $available[$row->idProduct],
        );
    }

    /**
     * Validar lote de productos del carrito y devolverlos enriquecidos con precio actual.
     */
    public function validate_cart($cartItems) {
        if (empty($cartItems)) return array();
        $codes = array_map(function($i) { return $i['id']; }, $cartItems);
        $rows = $this->db->select('p.idProduct, p.description, p.price, COALESCE(inv.stock,0) as stock')
            ->from('products p')
            ->join('inventory inv', 'inv.idProduct = p.idProduct AND inv.idStore = ' . self::STORE_ID, 'left')
            ->where('p.deleted', 0)
            ->where_in('p.idProduct', $codes)
            ->get()->result();
        $byCode = array();
        foreach ($rows as $r) $byCode[$r->idProduct] = $r;

        $result = array();
        foreach ($cartItems as $i) {
            $code = $i['id'];
            if (!isset($byCode[$code])) continue;
            $r = $byCode[$code];
            $qty = max(1, min((int)$i['qty'], (int)$r->stock));
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
