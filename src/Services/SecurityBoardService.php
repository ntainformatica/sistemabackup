<?php

declare(strict_types=1);

/**
 * Lista e detalhe de eventos do módulo Segurança (Fase 1).
 */
final class SecurityBoardService
{
    public const LIMIT_DEFAULT = 500;

    public const LIMIT_MAX = 1000;

    public function __construct(private PDO $pdo)
    {
    }

    /**
     * Limite de linhas para listagem (GET `limit`, default 500, máximo 1000).
     */
    public static function clampLimit(?string $raw): int
    {
        if ($raw === null || trim($raw) === '') {
            return self::LIMIT_DEFAULT;
        }
        $n = (int) $raw;
        if ($n < 1) {
            return self::LIMIT_DEFAULT;
        }

        return min($n, self::LIMIT_MAX);
    }

    /**
     * @param array{
     *   empresa?:string,
     *   server_name?:string,
     *   severity?:string,
     *   date_from?:string,
     *   date_to?:string,
     *   limit?:int
     * } $filters
     * @return list<array<string,mixed>>
     */
    public function fetchEvents(array $filters): array
    {
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['empresa'])) {
            $where[] = 'empresa = :empresa';
            $params['empresa'] = $filters['empresa'];
        }
        if (!empty($filters['server_name'])) {
            $where[] = 'server_name = :server_name';
            $params['server_name'] = $filters['server_name'];
        }
        if (!empty($filters['severity'])) {
            $where[] = 'lower(severity) = lower(:severity)';
            $params['severity'] = strtolower(trim((string) $filters['severity']));
        }
        if (!empty($filters['date_from'])) {
            $where[] = 'event_timestamp >= CAST(:date_from AS date)';
            $params['date_from'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where[] = 'event_timestamp < CAST(:date_to AS date) + interval \'1 day\'';
            $params['date_to'] = $filters['date_to'];
        }

        $sql = '
            SELECT
                id,
                event_uid,
                event_source,
                event_type,
                severity,
                empresa,
                server_name,
                source_ip,
                destination_port,
                username,
                title,
                message,
                event_timestamp,
                received_at,
                raw_payload_json
            FROM security_events
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY event_timestamp DESC NULLS LAST, id DESC
            LIMIT ' . (int) ($filters['limit'] ?? self::LIMIT_DEFAULT) . '
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }

    /**
     * @return array{empresas:list<string>,server_names:list<string>}
     */
    public function filterOptions(): array
    {
        $e = $this->pdo->query(
            'SELECT DISTINCT empresa FROM security_events ORDER BY empresa'
        )->fetchAll(PDO::FETCH_COLUMN);
        $s = $this->pdo->query(
            'SELECT DISTINCT server_name FROM security_events ORDER BY server_name'
        )->fetchAll(PDO::FETCH_COLUMN);

        return [
            'empresas' => array_map('strval', $e ?: []),
            'server_names' => array_map('strval', $s ?: []),
        ];
    }

    /**
     * @return array<string,mixed>|null
     */
    public function fetchEventById(int $id): ?array
    {
        if ($id < 1) {
            return null;
        }
        $sql = '
            SELECT
                id,
                event_uid,
                event_source,
                event_type,
                severity,
                empresa,
                server_name,
                source_ip,
                destination_port,
                username,
                title,
                message,
                event_timestamp,
                received_at,
                created_at,
                raw_payload_json
            FROM security_events
            WHERE id = :id
            LIMIT 1
        ';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }
}
