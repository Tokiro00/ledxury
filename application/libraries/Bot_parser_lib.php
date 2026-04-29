<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Bot_parser_lib — Parser de mensajes PEDIDO_CONFIRMADO de WhatsApp.
 *
 * Extracted from BotImport.php (que pasaba 3000 líneas) en abril 2026.
 *
 * Responsabilidad: dado el contenido textual de una conversación de WhatsApp
 * (incluyendo mensajes del cliente y el bot), extraer:
 *   - Nombre del cliente
 *   - Documento (cédula)
 *   - Dirección
 *   - Teléfono celular
 *   - Total de la venta
 *   - Lista de productos (con cantidad, código, subtotal)
 *
 * No accede al modelo `products` directamente — recibe un callback `code_resolver`
 * que el controller le inyecta. Eso permite testear el parser sin DB.
 *
 * Uso:
 *   $parser = new Bot_parser_lib();
 *   $parser->setCodeResolver(function($name, $voltaje, $color) use ($CI) {
 *       return $CI->bot_import->_findProductCode($name, $voltaje, $color);
 *   });
 *   $extracted = $parser->parse($content);
 *   // $extracted = ['nombre' => '...', 'documento' => '...', 'productos' => [...], ...]
 */
class Bot_parser_lib {

    /** @var callable|null Resolver de códigos (descripción → idProduct) inyectado */
    private $code_resolver = null;

    public function __construct($params = array()) { /* CI3-friendly */ }

    public function setCodeResolver(callable $cb) {
        $this->code_resolver = $cb;
    }

    // =========================================================================
    // PUBLIC API
    // =========================================================================

    /**
     * Punto de entrada: dado el contenido completo de la conversación,
     * extrae todos los campos del pedido.
     *
     * @param string $content
     * @return array {nombre, documento, direccion, celular, voltaje, color, total,
     *                pedido_text, productos_text, productos[]}
     */
    public function parse($content) {
        $content = (string) $content;

        $r = array(
            'nombre'         => $this->_extractField($content, 'Nombre'),
            'documento'      => $this->_extractField($content, 'Cédula')
                              ?: $this->_extractField($content, 'Cedula')
                              ?: $this->_extractField($content, 'Documento'),
            'direccion'      => $this->_extractField($content, 'Dirección')
                              ?: $this->_extractField($content, 'Direccion'),
            'barrio'         => $this->_extractField($content, 'Barrio'),
            'ciudad'         => $this->_extractField($content, 'Ciudad'),
            'departamento'   => $this->_extractField($content, 'Departamento'),
            'zona'           => $this->_extractField($content, 'Zona'),
            'referencia'     => $this->_extractField($content, 'Referencia'),
            'celular'        => $this->_extractField($content, 'Celular'),
            'voltaje'        => $this->_extractField($content, 'Voltaje'),
            'color'          => $this->_extractField($content, 'Color'),
            'total'          => 0,
            'pedido_text'    => $this->_extractField($content, 'Pedido'),
            'productos_text' => $this->_extractField($content, 'Productos') ?: $this->_extractField($content, 'Producos'),
            'productos'      => array(),
        );

        // ── Fallbacks cuando los labels no traen colon (formato del bot actual) ──
        if (empty($r['documento'])) {
            if (preg_match('/\bC\.?C\.?\s*[#:]?\s*([0-9\.\-]{6,15})/i', $content, $m)) {
                $r['documento'] = trim($m[1]);
            } elseif (preg_match('/\b(?:cedula|cédula|documento|identificaci[oó]n|nit)\s+([0-9\.\-]{6,15})/i', $content, $m)) {
                $r['documento'] = trim($m[1]);
            }
        }
        if (empty($r['direccion'])) {
            if (preg_match('/Direcci[oó]n\s+([^\n\[]{8,200})/iu', $content, $m)) {
                $r['direccion'] = trim($m[1]);
            }
        }
        if (empty($r['nombre'])) {
            if (preg_match('/Nombre\s+(?:completo|del cliente|y apellido)\s*:\s*([^\n\[]{3,80})/iu', $content, $m)) {
                $r['nombre'] = trim($m[1]);
            } elseif (preg_match('/Nombre\s+([A-Za-zÁÉÍÓÚáéíóúñÑ]{2,30}(?:\s+[A-Za-zÁÉÍÓÚáéíóúñÑ]{2,30}){0,4})/u', $content, $m)) {
                $candidate = trim($m[1]);
                if (!preg_match('/^(completo|del|y|de)\b/i', $candidate)) {
                    $r['nombre'] = $candidate;
                }
            }
        }

        // ── Total: del campo "Total" o el monto $X más frecuente en la conversación ──
        $r['total'] = (int) preg_replace('/[^0-9]/', '', $this->_extractField($content, 'Total') ?: '0');
        if ($r['total'] <= 0) {
            $r['total'] = $this->_inferTotalFromAmounts($content);
        }

        // ── Productos: 3 fuentes, en orden de prioridad ──
        $r['productos'] = $this->_parseProductsBlock($content);
        if (empty($r['productos']) && (!empty($r['pedido_text']) || !empty($r['productos_text']))) {
            $r['productos'] = $this->_parsePedidoLine($r['pedido_text'], $r['productos_text'], $r['voltaje'], $r['color']);
        }
        if (empty($r['productos'])) {
            $r['productos'] = $this->_scanConversation($content);
        }

        return $r;
    }

