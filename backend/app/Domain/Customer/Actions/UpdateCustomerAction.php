<?php

declare(strict_types=1);

namespace App\Domain\Customer\Actions;

use App\Domain\Customer\Data\UpdateCustomerDto;
use App\Domain\Customer\Models\Customer;

/**
 * Action responsible for updating an existing customer's profile fields.
 *
 * Only non-null DTO properties are applied, allowing partial updates
 * without risk of overwriting existing data with empty values.
 */
class UpdateCustomerAction
{
    /**
     * Apply updates to the given customer model.
     *
     * @param  UpdateCustomerDto  $dto       Validated update data.
     * @param  Customer           $customer  The customer to update.
     * @return Customer                      The updated customer model.
     */
    public function execute(UpdateCustomerDto $dto, Customer $customer): Customer
    {
        $updates = array_filter([
            'first_name' => $dto->firstName,
            'last_name'  => $dto->lastName,
            'email'      => $dto->email,
            'phone'      => $dto->phone,
            'gender'     => $dto->gender,
            'source'     => $dto->source,
            'notes'      => $dto->notes,
        ], fn (mixed $value): bool => $value !== null);

        $customer->update($updates);
        $customer->refresh();

        return $customer;
    }
}
