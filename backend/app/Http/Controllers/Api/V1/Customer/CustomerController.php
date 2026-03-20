<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Customer;

use App\Domain\Customer\Actions\CreateCustomerAction;
use App\Domain\Customer\Actions\UpdateCustomerAction;
use App\Domain\Customer\Exceptions\CustomerPhoneAlreadyExistsException;
use App\Domain\Customer\Models\Customer;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Customer\CreateCustomerRequest;
use App\Http\Requests\Customer\UpdateCustomerRequest;
use App\Http\Resources\Customer\CustomerResource;
use App\Http\Resources\ErrorResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Handles all CRUD and action endpoints for the Customer resource.
 */
class CustomerController extends BaseApiController
{
    /**
     * List customers with filtering and pagination.
     *
     * @param  Request  $request
     * @return AnonymousResourceCollection|JsonResponse
     */
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $customers = QueryBuilder::for(Customer::class)
                ->allowedFilters(
                    AllowedFilter::partial('name', 'first_name'),
                    AllowedFilter::exact('email'),
                    AllowedFilter::exact('phone'),
                    AllowedFilter::exact('source'),
                    AllowedFilter::exact('is_blocked'),
                    AllowedFilter::scope('tags'),
                )
                ->paginate(20);

            return CustomerResource::collection($customers);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'LIST_CUSTOMERS');
        }
    }

    /**
     * Create a new customer.
     *
     * @param  CreateCustomerRequest  $request
     * @param  CreateCustomerAction   $action
     * @return CustomerResource|JsonResponse
     */
    public function store(CreateCustomerRequest $request, CreateCustomerAction $action): CustomerResource|JsonResponse
    {
        try {
            $customer = $action->execute($request->dto());
            return (new CustomerResource($customer))->response()->setStatusCode(201);
        } catch (CustomerPhoneAlreadyExistsException $e) {
            return ErrorResource::fromException($e, 'CREATE_CUSTOMER')->response()->setStatusCode(422);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'CREATE_CUSTOMER');
        }
    }

    /**
     * Show a single customer with loyalty and interaction count.
     *
     * @param  Customer  $customer
     * @return CustomerResource|JsonResponse
     */
    public function show(Customer $customer): CustomerResource|JsonResponse
    {
        try {
            $customer->loadMissing(['loyaltyAccount.tier'])->loadCount('interactions');
            return new CustomerResource($customer);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'SHOW_CUSTOMER');
        }
    }

    /**
     * Update an existing customer.
     *
     * @param  UpdateCustomerRequest  $request
     * @param  Customer               $customer
     * @param  UpdateCustomerAction   $action
     * @return CustomerResource|JsonResponse
     */
    public function update(UpdateCustomerRequest $request, Customer $customer, UpdateCustomerAction $action): CustomerResource|JsonResponse
    {
        try {
            return new CustomerResource($action->execute($request->dto(), $customer));
        } catch (\Throwable $e) {
            return $this->serverError($e, 'UPDATE_CUSTOMER');
        }
    }

    /**
     * Soft-delete a customer record.
     *
     * @param  Customer  $customer
     * @return JsonResponse
     */
    public function destroy(Customer $customer): JsonResponse
    {
        try {
            $customer->delete();
            return response()->json(['success' => true], 204);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'DELETE_CUSTOMER');
        }
    }

    /**
     * Block a customer, preventing them from placing orders.
     *
     * @param  Request   $request
     * @param  Customer  $customer
     * @return CustomerResource|JsonResponse
     */
    public function block(Request $request, Customer $customer): CustomerResource|JsonResponse
    {
        try {
            $customer->update([
                'is_blocked'     => true,
                'blocked_reason' => $request->input('blocked_reason'),
            ]);
            return new CustomerResource($customer->refresh());
        } catch (\Throwable $e) {
            return $this->serverError($e, 'BLOCK_CUSTOMER');
        }
    }

    /**
     * Show loyalty account for the customer.
     *
     * @param  Customer  $customer
     * @return JsonResponse
     */
    public function loyalty(Customer $customer): JsonResponse
    {
        try {
            $customer->loadMissing('loyaltyAccount.tier');
            $account = $customer->loyaltyAccount;

            if ($account === null) {
                return $this->notFound('SHOW_CUSTOMER_LOYALTY', 'No loyalty account found for this customer.');
            }

            return (new \App\Http\Resources\Loyalty\LoyaltyAccountResource($account))->response();
        } catch (\Throwable $e) {
            return $this->serverError($e, 'SHOW_CUSTOMER_LOYALTY');
        }
    }
}
