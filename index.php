<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

Auth::initSession();

$route = isset($_GET['route']) ? trim((string) $_GET['route']) : 'board';
if ($route === '') {
    $route = 'board';
}

if ($route === 'login') {
    if (Auth::isAuthenticated()) {
        $ret = isset($_GET['return']) ? (string) $_GET['return'] : '';
        header('Location: ' . Auth::safeReturnUrl($ret !== '' ? $ret : null), true, 302);
        exit;
    }
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        $username = isset($_POST['username']) ? (string) $_POST['username'] : '';
        $password = isset($_POST['password']) ? (string) $_POST['password'] : '';
        $returnRaw = isset($_POST['return']) ? (string) $_POST['return'] : '';
        try {
            $pdo = db();
            if (Auth::attemptLogin($pdo, $username, $password)) {
                $target = Auth::safeReturnUrl($returnRaw !== '' ? $returnRaw : null);
                header('Location: ' . $target, true, 302);
                exit;
            }
        } catch (Throwable) {
            // falha genérica — sem detalhe ao cliente
        }
        header('Location: index.php?route=login&err=1', true, 302);
        exit;
    }
    $pageTitle = 'Login — NOC';
    $loginError = isset($_GET['err']) && (string) $_GET['err'] === '1';
    $returnGet = isset($_GET['return']) ? (string) $_GET['return'] : '';
    $loginReturnUrl = Auth::safeReturnUrl($returnGet !== '' ? $returnGet : null);
    require __DIR__ . '/templates/login.php';
    exit;
}

if ($route === 'logout') {
    if (Auth::isAuthenticated()) {
        Auth::logout();
        Auth::initSession();
    }
    header('Location: index.php?route=login', true, 302);
    exit;
}

if ($route === 'api_board') {
    Auth::requireAuthApi();
    ApiJobs::sendBoardJson(db());
    exit;
}

if ($route === 'job') {
    Auth::requireAuthHtml();
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
        $lastSuccess = $detail->fetchLastSuccessExecution($id);
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

Auth::requireAuthHtml();

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
    $qBoard = $_GET;
    $qBoard['route'] = 'board';
    $boardLoginReturnUrl = 'index.php?' . http_build_query($qBoard);
    require __DIR__ . '/templates/board.php';
} catch (Throwable $e) {
    http_response_code(500);
    $pageTitle = 'Erro';
    $errorMessage = $e->getMessage();
    require __DIR__ . '/templates/error.php';
}
