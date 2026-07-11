<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BannerStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBannerRequest;
use App\Http\Requests\Admin\UpdateBannerRequest;
use App\Models\Banner;
use App\Models\BannerPlacementDefinition;
use App\Models\Country;
use App\Services\BannerService;
use App\Traits\HasDataTable;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    use HasDataTable;

    public function __construct(private readonly BannerService $bannerService)
    {
    }

    // ─── Index ────────────────────────────────────────────────────────────────

    public function index(): \Illuminate\View\View
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('banners.view'), 403);

        $now = now();

        $stats = [
            'active' => Banner::where('status', 'active')->count(),
            'scheduled' => Banner::where('status', 'scheduled')->count(),
            'expired' => Banner::where('status', 'expired')->count(),
            'impressions_today' => (int) Banner::whereDate('updated_at', today())->sum('impressions_count'),
        ];

        $countries = Country::orderBy('name_en')->get(['id', 'name_en', 'flag_emoji']);
        $placements = BannerPlacementDefinition::where('is_active', true)->orderBy('sort_order')->get(['code', 'name']);

        return view('admin.banners.index', compact('stats', 'countries', 'placements'));
    }

    // ─── DataTable ────────────────────────────────────────────────────────────

    public function datatable(Request $request): JsonResponse
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('banners.view'), 403);

        $query = Banner::query()->with(['country', 'files']);

        $query = $this->applyFilters($query, $request, [
            'status' => fn($q, $v) => $q->where('status', $v),
            'placement_code' => fn($q, $v) => $q->where('placement_code', $v),
            'country_id' => fn($q, $v) => $q->where('country_id', $v),
            'device_target' => fn($q, $v) => $q->where('device_target', $v),
            'date_from' => fn($q, $v) => $q->whereDate('starts_at', '>=', $v),
            'date_to' => fn($q, $v) => $q->whereDate('ends_at', '<=', $v),
        ]);

        $columns = [
            ['searchable_columns' => [], 'orderable_column' => null],  // image
            ['searchable_columns' => ['banners.name'], 'orderable_column' => 'name'],
            ['searchable_columns' => [], 'orderable_column' => 'placement_code'],
            ['searchable_columns' => [], 'orderable_column' => null],  // country
            ['searchable_columns' => [], 'orderable_column' => 'status'],
            ['searchable_columns' => [], 'orderable_column' => 'device_target'],
            ['searchable_columns' => [], 'orderable_column' => 'audience'],
            ['searchable_columns' => [], 'orderable_column' => 'starts_at'],  // date range
            ['searchable_columns' => [], 'orderable_column' => 'impressions_count'],
            ['searchable_columns' => [], 'orderable_column' => 'clicks_count'],
            ['searchable_columns' => [], 'orderable_column' => null],  // CTR
            ['searchable_columns' => [], 'orderable_column' => 'priority'],
            ['searchable_columns' => [], 'orderable_column' => null],  // actions
        ];

        $statusColors = [
            'active' => 'success',
            'inactive' => 'gray',
            'scheduled' => 'primary',
            'expired' => 'danger',
        ];
        $deviceColors = ['all' => 'gray', 'desktop' => 'primary', 'mobile' => 'warning', 'app' => 'info'];
        $audienceColors = ['all' => 'gray', 'guest' => 'gray', 'logged_in' => 'primary', 'vip' => 'warning', 'new_user' => 'success'];

        $now = now();
        $canEdit = auth('admin')->user()->hasPermissionTo('banners.edit');
        $canDelete = auth('admin')->user()->hasPermissionTo('banners.delete');

        return $this->dataTableResponse($request, $query, $columns, function (Banner $row) use ($statusColors, $deviceColors, $audienceColors, $now, $canEdit, $canDelete) {
            // Desktop image thumbnail
            $desktopImg = $row->files->firstWhere('file_type', 'banner_desktop');
            $thumbUrl = $desktopImg ? \Illuminate\Support\Facades\Storage::disk($desktopImg->storage_type ?: 'public')->url($desktopImg->path) : null;

            // Status badge with expiry countdown
            $statusColor = $statusColors[$row->status->value] ?? 'gray';
            $statusLabel = $row->status->label();
            if ($row->status === BannerStatus::Active && $row->ends_at) {
                $diff = $now->diffInDays(Carbon::parse($row->ends_at), false);
                if ($diff >= 0 && $diff <= 3) {
                    $statusLabel .= ' (expires in ' . $diff . 'd)';
                }
            }

            // CTR
            $ctr = $row->impressions_count > 0
                ? round($row->clicks_count / $row->impressions_count * 100, 2)
                : 0;

            // Country
            $countryDisplay = $row->country
                ? ($row->country->flag_emoji ? $row->country->flag_emoji . ' ' : '') . e($row->country->name_en)
                : '<span class="text-gray-400 text-xs">All</span>';

            return [
                'image' => $thumbUrl,
                'name' => e($row->name),
                'placement_code' => e($row->placement_code),
                'country' => $countryDisplay,
                'status' => '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-' . $statusColor . '-100 text-' . $statusColor . '-700">' . $statusLabel . '</span>',
                'device_target' => $this->badgeHtml($deviceColors[$row->device_target->value] ?? 'gray', $row->device_target->label()),
                'audience' => $this->badgeHtml($audienceColors[$row->audience->value] ?? 'gray', $row->audience->label()),
                'date_range' => Carbon::parse($row->starts_at)->format('d M') . ' – ' . Carbon::parse($row->ends_at)->format('d M Y'),
                'impressions' => number_format($row->impressions_count),
                'clicks' => number_format($row->clicks_count),
                'ctr' => $ctr . '%',
                'priority' => $row->priority,
                'actions' => $this->buildRowActions($row, $canEdit, $canDelete),
                'DT_RowData' => ['id' => $row->id, 'status' => $row->status],
            ];
        });
    }

    private function badgeHtml(string $color, string $label): string
    {
        return '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-' . $color . '-100 text-' . $color . '-700">' . $label . '</span>';
    }

    private function buildRowActions(Banner $banner, bool $canEdit, bool $canDelete): string
    {
        $editUrl = route('admin.banners.edit', $banner->id);
        $duplicateUrl = route('admin.banners.duplicate', $banner->id);
        $deleteUrl = route('admin.banners.destroy', $banner->id);

        $html = '<div class="flex items-center gap-1">';
        if ($canEdit) {
            $html .= '<a href="' . $editUrl . '" class="btn btn-xs btn-secondary">Edit</a>';
            $html .= '<button type="button" class="btn btn-xs btn-ghost js-duplicate-btn" data-url="' . $duplicateUrl . '" data-name="' . e($banner->name) . '">Duplicate</button>';
        }
        if ($canDelete) {
            $html .= '<button type="button" class="btn btn-xs btn-danger js-delete-btn" data-url="' . $deleteUrl . '" data-name="' . e($banner->name) . '">Delete</button>';
        }
        $html .= '</div>';
        return $html;
    }

    // ─── Placements (dropdown data) ───────────────────────────────────────────

    public function placements(): JsonResponse
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('banners.view'), 403);

        $placements = BannerPlacementDefinition::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return response()->json($placements);
    }

    // ─── Create ───────────────────────────────────────────────────────────────

    public function create(): \Illuminate\View\View
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('banners.create'), 403);

        $countries = Country::orderBy('name_en')->where('is_launched', true)->get(['id', 'name_en', 'flag_emoji']);
        $placements = BannerPlacementDefinition::where('is_active', true)->orderBy('sort_order')->get();

        return view('admin.banners.create', compact('countries', 'placements'));
    }

    // ─── Store ────────────────────────────────────────────────────────────────

    public function store(StoreBannerRequest $request): JsonResponse
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('banners.create'), 403);

        $data = $request->validated();

        // Auto-schedule: if status=active but starts_at is in the future
        if (($data['status'] ?? 'inactive') === 'active' && Carbon::parse($data['starts_at'])->isFuture()) {
            $data['status'] = 'scheduled';
        }

        $data['created_by_admin_id'] = $admin->id;
        $data['priority'] = $data['priority'] ?? 0;

        // Remove file keys from data array
        unset($data['desktop_image'], $data['mobile_image']);

        $banner = Banner::create($data);

        // Handle image uploads
        if ($request->hasFile('desktop_image')) {
            $this->bannerService->storeImage($request->file('desktop_image'), $banner, 'desktop');
        }
        if ($request->hasFile('mobile_image')) {
            $this->bannerService->storeImage($request->file('mobile_image'), $banner, 'mobile');
        }

        return response()->json([
            'message' => 'Banner created.',
            'redirect' => route('admin.banners.index'),
        ]);
    }

    // ─── Edit ─────────────────────────────────────────────────────────────────

    public function edit(Banner $banner): \Illuminate\View\View
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('banners.edit'), 403);

        $banner->load('files');
        $desktopImage = $this->bannerService->getDesktopImage($banner);
        $mobileImage = $this->bannerService->getMobileImage($banner);

        $countries = Country::orderBy('name_en')->where('is_launched', true)->get(['id', 'name_en', 'flag_emoji']);
        $placements = BannerPlacementDefinition::where('is_active', true)->orderBy('sort_order')->get();

        return view('admin.banners.edit', compact('banner', 'countries', 'placements', 'desktopImage', 'mobileImage'));
    }

    // ─── Update ───────────────────────────────────────────────────────────────

    public function update(UpdateBannerRequest $request, Banner $banner): JsonResponse
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('banners.edit'), 403);

        $data = $request->validated();

        // Auto-schedule if status=active but starts_at is future
        if (($data['status'] ?? $banner->status->value) === 'active' && Carbon::parse($data['starts_at'])->isFuture()) {
            $data['status'] = 'scheduled';
        }

        $data['updated_by_admin_id'] = $admin->id;

        // Remove file keys from data array
        unset($data['desktop_image'], $data['mobile_image']);

        $banner->update($data);

        // Handle image uploads
        if ($request->hasFile('desktop_image')) {
            $this->bannerService->storeImage($request->file('desktop_image'), $banner, 'desktop');
        }
        if ($request->hasFile('mobile_image')) {
            $this->bannerService->storeImage($request->file('mobile_image'), $banner, 'mobile');
        }

        return response()->json([
            'message' => 'Banner updated.',
            'redirect' => route('admin.banners.index'),
        ]);
    }

    // ─── Destroy ──────────────────────────────────────────────────────────────

    public function destroy(Banner $banner): JsonResponse
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('banners.delete'), 403);

        // Delete associated files (physical + DB records)
        $this->bannerService->deleteAllImages($banner);

        $banner->delete();

        return response()->json(['message' => 'Banner deleted.']);
    }

    // ─── Duplicate ────────────────────────────────────────────────────────────

    public function duplicate(Banner $banner): JsonResponse
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('banners.create'), 403);

        $copy = $this->bannerService->duplicate($banner, $admin->id);

        return response()->json([
            'message' => 'Banner duplicated.',
            'redirect' => route('admin.banners.edit', $copy->id),
        ]);
    }

    // ─── Upload Image ─────────────────────────────────────────────────────────

    public function uploadImage(Request $request, Banner $banner): JsonResponse
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('banners.edit'), 403);

        $request->validate([
            'image' => ['required', 'image', 'max:5120'],
            'slot' => ['required', 'in:desktop,mobile'],
        ]);

        $file = $this->bannerService->storeImage(
            $request->file('image'),
            $banner,
            $request->input('slot')
        );

        return response()->json([
            'message' => 'Image uploaded.',
            'file_id' => $file->id,
            'url' => $file->full_path,
        ]);
    }

    // ─── Delete Image ─────────────────────────────────────────────────────────

    public function deleteImage(Request $request): JsonResponse
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('banners.edit'), 403);

        $request->validate(['file_id' => ['required', 'integer']]);

        $deleted = $this->bannerService->deleteFileRecord((int) $request->input('file_id'));

        if (!$deleted) {
            return response()->json(['message' => 'File not found.'], 404);
        }

        return response()->json(['message' => 'Image deleted.']);
    }

    // ─── Bulk Actions ─────────────────────────────────────────────────────────

    public function bulk(Request $request): JsonResponse
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('banners.edit'), 403);

        $request->validate([
            'action' => ['required', 'in:activate,deactivate,delete,duplicate'],
            'ids' => ['required', 'array'],
            'ids.*' => ['uuid'],
        ]);

        $ids = $request->input('ids');
        $action = $request->input('action');
        $count = 0;

        $banners = Banner::whereIn('id', $ids)->get();

        foreach ($banners as $banner) {
            match ($action) {
                'activate' => $banner->update(['status' => 'active']),
                'deactivate' => $banner->update(['status' => 'inactive']),
                'delete' => $this->doDelete($banner, $admin),
                'duplicate' => $this->bannerService->duplicate($banner, $admin->id),
            };
            $count++;
        }

        return response()->json(['message' => "{$count} banner(s) {$action}d."]);
    }

    private function doDelete(Banner $banner, $admin): void
    {
        if (!$admin->hasPermissionTo('banners.delete')) {
            return;
        }
        $this->bannerService->deleteAllImages($banner);
        $banner->delete();
    }
}
