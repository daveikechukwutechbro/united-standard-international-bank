<?php

declare(strict_types=1);

namespace App\Http\Requests\Wallet;

use App\Domain\MobilePayment\Enums\PaymentNetwork;
use Illuminate\Foundation\Http\FormRequest;

class ValidateAddressRequest extends FormRequest
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
            'address' => ['required', 'string', 'min:20', 'max:128'],
            'network' => ['required', 'string', 'in:' . implode(',', PaymentNetwork::values())],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'address.required' => 'A wallet address is required.',
            'network.required' => 'A network is required.',
            'network.in'       => 'Supported networks: ' . implode(', ', PaymentNetwork::values()),
        ];
    }
}
