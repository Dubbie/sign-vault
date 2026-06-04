<?php

namespace App\Http\Requests\Variant;

use App\Models\Folder;
use App\Models\Variant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Folder $folder */
        $folder = $this->route('folder');

        return $this->user() !== null && $folder->user_id === $this->user()->id;
    }

    /**
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        /** @var Folder $folder */
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
        ];
    }
}
