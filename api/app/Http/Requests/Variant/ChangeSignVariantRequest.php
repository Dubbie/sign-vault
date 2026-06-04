<?php

namespace App\Http\Requests\Variant;

use Illuminate\Foundation\Http\FormRequest;

class ChangeSignVariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'exists:signs,id'],
            'variant_id' => ['required', 'integer', 'exists:variants,id'],
        ];
    }
}
