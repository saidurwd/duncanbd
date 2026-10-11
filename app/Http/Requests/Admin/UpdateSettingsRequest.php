<?php

namespace App\Http\Requests\Admin;

use App\Domain\Settings\SettingsSchema;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('settings.manage');
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return SettingsSchema::rules();
    }
}
