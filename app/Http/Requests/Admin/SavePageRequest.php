<?php

namespace App\Http\Requests\Admin;

use App\Domain\Cms\Blocks\BlockType;
use App\Domain\Cms\Models\Page;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SavePageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('pages.edit');
    }

    /** The block editor posts its state as one JSON string. */
    protected function prepareForValidation(): void
    {
        $decoded = json_decode((string) $this->input('blocks_json', '[]'), true);

        $this->merge(['blocks' => is_array($decoded) ? $decoded : []]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        /** @var Page|null $page */
        $page = $this->route('page');

        $rules = [
            'title' => ['required', 'string', 'max:160'],
            'slug' => ['nullable', 'string', 'max:160', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'parent_id' => ['nullable', Rule::exists('pages', 'id')->whereNull('deleted_at'), Rule::notIn(array_filter([$page?->id]))],
            'template' => ['nullable', Rule::in(['default', 'wide'])],
            'seo.title' => ['nullable', 'string', 'max:70'],
            'seo.description' => ['nullable', 'string', 'max:170'],
            'seo.og_media_id' => ['nullable', 'integer', 'exists:media_items,id'],
            'note' => ['nullable', 'string', 'max:255'],
            'blocks' => ['array', 'max:60'],
            'blocks.*.type' => ['required', Rule::in(array_column(BlockType::cases(), 'value'))],
            'blocks.*.data' => ['array'],
        ];

        foreach ((array) $this->input('blocks', []) as $i => $block) {
            $type = BlockType::tryFrom((string) ($block['type'] ?? ''));

            foreach ($type?->rules() ?? [] as $field => $fieldRules) {
                $rules["blocks.$i.data.$field"] = $fieldRules;
            }
        }

        return $rules;
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['seo.title' => 'meta title', 'seo.description' => 'meta description'];
    }
}
