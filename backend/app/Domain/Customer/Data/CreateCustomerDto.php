<?php

declare(strict_types=1);

namespace App\Domain\Customer\Data;

/**
 * Data Transfer Object for creating a new customer.
 *
 * Carries only the fields required at registration time.
 * All optional contact and profile fields are nullable.
 */
readonly class CreateCustomerDto
{
    /**
     * @param string      $firstName  Customer's given name.
     * @param string      $lastName   Customer's family name.
     * @param string|null $email      Optional email address.
     * @param string|null $phone      Optional phone number.
     * @param string|null $gender     Optional gender (male, female, other).
     * @param string|null $source     Optional acquisition source (web, app, etc.).
     * @param string|null $notes      Optional internal notes.
     */
    public function __construct(
        public string  $firstName,
        public string  $lastName,
        public ?string $email   = null,
        public ?string $phone   = null,
        public ?string $gender  = null,
        public ?string $source  = null,
        public ?string $notes   = null,
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
            firstName: $data['first_name'],
            lastName:  $data['last_name'],
            email:     $data['email']  ?? null,
            phone:     $data['phone']  ?? null,
            gender:    $data['gender'] ?? null,
            source:    $data['source'] ?? null,
            notes:     $data['notes']  ?? null,
        );
    }
}
