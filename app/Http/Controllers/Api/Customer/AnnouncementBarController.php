<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\AnnouncementBar;
use App\Models\Country;
use App\Support\Bilingual;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AnnouncementBarController extends Controller
{
    /**
     * The active bar for a country, cached separately from the app_config payload so
     * this endpoint can be polled on its own. Busted alongside app_config_{countryId}
     * in Admin\AnnouncementBarController::bustCache().
     */
    public function index(Request $request): JsonResponse
    {
        /** @var Country $country */
        $country = $request->attributes->get('country');

        $bar = Cache::remember("announcement_bar_active_{$country->id}", 300, function () use ($country) {
            return AnnouncementBar::getActive($country->id);
        });

        if (!$bar) {
            return ApiResponse::success(['data' => []]);
        }

        return ApiResponse::success(['data' => [$this->shape($bar)]]);
    }

    public function dismiss(Request $request, string $id): JsonResponse
    {
        $bar = AnnouncementBar::where('id', $id)
            ->where('is_dismissible', true)
            ->first();

        if (!$bar) {
            throw new NotFoundHttpException();
        }

        $customerId = auth('customer')->id();

        $updated = DB::table('customer_dismissed_announcement_bars')
            ->where('customer_id', $customerId)
            ->where('announcement_bar_id', $bar->id)
            ->update(['dismissed_at' => now()]);

        if (!$updated) {
            DB::table('customer_dismissed_announcement_bars')->insert([
                'id' => (string) Str::uuid(),
                'customer_id' => $customerId,
                'announcement_bar_id' => $bar->id,
                'dismissed_at' => now(),
            ]);
        }

        return ApiResponse::success(['success' => true]);
    }

    private function shape(AnnouncementBar $bar): array
    {
        return [
            'id' => $bar->id,
            'message' => Bilingual::pairFromKeys($bar, 'message_ar', 'message_en'),
            'cta_label' => Bilingual::pairFromKeys($bar, 'cta_label_ar', 'cta_label_en'),
            'cta_url' => $bar->cta_url,
            'bg_color_hex' => $bar->bg_color_hex,
            'text_color_hex' => $bar->text_color_hex,
            'is_dismissible' => $bar->is_dismissible,
        ];
    }
}