    // =========================================================================
    // FIELD EXTRACTION
    // =========================================================================

    /**
     * Extrae "Campo: VALOR" de un texto multilínea. Quita asteriscos de WhatsApp,
     * emojis comunes y sufijos como "(con envío gratis)". Toma la última coincidencia.
     */
    public function _extractField($text, $fieldName) {
        $clean = preg_replace('/\*\s*\*([^*]+)\*/', '$1', $text);
        $clean = preg_replace('/\*([^*]+)\*/', '$1', $clean);
        $pattern = '/' . preg_quote($fieldName, '/') . '\s*:\s*(.+)/iu';
        if (preg_match_all($pattern, $clean, $matches)) {
            $val = trim(end($matches[1]));
            $val = preg_replace('/[\x{1F300}-\x{1F9FF}]/u', '', $val);
            $val = preg_replace('/\(con envío gratis\)/i', '', $val);
            $val = preg_replace('/\(Premium\)/i', '', $val);
            return trim($val);
        }
        return '';
    }

    /**
     * Cuando "Total:" no se extrae, escanea $X.XXX más frecuente en mensajes del bot.
     */
    private function _inferTotalFromAmounts($content) {
        if (preg_match_all('/\$\s*([0-9]{1,3}(?:[\.,][0-9]{3})+|[0-9]{4,7})/u', $content, $tm)) {
            $amounts = array();
            foreach ($tm[1] as $amt) {
                $amt_int = (int) preg_replace('/[^0-9]/', '', $amt);
                if ($amt_int >= 30000 && $amt_int <= 5000000) $amounts[] = $amt_int;
            }
            if (!empty($amounts)) {
                $counts = array_count_values($amounts);
                arsort($counts);
                return (int) array_key_first($counts);
            }
        }
        return 0;
    }

    /**
     * Normaliza texto para matching contra bot_product_aliases.
     */
    public function normalizeAlias($text) {
        $t = strtoupper(trim((string)$text));
        $t = preg_replace('/\s+/', ' ', $t);
        return $t;
    }

    // =========================================================================
    // PRODUCT EXTRACTION
    // =========================================================================

    /**
     * Bloque "Productos:" multi-línea.
     * Formato preferido (pipe-delimited): "- 20x 12LED-12V-H | $65000"
     * Formato fallback (descriptivo): "Módulos LED 3 estándar: 40 unidades" + Color/Voltaje
     */
    private function _parseProductsBlock($content) {
        $products = array();
        $clean = preg_replace('/\*\s*\*([^*]+)\*/', '$1', $content);
        $clean = preg_replace('/\*([^*]+)\*/', '$1', $clean);

        if (!preg_match('/Productos\s*:\s*\n(.*?)(?=\n\s*Total\s*:|\Z)/usi', $clean, $m)) {
            return $products;
        }
        $block = $m[1];

        // Formato A: pipe
        $lineRegex = '/^\s*[-*•]?\s*(\d+)\s*x\s+([A-Z0-9][A-Z0-9\-\/]*)\s*\|\s*\$?\s*([\d.,]+)\s*$/im';
        if (preg_match_all($lineRegex, $block, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $row) {
                $qty = (int) $row[1];
                $code = trim(strtoupper($row[2]));
                $subtotal = (int) preg_replace('/[^0-9]/', '', $row[3]);
                if ($qty <= 0 || $code === '' || $subtotal <= 0) continue;
                $products[] = array(
                    'qty'      => $qty,
                    'name'     => $code,
                    'code'     => $code,
                    'subtotal' => $subtotal,
                );
            }
            if (!empty($products)) return $products;
        }

        // Formato B: descriptivo
        $blockColor = '';
        $blockVoltaje = '';
        if (preg_match('/Color\s*:\s*([^\n]+)/iu', $block, $cm)) $blockColor = trim($cm[1]);
        if (preg_match('/Voltaje\s*:\s*([^\n]+)/iu', $block, $vm)) $blockVoltaje = trim($vm[1]);

        $descriptiveRegexes = array(
            '/(.+?)\s*:\s*(\d+)\s*unidad(?:es)?/iu',
            '/(\d+)\s*unidad(?:es)?\s+(?:de\s+)?(.+)/iu',
            '/(\d+)\s*m[oó]dulos?\s+(.+)/iu',
            '/(\d+)\s*x\s+(.+)/iu',
        );
        foreach (preg_split('/\n/', $block) as $line) {
            $line = trim(preg_replace('/^[\-\*\•\s]+/u', '', $line));
            if ($line === '' || stripos($line, 'color') === 0 || stripos($line, 'voltaje') === 0) continue;

            $qty = 0; $name = '';
            foreach ($descriptiveRegexes as $rx) {
                if (preg_match($rx, $line, $mm)) {
                    if (ctype_digit(trim($mm[1]))) { $qty = (int)$mm[1]; $name = trim($mm[2]); }
                    else { $qty = (int)$mm[2]; $name = trim($mm[1]); }
                    break;
                }
            }
            if ($qty <= 0 || $name === '') continue;

            $code = $this->_resolveCode($name, $blockVoltaje, $blockColor);
            $products[] = array(
                'qty'      => $qty,
                'name'     => $name,
                'code'     => $code ?: 'PENDIENTE',
                'subtotal' => 0,
            );
            break; // formato descriptivo viene con UNA sola línea de producto
        }

        return $products;
    }

