<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Http\InputSanitizer;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SanitizeUserInput
{
    public function __construct(
        private readonly InputSanitizer $sanitizer,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldSkip($request)) {
            return $next($request);
        }

        $request->query->replace($this->sanitizer->sanitizeArray($request->query->all(), stripTags: false));

        if ($request->isJson()) {
            $request->json()->replace($this->sanitizer->sanitizeArray($request->json()->all(), stripTags: false));
        } else {
            $request->request->replace($this->sanitizer->sanitizeArray($request->request->all(), stripTags: false));
        }

        return $next($request);
    }

    private function shouldSkip(Request $request): bool
    {
        $routeName = $request->route()?->getName();

        return str_starts_with((string) $routeName, 'webhook.')
            || $request->is('api/v1/webhooks/*');
    }
}
