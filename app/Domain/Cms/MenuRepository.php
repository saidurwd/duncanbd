<?php

namespace App\Domain\Cms;

use App\Domain\Cms\Enums\MenuLocation;
use App\Domain\Cms\Models\Menu;
use Illuminate\Support\Facades\Cache;

class MenuRepository
{
    /**
     * Active items as a two-level tree: [['label', 'url', 'target', 'children' => [...]]].
     *
     * @return list<array{label: string, url: ?string, target: string, children: list<array{label: string, url: ?string, target: string}>}>
     */
    public function tree(MenuLocation $location): array
    {
        return Cache::remember("menu.{$location->value}", 3600, function () use ($location) {
            $menu = Menu::where('location', $location->value)->with(['items.page'])->first();

            if (! $menu) {
                return [];
            }

            $items = $menu->items->where('is_active', true);
            $resolve = fn ($item) => [
                'label' => $item->label,
                'url' => $item->page ? '/'.($item->page->path === 'home' ? '' : $item->page->path) : $item->url,
                'target' => $item->target,
            ];

            return $items->whereNull('parent_id')->map(fn ($top) => $resolve($top) + [
                'children' => $items->where('parent_id', $top->id)->map($resolve)->values()->all(),
            ])->values()->all();
        });
    }

    public function forget(): void
    {
        foreach (MenuLocation::cases() as $location) {
            Cache::forget("menu.{$location->value}");
        }
    }
}
