<table class="table-base w-full">
    <thead>
        <tr>
            <th>Customer</th>
            <th>Rating</th>
            <th>Review</th>
            <th>Status</th>
            <th>Date</th>
            <th class="text-end">Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($reviews as $review)
            <tr class="border-b border-gray-100" id="review-row-{{ $review->id }}">
                <td>{{ $review->customer?->name ?? 'Anonymous' }}</td>
                <td class="whitespace-nowrap">
                    <span class="text-yellow-500">{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}</span>
                </td>
                <td class="max-w-xs truncate" title="{{ $review->body }}">{{ \Illuminate\Support\Str::limit($review->body, 80) }}</td>
                <td>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700" data-review-status="{{ $review->id }}">
                        {{ ucfirst(str_replace('_', ' ', $review->status->value)) }}
                    </span>
                </td>
                <td>{{ $review->created_at->format('Y-m-d') }}</td>
                <td class="text-end whitespace-nowrap">
                    <a href="{{ route('admin.reviews.show', $review) }}" class="text-primary-600 hover:underline text-xs" target="_blank">View</a>
                    <button type="button" @click="approveReview('{{ $review->id }}')" class="text-green-600 hover:underline text-xs ms-2">Approve</button>
                    <button type="button" @click="rejectReview('{{ $review->id }}')" class="text-red-600 hover:underline text-xs ms-2">Reject</button>
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center text-gray-400 py-6">No reviews yet.</td></tr>
        @endforelse
    </tbody>
</table>
@if($reviews->hasPages())
    <div class="px-4 py-3 border-t border-gray-100">
        {{ $reviews->links() }}
    </div>
@endif
