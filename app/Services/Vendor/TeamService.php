<?php

namespace App\Services\Vendor;

use App\Enums\VendorAdminRole;
use App\Models\Vendor;
use App\Models\VendorAdmin;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TeamService
{
    public function invite(Vendor $vendor, array $data): VendorAdmin
    {
        $tempPassword = Str::random(16);

        $member = VendorAdmin::create([
            'vendor_id' => $vendor->id,
            'name'      => $data['name'],
            'email'     => $data['email'],
            'password'  => Hash::make($tempPassword),
            'role'      => $data['role'],
            'is_active' => true,
        ]);

        // Queue invite email with password-set link.
        // SendVendorTeamInviteJob::dispatch($member, $tempPassword);

        return $member;
    }

    public function canModify(VendorAdmin $actor, VendorAdmin $target): bool
    {
        // Nobody can modify an owner row (except the owner themselves changing
        // their own profile — that goes through a separate profile endpoint).
        if ($target->isOwner()) {
            return false;
        }

        return in_array($actor->role, [VendorAdminRole::Owner, VendorAdminRole::Manager], true);
    }
}
