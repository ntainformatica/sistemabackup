<?php

declare(strict_types=1);

/**
 * Respostas JSON compartilhadas: /api/jobs/board e /api/jobs/{id}.
 */
final class ApiJobs
{
    public static function sendBoardJson(PDO $pdo): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $svc = new JobBoardService($pdo);
            $filters = [
                'empresa' => isset($_GET['empresa']) ? (string) $_GET['empresa'] : '',
                'servidor' => isset($_GET['servidor']) ? (string) $_GET['servidor'] : '',
                'status' => isset($_GET['status']) ? (string) $_GET['status'] : '',
                'incident' => isset($_GET['incident']) ? (string) $_GET['incident'] : '',
                'severity' => isset($_GET['severity']) ? (string) $_GET['severity'] : '',
            ];
            $rows = $svc->fetchBoard($filters);
            echo json_encode(
                [
                    'ok' => true,
                    'items' => $rows,
                    'generated_at' => gmdate('c'),
                ],
                JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
            );
        } catch (Throwable) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => 'Erro ao carregar dashboard'], JSON_UNESCAPED_UNICODE);
        }
    }

    public static function sendJobJson(PDO $pdo, int $jobCatalogId): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if ($jobCatalogId < 1) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'id inválido'], JSON_UNESCAPED_UNICODE);
            return;
        }
        try {
            $detail = new JobDetailService($pdo);
            $payload = $detail->fetchDetailApi($jobCatalogId);
            if ($payload === null) {
                http_response_code(404);
                echo json_encode(['ok' => false, 'error' => 'Job não encontrado'], JSON_UNESCAPED_UNICODE);
                return;
            }
            echo json_encode(
                [
                    'ok' => true,
                    'generated_at' => gmdate('c'),
                    'job_catalog_id' => $jobCatalogId,
                    'catalog' => $payload['catalog'],
                    'state' => $payload['state'],
                    'executions' => $payload['executions'],
                    'last_success_execution' => $payload['last_success_execution'],
                    'alerts' => $payload['alerts'],
                ],
                JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
            );
        } catch (Throwable) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => 'Erro ao carregar job'], JSON_UNESCAPED_UNICODE);
        }
    }
}
