<?php

declare(strict_types=1);

namespace App\Domain\Payment\Services;

use App\Domain\Payment\Models\PendingDeposit;

/**
 * Service for matching bank transactions to pending deposits.
 *
 * Uses fuzzy matching algorithms to find the best match between
 * incoming bank transactions and expected order deposits.
 */
class TransactionMatcher
{
    /**
     * Minimum confidence score to consider a match valid.
     */
    private const MIN_CONFIDENCE = 75;

    /**
     * Find the best matching pending deposit for a bank transaction.
     *
     * @param  array<string,mixed>  $transaction
     * @param  list<PendingDeposit> $pendingDeposits
     * @return array{match: PendingDeposit|null, confidence: float, reason: string}
     */
    public function findBestMatch(array $transaction, array $pendingDeposits): array
    {
        $bestMatch = null;
        $bestConfidence = 0;
        $bestReason = '';

        foreach ($pendingDeposits as $deposit) {
            $result = $this->calculateMatchConfidence($transaction, $deposit);

            if ($result['confidence'] > $bestConfidence) {
                $bestConfidence = $result['confidence'];
                $bestMatch = $deposit;
                $bestReason = $result['reason'];
            }
        }

        if ($bestConfidence < self::MIN_CONFIDENCE) {
            return [
                'match' => null,
                'confidence' => $bestConfidence,
                'reason' => 'No match met minimum confidence threshold (' . self::MIN_CONFIDENCE . '%)',
            ];
        }

        return [
            'match' => $bestMatch,
            'confidence' => $bestConfidence,
            'reason' => $bestReason,
        ];
    }

    /**
     * Calculate match confidence between a transaction and pending deposit.
     *
     * @param  array<string,mixed>  $transaction
     * @param  PendingDeposit       $deposit
     * @return array{confidence: float, reason: string}
     */
    public function calculateMatchConfidence(array $transaction, PendingDeposit $deposit): array
    {
        $scores = [];
        $reasons = [];

        // 1. Amount match (40% weight)
        $amountScore = $this->calculateAmountScore($transaction['amount'] ?? 0, $deposit);
        $scores[] = $amountScore * 0.40;
        if ($amountScore > 0.8) {
            $reasons[] = 'amount matches';
        }

        // 2. Transfer content pattern match (40% weight)
        $contentScore = $this->calculateContentScore(
            $transaction['transfer_content'] ?? '',
            $transaction['sender_name'] ?? '',
            $deposit
        );
        $scores[] = $contentScore * 0.40;
        if ($contentScore > 0.8) {
            $reasons[] = 'transfer content matches order pattern';
        }

        // 3. Time proximity (10% weight)
        $timeScore = $this->calculateTimeScore($transaction['timestamp'] ?? null, $deposit);
        $scores[] = $timeScore * 0.10;

        // 4. Sender name fuzzy match (10% weight)
        $senderScore = $this->calculateSenderScore(
            $transaction['sender_name'] ?? '',
            $deposit->order->customer?->fullName() ?? ''
        );
        $scores[] = $senderScore * 0.10;
        if ($senderScore > 0.8) {
            $reasons[] = 'sender name matches customer';
        }

        $totalConfidence = array_sum($scores) * 100;

        return [
            'confidence' => round($totalConfidence, 2),
            'reason' => implode(', ', $reasons) ?: 'partial match',
        ];
    }

    /**
     * Calculate amount match score (0-1).
     *
     * @param  int             $transactionAmount
     * @param  PendingDeposit  $deposit
     * @return float
     */
    private function calculateAmountScore(int $transactionAmount, PendingDeposit $deposit): float
    {
        if ($deposit->isAmountAcceptable($transactionAmount)) {
            // Perfect or near-perfect match
            $diff = abs($transactionAmount - $deposit->expected_amount);
            $percentageDiff = $diff / $deposit->expected_amount;

            return 1.0 - ($percentageDiff * 2); // Small penalty for difference
        }

        // Outside tolerance range
        $range = $deposit->getAcceptableAmountRange();
        $distance = min(
            abs($transactionAmount - $range['min']),
            abs($transactionAmount - $range['max'])
        );

        // Exponential decay for amounts outside tolerance
        return max(0, 1 - ($distance / $deposit->expected_amount));
    }

