<?php

namespace App\Http\Requests\Variant;

use App\Models\Folder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVariantRequest extends FormRequest
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
        ];
    }
}
