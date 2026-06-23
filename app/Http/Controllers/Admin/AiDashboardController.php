<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiFeatureCredit;
use App\Models\AiImageEnhancementJob;
use App\Models\AiVideoGenerationJob;
use App\Models\VirtualTryonSession;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AiDashboardController extends Controller
{
    public function index(): View
    {
        $imageStats = [
            'queued'     => AiImageEnhancementJob::where('status', 'queued')->count(),
            'processing' => AiImageEnhancementJob::where('status', 'processing')->count(),
            'completed'  => AiImageEnhancementJob::where('status', 'completed')->count(),
            'failed'     => AiImageEnhancementJob::where('status', 'failed')->count(),
        ];

        $tryonStats = [
            'queued'     => VirtualTryonSession::where('status', 'queued')->count(),
            'processing' => VirtualTryonSession::where('status', 'processing')->count(),
            'completed'  => VirtualTryonSession::where('status', 'completed')->count(),
            'failed'     => VirtualTryonSession::where('status', 'failed')->count(),
        ];

        $videoStats = [
            'queued'     => AiVideoGenerationJob::where('status', 'queued')->count(),
            'processing' => AiVideoGenerationJob::where('status', 'processing')->count(),
            'completed'  => AiVideoGenerationJob::where('status', 'completed')->count(),
            'failed'     => AiVideoGenerationJob::where('status', 'failed')->count(),
        ];

        $recentFailures = collect()
            ->merge(AiImageEnhancementJob::where('status', 'failed')->latest()->limit(5)->get()->map(fn($j) => ['type' => 'image_enhancement', 'id' => $j->id, 'error' => $j->error_message, 'at' => $j->updated_at]))
            ->merge(VirtualTryonSession::where('status', 'failed')->latest()->limit(5)->get()->map(fn($j) => ['type' => 'virtual_tryon', 'id' => $j->id, 'error' => $j->error_message, 'at' => $j->updated_at]))
            ->merge(AiVideoGenerationJob::where('status', 'failed')->latest()->limit(5)->get()->map(fn($j) => ['type' => 'video_generation', 'id' => $j->id, 'error' => $j->error_message, 'at' => $j->updated_at]))
            ->sortByDesc('at')
            ->take(10)
            ->values();

        $creditSummary = AiFeatureCredit::query()
            ->selectRaw('feature, SUM(credits_remaining) as total_remaining, SUM(credits_used_total) as total_used')
            ->groupBy('feature')
            ->get()
            ->keyBy('feature');

        return view('admin.ai-dashboard.index', compact(
            'imageStats', 'tryonStats', 'videoStats', 'recentFailures', 'creditSummary'
        ));
    }

    public function allocateCredits(Request $request): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'owner_type' => ['required', 'in:vendor,marketer'],
            'owner_id'   => ['required', 'uuid'],
            'feature'    => ['required', 'in:image_enhancement,virtual_tryon,video_generation'],
            'amount'     => ['required', 'integer', 'min:1', 'max:10000'],
            'reset_at'   => ['nullable', 'date'],
        ]);

        $credit = AiFeatureCredit::balanceFor(
            $validated['owner_type'],
            $validated['owner_id'],
            $validated['feature']
        );

        $credit->topUp($validated['amount']);

        if (! empty($validated['reset_at'])) {
            $credit->update(['reset_at' => $validated['reset_at']]);
        }

        return back()->with('success', "Added {$validated['amount']} {$validated['feature']} credits.");
    }
}