    /**
     * Calculate transfer content match score (0-1).
     *
     * @param  string          $transferContent
     * @param  string          $senderName
     * @param  PendingDeposit  $deposit
     * @return float
     */
    private function calculateContentScore(string $transferContent, string $senderName, PendingDeposit $deposit): float
    {
        $content = strtolower($transferContent . ' ' . $senderName);
        $pattern = strtolower((string) $deposit->transfer_content_pattern);
        $orderNumber = strtolower((string) $deposit->order->order_number);

        // Exact order number match (highest score)
        if (str_contains($content, $orderNumber)) {
            return 1.0;
        }

        // Pattern match
        if (str_contains($content, $pattern)) {
            return 0.95;
        }

        // Fuzzy pattern matching
        similar_text($content, $pattern, $similarity);
        if ($similarity > 80) {
            return 0.90;
        }

        // Check for common variations
        $variations = [
            str_replace(' ', '', $orderNumber),
            str_replace('-', '', $orderNumber),
            'dh' . preg_replace('/[^0-9]/', '', $orderNumber),
            'donhang' . preg_replace('/[^0-9]/', '', $orderNumber),
            'ck' . preg_replace('/[^0-9]/', '', $orderNumber),
        ];

        foreach ($variations as $variation) {
            if (str_contains($content, $variation)) {
                return 0.85;
            }
        }

        // Check for customer phone number in content
        $customerPhone = $deposit->order->customer?->phone ?? $deposit->order->guest_phone ?? '';
        if (!empty($customerPhone)) {
            $phoneDigits = preg_replace('/[^0-9]/', '', $customerPhone);
            if (str_contains($content, $phoneDigits)) {
                return 0.80;
            }
        }

        // Low similarity
        similar_text($content, $orderNumber, $orderSimilarity);

        return max(0, ($orderSimilarity / 100) * 0.5);
    }

    /**
     * Calculate time proximity score (0-1).
     *
     * @param  string|null     $transactionTimestamp
     * @param  PendingDeposit  $deposit
     * @return float
     */
    private function calculateTimeScore(?string $transactionTimestamp, PendingDeposit $deposit): float
    {
        if ($transactionTimestamp === null) {
            return 0.5; // Neutral if no timestamp
        }

        try {
            $transactionTime = new \DateTime($transactionTimestamp);
            $createdDiff = abs($transactionTime->getTimestamp() - $deposit->created_at->getTimestamp());

            // Within 1 hour = perfect match
            if ($createdDiff <= 3600) {
                return 1.0;
            }

            // Within 24 hours = good match
            if ($createdDiff <= 86400) {
                return 0.9 - (($createdDiff - 3600) / 86400 * 0.4);
            }

            // Decay for older transactions
            return max(0, 0.5 - ($createdDiff / 604800)); // Week decay
        } catch (\Throwable $e) {
            return 0.5;
        }
    }

    /**
     * Calculate sender name match score (0-1).
     *
     * @param  string  $senderName
     * @param  string  $customerName
     * @return float
     */
    private function calculateSenderScore(string $senderName, string $customerName): float
    {
        if (empty($senderName) || empty($customerName)) {
            return 0.5;
        }

        $sender = strtolower($this->normalizeVietnamese($senderName));
        $customer = strtolower($this->normalizeVietnamese($customerName));

        // Exact match
        if ($sender === $customer) {
            return 1.0;
        }

        // Contains match
        if (str_contains($sender, $customer) || str_contains($customer, $sender)) {
            return 0.9;
        }

        // Similarity match
        similar_text($sender, $customer, $similarity);

        return $similarity / 100;
    }

