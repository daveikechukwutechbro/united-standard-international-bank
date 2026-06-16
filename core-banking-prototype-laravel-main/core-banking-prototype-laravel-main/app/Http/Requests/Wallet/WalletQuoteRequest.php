<?php

declare(strict_types=1);

namespace App\Http\Requests\Wallet;

use App\Domain\MobilePayment\Enums\PaymentAsset;
use App\Domain\MobilePayment\Enums\PaymentNetwork;
use Illuminate\Foundation\Http\FormRequest;

class WalletQuoteRequest extends FormRequest
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
            'network' => ['required', 'string', 'in:' . implode(',', PaymentNetwork::values())],
            'asset'   => ['required', 'string', 'in:' . implode(',', PaymentAsset::values())],
            'amount'  => ['required', 'numeric', 'gt:0', 'max:1000000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'network.in' => 'Supported networks: ' . implode(', ', PaymentNetwork::values()),
            'asset.in'   => 'Supported assets: ' . implode(', ', PaymentAsset::values()),
            'amount.gt'  => 'Amount must be greater than zero.',
        ];
    }
}
