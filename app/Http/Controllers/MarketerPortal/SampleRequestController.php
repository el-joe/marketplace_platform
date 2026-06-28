<?php

namespace App\Http\Controllers\MarketerPortal;

use App\Http\Controllers\Controller;
use App\Models\MarketerSampleRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SampleRequestController extends Controller
{
    public function index(): View
    {
        /** @var \App\Models\Marketer $marketer */
        $marketer = Auth::guard('marketer')->user();

        $sampleRequests = MarketerSampleRequest::with(['campaign', 'items'])
            ->where('marketer_id', $marketer->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('marketer.samples.index', [
            'marketer' => $marketer,
            'sampleRequests' => $sampleRequests,
        ]);
    }

    public function markReceived(MarketerSampleRequest $sampleRequest): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        /** @var \App\Models\Marketer $marketer */
        $marketer = Auth::guard('marketer')->user();

        abort_if($sampleRequest->marketer_id !== $marketer->id, 403);

        if ($sampleRequest->status !== 'dispatched') {
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
