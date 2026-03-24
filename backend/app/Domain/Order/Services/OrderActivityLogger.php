<?php

declare(strict_types=1);

namespace App\Domain\Order\Services;

use App\Domain\Order\Models\Order;
use App\Domain\Order\Models\OrderActivity;
use App\Domain\Order\Models\OrderClaim;
use App\Domain\Staff\Models\User;
use Illuminate\Support\Facades\Request;

/**
 * Service for logging order activities.
 *
 * Provides convenient methods to record every significant event
 * in an order's lifecycle with proper audit trail metadata.
 */
class OrderActivityLogger
{
    /**
     * Log a generic order activity.
     *
     * @param  Order              $order
     * @param  string             $activityType
     * @param  string             $description
     * @param  array<string,mixed> $metadata
     * @param  User|null          $actor
     * @param  string             $actorType
     * @return OrderActivity
     */
    public function log(
        Order $order,
        string $activityType,
        string $description,
        array $metadata = [],
        ?User $actor = null,
        string $actorType = 'system'
    ): OrderActivity {
        /** @var OrderActivity $activity */
        $activity = OrderActivity::create([
            'order_id'       => $order->id,
            'actor_type'     => $actor ? 'staff' : $actorType,
            'actor_id'       => $actor?->id,
            'activity_type'  => $activityType,
            'description'    => $description,
            'metadata'       => $metadata,
            'ip_address'     => Request::ip(),
            'user_agent'     => Request::userAgent(),
            'created_at'     => now(),
        ]);

        return $activity;
    }

    /**
     * Log order creation.
     */
    public function logOrderCreated(Order $order, ?User $by = null): OrderActivity
    {
        return $this->log(
            $order,
            'order_created',
            "Order {$order->order_number} created with status {$order->status}",
            [
                'order_number'   => $order->order_number,
                'status'         => $order->status,
                'total_after_tax'=> $order->total_after_tax,
                'customer_id'    => $order->customer_id,
            ],
            $by,
            $by ? 'staff' : 'system'
        );
    }

    /**
     * Log a status transition.
     */
    public function logStatusChange(
        Order $order,
        string $fromStatus,
        string $toStatus,
        ?string $notes = null,
        ?User $by = null
    ): OrderActivity {
        return $this->log(
            $order,
            'status_change',
            $notes ?? "Status changed from {$fromStatus} to {$toStatus}",
            [
                'from_status' => $fromStatus,
                'to_status'   => $toStatus,
                'notes'       => $notes,
            ],
            $by,
            $by ? 'staff' : 'system'
        );
    }

    /**
     * Log a deposit recording.
     */
    public function logDepositRecorded(
        Order $order,
        int $amount,
        int $newBalanceDue,
        array $proofUrls,
        ?User $by = null
    ): OrderActivity {
        return $this->log(
            $order,
            'deposit_recorded',
            "Deposit of {$amount} recorded. Balance due: {$newBalanceDue}",
            [
                'amount'       => $amount,
                'balance_due'  => $newBalanceDue,
                'proof_count'  => count($proofUrls),
                'proof_urls'   => $proofUrls,
            ],
            $by
        );
    }

    /**
     * Log full payment received.
     */
    public function logPaymentReceived(
        Order $order,
        int $amount,
        string $method,
        ?string $reference = null,
        ?User $by = null
    ): OrderActivity {
        return $this->log(
            $order,
            'payment',
            "Payment of {$amount} received via {$method}",
            [
                'amount'    => $amount,
                'method'    => $method,
                'reference' => $reference,
            ],
            $by
        );
    }

    /**
     * Log a note being added.
     */
    public function logNoteAdded(
        Order $order,
        string $note,
        bool $isCustomerVisible,
        ?User $by = null
    ): OrderActivity {
        return $this->log(
            $order,
            'note_added',
            $isCustomerVisible ? 'Customer-visible note added' : 'Internal note added',
            [
                'note'               => $note,
                'is_customer_visible'=> $isCustomerVisible,
            ],
            $by
        );
    }

    /**
     * Log file upload.
     */
    public function logFileUpload(
        Order $order,
        string $fileType,
        string $url,
        ?User $by = null
    ): OrderActivity {
        return $this->log(
            $order,
            'file_upload',
            "{$fileType} uploaded",
            [
                'file_type' => $fileType,
                'url'       => $url,
            ],
            $by
        );
    }

