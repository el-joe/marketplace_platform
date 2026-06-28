@php
    /** @var \App\Models\DeliveryAssignment $assignment */
    $chipClass = 'chip-' . $assignment->status;
    $actionLabel = match ($assignment->status) {
        'assigned' => 'Accept Assignment',
        'accepted' => 'Mark as Picked Up',
        'picked_up' => 'Deliver',
        default => 'View',
    };
@endphp
<a href="{{ route('delivery.assignments.show', $assignment->id) }}"
    class="block d-card hover:bg-slate-700/50 transition-colors">
    <div class="flex items-start justify-between">
        <div class="flex-1 min-w-0">
            <p class="font-semibold text-sm truncate">
                #{{ $assignment->subOrder?->sub_order_number ?? substr($assignment->id, 0, 8) }}
            </p>
            <p class="text-xs text-slate-400 mt-0.5">
                {{ $assignment->subOrder?->items?->count() ?? '?' }} items
                @if($assignment->assigned_at)
                    · {{ $assignment->assigned_at->format('H:i') }}
                @endif
            </p>
        </div>
        <span class="chip {{ $chipClass }} ml-2 flex-shrink-0">
            {{ ucfirst(str_replace('_', ' ', $assignment->status)) }}
        </span>
    </div>
</a>
