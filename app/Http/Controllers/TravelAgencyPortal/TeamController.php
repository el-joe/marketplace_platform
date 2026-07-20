<?php

namespace App\Http\Controllers\TravelAgencyPortal;

use App\Http\Controllers\Controller;
use App\Mail\TravelAgencyTeamMemberInviteMail;
use App\Models\TravelAgencyMember;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class TeamController extends Controller
{
    // ── Helpers ───────────────────────────────────────────────────────────────

    private function member(): TravelAgencyMember
    {
        return Auth::guard('travel_agency')->user();
    }

    private function travelAgencyId(): string
    {
        return $this->member()->travel_agency_id;
    }

    /**
     * Guard: member must belong to the authenticated travel agency.
     */
    private function authoriseMember(TravelAgencyMember $member): void
    {
        if ($member->travel_agency_id !== $this->travelAgencyId()) {
            abort(404);
        }
    }

    /**
     * A role is assignable if it's a shared system role or one of this
     * agency's own custom roles — never another agency's custom role.
     */
    private function assignableRoleRule(): \Illuminate\Validation\Rules\Exists
    {
        return Rule::exists('roles', 'name')
            ->where('guard_name', 'travel_agency')
            ->where(function ($query) {
                $query->whereNull('travel_agency_id')
                    ->orWhere('travel_agency_id', $this->travelAgencyId());
            });
    }

    // ── Actions ───────────────────────────────────────────────────────────────

    /**
     * Team management page — all members ordered owner→manager→staff.
     */
    public function index(): View
    {
        $admin = $this->member();
        $members = TravelAgencyMember::where('travel_agency_id', $this->travelAgencyId())
            ->orderByDesc('is_owner')
            ->orderBy('created_at')
            ->get();

        $roleRows = Role::where('guard_name', 'travel_agency')
            ->where('name', '!=', 'travel_agency_owner')
            ->where(function ($query) {
                $query->whereNull('travel_agency_id')
                    ->orWhere('travel_agency_id', $this->travelAgencyId());
            })
            ->orderBy('name')
            ->get(['name', 'label']);

        $assignableRoles = $roleRows->pluck('name');
        $customRoleLabels = $roleRows->pluck('label', 'name')->filter()->toArray();

        return view('travel-agency.team.index', compact('admin', 'members', 'assignableRoles', 'customRoleLabels'));
    }

    /**
     * Invite a new team member (owner only).
     * Sends an email with a temporary password.
     */
    public function store(Request $request): JsonResponse
    {
        $admin = $this->member();

        if (!$admin->isOwner()) {
            return response()->json(['message' => 'Only the owner can add team members.'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:travel_agency_members,email',
            'role' => ['required', $this->assignableRoleRule(), Rule::notIn(['travel_agency_owner'])],
        ]);

        $tempPassword = Str::password(12, symbols: false);

        $member = DB::transaction(function () use ($validated, $tempPassword) {
            $member = TravelAgencyMember::create([
                'travel_agency_id' => $this->travelAgencyId(),
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($tempPassword),
                'role' => $validated['role'],
                'is_active' => true,
            ]);

            $member->assignRole($validated['role']);

            return $member;
        });

        Mail::to($member->email)->send(new TravelAgencyTeamMemberInviteMail($member, $tempPassword));

        return response()->json([
            'message' => 'Invitation sent successfully.',
            'member' => [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
                'role' => $member->role,
                'is_active' => $member->is_active,
                'last_login_at' => null,
            ],
        ], 201);
    }

    /**
     * Change a team member's role (owner only, cannot change own or another owner's role).
     */
    public function updateRole(Request $request, TravelAgencyMember $member): JsonResponse
    {
        $admin = $this->member();

        if (!$admin->isOwner()) {
            return response()->json(['message' => 'Only the owner can change member roles.'], 403);
        }

        $this->authoriseMember($member);

        if ($member->id === $admin->id) {
            return response()->json(['message' => 'You cannot change your own role.'], 422);
        }

        if ($member->isOwner()) {
            return response()->json(['message' => "The owner's role cannot be changed."], 422);
        }

        $validated = $request->validate([
            'role' => ['required', $this->assignableRoleRule(), Rule::notIn(['travel_agency_owner'])],
        ]);

        DB::transaction(function () use ($member, $validated) {
            $member->syncRoles([$validated['role']]);
            $member->update(['role' => $validated['role']]);
        });

        return response()->json([
            'message' => 'Member role updated successfully.',
            'role' => $member->role,
        ]);
    }

    /**
     * Toggle a team member's active status (owner only, cannot deactivate owner).
     */
    public function toggleActive(TravelAgencyMember $member): JsonResponse
    {
        $admin = $this->member();

        if (!$admin->isOwner()) {
            return response()->json(['message' => 'Only the owner can change member status.'], 403);
        }

        $this->authoriseMember($member);

        if ($member->isOwner()) {
            return response()->json(['message' => "The owner's account cannot be deactivated."], 422);
        }

        DB::transaction(function () use ($member) {
            $member->update(['is_active' => !$member->is_active]);

            if ($member->is_active) {
                $member->syncRoles([$member->role]);
            } else {
                $member->syncRoles([]);
            }
        });

        return response()->json([
            'message' => $member->is_active ? 'Member activated.' : 'Member deactivated.',
            'is_active' => $member->is_active,
        ]);
    }

    /**
     * Soft-delete a team member (owner only, cannot delete owner or self).
     */
    public function destroy(TravelAgencyMember $member): JsonResponse
    {
        $admin = $this->member();

        if (!$admin->isOwner()) {
            return response()->json(['message' => 'Only the owner can remove team members.'], 403);
        }

        $this->authoriseMember($member);

        if ($member->isOwner()) {
            return response()->json(['message' => "The owner's account cannot be removed."], 422);
        }

        if ($member->id === $admin->id) {
            return response()->json(['message' => 'You cannot remove your own account.'], 422);
        }

        $member->delete();

        return response()->json(['message' => 'Member removed successfully.']);
    }
}
