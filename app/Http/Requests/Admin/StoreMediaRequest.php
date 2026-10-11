<?php

namespace App\Http\Requests\Admin;

use App\Domain\Media\Models\MediaItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('media.manage');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'files' => ['required', 'array', 'min:1', 'max:20'],
            'files.*' => ['file', 'max:10240', 'mimetypes:'.implode(',', MediaItem::MIME_TYPES), 'dimensions:max_width=8000,max_height=8000'],
            'folder_id' => ['nullable', Rule::exists('media_folders', 'id')],
            'alt_text' => ['nullable', 'string', 'max:255', 'required_without:is_decorative'],
            'is_decorative' => ['sometimes', 'boolean'],
            'credit' => ['nullable', 'string', 'max:160'],
        ];
    }
}
