<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\FlashSale;

class FlashSalePolicy
{
    public function viewAny(Admin $admin): bool
    {
        return $admin->can('flash_sales.view');
    }

    public function view(Admin $admin, FlashSale $sale): bool
    {
        return $admin->can('flash_sales.view');
    }

    public function create(Admin $admin): bool
    {
        return $admin->can('flash_sales.create');
    }

    public function update(Admin $admin, FlashSale $sale): bool
    {
        return $admin->can('flash_sales.edit')
            && !in_array($sale->status, ['live', 'ended', 'cancelled'], true);
    }

    public function delete(Admin $admin, FlashSale $sale): bool
    {
        return $admin->can('flash_sales.edit') && $sale->status === 'draft';
    }

    public function transition(Admin $admin, FlashSale $sale): bool
    {
        return $admin->can('flash_sales.edit');
    }

    public function reviewSubmissions(Admin $admin, FlashSale $sale): bool
    {
        return $admin->can('flash_sales.review_submissions');
    }
}
