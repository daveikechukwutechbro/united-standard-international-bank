<?php

declare(strict_types=1);

namespace App\Http\Requests\RegTech;

use Illuminate\Foundation\Http\FormRequest;

class TravelRuleCheckRequest extends FormRequest
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
            'amount'                     => ['required', 'numeric', 'min:0'],
            'currency'                   => ['required', 'string', 'regex:/^[A-Z]{3}$/'],
            'originator'                 => ['nullable', 'array'],
            'originator.name'            => ['nullable', 'string', 'max:255'],
            'originator.address'         => ['nullable', 'string', 'max:500'],
            'originator.account_number'  => ['nullable', 'string', 'max:100'],
            'originator.doc_id'          => ['nullable', 'string', 'max:100'],
            'beneficiary'                => ['nullable', 'array'],
            'beneficiary.name'           => ['nullable', 'string', 'max:255'],
            'beneficiary.account_number' => ['nullable', 'string', 'max:100'],
        ];
    }
}
