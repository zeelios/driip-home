<?php

declare(strict_types=1);

namespace App\Domain\Shipment\Services;

use Illuminate\Container\Container;

/**
 * Resolves courier codes to concrete courier service implementations.
 *
 * Centralizing the mapping makes it easy to add new carriers such as
 * Viettel Post, J&T, SPX, or Grab without touching every shipment action.
 */
final class CourierServiceResolver
{
    /**
     * @var array<string, class-string<CourierServiceInterface>>
     */
    private array $services = [
        'ghtk' => GHTKService::class,
        'ghn' => GHNService::class,
    ];

    /**
     * Resolve a courier service instance by courier code.
     */
    public function resolve(string $courierCode): CourierServiceInterface
    {
        $serviceClass = $this->serviceClassFor($courierCode);

        /** @var CourierServiceInterface $service */
        $service = Container::getInstance()->make($serviceClass);

        return $service;
    }

    /**
     * Register or override a courier service mapping at runtime.
     *
     * This lets you plug in new services later without changing callers.
     */
    public function register(string $courierCode, string $serviceClass): void
    {
        $this->services[strtolower((string) $courierCode)] = $serviceClass;
    }

    /**
     * Return the supported courier codes.
     *
     * @return array<int,string>
     */
    public function supportedCouriers(): array
    {
        return array_keys($this->services);
    }

    /**
     * Determine the service class for a courier code.
     */
    public function serviceClassFor(string $courierCode): string
    {
        $code = strtolower(trim((string) $courierCode));

        if (!isset($this->services[$code])) {
            throw new \RuntimeException("Unsupported courier code: {$courierCode}");
        }

        return $this->services[$code];
    }
}
