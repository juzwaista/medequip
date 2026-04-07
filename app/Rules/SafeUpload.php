<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/**
 * Blocks risky client-reported extensions and enforces an allow-list.
 * Use together with Laravel's {@see \Illuminate\Validation\Rules\File::image()} or mimes rules.
 */
class SafeUpload implements ValidationRule
{
    /** @var list<string> */
    private const DANGEROUS_EXTENSIONS = [
        'php', 'phtml', 'phar', 'exe', 'sh', 'bash', 'bat', 'cmd', 'com', 'scr',
        'dll', 'so', 'dylib', 'sql', 'env', 'ini', 'log', 'htaccess', 'htpasswd', 'cgi', 'pl',
        'jsp', 'asp', 'aspx', 'jar', 'war', 'deb', 'rpm', 'msi', 'vbs', 'ps1',
        'svg', // XSS when served from public disk as image
    ];

    /**
     * @param  list<string>  $allowedExtensions  lowercase, no dots
     */
    public function __construct(
        private readonly array $allowedExtensions,
    ) {}

    public static function image(): self
    {
        return new self(['jpg', 'jpeg', 'png', 'gif', 'webp', 'heic', 'heif', 'bmp']);
    }

    public static function document(): self
    {
        return new self(['pdf', 'jpg', 'jpeg', 'png']);
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile || ! $value->isValid()) {
            return;
        }

        $name = strtolower($value->getClientOriginalName());
        if (str_contains($name, '.env') || preg_match('/(^|[\\/])\.env(\.|$)/', $name)) {
            $fail('This file type cannot be uploaded.');

            return;
        }

        $ext = strtolower($value->getClientOriginalExtension());
        if ($ext === '') {
            $fail('The file must have a recognized extension.');

            return;
        }

        foreach (self::DANGEROUS_EXTENSIONS as $blocked) {
            if ($ext === $blocked) {
                $fail('This file type cannot be uploaded.');

                return;
            }
            if (preg_match('/\.'.preg_quote($blocked, '/').'(\.|$)/', $name)) {
                $fail('This file type cannot be uploaded.');

                return;
            }
        }

        if (! in_array($ext, $this->allowedExtensions, true)) {
            $fail('This file type cannot be uploaded.');
        }
    }
}
