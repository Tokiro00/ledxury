<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * ReportInterface — contrato que toda definición de reporte debe cumplir.
 *
 * Vive en application/libraries/reports/definitions/{Report}.php
 *
 * Cada reporte declara su id, título, los roles que pueden verlo, los
 * filtros que acepta, los formatos disponibles, y cómo se obtienen los
 * datos. Los renderers se encargan de transformar esos datos en
 * HTML/PDF/XLSX/CSV; los dispatchers se encargan de enviarlos por
 * email/WhatsApp/cron.
 *
 * Ejemplo mínimo (ver definitions/ClientStatement.php para uno completo):
 *
 *   class MyReport implements ReportInterface {
 *       public function id(): string { return 'my_report'; }
 *       public function title(): string { return 'Mi Reporte'; }
 *       public function requiredRoles(): array { return [1, 2]; }
 *       public function filterDefinitions(): array { return [...]; }
 *       public function availableFormats(): array { return ['html','pdf','xlsx']; }
 *       public function defaultFormat(): string { return 'html'; }
 *       public function data(array $params): array { return ['rows' => ...]; }
 *       public function meta(array $params): array { return ['filename' => ...]; }
 *   }
 */
interface ReportInterface
{
    /**
     * Identificador único, usado en URL: /sisvent/admin/reports/{id}
     * Debe ser snake_case ASCII. No cambiar después de release (URLs públicas).
     */
    public function id(): string;

    /**
     * Título humano para UI y subject de email.
     */
    public function title(): string;

    /**
     * Descripción corta opcional para el listado de reportes.
     */
    public function description(): string;

    /**
     * Roles que pueden ver/ejecutar este reporte. El rol 1 (superadmin) siempre
     * pasa, no hace falta incluirlo.
     *
     * Reportes sensibles (vendor_profitability, vendor_commissions, cash_flow,
     * accounting_results) restringen a [2, 5]. Los públicos quedan abiertos a
     * su rol natural.
     *
     * @return int[] Array de role IDs.
     */
    public function requiredRoles(): array;

    /**
     * Filtros que el reporte acepta. Cada entrada describe un input del form:
     *
     *   ['name' => 'desde',       'label' => 'Desde',     'type' => 'date',    'required' => true,  'default' => '...']
     *   ['name' => 'client_id',   'label' => 'Cliente',   'type' => 'client',  'required' => false]
     *   ['name' => 'store_id',    'label' => 'Bodega',    'type' => 'select',  'options' => [...]]
     *   ['name' => 'group_by',    'label' => 'Agrupar',   'type' => 'radio',   'options' => [...]]
     *
     * El controller los valida y pasa a data() ya parseados.
     *
     * @return array<int, array<string, mixed>>
     */
    public function filterDefinitions(): array;

    /**
     * Formatos de salida soportados. Subset de ['html','pdf','xlsx','csv'].
     * Casi todos soportan los 4. Algunos reportes muy gráficos pueden excluir csv.
     *
     * @return string[]
     */
    public function availableFormats(): array;

    /**
     * Formato default si no se especifica ?format= en la URL.
     */
    public function defaultFormat(): string;

    /**
     * Canales de envío soportados. Subset de ['email','whatsapp','schedule'].
     *
     * Reportes orientados a cliente (client_statement, provider_statement)
     * permiten los 3. Reportes internos (cash_flow) típicamente solo email.
     *
     * @return string[]
     */
    public function availableChannels(): array;

    /**
     * Genera los datos del reporte aplicando los filtros.
     *
     * El array retornado se pasa a los renderers tal cual; cada renderer
     * sabe cómo presentarlo (html consume 'rows' + 'totals', xlsx mapea
     * 'columns' a header + cells, etc.).
     *
     * Estructura esperada:
     *   [
     *       'columns' => [['key' => 'date', 'label' => 'Fecha', 'type' => 'date'], ...],
     *       'rows'    => [...],
     *       'totals'  => [...],
     *       'meta'    => ['filename' => 'estado_cuenta_lujos_marinilla_2026-04', ...],
     *   ]
     *
     * @param array<string, mixed> $params Filtros validados desde el controller.
     * @return array<string, mixed>
     */
    public function data(array $params): array;

    /**
     * Metadata para el envío: filename, subject de email, body de WhatsApp,
     * client_id si aplica (para audit log + envío directo), etc.
     *
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function meta(array $params): array;
}
