<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Cms\Actions\SaveMenu;
use App\Domain\Cms\Enums\MenuLocation;
use App\Domain\Cms\Models\Menu;
use App\Domain\Cms\Models\Page;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveMenuRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class MenuController extends Controller
{
    public function index(): View
    {
        return view('admin.menus.index', ['locations' => MenuLocation::cases()]);
    }

    public function edit(MenuLocation $location): View
    {
        $items = Menu::firstOrNew(['location' => $location->value])->items;

        // Flatten to the editor's index-based parent references.
        $indexById = $items->pluck('id')->flip();
        $rows = $items->map(fn ($i) => [
            'label' => $i->label,
            'page_id' => $i->page_id,
            'url' => $i->url,
            'parent' => $i->parent_id ? $indexById[$i->parent_id] : null,
            'target' => $i->target,
            'is_active' => $i->is_active,
        ])->values();

        return view('admin.menus.edit', [
            'location' => $location,
            'rows' => old('items') ? array_values(old('items')) : $rows,
            'pages' => Page::orderBy('path')->get(['id', 'title', 'path']),
        ]);
    }

    public function update(SaveMenuRequest $request, MenuLocation $location, SaveMenu $save): RedirectResponse
    {
        // The form shows 1-based positions; the action works with 0-based indexes.
        $items = array_map(function (array $item) {
            $item['parent'] = isset($item['parent']) ? $item['parent'] - 1 : null;

            return $item;
        }, array_values($request->validated('items') ?? []));

        $save($location, $items);

        return back()->with('status', 'Menu saved.');
    }
}
