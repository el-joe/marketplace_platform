<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Services\Customer\UnifiedCategoryService;
use Illuminate\Http\JsonResponse;

class NavigationController extends Controller
{
    private const SECTION_LABELS = [
        'products' => [
            'label_en' => 'Shop',
            'label_ar' => 'تسوق',
        ],
        'classifieds' => [
            'label_en' => 'Classifieds',
            'label_ar' => 'الإعلانات المبوّبة',
        ],
        'travel' => [
            'label_en' => 'Travel',
            'label_ar' => 'السفر',
            'link'     => '/travel',
        ],
    ];

    public function __construct(
        private readonly UnifiedCategoryService $unifiedCategoryService,
    ) {}

    /**
     * GET /api/customer/v1/{country}/nav
     * Unified category nav tree: products → classifieds → travel.
     */
    public function index(Country $country): JsonResponse
    {
        $tree = $this->unifiedCategoryService->getMergedTree($country);

        $nav = array_map(function (array $section) {
            $labels = self::SECTION_LABELS[$section['section']] ?? [];

            return array_merge(
                ['section' => $section['section']],
                $labels,
                ['nodes' => $section['nodes']],
            );
        }, $tree);

        return response()->json([
            'success' => true,
            'data'    => [
                'nav' => $nav,
            ],
        ]);
    }
}
