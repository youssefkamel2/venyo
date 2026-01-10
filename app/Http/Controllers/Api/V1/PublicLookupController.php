<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseController;
use App\Models\Area;
use App\Models\CuisineType;
use App\Models\RestaurantType;
use App\Http\Resources\AreaResource;
use App\Http\Resources\CuisineResource;
use App\Http\Resources\RestaurantTypeResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class PublicLookupController extends BaseController
{
    public function areas(): JsonResponse
    {
        $areas = Cache::remember('public_areas', 60 * 60 * 24, function () {
            return Area::where('is_active', true)
                ->withCount([
                    'restaurants' => function ($query) {
                        $query->where('is_active', true)->where('is_profile_complete', true);
                    }
                ])
                ->with([
                    'subAreas' => function ($q) {
                        $q->withCount([
                            'restaurants' => function ($query) {
                                $query->where('is_active', true)->where('is_profile_complete', true);
                            }
                        ]);
                    }
                ])->get();
        });
        return $this->success(AreaResource::collection($areas), 'Areas retrieved successfully');
    }

    public function cuisines(): JsonResponse
    {
        $cuisines = Cache::remember('public_cuisines', 60 * 60 * 24, function () {
            return CuisineType::where('is_active', true)
                ->withCount([
                    'restaurants' => function ($query) {
                        $query->where('is_active', true)->where('is_profile_complete', true);
                    }
                ])->get();
        });
        return $this->success(CuisineResource::collection($cuisines), 'Cuisine types retrieved successfully');
    }

    public function types(): JsonResponse
    {
        $types = Cache::remember('public_restaurant_types', 60 * 60 * 24, function () {
            return RestaurantType::all();
        });
        return $this->success(RestaurantTypeResource::collection($types), 'Restaurant types retrieved successfully');
    }
}
