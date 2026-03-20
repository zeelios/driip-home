<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Staff\Models\User as DomainUser;

/**
 * Alias for the domain Staff User model.
 *
 * Required by some Laravel internals (Sanctum, password reset, etc.)
 * that reference App\Models\User by convention.
 *
 * @see \App\Domain\Staff\Models\User
 */
class User extends DomainUser {}
