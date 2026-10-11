<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Documents\Models\DocumentCategory;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class DocumentCategoryController extends Controller
{
    public function index(): View
    {
        return view('admin.documents.categories', ['categories' => DocumentCategory::withCount('documents')->orderBy('sort_order')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'is_investor' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        $base = Str::slug($data['name']);
        $slug = $base;
        for ($n = 2; DocumentCategory::where('slug', $slug)->exists(); $n++) {
            $slug = "$base-$n";
        }

        DocumentCategory::create([
            'name' => $data['name'],
            'slug' => $slug,
            'is_investor' => $request->boolean('is_investor'),
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        return back()->with('status', 'Category added.');
    }

    public function update(Request $request, DocumentCategory $category): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'is_investor' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999', Rule::requiredIf(false)],
        ]);

        $category->update([
            'name' => $data['name'],
            'is_investor' => $request->boolean('is_investor'),
            'sort_order' => $data['sort_order'] ?? $category->sort_order,
        ]);

        return back()->with('status', 'Category updated.');
    }

    public function destroy(DocumentCategory $category): RedirectResponse
    {
        abort_if($category->documents()->withTrashed()->exists(), 422, 'This category still has documents.');

        $category->delete();

        return back()->with('status', 'Category deleted.');
    }
}
