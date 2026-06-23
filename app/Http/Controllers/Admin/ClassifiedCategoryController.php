<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassifiedCategory;
use App\Models\ClassifiedContractTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ClassifiedCategoryController extends Controller
{
    public function index(): View
    {
        $categories = ClassifiedCategory::with(['parent', 'contractTemplate'])
            ->orderBy('sort_order')
            ->get();

        return view('admin.classified-categories.index', compact('categories'));
    }

    public function create(): View
    {
        $parents   = ClassifiedCategory::whereNull('parent_id')->where('is_active', true)->get();
        $templates = ClassifiedContractTemplate::where('is_active', true)->get();

        return view('admin.classified-categories.create', compact('parents', 'templates'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name_en'                  => 'required|string|max:150',
            'name_ar'                  => 'required|string|max:150',
            'slug'                     => 'nullable|string|max:150|unique:classified_categories,slug',
            'icon'                     => 'nullable|string|max:50',
            'parent_id'                => 'nullable|exists:classified_categories,id',
            'requires_location_map'    => 'boolean',
            'requires_sketch_upload'   => 'boolean',
            'contract_template_id'     => 'nullable|exists:classified_contract_templates,id',
            'required_attachment_types' => 'nullable|array',
            'required_attachment_types.*' => 'string',
            'sort_order'               => 'integer',
            'is_active'                => 'boolean',
        ]);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['name_en']);

        ClassifiedCategory::create($validated);

        return redirect()->route('admin.classifieds.categories.index')
            ->with('success', 'Category created.');
    }

    public function edit(ClassifiedCategory $category): View
    {
        $parents   = ClassifiedCategory::whereNull('parent_id')
            ->where('is_active', true)
            ->where('id', '!=', $category->id)
            ->get();
        $templates = ClassifiedContractTemplate::where('is_active', true)->get();

        return view('admin.classified-categories.edit', compact('category', 'parents', 'templates'));
    }

    public function update(Request $request, ClassifiedCategory $category): RedirectResponse
    {
        $validated = $request->validate([
            'name_en'                  => 'required|string|max:150',
            'name_ar'                  => 'required|string|max:150',
            'slug'                     => 'nullable|string|max:150|unique:classified_categories,slug,' . $category->id,
            'icon'                     => 'nullable|string|max:50',
            'parent_id'                => 'nullable|exists:classified_categories,id',
            'requires_location_map'    => 'boolean',
            'requires_sketch_upload'   => 'boolean',
            'contract_template_id'     => 'nullable|exists:classified_contract_templates,id',
            'required_attachment_types' => 'nullable|array',
            'required_attachment_types.*' => 'string',
            'sort_order'               => 'integer',
            'is_active'                => 'boolean',
        ]);

        $category->update($validated);

        return redirect()->route('admin.classifieds.categories.index')
            ->with('success', 'Category updated.');
    }

    public function destroy(ClassifiedCategory $category): RedirectResponse
    {
        $category->delete();

        return redirect()->route('admin.classifieds.categories.index')
            ->with('success', 'Category deleted.');
    }
}
