<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\AdCampaign;
use Illuminate\Auth\Access\HandlesAuthorization;

class AdCampaignPolicy
{
    use HandlesAuthorization;

    public function before(mixed $user, string $ability): bool|null
    {
        if ($user instanceof Admin) {
            return true;
        }

        return false;
    }

    public function viewAny(mixed $user): bool
    {
        return false;
    }
    public function view(mixed $user, AdCampaign $campaign): bool
    {
        return false;
    }
    public function create(mixed $user): bool
    {
        return false;
    }
    public function update(mixed $user, AdCampaign $campaign): bool
    {
        return false;
    }
    public function delete(mixed $user, AdCampaign $campaign): bool
    {
        return false;
    }
    public function approve(mixed $user, AdCampaign $campaign): bool
    {
        return false;
    }
    public function reject(mixed $user, AdCampaign $campaign): bool
    {
        return false;
    }
}
