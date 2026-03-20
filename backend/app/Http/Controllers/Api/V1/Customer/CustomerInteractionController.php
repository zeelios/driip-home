<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Customer;

use App\Domain\Customer\Models\Customer;
use App\Domain\Customer\Models\CustomerInteraction;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\Customer\CustomerInteractionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Manages interaction records attached to a specific customer.
 */
class CustomerInteractionController extends BaseApiController
{
    /**
     * List all interactions for a given customer, paginated.
     *
     * @param  Customer  $customer
     * @return AnonymousResourceCollection|JsonResponse
     */
    public function index(Customer $customer): AnonymousResourceCollection|JsonResponse
    {
        try {
            $interactions = $customer->interactions()
                ->with('createdBy')
                ->latest('created_at')
                ->paginate(20);

            return CustomerInteractionResource::collection($interactions);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'LIST_INTERACTIONS');
        }
    }

    /**
     * Create a new interaction record for the given customer.
     *
     * @param  Request   $request
     * @param  Customer  $customer
     * @return CustomerInteractionResource|JsonResponse
     */
    public function store(Request $request, Customer $customer): CustomerInteractionResource|JsonResponse
    {
        try {
            $validated = $request->validate([
                'type'         => ['required', 'string', 'max:50'],
                'channel'      => ['nullable', 'string', 'max:50'],
                'summary'      => ['nullable', 'string'],
                'outcome'      => ['nullable', 'string'],
                'follow_up_at' => ['nullable', 'date'],
            ]);

            /** @var CustomerInteraction $interaction */
            $interaction = $customer->interactions()->create([
                ...$validated,
                'created_by' => $request->user()?->id,
                'created_at' => now(),
            ]);

            $interaction->load('createdBy');

            return (new CustomerInteractionResource($interaction))->response()->setStatusCode(201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e, 'CREATE_INTERACTION');
        } catch (\Throwable $e) {
            return $this->serverError($e, 'CREATE_INTERACTION');
        }
    }
}
