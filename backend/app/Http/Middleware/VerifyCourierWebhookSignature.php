<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Shipment\Models\CourierConfig;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verify incoming courier webhook signatures using HMAC-SHA256.
 *
 * Each courier signs its webhook payload with a shared secret.
 * This middleware:
 * 1. Resolves the courier code from the route name (e.g., webhook.ghn → ghn)
 * 2. Fetches the courier's webhook_secret from courier_configs
 * 3. Computes HMAC-SHA256 of the raw request body
 * 4. Compares against the signature header
 * 5. Uses Redis to reject duplicate events (idempotency)
 *
 * Rejects with 401 on invalid signature, 409 on duplicate event.
 */
class VerifyCourierWebhookSignature
{
    /** @var array<string,string> Map of courier code → signature header name */
    private const SIGNATURE_HEADERS = [
        'ghn'     => 'X-Webhook-Token',
        'ghtk'    => 'X-Ghtk-Signature',
        'spx'     => 'X-Shopee-Signature',
        'viettel' => 'X-Viettel-Signature',
    ];

    /**
     * Handle an incoming webhook request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $routeName   = $request->route()->getName() ?? '';
        $courierCode = str_replace('webhook.', '', $routeName);

        /** @var CourierConfig|null $config */
        $config = CourierConfig::where('courier_code', $courierCode)
            ->where('is_active', true)
            ->first();

        if (!$config || !$config->webhook_secret) {
            return response()->json(['message' => 'Courier not configured.'], 401);
        }

        // Verify signature
        $headerName = self::SIGNATURE_HEADERS[$courierCode] ?? null;
        $signature  = $request->header($headerName ?? '');
        $rawBody    = $request->getContent();
        $expected   = hash_hmac('sha256', $rawBody, $config->webhook_secret);

        if (!hash_equals($expected, (string) $signature)) {
            return response()->json(['message' => 'Invalid webhook signature.'], 401);
        }

        // Idempotency: reject duplicate event IDs (5-min window)
        $eventId  = $request->input('order_code') ?? $request->input('tracking_number') ?? md5($rawBody);
        $redisKey = "webhook:{$courierCode}:{$eventId}";

        if (Redis::exists($redisKey)) {
            return response()->json(['message' => 'Duplicate event.'], 409);
        }

        Redis::setex($redisKey, 300, '1'); // 5-minute TTL

        return $next($request);
    }
}
