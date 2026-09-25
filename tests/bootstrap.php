<?php

/**
 * PHPUnit entrypoint — runs before Composer autoload and the Laravel app.
 *
 * If bootstrap/cache/config.php exists, Laravel ignores PHPUnit's DB_* env vars
 * and uses the cached mysql connection. Tests that use RefreshDatabase then run
 * migrate:fresh against your REAL database and wipe it.
 *
 * Tests run against real MySQL (not SQLite) so behavior actually matches production —
 * but against `medequip_testing`, a database dedicated to test runs, never `medequip`
 * itself. RefreshDatabase runs migrate:fresh against whatever DB_DATABASE points to, so
 * DB_DATABASE below must never be changed to the real database name.
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
    'DB_CONNECTION' => 'mysql',
    'DB_DATABASE' => 'medequip_testing',
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
