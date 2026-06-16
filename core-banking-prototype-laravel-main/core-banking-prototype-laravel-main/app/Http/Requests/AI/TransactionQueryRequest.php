<?php

declare(strict_types=1);

namespace App\Http\Requests\AI;

use Illuminate\Foundation\Http\FormRequest;

class TransactionQueryRequest extends FormRequest
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
            'query'        => ['nullable', 'string', 'max:500'],
            'account_uuid' => ['nullable', 'string', 'uuid'],
            'date_from'    => ['nullable', 'string', 'date'],
            'date_to'      => ['nullable', 'string', 'date', 'after_or_equal:date_from'],
            'amount_min'   => ['nullable', 'numeric', 'min:0'],
            'amount_max'   => ['nullable', 'numeric', 'min:0', 'gte:amount_min'],
            'category'     => ['nullable', 'string', 'max:50'],
            'asset_code'   => ['nullable', 'string', 'regex:/^[A-Z]{3,10}$/'],
            'limit'        => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'query.max'              => 'Query must be under 500 characters.',
            'asset_code.regex'       => 'Asset code must be 3-10 uppercase letters (e.g., USD, BTC).',
            'date_to.after_or_equal' => 'End date must be on or after the start date.',
        ];
    }
}
