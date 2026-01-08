<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Api\V1\BaseController;
use App\Http\Resources\FavoriteResource;
use App\Models\Favorite;
use App\Models\Restaurant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FavoriteController extends BaseController
{
    /**
     * Toggle a restaurant as favorite.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function toggle(Request $request): JsonResponse
    {
        $rawRestId = $request->input('restaurant_id');
        $restaurantId = Restaurant::decodeId($rawRestId) ?? (is_numeric($rawRestId) ? (int) $rawRestId : null);

        $validator = Validator::make(array_merge($request->all(), [
            'restaurant_id' => $restaurantId,
        ]), [
            'restaurant_id' => 'required|exists:restaurants,id',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $userId = $request->user()->id;

        $favorite = Favorite::where('user_id', $userId)
            ->where('restaurant_id', $restaurantId)
            ->first();

        if ($favorite) {
            $favorite->delete();
            return $this->success(null, 'Restaurant removed from favorites');
        }

        Favorite::create([
            'user_id' => $userId,
            'restaurant_id' => $restaurantId,
        ]);

        return $this->success(null, 'Restaurant added to favorites');
    }

    /**
     * List user's favorite restaurants.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $favorites = $request->user()->favorites()
            ->with(['restaurant.area', 'restaurant.type', 'restaurant.cuisine', 'restaurant.photos'])
            ->paginate($request->get('limit', 15));

        return $this->paginate(
            $favorites->setCollection(
                collect(FavoriteResource::collection($favorites->getCollection()))
            ),
            'Favorites retrieved successfully'
        );
    }
}
