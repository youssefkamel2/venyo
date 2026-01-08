<?php

namespace App\Http\Controllers\Api\V1\Restaurant;

use App\Http\Controllers\Api\V1\BaseController;
use App\Models\Reservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReservationController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $restaurant = $request->user()->restaurant;
        if (!$restaurant) {
            return $this->error('Restaurant profile not found', 404);
        }

        $reservations = $restaurant->reservations()
            ->with(['user', 'timeSlot'])
            ->where('status', '!=', 'hold')
            ->when($request->input('status'), function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->input('date'), function ($query, $date) {
                $query->where('reservation_date', $date);
            })
            ->orderBy('reservation_date', 'desc')
            ->orderBy('reservation_time', 'desc')
            ->paginate($request->get('limit', 15));

        return $this->paginate($reservations, 'Reservations retrieved successfully');
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $restaurant = $request->user()->restaurant;
        $reservation = $restaurant->reservations()->find($id);

        if (!$reservation) {
            return $this->error('Reservation not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:accepted,rejected,canceled,completed',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $newStatus = $request->input('status');

        $updateData = ['status' => $newStatus];
        if ($newStatus === 'completed') {
            $updateData['completed_at'] = now();
        }

        $reservation->update($updateData);

        // Notify Customer
        $reservation->user->notify(new \App\Notifications\ReservationStatusUpdated($reservation));

        return $this->success($reservation, "Reservation marked as {$newStatus} successfully");
    }
}
