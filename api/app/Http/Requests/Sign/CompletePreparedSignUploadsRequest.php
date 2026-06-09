<?php

namespace App\Http\Requests\Sign;

use App\Models\Folder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompletePreparedSignUploadsRequest extends FormRequest
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
        /** @var Folder $folder */
        $folder = $this->route('folder');

        return [
            'variant_id' => [
                'nullable',
                'integer',
                Rule::exists('variants', 'id')->where('folder_id', $folder->id),
            ],
            'upload_session_id' => ['nullable', 'uuid'],
            'intent_ids' => [
                'required',
                'array',
                'min:1',
                'max:'.config('signs.max_upload_files'),
            ],
            'intent_ids.*' => ['required', 'uuid'],
        ];
    }
}
