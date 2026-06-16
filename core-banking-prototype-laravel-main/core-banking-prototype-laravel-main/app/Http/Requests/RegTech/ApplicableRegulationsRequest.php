<?php

declare(strict_types=1);

namespace App\Http\Requests\RegTech;

use Illuminate\Foundation\Http\FormRequest;

class ApplicableRegulationsRequest extends FormRequest
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
            'amount'           => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
            'currency'         => ['required', 'string', 'regex:/^[A-Z]{3}$/'],
            'transaction_type' => ['required', 'string', 'max:50'],
            'is_crypto'        => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // Merge query parameters into the request for validation
        $this->merge($this->query());
    }
}
