<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBatchRequest;
use App\Models\GiftCard;
use App\Models\GiftCardBatch;
use App\Traits\HasDataTable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AdminGiftCardController extends Controller
{
    use HasDataTable;

    // ─────────────────────────────────────────────────────────────────────────
    // Card index (all cards, across batches)
    // ─────────────────────────────────────────────────────────────────────────

    public function cardIndex(): View
    {
        return view('admin.gift-cards.index', [
            'breadcrumbs' => [
                ['label' => __('admin.nav.dashboard'), 'url' => route('admin.dashboard')],
                ['label' => __('admin.gift_cards_section.title')],
            ],
        ]);
    }

    public function datatable(Request $request): JsonResponse
    {
        $columns = $this->cardColumnDefinitions();

        $query = GiftCard::query()->select([
            'gift_cards.id',
            'gift_cards.code',
            'gift_cards.amount',
            'gift_cards.currency_code',
            'gift_cards.status',
            'gift_cards.gift_card_batch_id',
            'gift_cards.expires_at',
            'gift_cards.created_at',
        ]);

        $query = $this->applyFilters($query, $request, [
            'status' => fn($q, $v) => $q->where('gift_cards.status', $v),
            'currency_code' => fn($q, $v) => $q->where('gift_cards.currency_code', $v),
            'search' => fn($q, $v) => $q->where('gift_cards.code', 'like', "%{$v}%"),
        ]);

        return $this->dataTableResponse($request, $query, $columns, function ($row) {
            return [
                'id' => $row->id,
                'code' => e($row->code),
                'amount' => (int) $row->amount,
                'currency_code' => $row->currency_code,
                'status' => $row->status,
                'expires_at' => $row->expires_at,
                'created_at' => $row->created_at,
                'batch_url' => $row->gift_card_batch_id
                    ? route('admin.gift-cards.batches.show', $row->gift_card_batch_id)
                    : null,
            ];
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Batch index
    // ─────────────────────────────────────────────────────────────────────────

    public function batchIndex(): View
    {
        $batches = GiftCardBatch::query()
            ->withCount([
                'giftCards as active_count' => fn($q) => $q->where('status', 'active'),
                'giftCards as redeemed_count' => fn($q) => $q->where('status', 'redeemed'),
            ])
            ->with('createdByAdmin:id,name')
            ->latest('created_at')
            ->paginate(25);

        $stats = [
            'total_batches' => GiftCardBatch::count(),
            'total_issued' => GiftCard::count(),
            'total_redeemed' => GiftCard::where('status', 'redeemed')->count(),
            'total_active' => GiftCard::where('status', 'active')->count(),
        ];

        return view('admin.gift-cards.batches.index', [
            'batches' => $batches,
            'stats' => $stats,
            'breadcrumbs' => [
                ['label' => __('admin.nav.dashboard'), 'url' => route('admin.dashboard')],
                ['label' => __('admin.gift_cards_section.batches_title')],
            ],
        ]);
    }

    public function batchDatatable(GiftCardBatch $batch, Request $request): JsonResponse
    {
        $columns = $this->batchCardColumnDefinitions();

        $query = GiftCard::query()
            ->select([
                'gift_cards.id',
                'gift_cards.code',
                'gift_cards.amount',
                'gift_cards.currency_code',
                'gift_cards.status',
                'gift_cards.redeemed_by_customer_id',
                'gift_cards.redeemed_at',
                'gift_cards.expires_at',
                'gift_cards.created_at',
            ])
            ->with('redeemedByCustomer:id,name')
            ->where('gift_card_batch_id', $batch->id);

        $query = $this->applyFilters($query, $request, [
            'status' => fn($q, $v) => $q->where('gift_cards.status', $v),
            'search' => fn($q, $v) => $q->where('gift_cards.code', 'like', "%{$v}%"),
        ]);

        return $this->dataTableResponse($request, $query, $columns, function ($row) {
            return [
                'id' => $row->id,
                'code' => e($row->code),
                'amount' => (int) $row->amount,
                'currency_code' => $row->currency_code,
                'status' => $row->status,
                'redeemed_by' => $row->redeemedByCustomer?->name,
                'redeemed_at' => $row->redeemed_at,
                'expires_at' => $row->expires_at,
            ];
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Batch create / store
    // ─────────────────────────────────────────────────────────────────────────

    public function batchCreate(): View
    {
        return view('admin.gift-cards.batches.create', [
            'breadcrumbs' => [
                ['label' => __('admin.nav.dashboard'), 'url' => route('admin.dashboard')],
                ['label' => __('admin.gift_cards_section.batches_title'), 'url' => route('admin.gift-cards.batches.index')],
                ['label' => __('admin.gift_cards_section.new_batch')],
            ],
        ]);
    }

    public function batchStore(StoreBatchRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        /** @var \App\Models\Admin $admin */
        $admin = Auth::guard('admin')->user();

        $batch = DB::transaction(function () use ($validated, $admin) {
            $batch = GiftCardBatch::create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'currency_code' => $validated['currency_code'],
                'amount' => $validated['amount'],
                'quantity' => $validated['quantity'],
                'expires_at' => $validated['expires_at'] ?? null,
                'created_by_admin_id' => $admin->id,
            ]);

            for ($i = 0; $i < $validated['quantity']; $i++) {
                GiftCard::create([
                    'gift_card_batch_id' => $batch->id,
                    'code' => GiftCard::generateCode(),
                    'pin_hash' => Hash::make(sprintf('%04d', random_int(0, 9999))),
                    'amount' => $validated['amount'],
                    'currency_code' => $validated['currency_code'],
                    'status' => 'inactive',
                    'expires_at' => $validated['expires_at'] ?? null,
                ]);
            }

            return $batch;
        });

        return redirect()
            ->route('admin.gift-cards.batches.show', $batch->id)
            ->with('success', __('admin.gift_cards_section.batch_created'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Batch show
    // ─────────────────────────────────────────────────────────────────────────

    public function batchShow(GiftCardBatch $batch): View
    {
        $batch->loadCount([
            'giftCards as active_count' => fn($q) => $q->where('status', 'active'),
            'giftCards as inactive_count' => fn($q) => $q->where('status', 'inactive'),
            'giftCards as redeemed_count' => fn($q) => $q->where('status', 'redeemed'),
            'giftCards as expired_count' => fn($q) => $q->where('status', 'expired'),
        ]);
        $batch->load('createdByAdmin:id,name');

        return view('admin.gift-cards.batches.show', [
            'batch' => $batch,
            'breadcrumbs' => [
                ['label' => __('admin.nav.dashboard'), 'url' => route('admin.dashboard')],
                ['label' => __('admin.gift_cards_section.batches_title'), 'url' => route('admin.gift-cards.batches.index')],
                ['label' => e($batch->name)],
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Batch activate
    // ─────────────────────────────────────────────────────────────────────────

    public function activateBatch(GiftCardBatch $batch): JsonResponse
    {
        $activated = GiftCard::where('gift_card_batch_id', $batch->id)
            ->where('status', 'inactive')
            ->update(['status' => 'active']);

        return response()->json([
            'success' => true,
            'activated_count' => $activated,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Expire stale cards
    // ─────────────────────────────────────────────────────────────────────────

    public function expireStale(): JsonResponse
    {
        $expired = GiftCard::where('status', 'active')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->update(['status' => 'expired']);

        return response()->json([
            'success' => true,
            'expired_count' => $expired,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private
    // ─────────────────────────────────────────────────────────────────────────

    private function cardColumnDefinitions(): array
    {
        return [
            ['title' => 'Code', 'data' => 'code', 'name' => 'code', 'orderable_column' => 'gift_cards.code', 'searchable_columns' => ['gift_cards.code']],
            ['title' => 'Amount', 'data' => 'amount', 'name' => 'amount', 'orderable_column' => 'gift_cards.amount', 'searchable' => false],
            ['title' => 'Currency', 'data' => 'currency_code', 'name' => 'currency_code', 'orderable_column' => 'gift_cards.currency_code', 'searchable' => false],
            ['title' => 'Status', 'data' => 'status', 'name' => 'status', 'orderable_column' => 'gift_cards.status', 'searchable' => false],
            ['title' => 'Expiry', 'data' => 'expires_at', 'name' => 'expires_at', 'orderable_column' => 'gift_cards.expires_at', 'searchable' => false],
            ['title' => '', 'data' => 'actions', 'name' => 'actions', 'orderable' => false, 'searchable' => false],
        ];
    }

    private function batchCardColumnDefinitions(): array
    {
        return [
            ['title' => 'Code', 'data' => 'code', 'name' => 'code', 'orderable_column' => 'gift_cards.code', 'searchable_columns' => ['gift_cards.code']],
            ['title' => 'Amount', 'data' => 'amount', 'name' => 'amount', 'orderable_column' => 'gift_cards.amount', 'searchable' => false],
            ['title' => 'Status', 'data' => 'status', 'name' => 'status', 'orderable_column' => 'gift_cards.status', 'searchable' => false],
            ['title' => 'Redeemed By', 'data' => 'redeemed_by', 'name' => 'redeemed_by', 'orderable' => false, 'searchable' => false],
            ['title' => 'Redeemed At', 'data' => 'redeemed_at', 'name' => 'redeemed_at', 'orderable_column' => 'gift_cards.redeemed_at', 'searchable' => false],
            ['title' => 'Expiry', 'data' => 'expires_at', 'name' => 'expires_at', 'orderable_column' => 'gift_cards.expires_at', 'searchable' => false],
        ];
    }
}
