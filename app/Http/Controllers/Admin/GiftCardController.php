<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GiftCard;
use App\Services\GiftCardService;
use App\Traits\HasDataTable;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class GiftCardController extends Controller
{
    use HasDataTable, AuthorizesRequests;

    public const DENOMINATIONS = [5000, 10000, 25000, 50000, 100000];

    public function __construct(private readonly GiftCardService $giftCards)
    {
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Index
    // ─────────────────────────────────────────────────────────────────────────

    public function index(): View
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('viewAny', GiftCard::class);

        return view('admin.gift-cards.index', [
            'breadcrumbs' => [
                ['label' => __('admin.nav.dashboard'), 'url' => route('admin.dashboard')],
                ['label' => __('admin.gift_cards_section.title')],
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DataTable
    // ─────────────────────────────────────────────────────────────────────────

    public function datatable(Request $request): JsonResponse
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('viewAny', GiftCard::class);

        $columns = $this->columnDefinitions();

        $query = GiftCard::query()->select([
            'gift_cards.id',
            'gift_cards.code',
            'gift_cards.denomination',
            'gift_cards.balance',
            'gift_cards.currency',
            'gift_cards.status',
            'gift_cards.recipient_name',
            'gift_cards.recipient_email',
            'gift_cards.expires_at',
            'gift_cards.created_at',
        ]);

        $query = $this->applyFilters($query, $request, [
            'status' => fn($q, $v) => $q->where('gift_cards.status', $v),
            'currency' => fn($q, $v) => $q->where('gift_cards.currency', $v),
            'date_from' => fn($q, $v) => $q->whereDate('gift_cards.created_at', '>=', $v),
            'date_to' => fn($q, $v) => $q->whereDate('gift_cards.created_at', '<=', $v),
            'search' => function ($q, $v) {
                $q->where(function ($sub) use ($v) {
                    $sub->where('gift_cards.code', 'like', "%{$v}%")
                        ->orWhere('gift_cards.recipient_name', 'like', "%{$v}%")
                        ->orWhere('gift_cards.recipient_email', 'like', "%{$v}%");
                });
            },
        ]);

        return $this->dataTableResponse($request, $query, $columns, function ($row) {
            return [
                'id' => $row->id,
                'code' => e($row->code),
                'denomination' => (int) $row->denomination,
                'balance' => (int) $row->balance,
                'currency' => $row->currency,
                'status' => $row->status,
                'recipient_name' => $row->recipient_name ? e($row->recipient_name) : null,
                'recipient_email' => $row->recipient_email ? e($row->recipient_email) : null,
                'expires_at' => $row->expires_at,
                'created_at' => $row->created_at,
                'show_url' => route('admin.gift-cards.show', $row->id),
            ];
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Create / Store
    // ─────────────────────────────────────────────────────────────────────────

    public function create(): View
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('create', GiftCard::class);

        return view('admin.gift-cards.create', [
            'denominations' => self::DENOMINATIONS,
            'breadcrumbs' => [
                ['label' => __('admin.nav.dashboard'), 'url' => route('admin.dashboard')],
                ['label' => __('admin.gift_cards_section.title'), 'url' => route('admin.gift-cards.index')],
                ['label' => __('admin.gift_cards_section.new_gift_card')],
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('create', GiftCard::class);

        $isAnonymous = $request->boolean('anonymous');

        $validated = $request->validate([
            'denomination_option' => ['required_without:custom_amount', 'nullable', Rule::in(self::DENOMINATIONS)],
            'custom_amount' => ['required_without:denomination_option', 'nullable', 'integer', 'min:100'],
            'currency' => ['required', 'string', 'size:3'],
            'quantity' => ['required', 'integer', 'min:1', 'max:' . GiftCardService::MAX_BATCH_SIZE],
            'recipient_name' => [$isAnonymous ? 'nullable' : 'required', 'nullable', 'string', 'max:100'],
            'recipient_email' => ['nullable', 'email', 'max:255'],
            'recipient_phone' => ['nullable', 'string', 'max:30'],
            'personal_message' => ['nullable', 'string', 'max:500'],
            'expires_at' => ['nullable', 'date', 'after:today'],
        ]);

        $denominationCents = $validated['custom_amount'] ?? $validated['denomination_option'];

        /** @var \App\Models\Admin $admin */
        $admin = Auth::guard('admin')->user();

        $cards = $this->giftCards->issueByAdmin([
            'denomination' => $denominationCents,
            'currency' => strtoupper($validated['currency']),
            'quantity' => $validated['quantity'],
            'recipient_name' => $validated['recipient_name'] ?? null,
            'recipient_email' => $validated['recipient_email'] ?? null,
            'recipient_phone' => $validated['recipient_phone'] ?? null,
            'personal_message' => $validated['personal_message'] ?? null,
            'expires_at' => $validated['expires_at'] ?? null,
        ], $admin);

        $redirect = $cards->count() === 1
            ? route('admin.gift-cards.show', $cards->first()->id)
            : route('admin.gift-cards.index');

        return redirect($redirect)->with('success', trans_choice(
            'admin.gift_cards_section.gift_cards_created',
            $cards->count(),
            ['count' => $cards->count()]
        ));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Show
    // ─────────────────────────────────────────────────────────────────────────

    public function show(GiftCard $giftCard): View
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('view', $giftCard);

        $giftCard->load([
            'transactions' => fn($q) => $q->orderByDesc('created_at'),
            'purchasedByCustomer:id,name,email',
            'createdByAdmin:id,name',
        ]);

        return view('admin.gift-cards.show', [
            'giftCard' => $giftCard,
            'breadcrumbs' => [
                ['label' => __('admin.nav.dashboard'), 'url' => route('admin.dashboard')],
                ['label' => __('admin.gift_cards_section.title'), 'url' => route('admin.gift-cards.index')],
                ['label' => e($giftCard->code)],
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Cancel
    // ─────────────────────────────────────────────────────────────────────────

    public function cancel(GiftCard $giftCard): RedirectResponse
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('update', $giftCard);

        /** @var \App\Models\Admin $admin */
        $admin = Auth::guard('admin')->user();

        $this->giftCards->cancel($giftCard, $admin);

        return back()->with('success', __('admin.gift_cards_section.gift_card_cancelled'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Extend
    // ─────────────────────────────────────────────────────────────────────────

    public function extend(GiftCard $giftCard, Request $request): RedirectResponse
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('update', $giftCard);

        $validated = $request->validate([
            'expires_at' => ['required', 'date', 'after:today'],
        ]);

        /** @var \App\Models\Admin $admin */
        $admin = Auth::guard('admin')->user();

        $this->giftCards->extend($giftCard, new \DateTimeImmutable($validated['expires_at']), $admin);

        return back()->with('success', __('admin.gift_cards_section.expiry_extended'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Adjust
    // ─────────────────────────────────────────────────────────────────────────

    public function adjust(GiftCard $giftCard, Request $request): RedirectResponse
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('update', $giftCard);

        $validated = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'type' => ['required', Rule::in(['add', 'subtract'])],
            'notes' => ['required', 'string', 'max:255'],
        ]);

        /** @var \App\Models\Admin $admin */
        $admin = Auth::guard('admin')->user();

        try {
            $this->giftCards->adjustBalance(
                $giftCard,
                $validated['amount'],
                $validated['type'],
                $validated['notes'],
                $admin
            );
        } catch (\DomainException $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        }

        return back()->with('success', __('admin.gift_cards_section.balance_adjusted'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private
    // ─────────────────────────────────────────────────────────────────────────

    private function columnDefinitions(): array
    {
        return [
            ['title' => 'Code', 'data' => 'code', 'name' => 'code', 'orderable_column' => 'gift_cards.code', 'searchable_columns' => ['gift_cards.code', 'gift_cards.recipient_name', 'gift_cards.recipient_email']],
            ['title' => 'Denomination', 'data' => 'denomination', 'name' => 'denomination', 'orderable_column' => 'gift_cards.denomination', 'searchable' => false],
            ['title' => 'Balance', 'data' => 'balance', 'name' => 'balance', 'orderable_column' => 'gift_cards.balance', 'searchable' => false],
            ['title' => 'Currency', 'data' => 'currency', 'name' => 'currency', 'orderable_column' => 'gift_cards.currency', 'searchable' => false],
            ['title' => 'Status', 'data' => 'status', 'name' => 'status', 'orderable_column' => 'gift_cards.status', 'searchable' => false],
            ['title' => 'Recipient', 'data' => 'recipient_name', 'name' => 'recipient_name', 'searchable' => false],
            ['title' => 'Expiry', 'data' => 'expires_at', 'name' => 'expires_at', 'orderable_column' => 'gift_cards.expires_at', 'searchable' => false],
            ['title' => '', 'data' => 'actions', 'name' => 'actions', 'orderable' => false, 'searchable' => false],
        ];
    }
}
