<?php

namespace App\Domain\Cms\Blocks;

/**
 * The page-builder block catalogue (architecture doc §6.5). Each case defines its own fields;
 * validation, sanitising, the admin editor and the frontend component all derive from this.
 *
 * Field shape: key => ['label', 'type' => text|textarea|richtext|url|media|number|select|repeater,
 *                      'required'?, 'max'?, 'options'? (select), 'fields'? (repeater), 'limit'? (repeater)]
 */
enum BlockType: string
{
    case Hero = 'hero';
    case RichText = 'rich_text';
    case QuickLinks = 'quick_links';
    case Stats = 'stats';
    case CardsGrid = 'cards_grid';
    case CompanyAccordion = 'company_accordion';
    case FeaturedDocument = 'featured_document';
    case DocumentList = 'document_list';
    case NewsFeed = 'news_feed';
    case Video = 'video';
    case Gallery = 'gallery';
    case Timeline = 'timeline';
    case PeopleGrid = 'people_grid';
    case CtaBand = 'cta_band';
    case AccordionFaq = 'accordion_faq';
    case Map = 'map';
    case Certifications = 'certifications';

    public const SCHEMA_VERSION = 1;

    public function label(): string
    {
        return ucwords(str_replace('_', ' ', $this->value));
    }

    /** @return array<string, array<string, mixed>> */
    public function fields(): array
    {
        $link = fn () => [
            'label' => ['label' => 'Label', 'type' => 'text', 'required' => true, 'max' => 80],
            'url' => ['label' => 'URL', 'type' => 'url', 'required' => true],
        ];

        return match ($this) {
            self::Hero => [
                'heading' => ['label' => 'Heading (H1)', 'type' => 'text', 'required' => true, 'max' => 160],
                'intro' => ['label' => 'Intro statement', 'type' => 'textarea', 'max' => 400],
                'slides' => ['label' => 'Slides (max 5)', 'type' => 'repeater', 'limit' => 5, 'fields' => [
                    'media_id' => ['label' => 'Image', 'type' => 'media', 'required' => true],
                    'caption' => ['label' => 'Caption', 'type' => 'text', 'max' => 160],
                ]],
            ],
            self::RichText => [
                'heading' => ['label' => 'Heading', 'type' => 'text', 'max' => 160],
                'body' => ['label' => 'Content', 'type' => 'richtext', 'required' => true],
            ],
            self::QuickLinks => [
                'items' => ['label' => 'Tiles (3–5)', 'type' => 'repeater', 'limit' => 5, 'fields' => $link()],
            ],
            self::Stats => [
                'items' => ['label' => 'Figures (up to 4)', 'type' => 'repeater', 'limit' => 4, 'fields' => [
                    'value' => ['label' => 'Value', 'type' => 'text', 'required' => true, 'max' => 20],
                    'label' => ['label' => 'Label', 'type' => 'text', 'required' => true, 'max' => 80],
                ]],
            ],
            self::CardsGrid => [
                'heading' => ['label' => 'Heading', 'type' => 'text', 'max' => 160],
                'items' => ['label' => 'Cards', 'type' => 'repeater', 'limit' => 12, 'fields' => [
                    'title' => ['label' => 'Title', 'type' => 'text', 'required' => true, 'max' => 120],
                    'text' => ['label' => 'Text', 'type' => 'textarea', 'max' => 400],
                    'media_id' => ['label' => 'Image', 'type' => 'media'],
                    'url' => ['label' => 'Link URL', 'type' => 'url'],
                ]],
            ],
            self::CompanyAccordion => [
                'items' => ['label' => 'Companies', 'type' => 'repeater', 'limit' => 20, 'fields' => [
                    'name' => ['label' => 'Name', 'type' => 'text', 'required' => true, 'max' => 120],
                    'description' => ['label' => 'Description', 'type' => 'textarea', 'max' => 800],
                    'metric_1' => ['label' => 'Metric 1', 'type' => 'text', 'max' => 60],
                    'metric_2' => ['label' => 'Metric 2', 'type' => 'text', 'max' => 60],
                    'url' => ['label' => 'Find out more URL', 'type' => 'url'],
                ]],
            ],
            self::FeaturedDocument => [
                'heading' => ['label' => 'Heading', 'type' => 'text', 'max' => 160],
                'document_id' => ['label' => 'Document ID', 'type' => 'number', 'required' => true],
            ],
            self::DocumentList => [
                'heading' => ['label' => 'Heading', 'type' => 'text', 'max' => 160],
                'category' => ['label' => 'Category slug', 'type' => 'text', 'max' => 80],
                'limit' => ['label' => 'How many', 'type' => 'number'],
            ],
            self::NewsFeed => [
                'heading' => ['label' => 'Heading', 'type' => 'text', 'max' => 160],
                'limit' => ['label' => 'How many', 'type' => 'number'],
            ],
            self::Video => [
                'heading' => ['label' => 'Heading', 'type' => 'text', 'max' => 160],
                'youtube_id' => ['label' => 'YouTube video ID', 'type' => 'text', 'required' => true, 'max' => 20],
                'poster_media_id' => ['label' => 'Poster image', 'type' => 'media'],
            ],
            self::Gallery => [
                'heading' => ['label' => 'Heading', 'type' => 'text', 'max' => 160],
                'items' => ['label' => 'Images', 'type' => 'repeater', 'limit' => 40, 'fields' => [
                    'media_id' => ['label' => 'Image', 'type' => 'media', 'required' => true],
                ]],
            ],
            self::Timeline => [
                'items' => ['label' => 'Events', 'type' => 'repeater', 'limit' => 60, 'fields' => [
                    'year' => ['label' => 'Year', 'type' => 'text', 'required' => true, 'max' => 10],
                    'title' => ['label' => 'Title', 'type' => 'text', 'required' => true, 'max' => 120],
                    'text' => ['label' => 'Text', 'type' => 'textarea', 'max' => 600],
                ]],
            ],
            self::PeopleGrid => [
                'heading' => ['label' => 'Heading', 'type' => 'text', 'max' => 160],
                'group' => ['label' => 'Group', 'type' => 'select', 'options' => ['board' => 'Board', 'leadership' => 'Leadership']],
            ],
            self::CtaBand => [
                'heading' => ['label' => 'Heading', 'type' => 'text', 'required' => true, 'max' => 160],
                'text' => ['label' => 'Text', 'type' => 'textarea', 'max' => 400],
                'button_label' => ['label' => 'Button label', 'type' => 'text', 'required' => true, 'max' => 60],
                'button_url' => ['label' => 'Button URL', 'type' => 'url', 'required' => true],
            ],
            self::AccordionFaq => [
                'heading' => ['label' => 'Heading', 'type' => 'text', 'max' => 160],
                'items' => ['label' => 'Questions', 'type' => 'repeater', 'limit' => 40, 'fields' => [
                    'question' => ['label' => 'Question', 'type' => 'text', 'required' => true, 'max' => 200],
                    'answer' => ['label' => 'Answer', 'type' => 'richtext', 'required' => true],
                ]],
            ],
            self::Certifications => [
                'heading' => ['label' => 'Heading', 'type' => 'text', 'max' => 160],
                'intro' => ['label' => 'Introduction', 'type' => 'textarea', 'max' => 600],
                'items' => ['label' => 'Certifications, standards and awards', 'type' => 'repeater', 'limit' => 24, 'fields' => [
                    'name' => ['label' => 'Name (e.g. ISO 22000:2005)', 'type' => 'text', 'required' => true, 'max' => 120],
                    'issuer' => ['label' => 'Issued by / standard body', 'type' => 'text', 'max' => 160],
                    'year' => ['label' => 'Year (optional)', 'type' => 'text', 'max' => 10],
                    'scope' => ['label' => 'What it covers', 'type' => 'textarea', 'max' => 500],
                    'media_id' => ['label' => 'Logo or certificate image', 'type' => 'media'],
                    'url' => ['label' => 'More information link', 'type' => 'url'],
                ]],
            ],
            self::Map => [
                'heading' => ['label' => 'Heading', 'type' => 'text', 'max' => 160],
                'lat' => ['label' => 'Latitude', 'type' => 'number', 'required' => true],
                'lng' => ['label' => 'Longitude', 'type' => 'number', 'required' => true],
                'address' => ['label' => 'Address', 'type' => 'textarea', 'max' => 300],
            ],
        };
    }

