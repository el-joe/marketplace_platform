<?php

namespace App\Models;

/**
 * Legacy typo alias – kept to avoid breaking existing references.
 * Use Wishlist instead.
 */
class Whishlist extends Wishlist
{
    protected $fillable = [
        'customer_id',
        'product_id',
        'vendor_listing_id',
        'product_variant_id',
        'added_at',
    ];

    protected $table = 'wishlists';
}
