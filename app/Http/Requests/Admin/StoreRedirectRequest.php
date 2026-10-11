<?php

namespace App\Http\Requests\Admin;

use App\Domain\Cms\Models\Redirect;
use App\Domain\Cms\RedirectManager;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreRedirectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('seo.manage');
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('from_path'))) {
            $this->merge(['from_path' => RedirectManager::normalize($this->input('from_path'))]);
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'from_path' => ['required', 'string', 'max:500', 'unique:redirects,from_path', 'regex:/^\/[^\s]*$/'],
            'to_url' => ['required', 'string', 'max:500', 'regex:/^(https:\/\/|\/)[^\s]*$/i'],
            'status_code' => ['required', 'in:301,302'],
        ];
    }

    /** @return list<\Closure> */
    public function after(): array
    {
        return [function (Validator $validator) {
            $from = $this->input('from_path');
            $to = RedirectManager::normalize((string) $this->input('to_url'));

            if ($from === $to && str_starts_with((string) $this->input('to_url'), '/')) {
                $validator->errors()->add('to_url', 'A redirect cannot point to itself.');
            }

            if (str_starts_with((string) $this->input('to_url'), '/') && Redirect::where('from_path', $to)->where('to_url', $from)->exists()) {
                $validator->errors()->add('to_url', 'That would create a redirect loop.');
            }

            if (str_starts_with((string) $from, '/'.config('admin.path'))) {
                $validator->errors()->add('from_path', 'The admin area cannot be redirected.');
            }
        }];
    }
}
