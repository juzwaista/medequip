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
        if (! $value instanceof UploadedFile) {
            $fail('Please choose a valid file to upload.');

            return;
        }

        if (! $value->isValid()) {
            $msg = 'The file could not be uploaded.';
            $err = $value->getError();
            if ($err === \UPLOAD_ERR_INI_SIZE || $err === \UPLOAD_ERR_FORM_SIZE) {
                $msg = 'The file is too large for the server (PHP upload_max_filesize / post_max_size). Use a smaller file or ask your host to raise those limits.';
            } elseif ($err === \UPLOAD_ERR_PARTIAL) {
                $msg = 'The upload was interrupted. Please try again.';
            } elseif ($err === \UPLOAD_ERR_NO_FILE) {
                $msg = 'No file was received. Please choose a file and try again.';
            }
            $fail($msg);

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
