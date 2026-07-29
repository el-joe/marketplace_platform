<?php

namespace App\Http\Controllers\MarketerPortal;

use App\Enums\PromotionCategory;
use App\Http\Controllers\Controller;
use App\Models\MarketerMonthlyQuotaProgress;
use App\Services\MarketerMonthlyQuotaService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class QuotaController extends Controller
{
    public function __construct(
        private readonly MarketerMonthlyQuotaService $quotaService,
    ) {}

    public function index(): View
    {
        /** @var \App\Models\Marketer $marketer */
        $marketer = Auth::guard('marketer')->user();

        $today = now();

        $progressByCategory = MarketerMonthlyQuotaProgress::query()
            ->where('marketer_id', $marketer->id)
            ->forPeriod($today->year, $today->month)
            ->get()
            ->keyBy('promotion_category');

        $isLastWeekOfMonth = $today->day > $today->daysInMonth - 7;

        $categories = collect(PromotionCategory::cases())->map(function (PromotionCategory $category) use ($marketer, $progressByCategory, $isLastWeekOfMonth) {
            $progress = $progressByCategory->get($category->value);
            $completed = $progress->completed_count ?? 0;
            $target = $progress->quota_target ?? $this->quotaService->getEffectiveQuota($marketer, $category->value);
            $percent = $target > 0 ? min(100, (int) round($completed / $target * 100)) : 100;

            if ($percent >= 100) {
                $color = 'green';
            } elseif ($isLastWeekOfMonth && $percent < 50) {
                $color = 'red';
            } else {
                $color = 'yellow';
            }

            return [
                'category' => $category,
                'label' => $category->label(),
                'completed' => $completed,
                'target' => $target,
                'percent' => $percent,
                'color' => $color,
            ];
        });

        return view('marketer.quota.index', [
            'marketer' => $marketer,
            'categories' => $categories,
        ]);
    }
}
