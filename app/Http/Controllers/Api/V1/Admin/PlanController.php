<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\BaseController;
use App\Models\Plan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PlanController extends BaseController
{
    /**
     * List all plans.
     */
    public function index(): JsonResponse
    {
        return $this->success(Plan::orderBy('sort_order')->get(), 'Plans retrieved successfully');
    }

    /**
     * Store a new plan.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name_en' => 'required|string|max:255',
            'name_ar' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'duration_days' => 'required|integer|min:1',
            'max_photos' => 'required|integer|min:1',
            'is_promoted_included' => 'boolean',
        ]);

        if ($validator->fails()) return $this->validationError($validator->errors());

        $plan = Plan::create(array_merge($request->all(), [
            'slug' => Str::slug($request->input('name_en')),
        ]));

        return $this->success($plan, 'Plan created successfully', 201);
    }

    /**
     * Update plan.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $plan = Plan::findOrFail($id);
        $plan->update($request->all());
        return $this->success($plan, 'Plan updated successfully');
    }
}
