<?php

declare(strict_types=1);

namespace App\Domain\Customer\Exceptions;

use RuntimeException;

/**
 * Thrown when attempting to create or update a customer with a phone number
 * that is already registered to another customer.
 */
class CustomerPhoneAlreadyExistsException extends RuntimeException
{
    /**
     * Create a new exception instance.
     *
     * @param  string  $phone  The duplicate phone number.
     */
    public function __construct(string $phone)
    {
        parent::__construct("A customer with the phone number '{$phone}' already exists.");
    }
}
