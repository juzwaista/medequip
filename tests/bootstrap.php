<?php

/**
 * PHPUnit entrypoint — runs before Composer autoload and the Laravel app.
 *
 * If bootstrap/cache/config.php exists, Laravel ignores PHPUnit's DB_* env vars
 * and uses the cached mysql connection. Tests that use RefreshDatabase then run
 * migrate:fresh against your REAL database and wipe it.
 */
declare(strict_types=1);

$basePath = dirname(__DIR__);

$configCache = $basePath.'/bootstrap/cache/config.php';
if (is_file($configCache)) {
    @unlink($configCache);
}

$testingEnv = [
    'APP_ENV' => 'testing',
    'APP_MAINTENANCE_DRIVER' => 'file',
    'BCRYPT_ROUNDS' => '4',
    'BROADCAST_CONNECTION' => 'null',
    'CACHE_STORE' => 'array',
    'DB_CONNECTION' => 'sqlite',
    'DB_DATABASE' => ':memory:',
    'MAIL_MAILER' => 'array',
    'QUEUE_CONNECTION' => 'sync',
    'SESSION_DRIVER' => 'array',
];

foreach ($testingEnv as $key => $value) {
    putenv("{$key}={$value}");
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
}

require $basePath.'/vendor/autoload.php';
