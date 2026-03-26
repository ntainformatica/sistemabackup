<?php

declare(strict_types=1);

/**
 * Conexão PostgreSQL via PDO.
 *
 * Variáveis de ambiente (opcionais):
 * - DB_DSN   ex.: pgsql:host=127.0.0.1;port=5432;dbname=backups
 * - DB_USER
 * - DB_PASS
 */

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = getenv('DB_DSN') ?: '';
    $user = getenv('DB_USER') ?: '';
    $pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';

    if ($dsn === '') {
        $local = __DIR__ . '/database.local.php';
        if (is_file($local)) {
            /** @var array{dsn:string,user:string,pass:string} $cfg */
            $cfg = require $local;
            $dsn = $cfg['dsn'];
            $user = $cfg['user'];
            $pass = $cfg['pass'];
        }
    }

    if ($dsn === '') {
        throw new RuntimeException(
            'Configure DB: defina DB_DSN/DB_USER/DB_PASS ou crie config/database.local.php (veja database.local.example.php).'
        );
    }

    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    return $pdo;
}
