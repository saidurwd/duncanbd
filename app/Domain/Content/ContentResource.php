<?php

namespace App\Domain\Content;

use App\Domain\Business\Models\Business;
use App\Domain\Business\Models\Estate;
use App\Domain\People\Models\Person;
use App\Domain\People\Models\TimelineEvent;
use Illuminate\Database\Eloquent\Model;

/**
 * Describes one structured content type managed through the generic admin screen:
 * its model, list columns and form fields. Validation, persistence and the form are all derived from it.
 *
 * Field types: text, textarea, richtext, number, url, media, boolean, select, lines (one item per line -> array),
 * pairs ("label | value" per line -> [{label,value}]).
 */
class ContentResource
{
    /**
     * @param  class-string<Model>  $model
     * @param  array<string, string>  $columns  list columns, attribute => heading
     * @param  array<string, array<string, mixed>>  $fields
     */
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly string $model,
        public readonly array $columns,
        public readonly array $fields,
        public readonly ?string $slugFrom = null,
        public readonly string $orderBy = 'sort_order',
    ) {}

    /** @return array<string, self> */
    public static function all(): array
    {
        $resources = [
            new self('businesses', 'Businesses', Business::class, ['name' => 'Name', 'sort_order' => 'Order'], [
                'name' => ['label' => 'Name', 'type' => 'text', 'rules' => ['required', 'string', 'max:160']],
                'summary' => ['label' => 'Summary', 'type' => 'textarea', 'rules' => ['nullable', 'string', 'max:500']],
                'body' => ['label' => 'Description', 'type' => 'richtext', 'rules' => ['nullable', 'string', 'max:100000']],
                'metrics' => ['label' => 'Headline metrics (one per line: Label | Value)', 'type' => 'pairs', 'rules' => ['nullable', 'string', 'max:1000']],
                'logo_media_id' => ['label' => 'Logo (media ID)', 'type' => 'media', 'rules' => ['nullable', 'integer', 'exists:media_items,id']],
                'website_url' => ['label' => 'Website', 'type' => 'url', 'rules' => ['nullable', 'url:https', 'max:500']],
                'sort_order' => ['label' => 'Order', 'type' => 'number', 'rules' => ['nullable', 'integer', 'min:0', 'max:9999']],
                'is_active' => ['label' => 'Visible on site', 'type' => 'boolean', 'rules' => ['boolean']],
            ], 'name'),
            new self('estates', 'Estates', Estate::class, ['name' => 'Name', 'district' => 'District', 'area_ha' => 'Hectares'], [
                'business_id' => ['label' => 'Business', 'type' => 'select', 'rules' => ['required', 'exists:businesses,id'], 'options' => fn () => Business::orderBy('name')->pluck('name', 'id')->all()],
                'name' => ['label' => 'Name', 'type' => 'text', 'rules' => ['required', 'string', 'max:160']],
                'district' => ['label' => 'District', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:120']],
                'area_ha' => ['label' => 'Area (hectares)', 'type' => 'number', 'rules' => ['nullable', 'numeric', 'min:0', 'max:99999999']],
                'factory' => ['label' => 'Factory', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:160']],
                'certifications' => ['label' => 'Certifications (one per line)', 'type' => 'lines', 'rules' => ['nullable', 'string', 'max:1000']],
                'lat' => ['label' => 'Latitude', 'type' => 'number', 'rules' => ['nullable', 'numeric', 'between:-90,90']],
                'lng' => ['label' => 'Longitude', 'type' => 'number', 'rules' => ['nullable', 'numeric', 'between:-180,180']],
                'body' => ['label' => 'Description', 'type' => 'richtext', 'rules' => ['nullable', 'string', 'max:100000']],
                'photo_media_id' => ['label' => 'Photo (media ID)', 'type' => 'media', 'rules' => ['nullable', 'integer', 'exists:media_items,id']],
                'sort_order' => ['label' => 'Order', 'type' => 'number', 'rules' => ['nullable', 'integer', 'min:0', 'max:9999']],
                'is_active' => ['label' => 'Visible on site', 'type' => 'boolean', 'rules' => ['boolean']],
            ], 'name'),
            new self('people', 'Board & leadership', Person::class, ['name' => 'Name', 'position' => 'Position', 'group' => 'Group'], [
                'name' => ['label' => 'Name', 'type' => 'text', 'rules' => ['required', 'string', 'max:160']],
                'position' => ['label' => 'Position', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:160']],
                'group' => ['label' => 'Group', 'type' => 'select', 'rules' => ['required', 'in:board,leadership'], 'options' => fn () => ['board' => 'Board', 'leadership' => 'Leadership']],
                'bio' => ['label' => 'Biography', 'type' => 'richtext', 'rules' => ['nullable', 'string', 'max:50000']],
                'photo_media_id' => ['label' => 'Photo (media ID)', 'type' => 'media', 'rules' => ['nullable', 'integer', 'exists:media_items,id']],
                'sort_order' => ['label' => 'Order', 'type' => 'number', 'rules' => ['nullable', 'integer', 'min:0', 'max:9999']],
                'is_active' => ['label' => 'Visible on site', 'type' => 'boolean', 'rules' => ['boolean']],
            ], 'name'),
            new self('timeline', 'History timeline', TimelineEvent::class, ['year' => 'Year', 'title' => 'Title'], [
                'year' => ['label' => 'Year', 'type' => 'text', 'rules' => ['required', 'string', 'max:10']],
                'title' => ['label' => 'Title', 'type' => 'text', 'rules' => ['required', 'string', 'max:160']],
                'body' => ['label' => 'Text', 'type' => 'textarea', 'rules' => ['nullable', 'string', 'max:2000']],
                'media_id' => ['label' => 'Image (media ID)', 'type' => 'media', 'rules' => ['nullable', 'integer', 'exists:media_items,id']],
                'sort_order' => ['label' => 'Order', 'type' => 'number', 'rules' => ['nullable', 'integer', 'min:0', 'max:9999']],
            ], null, 'year'),
        ];

        return array_column(array_map(fn ($r) => [$r->key, $r], $resources), 1, 0);
    }

    public static function find(string $key): self
    {
        return self::all()[$key] ?? abort(404);
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return array_map(fn ($f) => $f['rules'], $this->fields);
    }
}
