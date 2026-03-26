<?php

declare(strict_types=1);

/**
 * Lista NOC: fonte principal backup_job_state + join mínimo em backup_job_catalog.
 */
final class JobBoardService
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * @param array{
     *   empresa?:string,
     *   servidor?:string,
     *   status?:string,
     *   incident?:string,
     *   severity?:string
     * } $filters
     * @return list<array<string,mixed>>
     */
    public function fetchBoard(array $filters): array
    {
        $where = ['c.is_active = TRUE'];
        $params = [];

        if (!empty($filters['empresa'])) {
            $where[] = 'c.empresa = :empresa';
            $params['empresa'] = $filters['empresa'];
        }
        if (!empty($filters['servidor'])) {
            $where[] = 'c.servidor = :servidor';
            $params['servidor'] = $filters['servidor'];
        }
        if (!empty($filters['status'])) {
            $where[] = 's.last_status_efetivo = :status';
            $params['status'] = $filters['status'];
        }
        if (isset($filters['incident']) && $filters['incident'] !== '' && $filters['incident'] !== null) {
            $where[] = 's.is_incident_open = :incident';
            $params['incident'] = (string) $filters['incident'] === '1';
        }
        if (!empty($filters['severity'])) {
            $where[] = 's.incident_severity = :severity';
            $params['severity'] = $filters['severity'];
        }

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
                c.criticality,
                s.last_status_reportado,
                s.last_status_efetivo,
                s.is_incident_open,
                s.incident_severity,
                s.last_snapshot_id,
                s.last_seen_at,
                s.last_duration_seconds,
                s.consecutive_warning_count,
                s.consecutive_error_count,
                s.last_warning_signature,
                s.missed_count,
                s.updated_at
            FROM backup_job_state s
            INNER JOIN backup_job_catalog c ON c.id = s.job_catalog_id
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY
                s.is_incident_open DESC NULLS LAST,
                CASE s.last_status_efetivo
                    WHEN \'ERROR\' THEN 1
                    WHEN \'WARNING\' THEN 2
                    WHEN \'MISSED\' THEN 3
                    WHEN \'OK\' THEN 4
                    ELSE 5
                END,
                s.updated_at DESC NULLS LAST
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }

    /**
     * Valores distintos para filtros (empresa, servidor).
     *
     * @return array{empresas:list<string>,servidores:list<string>}
     */
    public function filterOptions(): array
    {
        $e = $this->pdo->query(
            'SELECT DISTINCT empresa FROM backup_job_catalog WHERE is_active = TRUE ORDER BY empresa'
        )->fetchAll(PDO::FETCH_COLUMN);
        $s = $this->pdo->query(
            'SELECT DISTINCT servidor FROM backup_job_catalog WHERE is_active = TRUE ORDER BY servidor'
        )->fetchAll(PDO::FETCH_COLUMN);

        return [
            'empresas' => array_map('strval', $e ?: []),
            'servidores' => array_map('strval', $s ?: []),
        ];
    }
}
