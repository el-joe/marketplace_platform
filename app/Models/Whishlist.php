<?php

namespace App\Models;

/**
 * Legacy typo alias – kept to avoid breaking existing references.
 * Use Wishlist instead.
 */
class Whishlist extends Wishlist
{
    protected $table = 'wishlists';
}
