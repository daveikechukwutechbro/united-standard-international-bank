<?php

declare(strict_types=1);

namespace App\Http\Requests\RegTech;

use App\Domain\RegTech\Enums\Jurisdiction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'report_type'  => ['required', 'string', 'max:50'],
            'jurisdiction' => ['required', 'string', Rule::in(Jurisdiction::values())],
            'report_data'  => ['required', 'array'],
            'metadata'     => ['nullable', 'array'],
        ];
    }
}
