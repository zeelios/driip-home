<?php

declare(strict_types=1);

namespace App\Domain\Staff\Exceptions;

use RuntimeException;

/**
 * Thrown when attempting to create or update staff with a duplicate email address.
 */
class StaffEmailAlreadyExistsException extends RuntimeException
{
    /**
     * @param string $email The duplicate email address that triggered this exception.
     */
    public function __construct(string $email)
    {
        parent::__construct("A staff member with email [{$email}] already exists.");
    }
}
