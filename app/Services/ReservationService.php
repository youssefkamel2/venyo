<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\TimeSlot;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReservationService
{
    public function isAvailable(int $slotId, string $date): bool
    {
        $slot = TimeSlot::findOrFail($slotId);

        // 1. Check if the slot is active
        if (!$slot->is_active) {
            return false;
        }

        // 2. Check if the slot is in the past (if date is today)
        if ($date === now()->toDateString()) {
            if (Carbon::parse($slot->start_time)->isPast()) {
                return false;
            }
        }

        // 3. Count active reservations and holds for this slot on this date
        $activeReservations = Reservation::where('time_slot_id', $slotId)
            ->where('reservation_date', $date)
            ->where(function ($query) {
                // Hold, Pending, Accepted, or Completed all consume capacity
                $query->whereIn('status', ['accepted', 'pending', 'completed'])
                    ->orWhere(function ($q) {
                    $q->where('status', 'hold')
                        ->where('locked_until', '>', now());
                });
            })
            ->count();

        // Check if we still have available tables
        return $activeReservations < $slot->tables_count;
    }

    public function getAvailableSlots(int $restaurantId, string $date): array
    {
        $slots = TimeSlot::where('restaurant_id', $restaurantId)
            ->where('is_active', true)
            ->orderBy('start_time')
            ->get();

        $allSlots = [];

        foreach ($slots as $slot) {
            $isAvailable = $this->isAvailable($slot->id, $date);

            $allSlots[] = [
                'id' => $slot->id,
                'time' => Carbon::parse($slot->start_time)->format('g:i A'),
                'raw_time' => $slot->start_time,
                'is_available' => $isAvailable
            ];
        }

        return $allSlots;
    }

    public function lockSlot(int $userId, int $restaurantId, int $slotId, string $date, int $guests): ?Reservation
    {
        return DB::transaction(function () use ($userId, $restaurantId, $slotId, $date, $guests) {
            // 1. Check if user already has a pending/hold reservation (any date) or accepted (same date)
            $existing = Reservation::where('user_id', $userId)
                ->where('restaurant_id', $restaurantId)
                ->where(function ($q) use ($date) {
                    $q->where('status', 'pending')
                        ->orWhere(function ($sq) {
                            $sq->where('status', 'hold')
                                ->where('locked_until', '>', now());
                        })
                        ->orWhere(function ($sq) use ($date) {
                            $sq->where('status', 'accepted')
                                ->where('reservation_date', $date);
                        });
                })
                ->lockForUpdate()
                ->exists();

            if ($existing) {
                throw new \Exception('You already have an active booking or session at this restaurant. Please complete or cancel it before booking another.');
            }

            // 2. Lock the TimeSlot record to prevent concurrent availability checks
            $slot = TimeSlot::where('id', $slotId)
                ->where('is_active', true) // Only lock if it's still active
                ->lockForUpdate()
                ->first();

            if (!$slot) {
                throw new \Exception('Selected time slot is no longer available or inactive.');
            }

            // 3. Re-verify availability inside transaction with the locked slot
            if (!$this->isAvailable($slotId, $date)) {
                return null;
            }

            return Reservation::create([
                'user_id' => $userId,
                'restaurant_id' => $restaurantId,
                'time_slot_id' => $slotId,
                'reservation_date' => $date,
                'reservation_time' => TimeSlot::find($slotId)->start_time,
                'guests_count' => $guests,
                'status' => 'hold',
                'locked_until' => now()->addMinutes(10),
            ]);
        });
    }

    public function cancel(int $userId, int $reservationId): bool
    {
        $reservation = Reservation::where('id', $reservationId)
            ->where('user_id', $userId)
            ->whereIn('status', ['pending', 'accepted', 'hold'])
            ->first();

        if (!$reservation) {
            return false;
        }

        return $reservation->update(['status' => 'canceled']);
    }

    public function completeReservation(int $reservationId, array $details): ?Reservation
    {
        $reservation = Reservation::findOrFail($reservationId);

        if ($reservation->locked_until && $reservation->locked_until->isPast()) {
            $reservation->update(['status' => 'canceled']);
            return null;
        }

        $restaurant = $reservation->restaurant;
        $status = $restaurant->auto_accept ? 'accepted' : 'pending';

        $reservation->update(array_merge($details, [
            'locked_until' => null,
            'status' => $status,
            'completed_at' => now(),
        ]));

        return $reservation;
    }

    public function cleanupLocks(): void
    {
        Reservation::where('status', 'hold')
            ->whereNotNull('locked_until')
            ->where('locked_until', '<', now())
            ->update(['status' => 'canceled']);
    }

    public function autoCancelPending(): void
    {
        $traceId = \App\Services\LoggingService::getTraceId();
        $now = now();
        $reservations = Reservation::where('status', 'pending')
            ->where(function ($query) use ($now) {
                $query->where('created_at', '<', $now->subHours(24))
                    ->orWhere(function ($q) use ($now) {
                        $q->where('reservation_date', '<', $now->toDateString())
                            ->orWhere(function ($sq) use ($now) {
                                $sq->where('reservation_date', $now->toDateString())
                                    ->where('reservation_time', '<', $now->toTimeString());
                            });
                    });
            })
            ->get();

        foreach ($reservations as $reservation) {
            $reservation->update(['status' => 'canceled']);
            \Illuminate\Support\Facades\Log::channel('api')->info('Auto-canceled reservation', [
                'trace_id' => $traceId,
                'reservation_id' => $reservation->id,
                'reason' => 'No response from restaurant',
            ]);
            $reservation->user->notify(new \App\Notifications\ReservationStatusUpdated($reservation));
        }
    }
}
