<?php

declare(strict_types=1);

namespace App\Domain\SaleEvent\Data;

use Illuminate\Support\Str;

/**
 * Data Transfer Object for creating a new sale event.
 *
 * Carries all fields required to persist a SaleEvent record.
 * The slug is auto-generated from the name if not provided.
 */
readonly class CreateSaleEventDto
{
    /**
     * @param  string              $name            Display name of the sale event.
     * @param  string              $slug            URL-friendly slug (auto-generated from name if omitted).
     * @param  string|null         $description     Optional description of the event.
     * @param  string              $type            Event type: flash_sale, drop_launch, clearance, bundle.
     * @param  string              $startsAt        ISO-8601 datetime when the event starts.
     * @param  string|null         $endsAt          ISO-8601 datetime when the event ends (null = open-ended).
     * @param  int|null            $maxOrdersTotal  Maximum total number of orders allowed for this event.
     * @param  bool                $isPublic        Whether the event is visible to the public.
     * @param  string|null         $bannerUrl       URL of the promotional banner image.
     * @param  string              $createdBy       UUID of the staff user creating this event.
     * @param  string              $status          Initial status: draft or scheduled.
     */
    public function __construct(
        public string  $name,
        public string  $slug,
        public ?string $description,
        public string  $type,
        public string  $startsAt,
        public ?string $endsAt,
        public ?int    $maxOrdersTotal,
        public bool    $isPublic,
        public ?string $bannerUrl,
        public string  $createdBy,
        public string  $status = 'draft',
    ) {}

    /**
     * Build a CreateSaleEventDto from a validated request array.
     *
     * @param  array<string,mixed>  $data
     * @param  string               $createdBy  UUID of the authenticated staff user.
     * @return self
     */
    public static function fromArray(array $data, string $createdBy): self
    {
        $name = $data['name'];
        $slug = $data['slug'] ?? Str::slug($name);

        return new self(
            name:           $name,
            slug:           $slug,
            description:    $data['description'] ?? null,
            type:           $data['type'],
            startsAt:       $data['starts_at'],
            endsAt:         $data['ends_at'] ?? null,
            maxOrdersTotal: isset($data['max_orders_total']) ? (int) $data['max_orders_total'] : null,
            isPublic:       (bool) ($data['is_public'] ?? false),
            bannerUrl:      $data['banner_url'] ?? null,
            createdBy:      $createdBy,
            status:         $data['status'] ?? 'draft',
        );
    }
}
