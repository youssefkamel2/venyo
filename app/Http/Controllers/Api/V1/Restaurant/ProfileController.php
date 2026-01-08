<?php

namespace App\Http\Controllers\Api\V1\Restaurant;

use App\Http\Controllers\Api\V1\BaseController;
use App\Models\Restaurant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ProfileController extends BaseController
{
    /**
     * Get restaurant profile.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function show(Request $request): JsonResponse
    {
        $owner = $request->user();
        $restaurant = $owner->restaurant()->with(['area', 'subArea', 'type', 'cuisine', 'photos'])->first();

        if (!$restaurant) {
            return $this->error('Restaurant profile not found. Please complete your registration.', 404);
        }

        return $this->success($restaurant, 'Restaurant profile retrieved successfully');
    }

    /**
     * Update/Create restaurant profile.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function update(Request $request): JsonResponse
    {
        $owner = $request->user();
        
        $validator = Validator::make($request->all(), [
            'name_en' => 'required|string|max:255',
            'name_ar' => 'required|string|max:255',
            'description_en' => 'nullable|string',
            'description_ar' => 'nullable|string',
            'area_id' => 'required|exists:areas,id',
            'sub_area_id' => 'required|exists:sub_areas,id',
            'restaurant_type_id' => 'required|exists:restaurant_types,id',
            'cuisine_type_id' => 'required|exists:cuisine_types,id',
            'address' => 'required|string|max:500',
            'location_lat' => 'nullable|numeric',
            'location_lng' => 'nullable|numeric',
            'phone' => 'nullable|string|max:20',
            'opening_time' => 'nullable|date_format:H:i',
            'closing_time' => 'nullable|date_format:H:i',
            'is_reservable' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $data = $request->all();
        $data['owner_id'] = $owner->id;
        $data['slug'] = Str::slug($request->input('name_en'));
        $data['is_profile_complete'] = true; // For now marking as complete if these fields are set

        $restaurant = Restaurant::updateOrCreate(
            ['owner_id' => $owner->id],
            $data
        );

        return $this->success($restaurant, 'Restaurant profile updated successfully');
    }
}
