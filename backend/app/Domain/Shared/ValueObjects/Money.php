<?php

declare(strict_types=1);

namespace App\Domain\Shared\ValueObjects;

use InvalidArgumentException;

/**
 * Immutable value object representing a VND monetary amount.
 *
 * All monetary values in the system are stored as integer VND (no decimals).
 * This value object ensures non-negative values and provides formatting helpers.
 */
final class Money
{
    /**
     * @param  int  $amount  Amount in VND (must be >= 0).
     *
     * @throws InvalidArgumentException  If amount is negative.
     */
    public function __construct(private readonly int $amount)
    {
        if ($amount < 0) {
            throw new InvalidArgumentException("Monetary amount cannot be negative: {$amount}");
        }
    }

    /**
     * Create a Money instance from an integer amount.
     *
     * @param  int  $amount
     */
    public static function of(int $amount): self
    {
        return new self($amount);
    }

    /** Return the raw integer amount in VND. */
    public function amount(): int
    {
        return $this->amount;
    }

    /** Add another Money value and return a new instance. */
    public function add(self $other): self
    {
        return new self($this->amount + $other->amount);
    }

    /** Subtract another Money value and return a new instance. */
    public function subtract(self $other): self
    {
        return new self(max(0, $this->amount - $other->amount));
    }

    /**
     * Apply a percentage discount (0–100) and return the discounted amount.
     *
     * @param  float  $percent  e.g. 20 for 20%.
     */
    public function applyDiscount(float $percent): self
    {
        $discount = (int) round($this->amount * ($percent / 100));

        return new self($this->amount - $discount);
    }

    /**
     * Format as Vietnamese dong string, e.g. "784.000đ".
     */
    public function format(): string
    {
        return number_format($this->amount, 0, ',', '.') . 'đ';
    }

    /** @return string */
    public function __toString(): string
    {
        return (string) $this->amount;
    }
}