    /**
     * Log claim creation.
     */
    public function logClaimCreated(
        Order $order,
        OrderClaim $claim,
        ?User $by = null
    ): OrderActivity {
        return $this->log(
            $order,
            'claim_created',
            "Claim {$claim->claim_number} created: {$claim->type}",
            [
                'claim_id'     => $claim->id,
                'claim_number' => $claim->claim_number,
                'type'         => $claim->type,
                'status'       => $claim->status,
            ],
            $by,
            $by ? 'staff' : 'customer'
        );
    }

    /**
     * Log claim resolution.
     */
    public function logClaimResolved(
        Order $order,
        OrderClaim $claim,
        string $resolution,
        ?int $refundAmount = null,
        ?User $by = null
    ): OrderActivity {
        $metadata = [
            'claim_id'    => $claim->id,
            'resolution'  => $resolution,
        ];

        if ($refundAmount !== null) {
            $metadata['refund_amount'] = $refundAmount;
        }

        return $this->log(
            $order,
            'claim_resolved',
            "Claim {$claim->claim_number} resolved: {$resolution}",
            $metadata,
            $by
        );
    }

    /**
     * Log return shipment.
     */
    public function logReturnShipped(
        Order $order,
        string $returnNumber,
        string $courier,
        string $tracking,
        ?User $by = null
    ): OrderActivity {
        return $this->log(
            $order,
            'return_shipped',
            "Return {$returnNumber} shipped via {$courier}",
            [
                'return_number'   => $returnNumber,
                'courier'         => $courier,
                'tracking_number' => $tracking,
            ],
            $by,
            $by ? 'staff' : 'customer'
        );
    }

    /**
     * Log return received.
     */
    public function logReturnReceived(
        Order $order,
        string $returnNumber,
        ?User $by = null
    ): OrderActivity {
        return $this->log(
            $order,
            'return_received',
            "Return {$returnNumber} received and inspected",
            [
                'return_number' => $returnNumber,
            ],
            $by
        );
    }

    /**
     * Log refund processed.
     */
    public function logRefundProcessed(
        Order $order,
        int $amount,
        string $method,
        string $reference,
        ?User $by = null
    ): OrderActivity {
        return $this->log(
            $order,
            'refund_processed',
            "Refund of {$amount} processed via {$method}",
            [
                'amount'    => $amount,
                'method'    => $method,
                'reference' => $reference,
            ],
            $by
        );
    }

    /**
     * Log commission calculation.
     */
    public function logCommissionCalculated(
        Order $order,
        int $amount,
        float $rate,
        ?string $referralCode,
        ?User $by = null
    ): OrderActivity {
        return $this->log(
            $order,
            'commission_calculated',
            "Commission calculated: {$amount} at {$rate}% rate",
            [
                'amount'        => $amount,
                'rate'          => $rate,
                'referral_code' => $referralCode,
                'sales_rep_id'  => $order->sales_rep_id,
            ],
            $by
        );
    }

    /**
     * Log commission paid.
     */
    public function logCommissionPaid(
        Order $order,
        int $amount,
        string $reference,
        ?User $by = null
    ): OrderActivity {
        return $this->log(
            $order,
            'commission_paid',
            "Commission of {$amount} paid",
            [
                'amount'    => $amount,
                'reference' => $reference,
            ],
            $by
        );
    }

    /**
     * Log order edit.
     */
    public function logOrderEdited(
        Order $order,
        array $changes,
        ?User $by = null
    ): OrderActivity {
        return $this->log(
            $order,
            'order_edited',
            'Order details updated',
            [
                'changes' => $changes,
            ],
            $by
        );
    }

    /**
     * Log customer notification.
     */
    public function logCustomerNotified(
        Order $order,
        string $channel,
        string $subject,
        ?User $by = null
    ): OrderActivity {
        return $this->log(
            $order,
            'customer_notified',
            "Customer notified via {$channel}: {$subject}",
            [
                'channel' => $channel,
                'subject' => $subject,
            ],
            $by,
            $by ? 'staff' : 'system'
        );
    }

    /**
     * Log system event.
     */
    public function logSystemEvent(
        Order $order,
        string $event,
        array $metadata = []
    ): OrderActivity {
        return $this->log(
            $order,
            'system_event',
            $event,
            $metadata,
            null,
            'system'
        );
    }
}
