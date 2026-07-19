<?php

namespace App\Http\Controllers\MarketerPortal;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\InfluencerDeal;
use App\Models\InfluencerDealDeliverable;
use App\Notifications\Admin\DealContentSubmitted;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

class InfluencerDealController extends Controller
{
    public function index(): View
    {
        /** @var \App\Models\Marketer $marketer */
        $marketer = Auth::guard('marketer')->user();

        $deals = InfluencerDeal::with(['vendor', 'deliverables'])
            ->forMarketer($marketer->id)
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('status');

        return view('marketer.deals.index', [
            'marketer' => $marketer,
            'deals' => $deals,
        ]);
    }

    public function show(string $id): View
    {
        /** @var \App\Models\Marketer $marketer */
        $marketer = Auth::guard('marketer')->user();

        $deal = InfluencerDeal::with(['vendor', 'campaign', 'deliverables'])
            ->forMarketer($marketer->id)
            ->findOrFail($id);

        return view('marketer.deals.show', [
            'marketer' => $marketer,
            'deal' => $deal,
        ]);
    }

    public function accept(string $id): RedirectResponse
    {
        /** @var \App\Models\Marketer $marketer */
        $marketer = Auth::guard('marketer')->user();

        $deal = InfluencerDeal::forMarketer($marketer->id)->findOrFail($id);

        abort_if($deal->status !== 'proposed', 422, 'Deal is not awaiting a response.');
        abort_unless(in_array($deal->proposed_by, ['admin', 'vendor'], true), 422);

        $deal->update([
            'status' => 'accepted',
            'contract_signed_at' => now(),
        ]);

        return back()->with('success', 'Deal accepted.');
    }

    public function reject(string $id, Request $request): RedirectResponse
    {
        $request->validate(['reason' => 'required|string|max:500']);

        /** @var \App\Models\Marketer $marketer */
        $marketer = Auth::guard('marketer')->user();

        $deal = InfluencerDeal::forMarketer($marketer->id)->findOrFail($id);

        abort_if($deal->status !== 'proposed', 422, 'Deal is not awaiting a response.');

        $deal->update([
            'status' => 'rejected',
            'cancellation_reason' => $request->input('reason'),
        ]);

        return back()->with('success', 'Deal rejected.');
    }

    public function submitDeliverable(string $dealId, string $deliverableId, Request $request): RedirectResponse
    {
        $request->validate([
            'content_url' => 'required|url|max:2048',
            'content_notes' => 'nullable|string|max:2000',
        ]);

        /** @var \App\Models\Marketer $marketer */
        $marketer = Auth::guard('marketer')->user();

        $deal = InfluencerDeal::forMarketer($marketer->id)->with('deliverables')->findOrFail($dealId);

        abort_unless(in_array($deal->status, ['accepted', 'in_progress'], true), 422, 'Deal is not in progress.');

        $deliverable = $deal->deliverables->firstWhere('id', $deliverableId);
        abort_if(! $deliverable, 404);

        DB::transaction(function () use ($deal, $deliverable, $request) {
            $deliverable->update([
                'content_url' => $request->input('content_url'),
                'content_notes' => $request->input('content_notes'),
                'submitted_at' => now(),
                'status' => 'submitted',
            ]);

            $deal->refresh();
            $allSubmitted = $deal->deliverables->isNotEmpty()
                && $deal->deliverables->every(fn (InfluencerDealDeliverable $d) => in_array($d->status, ['submitted', 'approved'], true));

            if ($allSubmitted) {
                $deal->update(['status' => 'content_submitted']);

                Notification::send(
                    Admin::permission('marketers.view')->get(),
                    new DealContentSubmitted($deal),
                );
            } elseif ($deal->status === 'accepted') {
                $deal->update(['status' => 'in_progress']);
            }
        });

        return back()->with('success', 'Deliverable submitted.');
    }
}
