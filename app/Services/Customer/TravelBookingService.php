<?php

namespace App\Services\Customer;

use App\Jobs\NotifyTravelBookingJob;
use App\Models\Customer;
use App\Models\TravelBooking;
use App\Models\TravelPackage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TravelBookingService
{
    // ── Account management ────────────────────────────────────────────────────

    public function listForCustomer(Customer $customer, array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $customer->travelBookings()
            ->with(['package:id,title,price_cents,currency'])
            ->when(isset($filters['status']), fn ($q) => $q->where('status', $filters['status']))
            ->latest()
            ->paginate(15);
    }

    public function showForCustomer(Customer $customer, string $id): TravelBooking
    {
        return $customer->travelBookings()
            ->with(['package.media', 'package.agency:id,name'])
            ->findOrFail($id);
    }

    public function cancel(Customer $customer, string $id, string $reason): TravelBooking
    {
        $booking = $customer->travelBookings()->findOrFail($id);

        if (! in_array($booking->status, ['pending_documents', 'confirmed'], true)) {
            throw ValidationException::withMessages([
                'status' => 'This booking cannot be cancelled in its current state.',
            ]);
        }

        // No automatic refund logic or cancellation_reason column exists in
        // the schema — cancellation is marked and left for admin/agency review.
        $booking->update(['status' => 'cancelled']);

        return $booking->fresh();
    }

    // ── Booking creation (called from storefront) ─────────────────────────────

    public function book(TravelPackage $package, Customer $customer, array $data): TravelBooking
    {
        $travelersCount = (int) $data['travelers_count'];

        return DB::transaction(function () use ($package, $customer, $travelersCount, $data): TravelBooking {
            /** @var TravelPackage $package */
            $package = TravelPackage::lockForUpdate()->findOrFail($package->id);

            $remaining = $package->seatsRemaining();
            if ($remaining !== null && $remaining < $travelersCount) {
                throw ValidationException::withMessages([
                    'travelers_count' => "Only {$remaining} seat(s) remaining for this package.",
                ]);
            }

            $totalCents = $package->price_cents * $travelersCount;

            $booking = TravelBooking::create([
                'travel_package_id'   => $package->id,
                'customer_id'         => $customer->id,
                'travelers_count'     => $travelersCount,
                'total_price_cents'   => $totalCents,
                'passport_file_path'  => $data['passport_file_path'] ?? null,
                'status'              => 'pending_documents',
            ]);

            $package->increment('seats_booked', $travelersCount);
            // After increment(), Eloquent updates the in-memory attribute too.
            if ($package->available_seats !== null
                && $package->seats_booked >= $package->available_seats
            ) {
                $package->update(['status' => 'sold_out']);
            }

            NotifyTravelBookingJob::dispatch($booking);

            return $booking;
        });
    }
}
