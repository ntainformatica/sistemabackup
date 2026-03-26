<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$route = isset($_GET['route']) ? trim((string) $_GET['route']) : 'board';
if ($route === '') {
    $route = 'board';
}

if ($route === 'api_board') {
    ApiJobs::sendBoardJson(db());
    exit;
}

if ($route === 'job') {
    $id = isset($_GET['catalog_id']) ? (int) $_GET['catalog_id'] : 0;
    if ($id < 1) {
        http_response_code(400);
        echo 'Requisição inválida: catalog_id';
        exit;
    }
    try {
        $pdo = db();
        $detail = new JobDetailService($pdo);
        $header = $detail->fetchHeader($id);
        if ($header === null) {
            http_response_code(404);
            echo 'Job não encontrado';
            exit;
        }
        $executions = $detail->fetchExecutions($id);
        $alerts = $detail->fetchAlerts($id);
        $series = $detail->fetchStatusSeries($id, 14);
        $pageTitle = 'Detalhe — ' . (string) ($header['job'] ?? 'job');
        require __DIR__ . '/templates/job_detail.php';
    } catch (Throwable) {
        http_response_code(500);
        require __DIR__ . '/templates/error.php';
    }
    exit;
}

try {
    $pdo = db();
    $boardSvc = new JobBoardService($pdo);
    $opts = $boardSvc->filterOptions();
    $filters = [
        'empresa' => isset($_GET['empresa']) ? (string) $_GET['empresa'] : '',
        'servidor' => isset($_GET['servidor']) ? (string) $_GET['servidor'] : '',
        'status' => isset($_GET['status']) ? (string) $_GET['status'] : '',
        'incident' => isset($_GET['incident']) ? (string) $_GET['incident'] : '',
        'severity' => isset($_GET['severity']) ? (string) $_GET['severity'] : '',
    ];
    $rows = $boardSvc->fetchBoard($filters);
    $pageTitle = 'NOC — Backups';
    require __DIR__ . '/templates/board.php';
} catch (Throwable $e) {
    http_response_code(500);
    $pageTitle = 'Erro';
    $errorMessage = $e->getMessage();
    require __DIR__ . '/templates/error.php';
}
