<?php

namespace App\Support;

/**
 * Browser URL for files on the public disk, always host-relative.
 * Avoids broken images when APP_URL does not match the request host (www/https/subfolder on shared hosting).
 */
final class PublicStorageUrl
{
    public static function url(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        return '/storage/'.ltrim($path, '/');
    }
}
