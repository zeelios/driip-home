<?php

declare(strict_types=1);

namespace App\Support\Http;

class InputSanitizer
{
    /**
     * @param  array<string, mixed>  $input
     * @param  array<int, string>  $except
     * @return array<string, mixed>
     */
    public function sanitizeArray(array $input, array $except = [], bool $stripTags = true): array
    {
        return $this->sanitizeValue($input, $except, null, $stripTags);
    }

    /**
     * @param  mixed  $value
     * @param  array<int, string>  $except
     * @param  string|null  $path
     * @param  bool  $stripTags
     * @return mixed
     */
    public function sanitizeValue(mixed $value, array $except = [], ?string $path = null, bool $stripTags = true): mixed
    {
        if (is_array($value)) {
            $sanitized = [];

            foreach ($value as $key => $item) {
                $childPath = $path === null ? (string) $key : $path.'.'.$key;
                $sanitized[$key] = $this->sanitizeValue($item, $except, $childPath, $stripTags);
            }

            return $sanitized;
        }

        if (! is_string($value) || $this->isExcepted($path, $except)) {
            return $value;
        }

        return $this->sanitizeString($value, $stripTags);
    }

    private function sanitizeString(string $value, bool $stripTags): string
    {
        $value = str_replace("\0", '', $value);
        $value = preg_replace('/[\x01-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? $value;

        if ($stripTags) {
            $value = preg_replace('/<(script|style)\b[^>]*>.*?<\/\1>/is', '', $value) ?? $value;
            $value = strip_tags($value);
        }

        return trim($value);
    }

    /**
     * @param  array<int, string>  $except
     */
    private function isExcepted(?string $path, array $except): bool
    {
        if ($path === null) {
            return false;
        }

        foreach ($except as $pattern) {
            if ($path === $pattern) {
                return true;
            }

            $quoted = preg_quote($pattern, '/');
            $quoted = str_replace('\\*', '[^.]+', $quoted);

            if (preg_match('/^'.$quoted.'$/', $path) === 1) {
                return true;
            }
        }

        return false;
    }
}
