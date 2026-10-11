<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveMenuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('menus.manage');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'items' => ['nullable', 'array', 'max:80'],
            'items.*.label' => ['required', 'string', 'max:80'],
            'items.*.page_id' => ['nullable', Rule::exists('pages', 'id')->whereNull('deleted_at')],
            'items.*.url' => ['nullable', 'string', 'max:500', 'regex:/^(https?:\/\/|\/)/i'],
            'items.*.parent' => ['nullable', 'integer', 'min:0'],
            'items.*.target' => ['nullable', 'in:_self,_blank'],
            'items.*.is_active' => ['nullable', 'boolean'],
        ];
    }
}
