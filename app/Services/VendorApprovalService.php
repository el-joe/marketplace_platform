<?php

namespace App\Services;

use App\Jobs\VendorApprovedJob;
use App\Models\Admin;
use App\Models\Vendor;
use App\Models\VendorStrike;
use Illuminate\Support\Facades\DB;

class VendorApprovalService
{
    // ── Approval ───────────────────────────────────────────────────────────────

    public function approve(Vendor $vendor, Admin $admin): void
    {
        DB::transaction(function () use ($vendor, $admin) {
            $vendor->update([
                'global_status' => 'active',
                'status' => 'active',
                'approved_at' => now(),
                'approved_by_admin_id' => $admin->id,
                'onboarding_completed_at' => now(),
            ]);

            $this->logActivity($vendor, $admin, 'approved', 'Vendor account approved');
            $this->createNotification($vendor, 'Your account has been approved!', 'vendor_approved');

            VendorApprovedJob::dispatch($vendor->id);
        });
    }

    // ── Rejection ─────────────────────────────────────────────────────────────

    public function reject(Vendor $vendor, string $reason, Admin $admin): void
    {
        DB::transaction(function () use ($vendor, $reason, $admin) {
            $vendor->update([
                'global_status' => 'rejected',
                'status' => 'inactive',
                'rejection_reason' => $reason,
            ]);

            $this->logActivity($vendor, $admin, 'rejected', 'Vendor application rejected', ['reason' => $reason]);
            $this->createNotification($vendor, 'Your vendor application was rejected. Reason: ' . $reason, 'vendor_rejected');
        });
    }

    // ── Request more info ────────────────────────────────────────────────────

    public function requestMoreInfo(Vendor $vendor, array $documentTypes, Admin $admin): void
    {
        DB::transaction(function () use ($vendor, $documentTypes, $admin) {
            $vendor->update(['global_status' => 'under_review']);

            foreach ($documentTypes as $type) {
                $vendor->documents()->firstOrCreate(
                    ['document_type' => $type],
                    ['status' => 'pending']
                );
            }

            $this->logActivity($vendor, $admin, 'info_requested', 'Additional documents requested', ['types' => $documentTypes]);
            $this->createNotification(
                $vendor,
                'Please upload the following documents: ' . implode(', ', $documentTypes),
                'documents_requested'
            );
        });
    }

    // ── Strike issuance ────────────────────────────────────────────────────────

    public function issueStrike(Vendor $vendor, array $data, Admin $admin): VendorStrike
    {
        return DB::transaction(function () use ($vendor, $data, $admin) {
            $strike = VendorStrike::create([
                'vendor_id' => $vendor->id,
                'reason' => $data['reason'],
                'severity' => $data['severity'],
                'description' => $data['description'] ?? null,
                'issued_by_admin_id' => $admin->id,
                'expires_at' => $data['expires_at'] ?? null,
                'is_active' => true,
            ]);

            $vendor->increment('strikes_count');
            $vendor->refresh();

            $activeCount = $vendor->activeStrikes()->count();
            $autoSuspended = false;

            if ($data['severity'] === 'critical' || $activeCount >= 3) {
                $this->suspend($vendor, 'Auto-suspended: ' . ($data['severity'] === 'critical' ? 'critical strike issued' : '3 active strikes'), $admin);
                $autoSuspended = true;
            }

            $this->logActivity($vendor, $admin, 'strike_issued', 'Strike issued', [
                'severity' => $data['severity'],
                'reason' => $data['reason'],
                'auto_suspend' => $autoSuspended,
            ]);
            $this->createNotification(
                $vendor,
                'A ' . $data['severity'] . ' strike has been issued on your account.',
                'strike_issued'
            );

            $strike->auto_suspended = $autoSuspended;
            $strike->active_count = $activeCount;

            return $strike;
        });
    }

    // ── Suspension / reactivation ─────────────────────────────────────────────

    public function suspend(Vendor $vendor, string $reason, Admin $admin): void
    {
        $vendor->update([
            'global_status' => 'suspended',
            'status' => 'inactive',
        ]);

        $this->logActivity($vendor, $admin, 'suspended', 'Vendor suspended', ['reason' => $reason]);
        $this->createNotification($vendor, 'Your account has been suspended. Reason: ' . $reason, 'account_suspended');
    }

    public function reactivate(Vendor $vendor, Admin $admin): void
    {
        $vendor->update([
            'global_status' => 'active',
            'status' => 'active',
        ]);

        $this->logActivity($vendor, $admin, 'reactivated', 'Vendor reactivated');
        $this->createNotification($vendor, 'Your account has been reactivated.', 'account_reactivated');
    }

    public function blacklist(Vendor $vendor, string $reason, Admin $admin): void
    {
        $vendor->update([
            'global_status' => 'blacklisted',
            'status' => 'inactive',
            'rejection_reason' => $reason,
        ]);

        $this->logActivity($vendor, $admin, 'blacklisted', 'Vendor blacklisted', ['reason' => $reason]);
    }

    // ── Payout hold ───────────────────────────────────────────────────────────

    public function placePayoutHold(Vendor $vendor, string $reason): void
    {
        $vendor->update([
            'payout_hold_active' => true,
            'payout_hold_reason' => $reason,
        ]);

        $this->createNotification($vendor, 'A payout hold has been placed on your account. Reason: ' . $reason, 'payout_hold_placed');
    }

    public function releasePayoutHold(Vendor $vendor): void
    {
        $vendor->update([
            'payout_hold_active' => false,
            'payout_hold_reason' => null,
        ]);

        $this->createNotification($vendor, 'Your payout hold has been released.', 'payout_hold_released');
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function logActivity(Vendor $vendor, Admin $admin, string $event, string $description, array $properties = []): void
    {
        DB::table('activity_log')->insert([
            'log_name' => 'vendor',
            'description' => $description,
            'subject_type' => Vendor::class,
            'subject_id' => $vendor->id,
            'causer_type' => Admin::class,
            'causer_id' => $admin->id,
            'properties' => json_encode(array_merge(['event' => $event], $properties)),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createNotification(Vendor $vendor, string $message, string $type): void
    {
        DB::table('notifications')->insert([
            'id' => \Illuminate\Support\Str::uuid(),
            'type' => 'App\\Notifications\\Vendor\\' . str($type)->studly(),
            'notifiable_type' => Vendor::class,
            'notifiable_id' => $vendor->id,
            'data' => json_encode(['message' => $message, 'type' => $type]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
