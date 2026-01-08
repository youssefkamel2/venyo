<?php

namespace App\Http\Controllers\Api\V1\Restaurant;

use App\Http\Controllers\Api\V1\BaseController;
use App\Models\TimeSlot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TimeSlotController extends BaseController
{
    /**
     * List all time slots for the restaurant.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $restaurant = $request->user()->restaurant;
        if (!$restaurant) {
            return $this->error('Restaurant profile not found', 404);
        }

        $slots = $restaurant->slots()
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        return $this->success($slots, 'Time slots retrieved successfully');
    }

    /**
     * Store a new time slot.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $restaurant = $request->user()->restaurant;
        if (!$restaurant) {
            return $this->error('Restaurant profile not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'day_of_week' => 'required|integer|min:0|max:6', // 0=Sunday, 6=Saturday
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'max_capacity' => 'required|integer|min:1',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $slot = $restaurant->slots()->create([
            'day_of_week' => $request->input('day_of_week'),
            'start_time' => $request->input('start_time'),
            'end_time' => $request->input('end_time'),
            'max_capacity' => $request->input('max_capacity'),
            'is_active' => $request->input('is_active') ?? true,
        ]);

        return $this->success($slot, 'Time slot created successfully', 201);
    }

    /**
     * Update an existing time slot.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $restaurant = $request->user()->restaurant;
        $slot = $restaurant->slots()->find($id);

        if (!$slot) {
            return $this->error('Time slot not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'day_of_week' => 'integer|min:0|max:6',
            'start_time' => 'date_format:H:i',
            'end_time' => 'date_format:H:i|after:start_time',
            'max_capacity' => 'integer|min:1',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $slot->update($request->all());

        return $this->success($slot, 'Time slot updated successfully');
    }

    /**
     * Delete a time slot.
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function destroy(int $id, Request $request): JsonResponse
    {
        $restaurant = $request->user()->restaurant;
        $slot = $restaurant->slots()->find($id);

        if (!$slot) {
            return $this->error('Time slot not found', 404);
        }

        $slot->delete();

        return $this->success(null, 'Time slot deleted successfully');
    }
}
