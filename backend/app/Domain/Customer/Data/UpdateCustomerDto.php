<?php

declare(strict_types=1);

namespace App\Domain\Customer\Data;

/**
 * Data Transfer Object for updating an existing customer.
 *
 * All fields are optional; only non-null values will be applied to the model.
 */
readonly class UpdateCustomerDto
{
    /**
     * @param string|null $firstName Updated given name.
     * @param string|null $lastName  Updated family name.
     * @param string|null $email     Updated email address.
     * @param string|null $phone     Updated phone number.
     * @param string|null $gender    Updated gender.
     * @param string|null $source    Updated acquisition source.
     * @param string|null $notes     Updated internal notes.
     */
    public function __construct(
        public ?string $firstName = null,
        public ?string $lastName  = null,
        public ?string $email     = null,
        public ?string $phone     = null,
        public ?string $gender    = null,
        public ?string $source    = null,
        public ?string $notes     = null,
    ) {}

    /**
     * Build a DTO from a validated request array.
     *
     * @param  array<string,mixed>  $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            firstName: $data['first_name'] ?? null,
            lastName:  $data['last_name']  ?? null,
            email:     $data['email']      ?? null,
            phone:     $data['phone']      ?? null,
            gender:    $data['gender']     ?? null,
            source:    $data['source']     ?? null,
            notes:     $data['notes']      ?? null,
        );
    }
}
