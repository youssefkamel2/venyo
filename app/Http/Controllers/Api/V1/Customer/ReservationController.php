<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Api\V1\BaseController;
use App\Http\Resources\ReservationResource;
use App\Models\Restaurant;
use App\Models\TimeSlot;
use App\Services\ReservationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReservationController extends BaseController
{
    protected $reservationService;

    public function __construct(ReservationService $reservationService)
    {
        $this->reservationService = $reservationService;
    }

    public function lockSlot(Request $request): JsonResponse
    {
        // Require verified email
        if (!$request->user()->hasVerifiedEmail()) {
            return $this->error('Please verify your email address before making a reservation.', 403);
        }

        $rawRestaurantId = $request->input('restaurant_id');
        $rawSlotId = $request->input('time_slot_id');

        $restaurantId = Restaurant::decodeId($rawRestaurantId) ?? (is_numeric($rawRestaurantId) ? (int) $rawRestaurantId : null);
        $slotId = TimeSlot::decodeId($rawSlotId) ?? (is_numeric($rawSlotId) ? (int) $rawSlotId : null);

        $validator = Validator::make(array_merge($request->all(), [
            'restaurant_id' => $restaurantId,
            'time_slot_id' => $slotId,
        ]), [
            'restaurant_id' => 'required|exists:restaurants,id',
            'time_slot_id' => 'required|exists:time_slots,id',
            'date' => 'required|date|after_or_equal:today',
            'guests_count' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $restaurant = Restaurant::find($restaurantId);
        if (!$restaurant || !$restaurant->is_active || !$restaurant->is_reservable) {
            return $this->error('Restaurant is not accepting reservations currently', 422);
        }

        try {
            $reservation = $this->reservationService->lockSlot(
                $request->user()->id,
                $restaurantId,
                $slotId,
                $request->input('date'),
                $request->input('guests_count')
            );

            if (!$reservation) {
                return $this->error('Slot is no longer available.', 422);
            }

            return $this->success([
                'reservation_id' => $reservation->hashed_id,
                'locked_until' => $reservation->locked_until,
            ], 'Slot locked successfully for 5 minutes');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * Get reservation details securely for the completion page.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $decodedId = \App\Models\Reservation::decodeId($id) ?? (is_numeric($id) ? (int) $id : null);

        $reservation = $request->user()->reservations()
            ->with(['restaurant', 'timeSlot'])
            ->where('id', $decodedId)
            ->first();

        if (!$reservation) {
            return $this->error('Reservation not found or cannot be viewed.', 404);
        }

        return $this->success(new ReservationResource($reservation), 'Reservation details retrieved.');
    }

    /**
     * Complete the reservation with additional details.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $rawResId = $request->input('reservation_id');
        $reservationId = \App\Models\Reservation::decodeId($rawResId) ?? (is_numeric($rawResId) ? (int) $rawResId : null);

        $validator = Validator::make(array_merge($request->all(), [
            'reservation_id' => $reservationId,
        ]), [
            'reservation_id' => 'required|exists:reservations,id',
            'occasion' => 'nullable|string|max:255',
            'special_request' => 'nullable|string|max:1000',
            'subscribe_newsletter' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $reservationExists = $request->user()->reservations()
            ->where('id', $reservationId)
            ->exists();

        if (!$reservationExists) {
            return $this->forbidden('You do not have permission to complete this reservation.');
        }

        $reservation = $this->reservationService->completeReservation($reservationId, [
            'occasion' => $request->input('occasion'),
            'special_request' => $request->input('special_request'),
            'subscribe_newsletter' => $request->input('subscribe_newsletter', false),
        ]);

        if (!$reservation) {
            return $this->error('Reservation lock expired. Please try again.', 422);
        }

        // Notify Restaurant Owner
        $reservation->restaurant->owner->notify(new \App\Notifications\ReservationCreated($reservation));

        return $this->success(new ReservationResource($reservation->load(['restaurant', 'timeSlot'])), 'Reservation submitted successfully and is pending approval.');
    }

    public function index(Request $request): JsonResponse
    {
        $this->reservationService->cleanupLocks();

        $reservations = $request->user()->reservations()
            ->with(['restaurant', 'timeSlot', 'review'])
            ->where(function ($query) {
                $query->where('status', '!=', 'hold')
                    ->orWhere(function ($q) {
                        $q->where('status', 'hold')
                            ->where('locked_until', '>', now());
                    });
            })
            ->orderBy('reservation_date', 'desc')
            ->orderBy('reservation_time', 'desc')
            ->paginate($request->get('limit', 15));

        return $this->paginate(
            $reservations->setCollection(
                collect(ReservationResource::collection($reservations->getCollection()))
            ),
        );
    }

    public function cancel(Request $request, string $id): JsonResponse
    {
        $decodedId = \App\Models\Reservation::decodeId($id) ?? (is_numeric($id) ? (int) $id : null);
        $cancelled = $this->reservationService->cancel($request->user()->id, $decodedId);

        if (!$cancelled) {
            return $this->error('Reservation cannot be cancelled.', 422);
        }

        return $this->success(null, 'Reservation cancelled successfully');
    }
}
