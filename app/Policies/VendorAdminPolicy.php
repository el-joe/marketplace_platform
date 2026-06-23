<?php

namespace App\Policies;

use App\Models\VendorAdmin;

class VendorAdminPolicy
{
    public function manageTeam(VendorAdmin $actor): bool
    {
        return in_array($actor->role, ['owner', 'manager'], true);
    }

    public function mutateTeamMember(VendorAdmin $actor, VendorAdmin $target): bool
    {
        if ($target->isOwner()) {
            return false;
        }

        return in_array($actor->role, ['owner', 'manager'], true);
    }
}
