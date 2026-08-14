<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once __DIR__ . '/ReportInterface.php';

/**
 * AbstractReport — base class con defaults sensatos.
 *
 * Las definiciones extienden de aquí en lugar de implementar ReportInterface
 * directamente, así solo overridean lo necesario. Por ejemplo, casi todos
 * los reportes:
 *   - Aceptan los 4 formatos.
 *   - Tienen html como default.
 *   - Permiten email/whatsapp si `meta()` retorna client_id.
 *
 * Ejemplo:
 *
 *   class ClientStatement extends AbstractReport {
 *       public function id(): string { return 'client_statement'; }
 *       public function title(): string { return 'Estado de Cuenta'; }
 *       public function requiredRoles(): array { return [2, 3, 5, 8]; }
 *       public function filterDefinitions(): array { return [
 *           ['name' => 'client_id', 'label' => 'Cliente', 'type' => 'client', 'required' => true],
 *           ['name' => 'desde',     'label' => 'Desde',   'type' => 'date',   'default' => date('Y-01-01')],
 *           ['name' => 'hasta',     'label' => 'Hasta',   'type' => 'date',   'default' => date('Y-m-d')],
 *       ]; }
 *       public function data(array $p): array { ... }
 *       public function meta(array $p): array { return [
 *           'filename'        => 'estado_cuenta_' . slug($client->name) . '_' . $p['hasta'],
 *           'email_subject'   => 'Estado de cuenta — ' . $client->name,
 *           'whatsapp_body'   => sprintf('Hola %s, te enviamos tu estado de cuenta al %s.', $client->name, $p['hasta']),
 *           'client_id'       => $client->idClient,
 *       ]; }
 *   }
 */
abstract class AbstractReport implements ReportInterface
{
    public function description(): string
    {
        return '';
    }

    public function availableFormats(): array
    {
        return ['html', 'pdf', 'xlsx', 'csv'];
    }

    public function defaultFormat(): string
    {
        return 'html';
    }

    public function availableChannels(): array
    {
        return ['email', 'whatsapp', 'schedule'];
    }

    public function meta(array $params): array
    {
        return [
            'filename' => $this->id() . '_' . date('Y-m-d'),
        ];
    }

    public function filterDefinitions(): array
    {
        return [];
    }

    /**
     * Helper: aplica los defaults de filterDefinitions() a $params, así data()
     * recibe siempre todos los keys con valor (default o el del usuario).
     */
    protected function applyFilterDefaults(array $params): array
    {
        foreach ($this->filterDefinitions() as $f) {
            $name = $f['name'];
            if (!isset($params[$name]) || $params[$name] === '') {
                $params[$name] = $f['default'] ?? null;
            }
        }
        return $params;
    }

    /**
     * Helper: chequea que un usuario con role $userRole puede ver el reporte.
     * Role 1 (superadmin) siempre pasa. Si el usuario tiene el permiso
     * 'reportes_v2' en su sesion, tambien pasa (override matriz). Sino, valida
     * contra requiredRoles() de la subclase.
     */
    public function userCanAccess(int $userRole): bool
    {
        if ($userRole === 1) return true;
        // Override desde la matriz: si tiene reportes_v2, ve todos los reports v2
        if (function_exists('has_permission') && has_permission('reportes_v2')) return true;
        return in_array($userRole, $this->requiredRoles(), true);
    }

    /**
     * Helper: lista de bodegas para usar como options en un filtro.
     * Reutilizable por cualquier reporte que declare filterDefinitions con
     * una entrada como:
     *
     *   [
     *       'name' => 'store_id',
     *       'label' => 'Bodega',
     *       'type' => 'select',
     *       'options' => $this->storeOptions(),
     *   ]
     *
     * @return array<string, string>
     */
    protected function storeOptions(): array
    {
        $CI =& get_instance();
        $rows = $CI->db->query("SELECT idStore, name FROM stores WHERE deleted = 0 ORDER BY name")->result();
        $opts = [];
        foreach ($rows as $r) {
            $opts[$r->idStore] = $r->name;
        }
        return $opts;
    }

    /**
     * Helper: definición standard del filtro Bodega. Permite añadirlo a
     * cualquier reporte con una sola linea:
     *
     *   public function filterDefinitions(): array {
     *       return [
     *           ...
     *           $this->storeFilterDefinition(),
     *       ];
     *   }
     *
     * @return array<string, mixed>
     */
    protected function storeFilterDefinition(): array
    {
        return [
            'name' => 'store_id',
            'label' => 'Bodega',
            'type' => 'select',
            'options' => $this->storeOptions(),
            'default' => '',
        ];
    }
}
