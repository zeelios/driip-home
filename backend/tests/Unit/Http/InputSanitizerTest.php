<?php

declare(strict_types=1);

use App\Http\Middleware\SanitizeUserInput;
use App\Support\Http\InputSanitizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

it('sanitizes nested string payloads recursively', function () {
    $sanitizer = new InputSanitizer();

    $clean = $sanitizer->sanitizeArray([
        'name' => "  <b>Alice</b>\0  ",
        'notes' => [
            'summary' => "\x07<script>alert(1)</script>Hello\n",
        ],
        'count' => 5,
    ]);

    expect($clean)->toBe([
        'name' => 'Alice',
        'notes' => [
            'summary' => 'Hello',
        ],
        'count' => 5,
    ]);
});

it('supports wildcard field exclusions', function () {
    $sanitizer = new InputSanitizer();

    $clean = $sanitizer->sanitizeArray([
        'settings' => [
            ['value' => '  <b>keep</b>  '],
            ['value' => "\0  <i>this too</i>  "],
        ],
        'name' => '  <b>trim me</b>  ',
    ], ['settings.*.value']);

    expect($clean)->toBe([
        'settings' => [
            ['value' => '  <b>keep</b>  '],
            ['value' => "\0  <i>this too</i>  "],
        ],
        'name' => 'trim me',
    ]);
});

it('sanitizes regular api requests in middleware', function () {
    $request = Request::create('/api/v1/panel/customers', 'POST', [
        'name' => '  <b>Alice</b>  ',
        'notes' => "\0<script>x</script>safe",
    ]);

    $middleware = new SanitizeUserInput(new InputSanitizer());

    $response = $middleware->handle($request, function (Request $request) {
        return new JsonResponse($request->request->all());
    });

    expect($response->getData(true))->toBe([
        'name' => '<b>Alice</b>',
        'notes' => '<script>x</script>safe',
    ]);
});

it('skips webhook routes in middleware', function () {
    $request = Request::create('/api/v1/webhooks/ghn', 'POST', [
        'order_code' => '  <b>ORD-1</b>  ',
    ]);

    $request->setRouteResolver(fn () => new class {
        public function getName(): string
        {
            return 'webhook.ghn';
        }
    });

    $middleware = new SanitizeUserInput(new InputSanitizer());

    $response = $middleware->handle($request, function (Request $request) {
        return new JsonResponse($request->request->all());
    });

    expect($response->getData(true))->toBe([
        'order_code' => '  <b>ORD-1</b>  ',
    ]);
});
