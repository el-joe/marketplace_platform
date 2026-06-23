<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\Review;
use Illuminate\Support\Facades\DB;

class ReviewModerationService
{
    /**
     * Approve a review: publish it and recalculate the product rating.
     */
    public function approve(Review $review, Admin $admin): void
    {
        DB::transaction(function () use ($review, $admin) {
            $review->update([
                'status' => 'published',
                'moderated_by_admin_id' => $admin->id,
            ]);

            $this->recalculateProductRating($review->product_id);
        });
    }

    /**
     * Reject a review: record moderator and soft-delete.
     */
    public function reject(Review $review, Admin $admin): void
    {
        DB::transaction(function () use ($review, $admin) {
            $review->update([
                'status' => 'rejected',
                'moderated_by_admin_id' => $admin->id,
            ]);

            $this->recalculateProductRating($review->product_id);

            $review->delete(); // soft delete
        });
    }

    /**
     * Recalculate rating_avg and rating_count for a product.
     */
    public function recalculateProductRating(string $productId): void
    {
        DB::table('products')
            ->where('id', $productId)
            ->update([
                'rating_avg' => DB::raw("(SELECT AVG(rating) FROM reviews WHERE product_id = '{$productId}' AND status = 'published' AND deleted_at IS NULL)"),
                'rating_count' => DB::raw("(SELECT COUNT(*) FROM reviews WHERE product_id = '{$productId}' AND status = 'published' AND deleted_at IS NULL)"),
                'updated_at' => now(),
            ]);
    }
}
