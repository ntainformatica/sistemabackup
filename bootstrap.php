<?php

declare(strict_types=1);

/**
 * Bootstrap mínimo do MVP dashboard NOC (PDO + helpers).
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/src/helpers.php';
require_once __DIR__ . '/src/Services/JobBoardService.php';
require_once __DIR__ . '/src/Services/JobDetailService.php';
require_once __DIR__ . '/src/ApiJobs.php';
