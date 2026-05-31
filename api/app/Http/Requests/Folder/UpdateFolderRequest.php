<?php

namespace App\Http\Requests\Folder;

use App\Enums\FolderVisibility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFolderRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:100'],
            'visibility' => ['required', Rule::enum(FolderVisibility::class)],
            'password' => ['nullable', 'string', 'max:255', 'required_if:visibility,'.FolderVisibility::Password->value],
        ];
    }
}
