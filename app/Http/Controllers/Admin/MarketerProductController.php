<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MarketerProduct;
use App\Notifications\Marketer\ProductApproved;
use App\Notifications\Marketer\ProductRejected;
use App\Traits\HasDataTable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MarketerProductController extends Controller
{
    use HasDataTable;

    public function index(): View
    {
        $stats = [
            'total' => MarketerProduct::count(),
            'pending_review' => MarketerProduct::where('status', 'pending_review')->count(),
            'active' => MarketerProduct::where('status', 'active')->count(),
            'rejected' => MarketerProduct::where('status', 'rejected')->count(),
        ];

        return view('admin.marketer-products.index', [
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
                ['label' => 'Marketer Products'],
            ],
            'stats' => $stats,
        ]);
    }

    public function datatable(Request $request): JsonResponse
    {
        $columns = [
            ['searchable_columns' => ['marketer_products.name_en', 'marketer_products.name_ar']],
            ['searchable_columns' => ['marketers.name']],
            ['orderable_column' => 'marketer_products.price'],
            ['orderable_column' => 'marketer_products.platform_commission_rate'],
            ['orderable_column' => 'marketer_products.status'],
            ['orderable_column' => 'marketer_products.created_at'],
            [],
        ];

        $query = MarketerProduct::query()
            ->join('marketers', 'marketers.id', '=', 'marketer_products.marketer_id')
            ->select(['marketer_products.*', 'marketers.name as marketer_name']);

        if ($request->filled('status')) {
            $query->where('marketer_products.status', $request->input('status'));
        }

        return $this->dataTableResponse($request, $query, $columns, function ($row) {
            return [
                'id' => $row->id,
                'name' => $row->name_en,
                'marketer_name' => $row->marketer_name,
                'price' => $row->price,
                'currency' => $row->currency,
                'platform_commission_rate' => $row->platform_commission_rate,
                'status' => $row->status,
                'created_at' => $row->created_at->format('Y-m-d H:i'),
            ];
        });
    }

    public function show(MarketerProduct $product): View
    {
        $product->load('marketer');

        return view('admin.marketer-products.show', [
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
                ['label' => 'Marketer Products', 'url' => route('admin.marketer-products.index')],
                ['label' => $product->name_en],
            ],
            'product' => $product,
        ]);
    }

    public function approve(MarketerProduct $product): JsonResponse
    {
        if ($product->status !== 'pending_review') {
            return response()->json(['success' => false, 'message' => 'Product is not pending review.'], 422);
        }

        $product->update([
            'status' => 'active',
            'rejection_reason' => null,
        ]);

        $product->marketer->notify(new ProductApproved($product));

        return response()->json(['success' => true, 'message' => 'Product approved and activated.']);
    }

    public function reject(Request $request, MarketerProduct $product): JsonResponse
    {
        $request->validate(['reason' => 'required|string|max:500']);

        $product->update([
            'status' => 'rejected',
            'rejection_reason' => $request->reason,
        ]);

        $product->marketer->notify(new ProductRejected($product, $request->reason));

        return response()->json(['success' => true, 'message' => 'Product rejected.']);
    }
}