    /**
     * Normalize Vietnamese characters to ASCII.
     *
     * @param  string  $str
     * @return string
     */
    private function normalizeVietnamese(string $str): string
    {
        $vietnamese = [
            'à',
            'á',
            'ạ',
            'ả',
            'ã',
            'â',
            'ầ',
            'ấ',
            'ậ',
            'ẩ',
            'ẫ',
            'ă',
            'ằ',
            'ắ',
            'ặ',
            'ẳ',
            'ẵ',
            'è',
            'é',
            'ẹ',
            'ẻ',
            'ẽ',
            'ê',
            'ề',
            'ế',
            'ệ',
            'ể',
            'ễ',
            'ì',
            'í',
            'ị',
            'ỉ',
            'ĩ',
            'ò',
            'ó',
            'ọ',
            'ỏ',
            'õ',
            'ô',
            'ồ',
            'ố',
            'ộ',
            'ổ',
            'ỗ',
            'ơ',
            'ờ',
            'ớ',
            'ợ',
            'ở',
            'ỡ',
            'ù',
            'ú',
            'ụ',
            'ủ',
            'ũ',
            'ư',
            'ừ',
            'ứ',
            'ự',
            'ử',
            'ữ',
            'ỳ',
            'ý',
            'ỵ',
            'ỷ',
            'ỹ',
            'đ',
            'À',
            'Á',
            'Ạ',
            'Ả',
            'Ã',
            'Â',
            'Ầ',
            'Ấ',
            'Ậ',
            'Ẩ',
            'Ẫ',
            'Ă',
            'Ằ',
            'Ắ',
            'Ặ',
            'Ẳ',
            'Ẵ',
            'È',
            'É',
            'Ẹ',
            'Ẻ',
            'Ẽ',
            'Ê',
            'Ề',
            'Ế',
            'Ệ',
            'Ể',
            'Ễ',
            'Ì',
            'Í',
            'Ị',
            'Ỉ',
            'Ĩ',
            'Ò',
            'Ó',
            'Ọ',
            'Ỏ',
            'Õ',
            'Ô',
            'Ồ',
            'Ố',
            'Ộ',
            'Ổ',
            'Ỗ',
            'Ơ',
            'Ờ',
            'Ớ',
            'Ợ',
            'Ở',
            'Ỡ',
            'Ù',
            'Ú',
            'Ụ',
            'Ủ',
            'Ũ',
            'Ư',
            'Ừ',
            'Ứ',
            'Ự',
            'Ử',
            'Ữ',
            'Ỳ',
            'Ý',
            'Ỵ',
            'Ỷ',
            'Ỹ',
            'Đ',
        ];

        $ascii = [
            'a',
            'a',
            'a',
            'a',
            'a',
            'a',
            'a',
            'a',
            'a',
            'a',
            'a',
            'a',
            'a',
            'a',
            'a',
            'a',
            'a',
            'e',
            'e',
            'e',
            'e',
            'e',
            'e',
            'e',
            'e',
            'e',
            'e',
            'e',
            'i',
            'i',
            'i',
            'i',
            'i',
            'o',
            'o',
            'o',
            'o',
            'o',
            'o',
            'o',
            'o',
            'o',
            'o',
            'o',
            'o',
            'o',
            'o',
            'o',
            'o',
            'o',
            'u',
            'u',
            'u',
            'u',
            'u',
            'u',
            'u',
            'u',
            'u',
            'u',
            'u',
            'y',
            'y',
            'y',
            'y',
            'y',
            'd',
            'A',
            'A',
            'A',
            'A',
            'A',
            'A',
            'A',
            'A',
            'A',
            'A',
            'A',
            'A',
            'A',
            'A',
            'A',
            'A',
            'A',
            'E',
            'E',
            'E',
            'E',
            'E',
            'E',
            'E',
            'E',
            'E',
            'E',
            'E',
            'I',
            'I',
            'I',
            'I',
            'I',
            'O',
            'O',
            'O',
            'O',
            'O',
            'O',
            'O',
            'O',
            'O',
            'O',
            'O',
            'O',
            'O',
            'O',
            'O',
            'O',
            'O',
            'U',
            'U',
            'U',
            'U',
            'U',
            'U',
            'U',
            'U',
            'U',
            'U',
            'U',
            'Y',
            'Y',
            'Y',
            'Y',
            'Y',
            'D',
        ];

        return str_replace($vietnamese, $ascii, $str);
    }
}
