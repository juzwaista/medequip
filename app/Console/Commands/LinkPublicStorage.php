<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Laravel's {@see \Illuminate\Foundation\Console\StorageLinkCommand} calls {@see \Illuminate\Filesystem\Filesystem::link},
 * which falls back to exec('ln -s ...') when symlink() is missing. Many shared hosts disable exec().
 * This command uses PHP's symlink() only, or prints an exact ln command for SSH.
 */
class LinkPublicStorage extends Command
{
    protected $signature = 'medequip:link-storage
                            {--force : Remove an existing symlink at public/storage before linking}';

    protected $description = 'Link public/storage → storage/app/public (Hostinger / exec()-disabled safe)';

    public function handle(): int
    {
        $link = public_path('storage');
        $target = storage_path('app/public');

        if (! is_dir($target)) {
            $this->error("Missing directory: {$target}");
            $this->line('Run: mkdir -p storage/app/public && chmod -R ug+rw storage');

            return self::FAILURE;
        }

        if (file_exists($link) || is_link($link)) {
            if (is_link($link)) {
                if ($this->option('force')) {
                    @unlink($link);
                } else {
                    $this->info('Symlink already exists: '.$link.' → '.readlink($link));

                    return self::SUCCESS;
                }
            } elseif (is_dir($link)) {
                $this->error("{$link} is a real folder, not a symlink.");
                $this->line('Rename or delete that folder (it should not contain real uploads), then run: php artisan medequip:link-storage --force');

                return self::FAILURE;
            } else {
                $this->error("{$link} exists and is not a symlink. Remove it, then run again.");

                return self::FAILURE;
            }
        }

        if (function_exists('symlink') && @symlink($target, $link)) {
            $this->info("Linked: {$link} → {$target}");

            return self::SUCCESS;
        }

        $this->warn('PHP could not create the symlink (symlink() missing, disabled, or permissions).');
        $this->newLine();
        $this->comment('Use SSH and run exactly (one line):');
        $this->line('ln -sfn '.escapeshellarg($target).' '.escapeshellarg($link));
        $this->newLine();
        $this->line('Then verify: ls -la '.escapeshellarg(dirname($link)));

        return self::FAILURE;
    }
}
