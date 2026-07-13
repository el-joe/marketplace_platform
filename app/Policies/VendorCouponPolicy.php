<?php

namespace App\Policies;

use App\Enums\VendorAdminRole;
use App\Models\Coupon;
use App\Models\VendorAdmin;
use App\Services\Vendor\CouponService;

/**
 * Not registered via Gate::policy() — the Coupon model already has an
 * admin-facing policy bound for the default guard. Vendor controllers
 * invoke this policy's methods directly instead.
 *
 * There is no general permission system for the vendor guard (VendorAdmin
 * only carries a plain owner/manager/staff role enum). Coupons touch
 * pricing/discounting directly, so — unlike most other vendor modules,
 * which are open to any role — mutation is restricted to owner/manager;
 * staff get read-only access.
 */
class VendorCouponPolicy
{
    private const MANAGE_ROLES = [VendorAdminRole::Owner, VendorAdminRole::Manager];

    public function viewAny(VendorAdmin $actor): bool
    {
        return true;
    }

    public function view(VendorAdmin $actor, Coupon $coupon): bool
    {
        return $coupon->vendor_id === $actor->vendor_id
            && in_array($coupon->scope->value, CouponService::VENDOR_MANAGEABLE_SCOPES, true);
    }

    public function create(VendorAdmin $actor): bool
    {
        return $this->canManage($actor);
    }

    public function update(VendorAdmin $actor, Coupon $coupon): bool
    {
        return $this->canManage($actor) && $this->view($actor, $coupon);
    }

    public function delete(VendorAdmin $actor, Coupon $coupon): bool
    {
        return $this->canManage($actor) && $this->view($actor, $coupon) && $coupon->times_used === 0;
    }

    public function toggleActive(VendorAdmin $actor, Coupon $coupon): bool
    {
        return $this->canManage($actor) && $this->view($actor, $coupon);
    }

    private function canManage(VendorAdmin $actor): bool
    {
        return in_array($actor->role, self::MANAGE_ROLES, true);
    }
}
