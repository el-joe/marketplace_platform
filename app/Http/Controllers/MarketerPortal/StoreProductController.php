<?php

namespace App\Http\Controllers\MarketerPortal;

use App\Http\Controllers\Controller;
use App\Models\MarketerProduct;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class StoreProductController extends Controller
{
    public function index(Request $request): View
    {
        /** @var \App\Models\Marketer $marketer */
        $marketer = Auth::guard('marketer')->user();

        $statusFilter = $request->input('status');

        $products = $marketer->products()
            ->when($statusFilter, fn($q) => $q->where('status', $statusFilter))
            ->orderByDesc('created_at')
            ->paginate(12)
            ->withQueryString();

        $statusCounts = $marketer->products()
            ->selectRaw('status, COUNT(*) as cnt')
            ->groupBy('status')
            ->pluck('cnt', 'status');

        $tabs = [];
        foreach (['draft', 'pending_review', 'active', 'paused', 'rejected'] as $status) {
            $tabs[$status] = (int) ($statusCounts[$status] ?? 0);
        }

        return view('marketer.store-products.index', [
            'marketer' => $marketer,
            'products' => $products,
            'statusFilter' => $statusFilter,
            'tabs' => $tabs,
        ]);
    }

    public function create(): View
    {
        $minRate = (int) Setting::get('min_marketer_product_commission_rate', 500);

        return view('marketer.store-products.create', [
            'minRate' => $minRate,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var \App\Models\Marketer $marketer */
        $marketer = Auth::guard('marketer')->user();

        $minRate = (int) Setting::get('min_marketer_product_commission_rate', 500);

        $validated = $request->validate([
            'name_en' => 'required|string|max:255',
            'name_ar' => 'required|string|max:255',
            'description_en' => 'nullable|string',
            'description_ar' => 'nullable|string',
            'price' => 'required|integer|min:1',
            'currency' => 'required|string|size:3|exists:currencies,code',
            'platform_commission_rate' => ['required', 'integer', "min:{$minRate}"],
            'stock_quantity' => 'required|integer|min:0',
            'sku' => 'nullable|string|max:100',
            'weight_grams' => 'nullable|integer|min:0',
            'is_digital' => 'nullable|boolean',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        if ($validated['platform_commission_rate'] < $minRate) {
            return back()->withInput()->withErrors([
                'platform_commission_rate' => "Minimum commission rate is {$minRate} basis points (" . ($minRate / 100) . '%).',
            ]);
        }

        $product = $marketer->products()->create([
            'name_en' => $validated['name_en'],
            'name_ar' => $validated['name_ar'],
            'description_en' => $validated['description_en'] ?? null,
            'description_ar' => $validated['description_ar'] ?? null,
            'price' => $validated['price'],
            'currency' => strtoupper($validated['currency']),
            'platform_commission_rate' => $validated['platform_commission_rate'],
            'stock_quantity' => $validated['stock_quantity'],
            'sku' => $validated['sku'] ?? null,
            'weight_grams' => $validated['weight_grams'] ?? null,
            'is_digital' => (bool) ($validated['is_digital'] ?? false),
            'status' => 'draft',
        ]);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store("marketer-products/{$product->id}", 'public');
            $product->update(['image_path' => $path]);
        }

        return redirect()->route('marketer.store-products.index')->with('success', 'Product created as draft.');
    }

    public function edit(MarketerProduct $product): View
    {
        $this->authorizeOwnership($product);

        $minRate = (int) Setting::get('min_marketer_product_commission_rate', 500);

        return view('marketer.store-products.edit', [
            'product' => $product,
            'minRate' => $minRate,
        ]);
    }

    public function update(Request $request, MarketerProduct $product): RedirectResponse
    {
        $this->authorizeOwnership($product);

        abort_unless(in_array($product->status, ['draft', 'rejected', 'paused'], true), 422, 'This product cannot be edited in its current status.');

        $minRate = (int) Setting::get('min_marketer_product_commission_rate', 500);

        $validated = $request->validate([
            'name_en' => 'required|string|max:255',
            'name_ar' => 'required|string|max:255',
            'description_en' => 'nullable|string',
            'description_ar' => 'nullable|string',
            'price' => 'required|integer|min:1',
            'currency' => 'required|string|size:3|exists:currencies,code',
            'platform_commission_rate' => ['required', 'integer', "min:{$minRate}"],
            'stock_quantity' => 'required|integer|min:0',
            'sku' => 'nullable|string|max:100',
            'weight_grams' => 'nullable|integer|min:0',
            'is_digital' => 'nullable|boolean',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $product->update([
            'name_en' => $validated['name_en'],
            'name_ar' => $validated['name_ar'],
            'description_en' => $validated['description_en'] ?? null,
            'description_ar' => $validated['description_ar'] ?? null,
            'price' => $validated['price'],
            'currency' => strtoupper($validated['currency']),
            'platform_commission_rate' => $validated['platform_commission_rate'],
            'stock_quantity' => $validated['stock_quantity'],
            'sku' => $validated['sku'] ?? null,
            'weight_grams' => $validated['weight_grams'] ?? null,
            'is_digital' => (bool) ($validated['is_digital'] ?? false),
        ]);

        if ($request->hasFile('image')) {
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }
            $path = $request->file('image')->store("marketer-products/{$product->id}", 'public');
            $product->update(['image_path' => $path]);
        }

        return redirect()->route('marketer.store-products.index')->with('success', 'Product updated.');
    }

    public function submitForReview(MarketerProduct $product): RedirectResponse
    {
        $this->authorizeOwnership($product);

        abort_unless(in_array($product->status, ['draft', 'rejected'], true), 422, 'This product cannot be submitted for review in its current status.');

        $product->update([
            'status' => 'pending_review',
            'rejection_reason' => null,
        ]);

        return redirect()->route('marketer.store-products.index')->with('success', 'Product submitted for admin review.');
    }

    public function destroy(MarketerProduct $product): RedirectResponse
    {
        $this->authorizeOwnership($product);

        abort_unless(in_array($product->status, ['draft', 'rejected', 'paused'], true), 422, 'This product cannot be deleted in its current status.');

        $product->delete();

        return redirect()->route('marketer.store-products.index')->with('success', 'Product deleted.');
    }

    private function authorizeOwnership(MarketerProduct $product): void
    {
        /** @var \App\Models\Marketer $marketer */
        $marketer = Auth::guard('marketer')->user();

        abort_unless($product->marketer_id === $marketer->id, 403);
    }
}
