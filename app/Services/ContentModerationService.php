<?php

namespace App\Services;

class ContentModerationService
{
    /**
     * @return array{text: string, blocked: bool, blocked_term: ?string, censored: bool}
     */
    public function process(string $text): array
    {
        $normalized = mb_strtolower($text);
        $blocked = (array) config('profanity.blocked', []);
        foreach ($blocked as $word) {
            $w = trim((string) $word);
            if ($w === '') {
                continue;
            }
            if ($this->containsWholeWord($normalized, mb_strtolower($w))) {
                return [
                    'text' => $text,
                    'blocked' => true,
                    'blocked_term' => $w,
                    'censored' => false,
                ];
            }
        }

        $censored = false;
        $out = $text;
        $censorList = array_values(array_unique(array_merge(
            ['damn', 'hell', 'crap', 'stupid', 'idiot', 'moron'],
            (array) config('profanity.censored', [])
        )));
        foreach ($censorList as $word) {
            $w = trim((string) $word);
            if ($w === '') {
                continue;
            }
            $pattern = '/\b'.preg_quote($w, '/').'\b/iu';
            $new = preg_replace_callback($pattern, function () {
                return '****';
            }, $out, -1, $count);
            if ($count > 0) {
                $censored = true;
                $out = $new;
            }
        }

        return [
            'text' => $out,
            'blocked' => false,
            'blocked_term' => null,
            'censored' => $censored,
        ];
    }

    private function containsWholeWord(string $haystack, string $needle): bool
    {
        return (bool) preg_match('/\b'.preg_quote($needle, '/').'\b/u', $haystack);
    }
}
