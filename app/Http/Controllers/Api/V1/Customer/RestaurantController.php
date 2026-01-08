<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Api\V1\BaseController;
use App\Http\Resources\RestaurantResource;
use App\Models\Restaurant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class RestaurantController extends BaseController
{
    /**
     * List all restaurants with advanced filtering and searching.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $restaurants = QueryBuilder::for(Restaurant::class)
            ->allowedFilters([
                AllowedFilter::partial('name', 'name_en'),
                AllowedFilter::partial('name_ar'),
                AllowedFilter::callback('area_id', function ($query, $value) {
                    $decoded = \App\Models\Area::decodeId($value);
                    if ($decoded)
                        $query->where('area_id', $decoded);
                }),
                AllowedFilter::callback('sub_area_id', function ($query, $value) {
                    $decoded = \App\Models\SubArea::decodeId($value);
                    if ($decoded)
                        $query->where('sub_area_id', $decoded);
                }),
                AllowedFilter::callback('restaurant_type_id', function ($query, $value) {
                    $decoded = \App\Models\RestaurantType::decodeId($value);
                    if ($decoded)
                        $query->where('restaurant_type_id', $decoded);
                }),
                AllowedFilter::callback('cuisine_type_id', function ($query, $value) {
                    $decoded = \App\Models\CuisineType::decodeId($value);
                    if ($decoded)
                        $query->where('cuisine_type_id', $decoded);
                }),
                AllowedFilter::callback('is_promoted', function ($query, $value) {
                    $query->where('is_promoted', $value);
                }),
            ])
            ->allowedSorts(['created_at'])
            ->where('is_active', true)
            ->where('is_profile_complete', true)
            ->with(['area', 'subArea', 'type', 'cuisine', 'media', 'reviews.user'])
            ->when(auth('sanctum')->check(), function ($query) {
                $query->withExists([
                    'favorites' => function ($query) {
                        $query->where('user_id', auth('sanctum')->id());
                    }
                ]);
            })
            ->paginate($request->get('limit', 15));

        return $this->paginate(
            $restaurants->setCollection(
                collect(RestaurantResource::collection($restaurants->getCollection()))
            ),
            'Restaurants retrieved successfully'
        );
    }

    /**
     * Get a specific restaurant by slug.
     *
     * @param string $slug
     * @return JsonResponse
     */
    public function show(string $slug): JsonResponse
    {
        $restaurant = Restaurant::where('slug', $slug)
            ->where('is_active', true)
            ->with([
                'area',
                'subArea',
                'type',
                'cuisine',
                'media',
                'reviews.user',
                'slots' => function ($query) {
                    $query->where('is_active', true);
                }
            ])
            ->when(auth('sanctum')->check(), function ($query) {
                $query->withExists([
                    'favorites' => function ($query) {
                        $query->where('user_id', auth('sanctum')->id());
                    }
                ]);
            })
            ->first();

        if (!$restaurant) {
            return $this->error('Restaurant not found', 404);
        }

        return $this->success(new RestaurantResource($restaurant), 'Restaurant details retrieved successfully');
    }

    /**
     * Get promoted restaurants for the home section.
     *
     * @return JsonResponse
     */
    public function promoted(): JsonResponse
    {
        $restaurants = Restaurant::where('is_promoted', true)
            ->where('is_active', true)
            ->where('is_profile_complete', true)
            ->with(['area', 'subArea', 'type', 'cuisine', 'media', 'reviews.user'])
            ->when(auth('sanctum')->check(), function ($query) {
                $query->withExists([
                    'favorites' => function ($query) {
                        $query->where('user_id', auth('sanctum')->id());
                    }
                ]);
            })
            ->limit(10)
            ->get();

        return $this->success(RestaurantResource::collection($restaurants), 'Promoted restaurants retrieved successfully');
    }

    /**
     * Get available slots for a restaurant on a specific date.
     *
     * @param Request $request
     * @param string $slug
     * @return JsonResponse
     */
    public function availableSlots(Request $request, string $slug): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date|after_or_equal:today',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $restaurant = Restaurant::where('slug', $slug)->first();

        if (!$restaurant) {
            return $this->error('Restaurant not found', 404);
        }

        $reservationService = app(\App\Services\ReservationService::class);
        $slots = $reservationService->getAvailableSlots(
            $restaurant->id,
            $request->input('date')
        );

        return $this->success($slots, 'Available slots retrieved successfully');
    }
}
