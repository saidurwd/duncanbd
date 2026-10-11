<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('media.manage');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:160'],
            'alt_text' => ['nullable', 'string', 'max:255', 'required_without:is_decorative'],
            'is_decorative' => ['sometimes', 'boolean'],
            'caption' => ['nullable', 'string', 'max:500'],
            'credit' => ['nullable', 'string', 'max:160'],
            'folder_id' => ['nullable', Rule::exists('media_folders', 'id')],
        ];
    }
}
