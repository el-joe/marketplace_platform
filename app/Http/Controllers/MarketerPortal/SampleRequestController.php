<?php

namespace App\Http\Controllers\MarketerPortal;

use App\Enums\MarketerSampleRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\MarketerSampleRequest;
use App\Traits\HasDataTable;
use App\Traits\HasExport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SampleRequestController extends Controller
{
    use HasDataTable;
    use HasExport;

    public function index(Request $request): View|\Symfony\Component\HttpFoundation\StreamedResponse
    {
        /** @var \App\Models\Marketer $marketer */
        $marketer = Auth::guard('marketer')->user();

        if ($request->filled('export')) {
            return $this->exportSampleRequests($request, $marketer);
        }

        $sampleRequests = $this->filteredSampleRequestsQuery($marketer, $request)
            ->with(['campaign', 'items' => fn($q) => $q->where('is_mandatory', false)])
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('marketer.samples.index', [
            'marketer' => $marketer,
            'sampleRequests' => $sampleRequests,
        ]);
    }

    private function filteredSampleRequestsQuery($marketer, Request $request)
    {
        return $this->applyFilters(
            MarketerSampleRequest::where('marketer_id', $marketer->id),
            $request,
            [
                'status' => fn($q, $v) => $q->where('status', $v),
                'date_from' => fn($q, $v) => $q->whereDate('created_at', '>=', $v),
                'date_to' => fn($q, $v) => $q->whereDate('created_at', '<=', $v),
            ]
        );
    }

    private function exportSampleRequests(Request $request, $marketer): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $sampleRequests = $this->filteredSampleRequestsQuery($marketer, $request)
            ->with(['items.vendorListing.productVariant.product'])
            ->orderByDesc('created_at')
            ->get();

        $headers = ['Product', 'Quantity', 'Status', 'Date'];

        $rows = $sampleRequests->flatMap(fn($sr) => $sr->items->isEmpty()
            ? [[
                '—',
                0,
                $sr->status?->value,
                $sr->created_at->format('Y-m-d'),
            ]]
            : $sr->items->map(fn($item) => [
                $item->vendorListing?->productVariant?->product?->name_en ?? '—',
                (int) $item->quantity,
                $sr->status?->value,
                $sr->created_at->format('Y-m-d'),
            ]));

        return match ($request->input('export')) {
            'excel' => $this->exportExcel('sample-requests', $headers, $rows),
            'csv' => $this->exportCsv('sample-requests', $headers, $rows),
            'word' => $this->exportWord('sample-requests', 'Sample Requests', $rows),
            default => abort(400, 'Invalid export format.'),
        };
    }

    public function markReceived(MarketerSampleRequest $sampleRequest): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        /** @var \App\Models\Marketer $marketer */
        $marketer = Auth::guard('marketer')->user();

        abort_if($sampleRequest->marketer_id !== $marketer->id, 403);

        if ($sampleRequest->status !== MarketerSampleRequestStatus::Dispatched) {
            if (request()->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Sample must be dispatched before marking received.'], 422);
            }
            return back()->with('error', 'Sample must be dispatched before marking received.');
        }

        $sampleRequest->update([
            'status' => 'received',
            'received_at' => now(),
        ]);

        if (request()->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Sample marked as received.');
    }
}
