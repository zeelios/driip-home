<?php

declare(strict_types=1);

namespace App\Http\Resources\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource transforming a Customer model into a JSON-serializable array.
 *
 * Includes computed full name and conditionally loads loyalty account data
 * when the relation has been eagerly loaded on the model.
 *
 * @mixin \App\Domain\Customer\Models\Customer
 */
class CustomerResource extends JsonResource
{
    /**
     * Transform the customer into an array for API responses.
     *
     * @param  Request  $request
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id'             => $this->id,
            'customer_code'  => $this->customer_code,
            'first_name'     => $this->first_name,
            'last_name'      => $this->last_name,
            'full_name'      => $this->fullName(),
            'email'          => $this->email,
            'phone'          => $this->phone,
            'gender'         => $this->gender,
            'date_of_birth'  => $this->date_of_birth?->toDateString(),
            'source'         => $this->source,
            'tags'           => $this->tags ?? [],
            'is_blocked'     => $this->is_blocked,
            'total_orders'   => $this->total_orders,
            'total_spent'    => $this->total_spent,
            'last_ordered_at' => $this->last_ordered_at?->toIso8601String(),
            'zalo_id'        => $this->zalo_id,
            'created_at'     => $this->created_at?->toIso8601String(),
        ];

        $this->whenLoaded('loyaltyAccount', function () use (&$data): void {
            $account = $this->loyaltyAccount;
            if ($account !== null) {
                $data['loyalty_account'] = [
                    'points_balance' => $account->points_balance,
                    'tier'           => $account->relationLoaded('tier') && $account->tier !== null
                        ? [
                            'id'               => $account->tier->id,
                            'name'             => $account->tier->name,
                            'slug'             => $account->tier->slug,
                            'discount_percent' => $account->tier->discount_percent,
                            'free_shipping'    => $account->tier->free_shipping,
                        ]
                        : null,
                ];
            }
        });

        return $data;
    }
}
