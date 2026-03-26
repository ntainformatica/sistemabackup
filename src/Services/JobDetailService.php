<?php

declare(strict_types=1);

/**
 * Drill-down: cabeçalho + execuções + alertas + série simples para gráfico.
 */
final class JobDetailService
{
    private const LIMIT_EVENTS = 200;

    public function __construct(private PDO $pdo)
    {
    }

    /**
     * Cabeçalho: catálogo + estado.
     *
     * @return array<string,mixed>|null
     */
    public function fetchHeader(int $jobCatalogId): ?array
    {
        $sql = '
            SELECT
                c.id AS job_catalog_id,
                c.empresa,
                c.servidor,
                c.job,
                c.repositorio,
                c.timezone,
                c.schedule_type,
                c.expected_start_time,
                c.grace_minutes,
                c.max_expected_duration_minutes,
                c.warning_policy,
                c.criticality,
                s.last_execution_id,
                s.last_seen_at,
                s.last_status_reportado,
                s.last_status_efetivo,
                s.last_snapshot_id,
                s.last_duration_seconds,
                s.consecutive_warning_count,
                s.consecutive_error_count,
                s.last_warning_signature,
                s.missed_count,
                s.is_incident_open,
                s.incident_severity,
                s.updated_at
            FROM backup_job_catalog c
            INNER JOIN backup_job_state s ON s.job_catalog_id = c.id
            WHERE c.id = :id
        ';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $jobCatalogId]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /**
     * @return list<array<string,mixed>>
     */
    public function fetchExecutions(int $jobCatalogId): array
    {
        $sql = '
            SELECT
                id,
                received_at,
                status_reportado,
                snapshot_id,
                exit_code_backup,
                exit_code_forget,
                repository_locked,
                warning_source_read,
                warnings_access_denied,
                warnings_file_in_use
            FROM backup_execution_events
            WHERE job_catalog_id = :jid
            ORDER BY received_at DESC
            LIMIT ' . self::LIMIT_EVENTS . '
        ';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['jid' => $jobCatalogId]);

        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }

    /**
     * @return list<array<string,mixed>>
     */
    public function fetchAlerts(int $jobCatalogId): array
    {
        $sql = '
            SELECT
                id,
                execution_event_id,
                alert_type,
                severity,
                dedup_key,
                title,
                message,
                sent_at,
                acknowledged_at,
                resolved_at
            FROM backup_alert_events
            WHERE job_catalog_id = :jid
            ORDER BY sent_at DESC
            LIMIT ' . self::LIMIT_EVENTS . '
        ';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['jid' => $jobCatalogId]);

        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }

    /**
     * Agregação diária para gráfico simples (últimos N dias).
     *
     * @return list<array{bucket:string,status_reportado:string,cnt:int}>
     */
    public function fetchStatusSeries(int $jobCatalogId, int $days = 14): array
    {
        $sql = '
            SELECT
                to_char(date_trunc(\'day\', received_at), \'YYYY-MM-DD\') AS bucket,
                COALESCE(status_reportado, \'\') AS status_reportado,
                COUNT(*)::int AS cnt
            FROM backup_execution_events
            WHERE job_catalog_id = :jid
              AND received_at >= NOW() - (:days::int * INTERVAL \'1 day\')
            GROUP BY 1, 2
            ORDER BY 1 ASC, 2 ASC
        ';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['jid' => $jobCatalogId, 'days' => $days]);

        /** @var list<array<string,mixed>> */
        $rows = $stmt->fetchAll();
        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'bucket' => (string) $r['bucket'],
                'status_reportado' => (string) $r['status_reportado'],
                'cnt' => (int) $r['cnt'],
            ];
        }
        return $out;
    }

    /**
     * Linha do tempo de alertas (MVP: ordenado por sent_at).
     *
     * @return list<array<string,mixed>>
     */
    public function fetchAlertTimeline(int $jobCatalogId): array
    {
        return $this->fetchAlerts($jobCatalogId);
    }

    /**
     * Catálogo (backup_job_catalog) por id.
     *
     * @return array<string,mixed>|null
     */
    public function fetchCatalog(int $jobCatalogId): ?array
    {
        $sql = '
            SELECT
                id,
                empresa,
                servidor,
                job,
                repositorio,
                timezone,
                schedule_type,
                expected_start_time,
                grace_minutes,
                max_expected_duration_minutes,
                warning_policy,
                criticality,
                is_active
            FROM backup_job_catalog
            WHERE id = :id
        ';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $jobCatalogId]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /**
     * Estado atual (backup_job_state).
     *
     * @return array<string,mixed>|null
     */
    public function fetchState(int $jobCatalogId): ?array
    {
        $sql = '
            SELECT
                job_catalog_id,
                last_execution_id,
                last_seen_at,
                last_status_reportado,
                last_status_efetivo,
                last_snapshot_id,
                last_duration_seconds,
                consecutive_warning_count,
                consecutive_error_count,
                last_warning_signature,
                missed_count,
                is_incident_open,
                incident_severity,
                updated_at
            FROM backup_job_state
            WHERE job_catalog_id = :id
        ';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $jobCatalogId]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /**
     * Payload GET /api/jobs/{id}: catálogo, state, execuções e alertas recentes.
     * Retorna null se o job não existir no catálogo.
     *
     * @return array{catalog:array<string,mixed>,state:array<string,mixed>|null,executions:list<array<string,mixed>>,alerts:list<array<string,mixed>>}|null
     */
    public function fetchDetailApi(int $jobCatalogId): ?array
    {
        $catalog = $this->fetchCatalog($jobCatalogId);
        if ($catalog === null) {
            return null;
        }
        $state = $this->fetchState($jobCatalogId);

        return [
            'catalog' => $catalog,
            'state' => $state,
            'executions' => $this->fetchExecutions($jobCatalogId),
            'alerts' => $this->fetchAlerts($jobCatalogId),
        ];
    }
}
