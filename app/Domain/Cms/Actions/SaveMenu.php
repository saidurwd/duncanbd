<?php

namespace App\Domain\Cms\Actions;

use App\Domain\Cms\Enums\MenuLocation;
use App\Domain\Cms\MenuRepository;
use App\Domain\Cms\Models\Menu;
use Illuminate\Support\Facades\DB;

class SaveMenu
{
    public function __construct(private MenuRepository $menus) {}

    /**
     * Replaces the whole menu. Items arrive in display order; `parent` is the 0-based index of a
     * top-level item (two levels maximum, matching the overlay menu design).
     *
     * @param  list<array{label: string, page_id?: ?int, url?: ?string, parent?: ?int, target?: ?string, is_active?: mixed}>  $items
     */
    public function __invoke(MenuLocation $location, array $items): Menu
    {
        return DB::transaction(function () use ($location, $items) {
            $menu = Menu::firstOrCreate(['location' => $location->value], ['name' => $location->label()]);
            $menu->items()->delete();

            $created = [];
            foreach ($items as $i => $item) {
                $parentIndex = isset($item['parent']) ? (int) $item['parent'] : null;
                $parent = $parentIndex !== null && $parentIndex < $i ? ($created[$parentIndex] ?? null) : null;

                // No third level: an item whose parent is itself a child becomes top-level.
                if ($parent && $parent->parent_id !== null) {
                    $parent = null;
                }

                $created[$i] = $menu->items()->create([
                    'parent_id' => $parent?->id,
                    'label' => $item['label'],
                    'page_id' => $item['page_id'] ?? null ?: null,
                    'url' => ! empty($item['page_id']) ? null : ($item['url'] ?? null ?: null),
                    'target' => ($item['target'] ?? '_self') === '_blank' ? '_blank' : '_self',
                    'sort_order' => $i,
                    'is_active' => (bool) ($item['is_active'] ?? true),
                ]);
            }

            $this->menus->forget();

            return $menu;
        });
    }
}
