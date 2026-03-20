<?php

declare(strict_types=1);

namespace App\Domain\Customer\Actions;

use App\Domain\Customer\Data\CreateCustomerDto;
use App\Domain\Customer\Exceptions\CustomerPhoneAlreadyExistsException;
use App\Domain\Customer\Models\Customer;
use App\Domain\Shared\Traits\GeneratesCode;
use Illuminate\Support\Facades\DB;

/**
 * Action responsible for creating a new customer record.
 *
 * Validates phone uniqueness, generates a customer code in the
 * DRP-C-XXXXX format, and wraps the operation in a database transaction.
 */
class CreateCustomerAction
{
    use GeneratesCode;

    /**
     * Execute the customer creation.
     *
     * Checks for duplicate phone numbers before persisting. The customer code
     * is derived from the auto-incremented sequence of total customers.
     *
     * @param  CreateCustomerDto  $dto  Validated creation data.
     * @return Customer               The newly created customer.
     *
     * @throws CustomerPhoneAlreadyExistsException If the phone is already taken.
     */
    public function execute(CreateCustomerDto $dto): Customer
    {
        if ($dto->phone !== null) {
            $exists = Customer::withTrashed()
                ->where('phone', $dto->phone)
                ->exists();

            if ($exists) {
                throw new CustomerPhoneAlreadyExistsException($dto->phone);
            }
        }

        return DB::transaction(function () use ($dto): Customer {
            $sequence = Customer::withTrashed()->count() + 1;

            /** @var Customer $customer */
            $customer = Customer::create([
                'customer_code' => $this->buildCode('DRP-C', $sequence),
                'first_name'    => $dto->firstName,
                'last_name'     => $dto->lastName,
                'email'         => $dto->email,
                'phone'         => $dto->phone,
                'gender'        => $dto->gender,
                'source'        => $dto->source,
                'notes'         => $dto->notes,
                'tags'          => [],
                'is_blocked'    => false,
                'total_orders'  => 0,
                'total_spent'   => 0,
            ]);

            return $customer;
        });
    }
}
