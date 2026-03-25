<?php

declare(strict_types=1);

namespace App\Domain\Shipment\Actions;

use App\Domain\Shipment\Data\GhtkCalculateFeeDto;
use App\Domain\Shipment\Services\GHTKService;

/**
 * Action to calculate shipping fee with GHTK.
 */
class CalculateGhtkFeeAction
{
    public function __construct(
        private readonly GHTKService $ghtkService
    ) {
    }

    /**
     * Execute fee calculation.
     *
     * @param  GhtkCalculateFeeDto  $dto
     * @return array<string,mixed>  Fee calculation result.
     *
     * @throws \RuntimeException On API error.
     */
    public function execute(GhtkCalculateFeeDto $dto): array
    {
        return $this->ghtkService->calculateFee($dto->toArray());
    }

    /**
     * Quick fee check without creating DTO.
     *
     * @param  string  $fromProvince
     * @param  string  $fromDistrict
     * @param  string  $toProvince
     * @param  string  $toDistrict
     * @param  int     $weightGrams
     * @param  int     $value
     * @return int    Calculated fee amount.
     */
    public function quickCalculate(
        string $fromProvince,
        string $fromDistrict,
        string $toProvince,
        string $toDistrict,
        int $weightGrams = 1000,
        int $value = 0
    ): int {
        $dto = new GhtkCalculateFeeDto(
            pickProvince: $fromProvince,
            pickDistrict: $fromDistrict,
            province: $toProvince,
            district: $toDistrict,
            address: '',
            weight: $weightGrams,
            value: $value,
        );

        $result = $this->execute($dto);

        return $result['fee'] ?? 0;
    }
}