    /**
     * Línea "Pedido:" del resumen viejo, formato: "10x MODULO 3LED - $55000, 5x CANDADO - $75000"
     */
    private function _parsePedidoLine($pedidoStr, $productosStr, $voltaje, $color) {
        $products = array();
        $source = trim((string)$pedidoStr) !== '' ? $pedidoStr : (string)$productosStr;
        if (trim($source) === '') return $products;

        $items = preg_split('/[,\n]+/', $source);
        foreach ($items as $item) {
            $item = trim($item);
            if (empty($item)) continue;
            $item = preg_replace('/^[\-\*\•]\s*/u', '', $item);

            $qty = 0; $productName = ''; $subtotal = 0;
            if (preg_match('/(\d+)\s*x\s+(.+?)\s*[-–]\s*\$?\s*([\d\.,]+)/iu', $item, $m)) {
                $qty = (int)$m[1]; $productName = trim($m[2]);
                $subtotal = (int) preg_replace('/[^0-9]/', '', $m[3]);
            } elseif (preg_match('/(\d+)\s*x\s+(.+?)\s*\(\s*\$?\s*([\d\.,]+)\s*\)/iu', $item, $m)) {
                $qty = (int)$m[1]; $productName = trim($m[2]);
                $subtotal = (int) preg_replace('/[^0-9]/', '', $m[3]);
            } elseif (preg_match('/(\d+)\s*x\s+(.+)/iu', $item, $m)) {
                $qty = (int)$m[1]; $productName = trim($m[2]); $subtotal = 0;
            } elseif (preg_match('/(\d+)\s*unidad(?:es)?\s+(?:de\s+)?(.+)/iu', $item, $m)) {
                $qty = (int)$m[1]; $productName = trim($m[2]); $subtotal = 0;
            } elseif (preg_match('/(.+?)\s*[:=]\s*(\d+)\s*unidad/iu', $item, $m)) {
                $qty = (int)$m[2]; $productName = trim($m[1]); $subtotal = 0;
            } else {
                continue;
            }

            if ($qty <= 0 || $productName === '') continue;
            $code = $this->_resolveCode($productName, $voltaje, $color);
            $products[] = array(
                'qty'      => $qty,
                'name'     => $productName,
                'code'     => $code ?: 'PENDIENTE',
                'subtotal' => $subtotal,
            );
        }
        return $products;
    }

    /**
     * Si no hay "Productos:" ni "Pedido:" estructurado, escanea menciones tipo
     * "40 modulos 6LED rojo 12 voltios" en cualquier parte de la conversación.
     */
    private function _scanConversation($content) {
        $products = array();
        $rx = '/(\d+)\s*(?:m[oó]dulos?|x|unidad(?:es)?)\s+([^\n\[]{0,100}?\b\d+\s*led\b[^\n\[]{0,80})/iu';
        if (preg_match_all($rx, $content, $matches, PREG_SET_ORDER)) {
            $last = end($matches);
            $qty = (int) $last[1];
            $nameWithCtx = trim($last[2]);
            if ($qty > 0 && $qty <= 5000) {
                $code = $this->_resolveCode($nameWithCtx, '', '');
                $products[] = array(
                    'qty'      => $qty,
                    'name'     => $nameWithCtx,
                    'code'     => $code ?: 'PENDIENTE',
                    'subtotal' => 0,
                );
            }
        }
        return $products;
    }

    /**
     * Helper interno: si hay code_resolver inyectado, lo usa. Si no, devuelve null.
     */
    private function _resolveCode($name, $voltaje = '', $color = '') {
        if (!is_callable($this->code_resolver)) return null;
        return call_user_func($this->code_resolver, $name, $voltaje, $color);
    }
}
