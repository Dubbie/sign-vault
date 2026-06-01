<?php

namespace App\Http\Requests\Sign;

use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeleteSignsRequest extends FormRequest
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
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => [
                'required',
                'integer',
                Rule::exists('signs', 'id')->where(function (Builder $query): Builder {
                    return $query->where('user_id', (int) $this->user()?->id);
                }),
            ],
        ];
    }
}
