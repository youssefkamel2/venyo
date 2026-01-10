<?php

namespace App\Http\Controllers\Api\V1\Restaurant;

use App\Http\Controllers\Api\V1\BaseController;
use App\Http\Resources\MenuCategoryResource;
use App\Http\Resources\MenuItemResource;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MenuController extends BaseController
{
    /**
     * Get all categories for the restaurant.
     */
    public function categories(Request $request): JsonResponse
    {
        $restaurant = $request->user()->restaurant;
        $categories = $restaurant->menuCategories()->orderBy('sort_order', 'asc')->get();
        return $this->success(MenuCategoryResource::collection($categories), 'Categories retrieved.');
    }

    /**
     * Store a new category.
     */
    public function storeCategory(Request $request): JsonResponse
    {
        $restaurant = $request->user()->restaurant;

        $validator = Validator::make($request->all(), [
            'name_en' => 'required|string|max:255',
            'name_ar' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $category = $restaurant->menuCategories()->create([
            'name_en' => $request->name_en,
            'name_ar' => $request->name_ar,
            'sort_order' => $restaurant->menuCategories()->count(),
        ]);

        return $this->success(new MenuCategoryResource($category), 'Category created successfully.', 201);
    }

    /**
     * Update a category.
     */
    public function updateCategory(Request $request, int $id): JsonResponse
    {
        $restaurant = $request->user()->restaurant;
        $category = $restaurant->menuCategories()->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name_en' => 'required|string|max:255',
            'name_ar' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $category->update($request->only(['name_en', 'name_ar']));

        return $this->success(new MenuCategoryResource($category), 'Category updated successfully.');
    }

    /**
     * Delete a category.
     */
    public function destroyCategory(Request $request, int $id): JsonResponse
    {
        $restaurant = $request->user()->restaurant;
        $category = $restaurant->menuCategories()->findOrFail($id);

        if ($category->menuItems()->exists()) {
            return $this->error('Cannot delete category with menu items.', 422);
        }

        $category->delete();

        return $this->success(null, 'Category deleted successfully.');
    }

    /**
     * Sort categories.
     */
    public function sortCategories(Request $request): JsonResponse
    {
        $restaurant = $request->user()->restaurant;
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        foreach ($request->ids as $index => $id) {
            $restaurant->menuCategories()->where('id', $id)->update(['sort_order' => $index]);
        }

        return $this->success(null, 'Categories sorted successfully.');
    }

    /**
     * Get all items.
     */
    public function items(Request $request): JsonResponse
    {
        $restaurant = $request->user()->restaurant;
        $items = MenuItem::whereIn('menu_category_id', $restaurant->menuCategories()->pluck('id'))
            ->with('category')
            ->get();

        return $this->success(MenuItemResource::collection($items), 'Items retrieved.');
    }

    /**
     * Store a new menu item.
     */
    public function storeItem(Request $request): JsonResponse
    {
        $restaurant = $request->user()->restaurant;

        $validator = Validator::make($request->all(), [
            'menu_category_id' => 'required|exists:menu_categories,id',
            'name_en' => 'required|string|max:255',
            'name_ar' => 'nullable|string|max:255',
            'description_en' => 'nullable|string|max:1000',
            'description_ar' => 'nullable|string|max:1000',
            'price' => 'required|numeric|min:0',
            'course' => 'nullable|string|max:50',
            'is_available' => 'boolean',
            'image' => 'nullable|image|max:2048',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        // Verify category belongs to restaurant
        $category = $restaurant->menuCategories()->findOrFail($request->menu_category_id);

        $item = $category->menuItems()->create($request->only([
            'name_en',
            'name_ar',
            'description_en',
            'description_ar',
            'price',
            'course',
            'is_available'
        ]));

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('restaurants/' . $restaurant->id . '/menu', 'public');
            $item->update(['image_url' => $path]);
        }

        return $this->success(new MenuItemResource($item), 'Menu item created successfully.', 201);
    }

    /**
     * Update a menu item.
     */
    public function updateItem(Request $request, int $id): JsonResponse
    {
        $restaurant = $request->user()->restaurant;
        $item = MenuItem::whereIn('menu_category_id', $restaurant->menuCategories()->pluck('id'))
            ->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'menu_category_id' => 'required|exists:menu_categories,id',
            'name_en' => 'required|string|max:255',
            'name_ar' => 'nullable|string|max:255',
            'description_en' => 'nullable|string|max:1000',
            'description_ar' => 'nullable|string|max:1000',
            'price' => 'required|numeric|min:0',
            'course' => 'nullable|string|max:50',
            'is_available' => 'boolean',
            'image' => 'nullable|image|max:2048',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        // Verify category belongs to restaurant
        $restaurant->menuCategories()->findOrFail($request->menu_category_id);

        $item->update($request->only([
            'menu_category_id',
            'name_en',
            'name_ar',
            'description_en',
            'description_ar',
            'price',
            'course',
            'is_available'
        ]));

        if ($request->hasFile('image')) {
            if ($item->image_url) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($item->image_url);
            }
            $path = $request->file('image')->store('restaurants/' . $restaurant->id . '/menu', 'public');
            $item->update(['image_url' => $path]);
        }

        return $this->success(new MenuItemResource($item), 'Menu item updated successfully.');
    }

    /**
     * Delete a menu item.
     */
    public function destroyItem(Request $request, int $id): JsonResponse
    {
        $restaurant = $request->user()->restaurant;
        $item = MenuItem::whereIn('menu_category_id', $restaurant->menuCategories()->pluck('id'))
            ->findOrFail($id);

        $item->delete();

        return $this->success(null, 'Menu item deleted successfully.');
    }
}
