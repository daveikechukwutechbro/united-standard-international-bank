<?php

declare(strict_types=1);

namespace App\Http\Requests\MobilePayment;

use Illuminate\Foundation\Http\FormRequest;

class SubmitPaymentIntentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'auth'   => ['sometimes', 'string', 'in:biometric,pin'],
            'shield' => ['sometimes', 'boolean'],
        ];
    }
}
