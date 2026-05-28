<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\Coupon;

class CouponPolicy
{
    public function viewAny(Admin $admin): bool
    {
        return $admin->hasPermissionTo('coupons.view');
    }

    public function view(Admin $admin, Coupon $coupon): bool
    {
        return $admin->hasPermissionTo('coupons.view');
    }

    public function create(Admin $admin): bool
    {
        return $admin->hasPermissionTo('coupons.create');
    }

    public function update(Admin $admin, Coupon $coupon): bool
    {
        return $admin->hasPermissionTo('coupons.edit');
    }

    public function delete(Admin $admin, Coupon $coupon): bool
    {
        return $admin->hasPermissionTo('coupons.delete') && $coupon->times_used === 0;
    }
}
