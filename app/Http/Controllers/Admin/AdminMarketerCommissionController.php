<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Marketer;
use App\Models\MarketerCategoryCommission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminMarketerCommissionController extends Controller
{
    public function index(Marketer $marketer): JsonResponse
    {
        $commissions = $marketer->categoryCommissions()
            ->with('category:id,name_en,name_ar')
            ->latest()
            ->get();

        return response()->json(['data' => $commissions]);
    }

    public function store(Request $request, Marketer $marketer): JsonResponse
    {
        $request->validate([
            'category_id' => 'required|uuid|exists:categories,id',
            'commission_amount' => 'required|integer|min:0',
            'currency_code' => ['required', Rule::in(['SAR', 'AED', 'EGP', 'KWD', 'OMR', 'QAR', 'BHD', 'JOD'])],
        ]);

        $commission = MarketerCategoryCommission::updateOrCreate(
            ['marketer_id' => $marketer->id, 'category_id' => $request->category_id],
            [
                'commission_amount' => $request->commission_amount,
                'currency_code' => $request->currency_code,
                'is_active' => true,
                'set_by_admin_id' => auth('admin')->id(),
            ]
        );

        return response()->json(['success' => true, 'message' => 'Commission saved successfully.', 'data' => $commission]);
    }

    public function update(Request $request, Marketer $marketer, MarketerCategoryCommission $commission): JsonResponse
    {
        abort_unless($commission->marketer_id === $marketer->id, 404);

        $request->validate([
            'commission_amount' => 'required|integer|min:0',
            'currency_code' => ['required', Rule::in(['SAR', 'AED', 'EGP', 'KWD', 'OMR', 'QAR', 'BHD', 'JOD'])],
            'is_active' => 'nullable|boolean',
        ]);

        $commission->update([
            'commission_amount' => $request->commission_amount,
            'currency_code' => $request->currency_code,
            'is_active' => $request->boolean('is_active', $commission->is_active),
            'set_by_admin_id' => auth('admin')->id(),
        ]);

        return response()->json(['success' => true, 'message' => 'Commission updated successfully.', 'data' => $commission]);
    }

    public function destroy(Marketer $marketer, MarketerCategoryCommission $commission): JsonResponse
    {
        abort_unless($commission->marketer_id === $marketer->id, 404);

        $commission->delete();

        return response()->json(['success' => true, 'message' => 'Commission removed successfully.']);
    }
}
