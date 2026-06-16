<?php

declare(strict_types=1);

namespace App\Http\Requests\RegTech;

use Illuminate\Foundation\Http\FormRequest;

class WhitepaperValidationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'issuer_legal_name' => ['nullable', 'string', 'max:255'],
            'publication_date'  => ['nullable', 'string', 'date'],
            'page_count'        => ['nullable', 'integer', 'min:0'],
            'sections'          => ['nullable', 'array'],
        ];
    }
}
