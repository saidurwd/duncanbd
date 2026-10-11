<?php

namespace App\Http\Requests\Admin;

use App\Domain\Documents\Models\Document;
use App\Domain\Documents\Models\DocumentCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveDocumentRequest extends FormRequest
{
    public const FILE_RULES = ['file', 'max:51200', 'extensions:pdf,docx,xlsx,pptx', 'mimes:pdf,docx,xlsx,pptx'];

    public function authorize(): bool
    {
        $category = DocumentCategory::find($this->input('category_id'));
        $document = $this->route('document');

        // The user must be allowed to manage the target category, and the current one when moving.
        if ($category && ! $this->user()->can('manageCategory', [Document::class, $category])) {
            return false;
        }

        return ! ($document instanceof Document) || $this->user()->can('update', $document);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $isNew = ! $this->route('document');

        return [
            'category_id' => ['required', Rule::exists('document_categories', 'id')],
            'title' => ['required', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:200', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'description' => ['nullable', 'string', 'max:2000'],
            'financial_year' => ['nullable', 'string', 'regex:/^\d{4}(-\d{4})?$/'],
            'document_date' => ['nullable', 'date'],
            'visibility' => ['required', 'in:public,private'],
            'published_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after:published_at'],
            'cover_media_id' => ['nullable', 'integer', 'exists:media_items,id'],
            'file' => [$isNew ? 'required' : 'nullable', ...self::FILE_RULES],
        ];
    }
}
