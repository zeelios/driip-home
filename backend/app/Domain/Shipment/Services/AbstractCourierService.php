<?php

declare(strict_types=1);

namespace App\Domain\Shipment\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * Base courier integration with shared HTTP plumbing.
 *
 * Concrete courier services provide the API-specific headers and implement
 * the provider-specific payload/response mapping on top of this request helper.
 */
abstract class AbstractCourierService
{
    protected const TIMEOUT = 30;
    protected const RETRY_TIMES = 3;
    protected const RETRY_SLEEP_MS = 500;

    protected string $baseUrl;
    protected string $apiToken;

    public function __construct(string $configKey)
    {
        $config = config("courier.{$configKey}", []);

        $this->baseUrl = rtrim((string) ($config['api_endpoint'] ?? ''), '/');
        $this->apiToken = (string) ($config['api_key'] ?? '');
    }

    /**
     * Default request headers for the provider.
     *
     * @return array<string,string>
     */
    abstract protected function defaultHeaders(): array;

    /**
     * Execute an HTTP request and normalize the JSON response payload.
     *
     * @param  string  $method
     * @param  string  $endpoint
     * @param  array<string,mixed>  $payload
     * @param  array<string,mixed>  $query
     * @return array<string,mixed>
     */
    protected function request(
        string $method,
        string $endpoint,
        array $payload = [],
        array $query = []
    ): array {
        $url = $this->baseUrl . '/' . ltrim($endpoint, '/');
        $attempt = 0;
        $lastError = null;

        while ($attempt < self::RETRY_TIMES) {
            try {
                $http = Http::withHeaders($this->defaultHeaders())
                    ->timeout(self::TIMEOUT)
                    ->acceptJson();

                $response = strtolower((string) $method) === 'get'
                    ? $http->get($url, $query)
                    : $http->send(strtoupper($method), $url, [
                        'json' => $payload,
                        'query' => $query,
                    ]);

                if (!$response->successful()) {
                    throw new \RuntimeException(
                        "Courier API error: HTTP {$response->status()} - {$response->body()}"
                    );
                }

                $data = $response->json();

                if (is_array($data)) {
                    return $data;
                }

                return [];
            } catch (\Throwable $e) {
                $lastError = $e;
                $attempt++;

                if ($attempt < self::RETRY_TIMES) {
                    usleep(self::RETRY_SLEEP_MS * 1000 * $attempt);
                }
            }
        }

        throw new \RuntimeException(
            "Courier API request failed after {$attempt} attempts: {$lastError->getMessage()}",
            0,
            $lastError
        );
    }

    /**
     * Return the provider API base URL.
     */
    protected function baseUrl(): string
    {
        return $this->baseUrl;
    }
}
