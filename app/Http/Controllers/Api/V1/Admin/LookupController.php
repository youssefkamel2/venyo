<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\BaseController;
use App\Models\Area;
use App\Models\SubArea;
use App\Models\CuisineType;
use App\Models\RestaurantType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class LookupController extends BaseController
{
    /**
     * List areas.
     */
    public function areasIndex(): JsonResponse
    {
        return $this->success(Area::all(), 'Areas retrieved successfully');
    }

    /**
     * Store area.
     */
    public function areaStore(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name_en' => 'required|string|max:255',
            'name_ar' => 'required|string|max:255',
        ]);

        if ($validator->fails()) return $this->validationError($validator->errors());

        $area = Area::create([
            'name_en' => $request->input('name_en'),
            'name_ar' => $request->input('name_ar'),
            'slug' => Str::slug($request->input('name_en')),
            'is_active' => true,
        ]);

        return $this->success($area, 'Area created successfully', 201);
    }

    /**
     * List cuisines.
     */
    public function cuisinesIndex(): JsonResponse
    {
        return $this->success(CuisineType::all(), 'Cuisines retrieved successfully');
    }

    /**
     * Store cuisine.
     */
    public function cuisineStore(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name_en' => 'required|string|max:255',
            'name_ar' => 'required|string|max:255',
        ]);

        if ($validator->fails()) return $this->validationError($validator->errors());

        $cuisine = CuisineType::create([
            'name_en' => $request->input('name_en'),
            'name_ar' => $request->input('name_ar'),
            'slug' => Str::slug($request->input('name_en')),
            'is_active' => true,
        ]);

        return $this->success($cuisine, 'Cuisine created successfully', 201);
    }
}
