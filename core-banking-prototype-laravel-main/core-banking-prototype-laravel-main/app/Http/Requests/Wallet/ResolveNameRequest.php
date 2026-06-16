<?php

declare(strict_types=1);

namespace App\Http\Requests\Wallet;

use App\Domain\MobilePayment\Enums\PaymentNetwork;
use Illuminate\Foundation\Http\FormRequest;

class ResolveNameRequest extends FormRequest
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
            'name'    => ['required', 'string', 'min:3', 'max:253', 'regex:/^[a-zA-Z0-9][a-zA-Z0-9.-]+\.[a-z]{2,}$/'],
            'network' => ['required', 'string', 'in:' . implode(',', PaymentNetwork::values())],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'A domain name is required (e.g. alice.sol, vitalik.eth).',
            'name.regex'    => 'Invalid name format. Expected format: name.tld (e.g. alice.sol).',
            'network.in'    => 'Supported networks: ' . implode(', ', PaymentNetwork::values()),
        ];
    }
}
