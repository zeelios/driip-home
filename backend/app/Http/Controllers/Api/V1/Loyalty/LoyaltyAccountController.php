<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Loyalty;

use App\Domain\Customer\Models\Customer;
use App\Domain\Loyalty\Actions\EarnPointsAction;
use App\Domain\Loyalty\Actions\RedeemPointsAction;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Loyalty\EarnPointsRequest;
use App\Http\Requests\Loyalty\RedeemPointsRequest;
use App\Http\Resources\Loyalty\LoyaltyAccountResource;
use App\Http\Resources\Loyalty\LoyaltyTransactionResource;
use App\Http\Resources\ErrorResource;
use Illuminate\Http\JsonResponse;

/**
 * Manages loyalty account operations for a customer — show, earn points, redeem points.
 *
 * Each write operation delegates to its dedicated Action class.
 */
class LoyaltyAccountController extends BaseApiController
{
    /**
     * @param EarnPointsAction   $earnPoints   Action responsible for crediting loyalty points.
     * @param RedeemPointsAction $redeemPoints Action responsible for debiting loyalty points.
     */
    public function __construct(
        private readonly EarnPointsAction   $earnPoints,
        private readonly RedeemPointsAction $redeemPoints,
    ) {}

    /**
     * Show the loyalty account for a given customer, including tier info and last 20 transactions.
     *
     * @param  Customer  $customer
     * @return LoyaltyAccountResource|JsonResponse
     */
    public function show(Customer $customer): LoyaltyAccountResource|JsonResponse
    {
        try {
            $account = $customer->loyaltyAccount()
                ->with([
                    'tier',
                    'transactions' => fn ($q) => $q->orderByDesc('created_at')->limit(20),
                ])
                ->first();

            if ($account === null) {
                return $this->notFound('SHOW_LOYALTY_ACCOUNT', 'No loyalty account found for this customer.');
            }

            $resource = (new LoyaltyAccountResource($account))->additional([
                'transactions' => LoyaltyTransactionResource::collection(
                    $account->getRelation('transactions')
                ),
            ]);

            return $resource->response();
        } catch (\Throwable $e) {
            return $this->serverError($e, 'SHOW_LOYALTY_ACCOUNT');
        }
    }

    /**
     * Manually credit points to a customer's loyalty account.
     *
     * @param  EarnPointsRequest  $request
     * @param  Customer           $customer
     * @return JsonResponse
     */
    public function earn(EarnPointsRequest $request, Customer $customer): JsonResponse
    {
        try {
            $validated = $request->validated();
            $account   = $customer->loyaltyAccount;

            if ($account === null) {
                return $this->notFound('EARN_LOYALTY_POINTS', 'No loyalty account found for this customer.');
            }

            $transaction = $this->earnPoints->execute(
                loyaltyAccountId: $account->id,
                points:           $validated['points'],
                referenceType:    $validated['reference_type'] ?? null,
                referenceId:      $validated['reference_id']   ?? null,
                description:      $validated['description']    ?? null,
                createdBy:        $request->user()?->id,
            );

            return response()->json([
                'success'        => true,
                'transaction_id' => $transaction->id,
                'balance_after'  => $transaction->balance_after,
            ]);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'EARN_LOYALTY_POINTS');
        }
    }

    /**
     * Redeem points from a customer's loyalty account.
     *
     * @param  RedeemPointsRequest  $request
     * @param  Customer             $customer
     * @return JsonResponse
     */
    public function redeem(RedeemPointsRequest $request, Customer $customer): JsonResponse
    {
        try {
            $validated = $request->validated();
            $account   = $customer->loyaltyAccount;

            if ($account === null) {
                return $this->notFound('REDEEM_LOYALTY_POINTS', 'No loyalty account found for this customer.');
            }

            $transaction = $this->redeemPoints->execute(
                loyaltyAccountId: $account->id,
                points:           $validated['points'],
                orderId:          $validated['reference_id'] ?? null,
                description:      $validated['description']  ?? null,
                createdBy:        $request->user()?->id,
            );

            return response()->json([
                'success'        => true,
                'transaction_id' => $transaction->id,
                'balance_after'  => $transaction->balance_after,
            ]);
        } catch (\InvalidArgumentException $e) {
            return ErrorResource::fromException($e, 'REDEEM_LOYALTY_POINTS')
                ->response()
                ->setStatusCode(422);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'REDEEM_LOYALTY_POINTS');
        }
    }
}
