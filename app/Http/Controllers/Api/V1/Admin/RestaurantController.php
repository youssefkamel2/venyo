<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\BaseController;
use App\Models\Restaurant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RestaurantController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $restaurants = Restaurant::with(['owner', 'area', 'type', 'cuisine'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('limit', 15));
        return $this->paginate($restaurants, 'Restaurants retrieved successfully');
    }

    public function show(int $id): JsonResponse
    {
        $restaurant = Restaurant::with(['owner', 'area', 'subArea', 'type', 'cuisine', 'photos', 'slots'])
            ->find($id);
        if (!$restaurant) return $this->error('Restaurant not found', 404);
        return $this->success($restaurant, 'Restaurant details retrieved successfully');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $restaurant = Restaurant::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'is_active' => 'boolean',
            'is_promoted' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $restaurant->update($request->only('is_active', 'is_promoted'));
        return $this->success($restaurant, 'Restaurant status updated successfully');
    }
}
