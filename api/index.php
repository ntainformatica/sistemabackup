<?php

declare(strict_types=1);

/**
 * Contrato HTTP: GET /api/jobs/board , GET /api/jobs/{id} , GET /api/security/events
 * Fallback sem mod_rewrite: api/index.php?__path=jobs/board ou ?__path=jobs/123
 */

require_once dirname(__DIR__) . '/bootstrap.php';

Auth::initSession();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    http_response_code(405);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => 'Method Not Allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

$routePath = '';
if (isset($_GET['__path'])) {
    $routePath = trim((string) $_GET['__path'], '/');
} else {
    $uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    if (is_string($uriPath)) {
        $pos = strpos($uriPath, '/api/');
        if ($pos !== false) {
            $routePath = trim(substr($uriPath, $pos + strlen('/api/')), '/');
        }
    }
}

Auth::requireAuthApi();

if ($routePath === 'jobs/board') {
    ApiJobs::sendBoardJson(db());
    exit;
}

if (preg_match('#^jobs/([1-9][0-9]*)$#', $routePath, $m)) {
    ApiJobs::sendJobJson(db(), (int) $m[1]);
    exit;
}

if ($routePath === 'security/events') {
    ApiSecurity::sendEventsJson(db());
    exit;
}

http_response_code(404);
header('Content-Type: application/json; charset=utf-8');
echo json_encode(
    [
        'ok' => false,
        'error' => 'Rota não encontrada',
        'hint' => 'Use GET /api/jobs/board, GET /api/jobs/{id} ou GET /api/security/events. Sem rewrite: ?__path=...',
    ],
    JSON_UNESCAPED_UNICODE
);
