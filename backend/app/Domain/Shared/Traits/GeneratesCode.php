<?php

declare(strict_types=1);

namespace App\Domain\Shared\Traits;

/**
 * Provides helper methods for generating human-readable entity codes.
 *
 * Example codes: DRP-C-00001, DRP-EMP-001, DRP-2603-0001
 */
trait GeneratesCode
{
    /**
     * Generate a zero-padded sequential code with a prefix.
     *
     * @param  string  $prefix  e.g. "DRP-C"
     * @param  int     $sequence  e.g. 42
     * @param  int     $pad  Pad length for the number (default 5).
     * @return string  e.g. "DRP-C-00042"
     */
    protected function buildCode(string $prefix, int $sequence, int $pad = 5): string
    {
        return $prefix . '-' . str_pad((string) $sequence, $pad, '0', STR_PAD_LEFT);
    }

    /**
     * Generate an order number in the format DRP-DDMM-XXXX.
     *
     * @param  int  $sequence  Daily sequence number.
     * @return string  e.g. "DRP-2103-0001"
     */
    protected function buildOrderNumber(int $sequence): string
    {
        $date = now()->timezone('Asia/Ho_Chi_Minh')->format('dmy');

        return 'DRP-' . $date . '-' . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
