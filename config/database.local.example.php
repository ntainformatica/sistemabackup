<?php

declare(strict_types=1);

/**
 * Copie para database.local.php e ajuste credenciais.
 * Não versionar database.local.php em repositórios públicos.
 */

return [
    'dsn' => 'pgsql:host=127.0.0.1;port=5432;dbname=SEU_BANCO',
    'user' => 'postgres',
    'pass' => '',
];
