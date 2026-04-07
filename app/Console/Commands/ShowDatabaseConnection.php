<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ShowDatabaseConnection extends Command
{
    protected $signature = 'db:where-am-i';

    protected $description = 'Print the default database connection Laravel is using (helps debug “empty users” / wrong DB).';

    public function handle(): int
    {
        $default = config('database.default');
        $cfg = config("database.connections.{$default}");
        $database = $cfg['database'] ?? '(unknown)';
        $driver = $cfg['driver'] ?? '(unknown)';

        $this->info('APP_ENV: '.config('app.env'));
        $this->info("Default connection: {$default}");
        $this->info("Driver: {$driver}");
        $this->line("Database: {$database}");

        if ($driver === 'sqlite' && $database !== ':memory:') {
            $this->warn('If phpMyAdmin shows MySQL but this says sqlite, Laravel is not using your XAMPP MySQL database.');
        }

        if ($driver === 'mysql' || $driver === 'mariadb') {
            try {
                $dbName = DB::selectOne('select database() as d');
                $this->line('Live database(): '.($dbName->d ?? '(n/a)'));
            } catch (\Throwable $e) {
                $this->warn('Could not query current database: '.$e->getMessage());
            }
        }

        if (app()->configurationIsCached()) {
            $this->warn('Config is cached (bootstrap/cache/config.php). Run: php artisan config:clear');
        }

        $this->newLine();
        $this->comment('Tips:');
        $this->line('• Email verification does not delete users; it only sets email_verified_at.');
        $this->line('• `accounts:purge-deactivated` is scheduled daily only in production (not local).');
        $this->warn('• If you use `php artisan test` while bootstrap/cache/config.php exists, tests used to');
        $this->line('  run migrate:fresh against your REAL MySQL (wiping users). PHPUnit now uses tests/bootstrap.php');
        $this->line('  to remove that cache first and force sqlite :memory:.');
        if (app()->environment('local')) {
            $this->line('• Recreate a test login: composer run dev-account  (or: php artisan db:seed --class=LocalTestAccountSeeder)');
        }

        return self::SUCCESS;
    }
}
