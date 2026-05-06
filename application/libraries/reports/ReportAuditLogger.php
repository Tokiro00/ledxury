<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * ReportAuditLogger — escribe en report_dispatches cada vez que un reporte
 * se renderiza/descarga/envía.
 *
 * Lo usan tanto los renderers (channel=download cuando es HTML interactivo
 * o un descarga) como los dispatchers (channel=email|whatsapp|schedule).
 *
 * No es CI library autoloaded: se instancia con $CI->db.
 *
 * Uso típico:
 *   $logger = new ReportAuditLogger($this->db, $this->session);
 *   $logger->logDispatch([
 *       'report_id'  => 'client_statement',
 *       'format'     => 'pdf',
 *       'channel'    => 'whatsapp',
 *       'recipient'  => '+573001234567',
 *       'recipient_client_id' => 42,
 *       'params'     => ['desde' => '2026-01-01', 'hasta' => '2026-04-30'],
 *       'status'     => 'sent',
 *   ]);
 *
 *   // Si falla:
 *   $logger->logDispatch([..., 'status' => 'failed', 'error' => 'Timeout']);
 */
class ReportAuditLogger
{
    /** @var CI_DB_query_builder */
    private $db;

    /** @var CI_Session */
    private $session;

    public function __construct($db, $session)
    {
        $this->db = $db;
        $this->session = $session;
    }

    /**
     * Persiste un dispatch. Devuelve el id insertado.
     *
     * @param array{
     *   report_id: string,
     *   format: string,
     *   channel: string,
     *   recipient?: string|null,
     *   recipient_client_id?: int|null,
     *   params?: array<string, mixed>,
     *   status?: string,
     *   error?: string|null,
     *   dispatched_by?: string|null
     * } $entry
     */
    public function logDispatch(array $entry): int
    {
        $userData = $this->session->userdata('user_data');
        $by = $entry['dispatched_by'] ?? ($userData['uname'] ?? 'system');

        $row = [
            'report_id' => $entry['report_id'],
            'format' => $entry['format'],
            'channel' => $entry['channel'],
            'recipient' => $entry['recipient'] ?? null,
            'recipient_client_id' => $entry['recipient_client_id'] ?? null,
            'params_json' => isset($entry['params']) ? json_encode($entry['params'], JSON_UNESCAPED_UNICODE) : null,
            'dispatched_by' => $by,
            'status' => $entry['status'] ?? 'sent',
            'error_message' => $entry['error'] ?? null,
        ];

        $this->db->insert('report_dispatches', $row);
        return (int) $this->db->insert_id();
    }

    /**
     * Lookup: cuántas veces se le envió este reporte a este cliente?
     * Útil para mostrar "Última envío: hace 3 días" en la UI.
     */
    public function lastDispatchToClient(string $reportId, int $clientId): ?array
    {
        $row = $this->db
            ->where('report_id', $reportId)
            ->where('recipient_client_id', $clientId)
            ->where('status', 'sent')
            ->order_by('dispatched_at', 'DESC')
            ->limit(1)
            ->get('report_dispatches')
            ->row_array();
        return $row ?: null;
    }

    /**
     * Histórico de dispatches de un reporte. Para UI de auditoría.
     *
     * @return array<int, array<string, mixed>>
     */
    public function history(string $reportId, int $limit = 100): array
    {
        return $this->db
            ->where('report_id', $reportId)
            ->order_by('dispatched_at', 'DESC')
            ->limit($limit)
            ->get('report_dispatches')
            ->result_array();
    }
}
