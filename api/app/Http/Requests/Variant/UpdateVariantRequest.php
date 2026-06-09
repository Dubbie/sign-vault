<?php

namespace App\Http\Requests\Variant;

use App\Enums\VariantGridBackgroundPreset;
use App\Models\Variant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        $folder = $this->route('folder');
        /** @var Variant $variant */
        $variant = $this->route('variant');

        return [
            'name' => [
                'nullable',
                'string',
                'max:100',
                'not_regex:/^\s*$/',
                Rule::unique('variants', 'name')
                    ->where('folder_id', $folder->id)
                    ->whereNotNull('name')
                    ->ignore($variant->id),
            ],
            'is_default' => ['nullable', 'boolean'],
            'grid_background_preset' => [
                'nullable',
                'string',
                Rule::in(VariantGridBackgroundPreset::values()),
            ],
        ];
    }
}
