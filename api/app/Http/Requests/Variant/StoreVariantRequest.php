<?php

namespace App\Http\Requests\Variant;

use App\Enums\VariantGridBackgroundPreset;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVariantRequest extends FormRequest
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

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                'not_regex:/^\s*$/',
                Rule::unique('variants', 'name')
                    ->where('folder_id', $folder->id)
                    ->whereNotNull('name'),
            ],
            'grid_background_preset' => [
                'nullable',
                'string',
                Rule::in(VariantGridBackgroundPreset::values()),
            ],
        ];
    }
}
