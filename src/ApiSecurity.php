<?php

declare(strict_types=1);

/**
 * Respostas JSON GET: /api/security/events
 */
final class ApiSecurity
{
    public static function sendEventsJson(PDO $pdo): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $svc = new SecurityBoardService($pdo);
            $filters = [
                'empresa' => isset($_GET['empresa']) ? (string) $_GET['empresa'] : '',
                'server_name' => isset($_GET['server_name']) ? (string) $_GET['server_name'] : '',
                'severity' => isset($_GET['severity']) ? (string) $_GET['severity'] : '',
                'date_from' => isset($_GET['date_from']) ? (string) $_GET['date_from'] : '',
                'date_to' => isset($_GET['date_to']) ? (string) $_GET['date_to'] : '',
                'limit' => SecurityBoardService::clampLimit(isset($_GET['limit']) ? (string) $_GET['limit'] : null),
            ];
            $rows = $svc->fetchEvents($filters);
            $items = [];
            foreach ($rows as $row) {
                if (is_array($row)) {
                    $row['event_timestamp'] = noc_format_timestamptz_br(
                        isset($row['event_timestamp']) ? (string) $row['event_timestamp'] : null
                    );
                    if (array_key_exists('received_at', $row)) {
                        $row['received_at'] = noc_format_timestamptz_br(
                            $row['received_at'] !== null && $row['received_at'] !== ''
                                ? (string) $row['received_at']
                                : null
                        );
                    }
                }
                $items[] = $row;
            }
            echo json_encode(
                [
                    'ok' => true,
                    'items' => $items,
                    'limit' => $filters['limit'],
                    'generated_at' => gmdate('c'),
                ],
                JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
            );
        } catch (Throwable) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => 'Erro ao carregar eventos de segurança'], JSON_UNESCAPED_UNICODE);
        }
    }
}
