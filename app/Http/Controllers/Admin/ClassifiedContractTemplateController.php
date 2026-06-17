<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassifiedCategory;
use App\Models\ClassifiedContractTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClassifiedContractTemplateController extends Controller
{
    public function index(): View
    {
        $templates = ClassifiedContractTemplate::with('category')
            ->orderByDesc('created_at')
            ->get();

        return view('admin.classified-contract-templates.index', compact('templates'));
    }

    public function create(): View
    {
        $categories = ClassifiedCategory::where('is_active', true)->get();

        return view('admin.classified-contract-templates.create', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'classified_category_id' => 'nullable|exists:classified_categories,id',
            'name'                   => 'required|string|max:200',
            'version'                => 'integer|min:1',
            'content_en'             => 'required|string',
            'content_ar'             => 'required|string',
            'is_active'              => 'boolean',
        ]);

        $validated['created_by_admin_id'] = auth('admin')->id();

        ClassifiedContractTemplate::create($validated);

        return redirect()->route('admin.classifieds.contract-templates.index')
            ->with('success', 'Contract template created.');
    }

    public function edit(ClassifiedContractTemplate $contractTemplate): View
    {
        $categories = ClassifiedCategory::where('is_active', true)->get();

        return view('admin.classified-contract-templates.edit', [
            'template'   => $contractTemplate,
            'categories' => $categories,
        ]);
    }

    public function update(Request $request, ClassifiedContractTemplate $contractTemplate): RedirectResponse
    {
        $validated = $request->validate([
            'classified_category_id' => 'nullable|exists:classified_categories,id',
            'name'                   => 'required|string|max:200',
            'version'                => 'integer|min:1',
            'content_en'             => 'required|string',
            'content_ar'             => 'required|string',
            'is_active'              => 'boolean',
        ]);

        $contractTemplate->update($validated);

        return redirect()->route('admin.classifieds.contract-templates.index')
            ->with('success', 'Template updated.');
    }

    public function destroy(ClassifiedContractTemplate $contractTemplate): RedirectResponse
    {
        $contractTemplate->delete();

        return redirect()->route('admin.classifieds.contract-templates.index')
            ->with('success', 'Template deleted.');
    }
}