    /** @return array<string, list<mixed>> Laravel rules keyed relative to the block's data. */
    public function rules(): array
    {
        return self::rulesFor($this->fields());
    }

    /**
     * @param  array<string, array<string, mixed>>  $fields
     * @return array<string, list<mixed>>
     */
    private static function rulesFor(array $fields): array
    {
        $rules = [];

        foreach ($fields as $key => $field) {
            $base = [($field['required'] ?? false) ? 'required' : 'nullable'];

            if ($field['type'] === 'repeater') {
                $rules[$key] = ['nullable', 'array', 'max:'.($field['limit'] ?? 50)];
                foreach (self::rulesFor($field['fields']) as $subKey => $subRules) {
                    $rules["$key.*.$subKey"] = $subRules;
                }

                continue;
            }

            $rules[$key] = match ($field['type']) {
                'text', 'textarea' => [...$base, 'string', 'max:'.($field['max'] ?? 255)],
                'richtext' => [...$base, 'string', 'max:50000'],
                // Absolute http(s) URL or a site-relative path ("/investors"); never javascript:, data: or protocol-relative.
                'url' => [...$base, 'regex:/^(https?:\/\/[^\s]+|\/(?!\/)[^\s]*)$/i', 'max:500'],
                'media' => [...$base, 'integer', 'exists:media_items,id'],
                'number' => [...$base, 'numeric'],
                'select' => [...$base, 'in:'.implode(',', array_keys($field['options']))],
                default => $base,
            };
        }

        return $rules;
    }
}
