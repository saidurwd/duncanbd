<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Media\Actions\UploadMediaItem;
use App\Domain\Media\Models\MediaFolder;
use App\Domain\Media\Models\MediaItem;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMediaRequest;
use App\Http\Requests\Admin\UpdateMediaRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MediaController extends Controller
{
    public function index(Request $request): View
    {
        $folderId = $request->integer('folder') ?: null;
        $search = trim((string) $request->query('q'));

        $items = MediaItem::query()
            ->with(['media', 'folder'])
            ->when($folderId, fn ($q) => $q->where('folder_id', $folderId))
            ->when($search !== '', fn ($q) => $q->where('title', 'like', '%'.addcslashes($search, '%_\\').'%'))
            ->latest()
            ->paginate(24)
            ->withQueryString();

        return view('admin.media.index', [
            'items' => $items,
            'folders' => MediaFolder::orderBy('name')->get(),
            'folderId' => $folderId,
            'search' => $search,
        ]);
    }

    public function store(StoreMediaRequest $request, UploadMediaItem $upload): RedirectResponse
    {
        $meta = $request->safe()->only(['alt_text', 'is_decorative', 'folder_id', 'credit']);

        foreach ($request->file('files') as $file) {
            $upload($file, $meta, $request->user()->id);
        }

        return back()->with('status', count($request->file('files')).' file(s) uploaded.');
    }

    public function edit(MediaItem $media): View
    {
        abort_unless(auth()->user()->can('media.manage'), 403);

        return view('admin.media.edit', [
            'item' => $media->load('media'),
            'folders' => MediaFolder::orderBy('name')->get(),
        ]);
    }

    public function update(UpdateMediaRequest $request, MediaItem $media): RedirectResponse
    {
        $media->update($request->validated() + ['is_decorative' => $request->boolean('is_decorative')]);

        return redirect()->route('admin.media.index')->with('status', 'Media updated.');
    }

    public function destroy(MediaItem $media): RedirectResponse
    {
        abort_unless(auth()->user()->can('media.manage'), 403);

        $media->delete();

        return redirect()->route('admin.media.index')->with('status', 'Media moved to trash.');
    }

    public function storeFolder(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('media.manage'), 403);

        $data = $request->validate(['name' => ['required', 'string', 'max:120']]);
        MediaFolder::create($data);

        return back()->with('status', 'Folder created.');
    }
}
