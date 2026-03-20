<?php

declare(strict_types=1);

namespace App\Domain\Settings\Data;

/**
 * Data transfer object for a batch settings update.
 */
class UpdateSettingsDto
{
    /**
     * Create a new UpdateSettingsDto.
     *
     * @param  array<int,array{group:string,key:string,value:mixed}>  $settings   Array of settings to update.
     * @param  string|null                                             $updatedBy  UUID of the staff user making the update.
     */
    public function __construct(
        public readonly array   $settings,
        public readonly ?string $updatedBy = null,
    ) {}
}
