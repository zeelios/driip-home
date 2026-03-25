<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Support\Http\InputSanitizer;
use Illuminate\Foundation\Http\FormRequest;

abstract class ApiRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $sanitizer = app(InputSanitizer::class);
        $except = $this->sanitizeExcept();

        $this->query->replace($sanitizer->sanitizeArray($this->query->all(), $except));

        if ($this->isJson()) {
            $this->json()->replace($sanitizer->sanitizeArray($this->json()->all(), $except));

            return;
        }

        $this->request->replace($sanitizer->sanitizeArray($this->request->all(), $except));
    }

    /**
     * @return array<int, string>
     */
    protected function sanitizeExcept(): array
    {
        return [];
    }
}
