<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domain\Order\Actions\RecordPaymentAction;
use App\Domain\Order\Data\RecordPaymentDto;
use App\Domain\Payment\Models\BankCheckLog;
use App\Domain\Payment\Models\BankConfig;
use App\Domain\Payment\Models\PendingDeposit;
use App\Domain\Payment\Services\TransactionMatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job to check bank transactions and match pending deposits.
 *
 * This job is dispatched on a schedule to periodically check
 * configured bank accounts for incoming transfers.
 */
class CheckBankTransactionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    public function __construct(
        private readonly ?BankConfig $specificBankConfig = null
    ) {
    }

    /**
     * Execute the job.
     *
     * @param  TransactionMatcher  $matcher
     * @param  RecordPaymentAction  $recordPayment
     * @return void
     */
    public function handle(TransactionMatcher $matcher, RecordPaymentAction $recordPayment): void
    {
        // Get bank configs to check
        $bankConfigs = $this->specificBankConfig
            ? collect([$this->specificBankConfig])
            : BankConfig::where('is_active', true)
                ->get()
                ->filter(fn ($config) => $config->isDueForCheck());

        foreach ($bankConfigs as $bankConfig) {
            $this->checkBank($bankConfig, $matcher, $recordPayment);
        }
    }

    /**
     * Check a single bank configuration.
     *
     * @param  BankConfig           $bankConfig
     * @param  TransactionMatcher   $matcher
     * @param  RecordPaymentAction  $recordPayment
     * @return void
     */
    private function checkBank(
        BankConfig $bankConfig,
        TransactionMatcher $matcher,
        RecordPaymentAction $recordPayment
    ): void {
        $log = BankCheckLog::create([
            'bank_config_id' => $bankConfig->id,
            'status' => 'success',
            'started_at' => now(),
        ]);

        try {
            // Fetch pending deposits for this bank
            $pendingDeposits = PendingDeposit::where('bank_config_id', $bankConfig->id)
                ->where('status', 'pending')
                ->where('expires_at', '>', now())
                ->with('order')
                ->get();

            if ($pendingDeposits->isEmpty()) {
                $log->markCompleted(0, 0, ['message' => 'No pending deposits to match']);
                $bankConfig->markChecked();

                return;
            }

            // Fetch transactions from bank (simulated for now - would call Node.js RPA service)
            $transactions = $this->fetchBankTransactions($bankConfig);

            $matchCount = 0;
            $matchDetails = [];

            foreach ($transactions as $transaction) {
                $result = $matcher->findBestMatch($transaction, $pendingDeposits->all());

                if ($result['match'] !== null) {
                    $deposit = $result['match'];
                    $matchCount++;

                    // Record the matched deposit
                    $this->processMatchedDeposit($deposit, $transaction, $recordPayment);

                    $matchDetails[] = [
                        'order_id' => $deposit->order_id,
                        'order_number' => $deposit->order->order_number,
                        'transaction_id' => $transaction['id'] ?? 'unknown',
                        'amount' => $transaction['amount'],
                        'confidence' => $result['confidence'],
                        'reason' => $result['reason'],
                    ];

                    // Remove from pending deposits to avoid double-matching
                    $pendingDeposits = $pendingDeposits->reject(fn ($d) => $d->id === $deposit->id);
                }
            }

            $log->markCompleted(
                count($transactions),
                $matchCount,
                [
                    'pending_deposits_count' => $pendingDeposits->count() + $matchCount,
                    'matches' => $matchDetails,
                ]
            );

            $bankConfig->markChecked();

            Log::info('Bank check completed', [
                'bank_config_id' => $bankConfig->id,
                'bank_provider' => $bankConfig->bank_provider,
                'transactions_found' => count($transactions),
                'deposits_matched' => $matchCount,
            ]);
        } catch (\Throwable $e) {
            $log->markFailed($e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            Log::error('Bank check failed', [
                'bank_config_id' => $bankConfig->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Fetch transactions from the bank.
     *
     * This is a placeholder that would call the Node.js RPA service.
     *
     * @param  BankConfig  $bankConfig
     * @return list<array<string,mixed>>
     *
     * @throws \RuntimeException
     */
    private function fetchBankTransactions(BankConfig $bankConfig): array
    {
        // In production, this would call the Node.js RPA service
        // Example:
        // $response = Http::timeout(120)
        //     ->post(config('services.bank_crawler.url') . '/crawl', [
        //         'bank_provider' => $bankConfig->bank_provider,
        //         'credentials' => $bankConfig->getCredentials(),
        //         'date_from' => now()->subDays(1)->format('Y-m-d'),
        //         'date_to' => now()->format('Y-m-d'),
        //     ]);
        //
        // return $response->json('transactions', []);

        // For now, return empty array as this requires the Node.js service
        // This method should be implemented when the RPA service is ready
        return [];
    }

    /**
     * Process a matched deposit.
     *
     * @param  PendingDeposit       $deposit
     * @param  array<string,mixed>  $transaction
     * @param  RecordPaymentAction  $recordPayment
     * @return void
     */
    private function processMatchedDeposit(
        PendingDeposit $deposit,
        array $transaction,
        RecordPaymentAction $recordPayment
    ): void {
        // Mark the pending deposit as matched
        $deposit->markMatched(
            $transaction['id'] ?? 'unknown',
            null // auto-matched, no specific user
        );

        // Record the actual payment
        $dto = new RecordPaymentDto(
            amount: $transaction['amount'],
            paymentMethod: 'bank_transfer',
            paymentType: $deposit->expected_amount >= $deposit->order->total_after_tax ? 'final' : 'deposit',
            reference: $transaction['id'] ?? null,
            notes: "Auto-matched from {$deposit->bankConfig?->bank_provider} transfer",
        );

        $recordPayment->execute($deposit->order, $dto);

        // TODO: Send notification to customer
        // Notification::send($deposit->order->customer, new DepositMatchedNotification($deposit));
    }
}
