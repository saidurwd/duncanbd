<?php

namespace App\Domain\Cms\Blocks;

use Mews\Purifier\Facades\Purifier;

/**
 * Normalises submitted blocks to exactly what the schema allows: unknown keys dropped,
 * rich text run through the HTMLPurifier allow-list, schema version stamped.
 */
class BlockSanitizer
{
    /**
     * @param  list<array<string, mixed>>  $blocks
     * @return list<array{type: string, schema_version: int, data: array<string, mixed>}>
     */
    public function sanitize(array $blocks): array
    {
        $clean = [];

        foreach ($blocks as $block) {
            $type = BlockType::tryFrom((string) ($block['type'] ?? ''));

            if ($type === null) {
                continue;
            }

            $clean[] = [
                'type' => $type->value,
                'schema_version' => BlockType::SCHEMA_VERSION,
                'data' => $this->fields($type->fields(), (array) ($block['data'] ?? [])),
            ];
        }

        return $clean;
    }

    /**
     * @param  array<string, array<string, mixed>>  $fields
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function fields(array $fields, array $data): array
    {
        $out = [];

        foreach ($fields as $key => $field) {
            $value = $data[$key] ?? null;

            $out[$key] = match ($field['type']) {
                'repeater' => array_values(array_map(
                    fn ($row) => $this->fields($field['fields'], (array) $row),
                    array_slice((array) $value, 0, $field['limit'] ?? 50),
                )),
                'richtext' => is_string($value) ? Purifier::clean($value) : null,
                'media', 'number' => is_numeric($value) ? $value + 0 : null,
                default => is_string($value) && $value !== '' ? $value : null,
            };
        }

        return $out;
    }
}
