<?php

namespace App\Http\Requests\Sign;

use App\Models\Folder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PrepareSignUploadsRequest extends FormRequest
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
            'files' => [
                'required',
                'array',
                'min:1',
                'max:'.config('signs.max_upload_files'),
            ],
            'files.*.original_name' => ['required', 'string', 'max:255'],
            'files.*.mime_type' => ['required', 'string', Rule::in([
                'image/png',
                'image/jpeg',
                'image/webp',
                'image/avif',
                'video/webm',
            ])],
            'files.*.size_bytes' => ['required', 'integer', 'min:1', 'max:10485760'],
            'files.*.width' => ['nullable', 'integer', 'min:1'],
            'files.*.height' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
