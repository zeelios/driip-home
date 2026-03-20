<?php

declare(strict_types=1);

namespace App\Domain\SaleEvent\Data;

/**
 * Data Transfer Object for updating an existing sale event.
 *
 * All fields are optional. Only non-null values are applied during the update,
 * allowing patch-style partial modifications.
 */
readonly class UpdateSaleEventDto
{
    /**
     * @param  string|null         $name            New display name.
     * @param  string|null         $slug            New URL-friendly slug.
     * @param  string|null         $description     New description.
     * @param  string|null         $type            New type: flash_sale, drop_launch, clearance, bundle.
     * @param  string|null         $status          New status: draft, scheduled, active, ended, cancelled.
     * @param  string|null         $startsAt        New start datetime (ISO-8601).
     * @param  string|null         $endsAt          New end datetime (ISO-8601).
     * @param  int|null            $maxOrdersTotal  New maximum total orders cap.
     * @param  bool|null           $isPublic        New public visibility flag.
     * @param  string|null         $bannerUrl       New banner image URL.
     */
    public function __construct(
        public ?string $name           = null,
        public ?string $slug           = null,
        public ?string $description    = null,
        public ?string $type           = null,
        public ?string $status         = null,
        public ?string $startsAt       = null,
        public ?string $endsAt         = null,
        public ?int    $maxOrdersTotal = null,
        public ?bool   $isPublic       = null,
        public ?string $bannerUrl      = null,
    ) {}

    /**
     * Build an UpdateSaleEventDto from a validated request array.
     *
     * @param  array<string,mixed>  $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name:           $data['name'] ?? null,
            slug:           $data['slug'] ?? null,
            description:    $data['description'] ?? null,
            type:           $data['type'] ?? null,
            status:         $data['status'] ?? null,
            startsAt:       $data['starts_at'] ?? null,
            endsAt:         $data['ends_at'] ?? null,
            maxOrdersTotal: isset($data['max_orders_total']) ? (int) $data['max_orders_total'] : null,
            isPublic:       isset($data['is_public']) ? (bool) $data['is_public'] : null,
            bannerUrl:      $data['banner_url'] ?? null,
        );
    }

    /**
     * Return only the non-null values as a key-value array suitable for Model::update().
     *
     * @return array<string,mixed>
     */
    public function toUpdateArray(): array
    {
        $map = [
            'name'             => $this->name,
            'slug'             => $this->slug,
            'description'      => $this->description,
            'type'             => $this->type,
            'status'           => $this->status,
            'starts_at'        => $this->startsAt,
            'ends_at'          => $this->endsAt,
            'max_orders_total' => $this->maxOrdersTotal,
            'is_public'        => $this->isPublic,
            'banner_url'       => $this->bannerUrl,
        ];

        return array_filter($map, fn ($v) => $v !== null);
    }
}
