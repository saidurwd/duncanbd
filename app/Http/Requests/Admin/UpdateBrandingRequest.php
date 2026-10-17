<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBrandingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('settings.manage');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        // No SVG: an SVG can carry script. PNG with a transparent background is the best choice for a logo.
        $logo = ['nullable', 'file', 'max:2048', 'extensions:png,jpg,jpeg,webp', 'mimes:png,jpg,jpeg,webp', 'dimensions:min_width=100,max_width=3000,max_height=1500'];

        return [
            'logo' => $logo,
            'logo_dark' => $logo,
            'share_image' => ['nullable', 'file', 'max:3072', 'extensions:png,jpg,jpeg,webp', 'mimes:png,jpg,jpeg,webp', 'dimensions:min_width=600,min_height=315,max_width=4000,max_height=4000'],
            'favicon' => ['nullable', 'file', 'max:512', 'extensions:png', 'mimes:png', 'dimensions:min_width=32,min_height=32,max_width=1024,ratio=1/1'],
            'remove' => ['nullable', 'array'],
            'remove.*' => ['in:logo,logo_dark,favicon,share_image'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'logo.dimensions' => 'The logo must be at least 100 px wide, at most 3000 × 1500 px.',
            'logo_dark.dimensions' => 'The logo must be at least 100 px wide, at most 3000 × 1500 px.',
            'share_image.dimensions' => 'The share image must be at least 600 × 315 px (1200 × 630 recommended).',
            'favicon.dimensions' => 'The favicon must be a square PNG between 32 and 1024 px.',
        ];
    }
}
