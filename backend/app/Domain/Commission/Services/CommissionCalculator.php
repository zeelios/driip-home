<?php

declare(strict_types=1);

namespace App\Domain\Commission\Services;

use App\Domain\Commission\Models\CommissionConfig;
use App\Domain\Order\Models\Order;
use App\Domain\Staff\Models\User;

/**
 * Service for calculating sales commissions.
 *
 * Computes commission amounts based on staff configurations,
 * order values, and applicable rates.
 */
class CommissionCalculator
{
    /** @var float Default commission rate if no config found */
    private const DEFAULT_RATE = 5.0;

    /**
     * Calculate commission for an order.
     *
     * Commission is calculated on (subtotal - coupon_discount) only.
     * Shipping fees and VAT are excluded from commission calculations.
     *
     * @param  Order  $order
     * @return array{amount: int, rate: float, base: int}
     */
    public function calculateForOrder(Order $order): array
    {
        // Find the sales rep by referral code or assigned_to
        $salesRep = $this->resolveSalesRep($order);

        if (!$salesRep) {
            return [
                'amount' => 0,
                'rate'   => 0.0,
                'base'   => 0,
            ];
        }

        // Get applicable rate
        $rate = $this->getRateForStaff($salesRep, $order);

        // Calculate base (subtotal - discounts, excluding shipping/VAT)
        $base = max(0, $order->subtotal - $order->coupon_discount);

        // Calculate commission
        $amount = (int) round($base * $rate / 100);

        return [
            'amount' => $amount,
            'rate'   => $rate,
            'base'   => $base,
        ];
    }

    /**
     * Calculate and persist commission for an order.
     *
     * @param  Order  $order
     * @return Order
     */
    public function computeAndSave(Order $order): Order
    {
        $calculation = $this->calculateForOrder($order);

        if ($calculation['amount'] > 0) {
            $salesRep = $this->resolveSalesRep($order);

            $order->update([
                'referral_code'      => $order->referral_code,
                'sales_rep_id'       => $salesRep?->id,
                'commission_amount'  => $calculation['amount'],
                'commission_rate'    => $calculation['rate'],
                'commission_status'  => 'pending',
            ]);
        }

        return $order;
    }

    /**
     * Resolve the sales rep for an order.
     *
     * @param  Order  $order
     * @return User|null
     */
    private function resolveSalesRep(Order $order): ?User
    {
        // If order has assigned_to, use that
        if ($order->assigned_to) {
            return User::find($order->assigned_to);
        }

        // If order has referral_code, try to find matching staff
        if ($order->referral_code) {
            // Map referral codes to staff employee_codes
            $staff = User::where('employee_code', strtoupper($order->referral_code))
                ->orWhere('employee_code', strtolower($order->referral_code))
                ->first();

            if ($staff) {
                return $staff;
            }
        }

        return null;
    }

    /**
     * Get commission rate for a staff member.
     *
     * Looks up active commission config, falls back to default rate.
     *
     * @param  User   $staff
     * @param  Order  $order
     * @return float
     */
    private function getRateForStaff(User $staff, Order $order): float
    {
        $config = CommissionConfig::where('staff_id', $staff->id)
            ->activeToday()
            ->first();

        if (!$config) {
            return self::DEFAULT_RATE;
        }

        // Check for category-specific rates
        $categoryRates = $config->category_rates ?? [];
        
        // For now, return base rate. Category-specific logic can be added
        // when orders have category-level breakdowns.

        return (float) $config->rate_percent;
    }

    /**
     * Get summary of commissions for a staff member.
     *
     * @param  string   $staffId
     * @param  string   $from
     * @param  string   $to
     * @return array{pending: int, approved: int, paid: int, total: int}
     */
    public function getSummaryForStaff(string $staffId, string $from, string $to): array
    {
        $orders = Order::where('sales_rep_id', $staffId)
            ->whereBetween('created_at', [$from, $to])
            ->get();

        return [
            'pending'  => $orders->where('commission_status', 'pending')->sum('commission_amount'),
            'approved' => $orders->where('commission_status', 'approved')->sum('commission_amount'),
            'paid'     => $orders->where('commission_status', 'paid')->sum('commission_amount'),
            'total'    => $orders->sum('commission_amount'),
        ];
    }
}
