<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Loyalty;

use App\Domain\Customer\Models\Customer;
use App\Domain\Loyalty\Actions\EarnPointsAction;
use App\Domain\Loyalty\Actions\RedeemPointsAction;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\Loyalty\LoyaltyAccountResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Manages loyalty account operations for a customer (show, earn, redeem).
 */
class LoyaltyAccountController extends BaseApiController
{
    /**
     * Show the loyalty account for a given customer.
     *
     * @param  Customer  $customer
     * @return LoyaltyAccountResource|JsonResponse
     */
    public function show(Customer $customer): LoyaltyAccountResource|JsonResponse
    {
        try {
            $account = $customer->loyaltyAccount()->with('tier')->first();

            if ($account === null) {
                return $this->notFound('SHOW_LOYALTY_ACCOUNT', 'No loyalty account found for this customer.');
            }

            return new LoyaltyAccountResource($account);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'SHOW_LOYALTY_ACCOUNT');
        }
    }

    /**
     * Manually credit points to a customer's loyalty account.
     *
     * @param  Request          $request
     * @param  Customer         $customer
     * @param  EarnPointsAction $action
     * @return JsonResponse
     */
    public function earn(Request $request, Customer $customer, EarnPointsAction $action): JsonResponse
    {
        try {
            $validated = $request->validate([
                'points'         => ['required', 'integer', 'min:1'],
                'reference_type' => ['nullable', 'string', 'max:100'],
                'reference_id'   => ['nullable', 'string', 'max:36'],
                'description'    => ['nullable', 'string'],
            ]);

            $account = $customer->loyaltyAccount;

            if ($account === null) {
                return $this->notFound('EARN_LOYALTY_POINTS', 'No loyalty account found for this customer.');
            }

            $transaction = $action->execute(
                loyaltyAccountId: $account->id,
                points:           $validated['points'],
                referenceType:    $validated['reference_type'] ?? null,
                referenceId:      $validated['reference_id']   ?? null,
                description:      $validated['description']    ?? null,
                createdBy:        $request->user()?->id,
            );

            return response()->json(['success' => true, 'transaction_id' => $transaction->id], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e, 'EARN_LOYALTY_POINTS');
        } catch (\Throwable $e) {
            return $this->serverError($e, 'EARN_LOYALTY_POINTS');
        }
    }

    /**
     * Redeem points from a customer's loyalty account.
     *
     * @param  Request             $request
     * @param  Customer            $customer
     * @param  RedeemPointsAction  $action
     * @return JsonResponse
     */
    public function redeem(Request $request, Customer $customer, RedeemPointsAction $action): JsonResponse
    {
        try {
            $validated = $request->validate([
                'points'      => ['required', 'integer', 'min:1'],
                'order_id'    => ['nullable', 'string', 'max:36'],
                'description' => ['nullable', 'string'],
            ]);

            $account = $customer->loyaltyAccount;

            if ($account === null) {
                return $this->notFound('REDEEM_LOYALTY_POINTS', 'No loyalty account found for this customer.');
            }

            $transaction = $action->execute(
                loyaltyAccountId: $account->id,
                points:           $validated['points'],
                orderId:          $validated['order_id']    ?? null,
                description:      $validated['description'] ?? null,
                createdBy:        $request->user()?->id,
            );

            return response()->json(['success' => true, 'transaction_id' => $transaction->id], 200);
        } catch (\InvalidArgumentException $e) {
            return \App\Http\Resources\ErrorResource::fromException($e, 'REDEEM_LOYALTY_POINTS')
                ->response()->setStatusCode(422);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e, 'REDEEM_LOYALTY_POINTS');
        } catch (\Throwable $e) {
            return $this->serverError($e, 'REDEEM_LOYALTY_POINTS');
        }
    }
}
