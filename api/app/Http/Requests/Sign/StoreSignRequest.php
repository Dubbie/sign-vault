<?php

namespace App\Http\Requests\Sign;

use Illuminate\Foundation\Http\FormRequest;

class StoreSignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->hasFile('file') && ! $this->hasFile('files')) {
            $this->merge([
                'files' => [$this->file('file')],
            ]);
        }
    }

    /**
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        return [
            'files' => [
                'required',
                'array',
                'min:1',
                'max:'.config('signs.max_upload_files'),
            ],
            'files.*' => [
                'required',
                'file',
                'image',
                'mimetypes:image/png,image/jpeg,image/webp',
                'max:10240',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'files.max' => 'You may upload at most :max files at a time.',
        ];
    }
}
