<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Mail\TeamMemberInviteMail;
use App\Models\VendorAdmin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TeamController extends Controller
{
    // ── Helpers ───────────────────────────────────────────────────────────────

    private function vendorAdmin(): VendorAdmin
    {
        return Auth::guard('vendor')->user();
    }

    private function vendorId(): string
    {
        return $this->vendorAdmin()->vendor_id;
    }

    /**
     * Guard: member must belong to the authenticated vendor.
     */
    private function authoriseMember(VendorAdmin $member): void
    {
        if ($member->vendor_id !== $this->vendorId()) {
            abort(404);
        }
    }

    // ── Actions ───────────────────────────────────────────────────────────────

    /**
     * Team management page — all vendor admins ordered owner→manager→staff.
     */
    public function index(): View
    {
        $admin = $this->vendorAdmin();
        $members = VendorAdmin::where('vendor_id', $this->vendorId())
            ->orderByRaw("FIELD(role, 'owner', 'manager', 'staff')")
            ->orderBy('created_at')
            ->get();

        return view('partner.team.index', compact('admin', 'members'));
    }

    /**
     * Invite a new team member (owner only).
     * Sends an email with a temporary password.
     */
    public function store(Request $request): JsonResponse
    {
        $admin = $this->vendorAdmin();

        if (!$admin->isOwner()) {
            return response()->json(['message' => 'يحق للمالك فقط إضافة أعضاء الفريق'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:vendor_admins,email',
            'role' => 'required|in:manager,staff',
        ]);

        $tempPassword = Str::password(12, symbols: false);

        $member = VendorAdmin::create([
            'vendor_id' => $this->vendorId(),
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($tempPassword),
            'role' => $validated['role'],
            'is_active' => true,
        ]);

        Mail::to($member->email)->send(new TeamMemberInviteMail($member, $tempPassword));

        return response()->json([
            'message' => 'تم إرسال الدعوة بنجاح',
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
     * Toggle a team member's active status (owner only, cannot deactivate owner).
     */
    public function toggleActive(VendorAdmin $member): JsonResponse
    {
        $admin = $this->vendorAdmin();

        if (!$admin->isOwner()) {
            return response()->json(['message' => 'يحق للمالك فقط تعديل حالة الأعضاء'], 403);
        }

        $this->authoriseMember($member);

        if ($member->isOwner()) {
            return response()->json(['message' => 'لا يمكن تعطيل حساب المالك'], 422);
        }

        $member->update(['is_active' => !$member->is_active]);

        return response()->json([
            'message' => $member->is_active ? 'تم تفعيل العضو' : 'تم تعطيل العضو',
            'is_active' => $member->is_active,
        ]);
    }

    /**
     * Soft-delete a team member (owner only, cannot delete owner).
     */
    public function destroy(VendorAdmin $member): JsonResponse
    {
        $admin = $this->vendorAdmin();

        if (!$admin->isOwner()) {
            return response()->json(['message' => 'يحق للمالك فقط حذف أعضاء الفريق'], 403);
        }

        $this->authoriseMember($member);

        if ($member->isOwner()) {
            return response()->json(['message' => 'لا يمكن حذف حساب المالك'], 422);
        }

        $member->delete();

        return response()->json(['message' => 'تم إزالة العضو بنجاح']);
    }
}
