<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Content\ContentResource;
use App\Domain\Content\SaveContentRecord;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** One CRUD screen for every schema-described content type (businesses, estates, people, timeline). */
class ContentResourceController extends Controller
{
    public function index(string $resource): View
    {
        $def = ContentResource::find($resource);

        return view('admin.content.index', [
            'def' => $def,
            'records' => $def->model::query()->orderBy($def->orderBy)->orderBy('id')->paginate(50),
        ]);
    }

    public function create(string $resource): View
    {
        $def = ContentResource::find($resource);

        return view('admin.content.form', ['def' => $def, 'record' => new ($def->model)]);
    }

    public function store(Request $request, string $resource, SaveContentRecord $save): RedirectResponse
    {
        $def = ContentResource::find($resource);
        $save($def, null, $request->validate($def->rules()));

        return redirect()->route('admin.content.index', $resource)->with('status', 'Saved.');
    }

    public function edit(string $resource, int $id): View
    {
        $def = ContentResource::find($resource);

        return view('admin.content.form', ['def' => $def, 'record' => $def->model::findOrFail($id)]);
    }

    public function update(Request $request, string $resource, int $id, SaveContentRecord $save): RedirectResponse
    {
        $def = ContentResource::find($resource);
        $save($def, $def->model::findOrFail($id), $request->validate($def->rules()));

        return redirect()->route('admin.content.index', $resource)->with('status', 'Saved.');
    }

    public function destroy(string $resource, int $id): RedirectResponse
    {
        $def = ContentResource::find($resource);
        $def->model::findOrFail($id)->delete();

        return redirect()->route('admin.content.index', $resource)->with('status', 'Deleted.');
    }
}
