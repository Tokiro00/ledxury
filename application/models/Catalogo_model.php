<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Modelo Catálogo Digital MAM
 *
 * Queries contra las tablas EXISTENTES de MAM:
 * - products (catálogo)
 * - product_families (familias)
 * - inventory (stock por tienda)
 * - budgets + budget_detail (presupuestos)
 * - users (vendedores)
 * - clients (clientes)
 *
 * NO crea tablas nuevas. Usa lo que ya existe.
 */
class Catalogo_model extends CI_Model {

    /**
     * Obtener familias de productos
     */
    public function get_families() {
        return $this->db
            ->order_by('name', 'ASC')
            ->get('product_families')
            ->result();
    }

    /**
     * Nombre de una familia
     */
    public function get_family_name($familyId) {
        $row = $this->db
            ->where('idFamily', $familyId)
            ->get('product_families')
            ->row();
        return $row ? $row->name : 'Sin categoría';
    }

    /**
     * Obtener productos con filtros
     */
    public function get_products($familyId = 0, $search = '', $onlyWithImg = false, $limit = 80, $offset = 0, $storeId = 0, $onlyAvailable = false) {
        $this->db->select('p.idProduct AS code, p.description AS name, p.family AS familyId,
                           p.price, p.price_base AS priceBase, p.price_dist AS priceDist,
                           p.picture_url AS image, p.is_national AS isNational, p.location,
                           f.name AS familyName');

        if ($storeId > 0) {
            $this->db->select('COALESCE(inv.stock, 0) AS stock');
            $this->db->join('inventory inv', 'inv.idProduct = p.idProduct AND inv.idStore = ' . (int)$storeId, 'left');
        } elseif ($onlyAvailable) {
            $this->db->select('COALESCE(inv.stock, 0) AS stock');
            $this->db->join('(SELECT idProduct, SUM(stock) AS stock FROM inventory GROUP BY idProduct) inv', 'inv.idProduct = p.idProduct', 'left');
        }

        $this->db->from('products p');
        $this->db->join('product_families f', 'f.idFamily = p.family', 'left');
        $this->db->where('p.deleted', 0);
        $this->db->where('p.price >', 0);

        if ($onlyAvailable) {
            $this->db->where('COALESCE(inv.stock, 0) >', 0);
        }

        if ($familyId > 0) {
            $this->db->where('p.family', $familyId);
        }

        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('p.idProduct', $search, 'both');
            $this->db->or_like('p.description', $search, 'both');
            $this->db->group_end();
        }

        if ($onlyWithImg) {
            $this->db->where('p.picture_url !=', 'products/no_image.png');
            $this->db->where('p.picture_url IS NOT NULL');
        }

        $this->db->order_by('p.description', 'ASC');
        $this->db->limit($limit, $offset);

        $results = $this->db->get()->result();

        // Agregar flag hasImage y resolver ruta correcta de imagen
        foreach ($results as &$r) {
            $r->hasImage = ($r->image && $r->image !== 'products/no_image.png');
            if ($r->hasImage) {
                // Buscar primero en uploads/, luego en public/dist/images/
                if (file_exists(FCPATH . 'uploads/' . $r->image)) {
                    $r->image_url = 'uploads/' . $r->image;
                } else {
                    $r->image_url = 'public/dist/images/' . $r->image;
                }
            } else {
                $r->image_url = '';
            }
        }

        return $results;
    }

    /**
     * Contar productos con filtros (para paginación)
     */
    public function count_products($familyId = 0, $search = '', $onlyWithImg = false) {
        $this->db->from('products');
        $this->db->where('deleted', 0);
        $this->db->where('price >', 0);

        if ($familyId > 0) {
            $this->db->where('family', $familyId);
        }

        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('idProduct', $search, 'both');
            $this->db->or_like('description', $search, 'both');
            $this->db->group_end();
        }

        if ($onlyWithImg) {
            $this->db->where('picture_url !=', 'products/no_image.png');
            $this->db->where('picture_url IS NOT NULL');
        }

        return $this->db->count_all_results();
    }

    /**
     * Detalle de un producto
     */
    public function get_product($code) {
        return $this->db->select('p.*, f.name AS familyName')
            ->from('products p')
            ->join('product_families f', 'f.idFamily = p.family', 'left')
            ->where('p.idProduct', $code)
            ->where('p.deleted', 0)
            ->get()
            ->row();
    }

    /**
     * Stock por tienda de un producto
     */
    public function get_stock($code) {
        return $this->db->select('i.stock, s.name AS storeName, s.idStore')
            ->from('inventory i')
            ->join('stores s', 's.idStore = i.idStore', 'left')
            ->where('i.idProduct', $code)
            ->where('i.stock >', 0)
            ->get()
            ->result();
    }

    /**
     * Info del vendedor
     */
    public function get_vendor($vendorId) {
        return $this->db->select('idUser, name, phone, email, store')
            ->where('idUser', $vendorId)
            ->where('user_status', 'active')
            ->get('users')
            ->row();
    }

    /**
     * Info del cliente
     */
    public function get_client($clientId) {
        return $this->db->select('idClient, name, commercial_name, city, phone, type, rate, vendor')
            ->where('idClient', $clientId)
            ->where('deleted', 0)
            ->get('clients')
            ->row();
    }

    /**
     * Stats generales del catálogo
     */
    public function get_stats() {
        $total = $this->db->where('deleted', 0)->where('price >', 0)->count_all_results('products');

        $withImg = $this->db->where('deleted', 0)->where('price >', 0)
            ->where('picture_url !=', 'products/no_image.png')
            ->where('picture_url IS NOT NULL')
            ->count_all_results('products');

        return [
            'total'    => $total,
            'withImg'  => $withImg,
            'noImg'    => $total - $withImg,
        ];
    }

    /**
     * Crear presupuesto (budget) desde el catálogo
     * Se inserta en las tablas budgets + budget_detail que ya existen
     */
    public function create_budget($clientId, $vendorId, $storeId, $items) {
        if (empty($items) || !$clientId || !$vendorId) {
            return ['error' => 'Faltan datos obligatorios'];
        }

        // Calcular total
        $total = 0;
        $details = [];
        foreach ($items as $item) {
            $product = $this->get_product($item['productId']);
            if (!$product) continue;

            $qty   = max(1, (int) $item['quantity']);
            $price = $product->price;
            $base  = $product->price_base ?: $product->price;
            $line  = $qty * $price;
            $total += $line;

            $details[] = [
                'productId' => $item['productId'],
                'quantity'  => $qty,
                'unit'      => $price,
                'base'      => $base,
                'total'     => $line,
            ];
        }

        if (empty($details)) {
            return ['error' => 'Ningún producto válido'];
        }

        // Insertar budget (presupuesto)
        $this->db->insert('budgets', [
            'clientId'  => $clientId,
            'vendorId'  => $vendorId,
            'storeId'   => $storeId,
            'total'     => $total,
            'date'      => date('Y-m-d H:i:s'),
            'state'     => 0, // Pendiente
            'hasIva'    => 0,
            'iva'       => 0,
            'e_commerce' => 0,
            'comments'  => 'Generado desde Catálogo Digital',
            'archived'  => 0,
        ]);

        $budgetId = $this->db->insert_id();

        // Insertar detalles
        foreach ($details as $detail) {
            $detail['budgetId'] = $budgetId;
            $this->db->insert('budget_detail', $detail);
        }

        return [
            'success'  => true,
            'budgetId' => $budgetId,
            'total'    => $total,
            'items'    => count($details),
        ];
    }
}
