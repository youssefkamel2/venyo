<?php

namespace App\Http\Controllers\Api\V1\Restaurant;

use App\Http\Controllers\Api\V1\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PhotoController extends BaseController
{
    /**
     * Upload a photo for the restaurant.
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
            'image' => 'required|image|max:5120', // 5MB max
            'is_cover' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $path = $request->file('image')->store('restaurants/' . $restaurant->id . '/photos', 'public');

        $photo = $restaurant->photos()->create([
            'photo_path' => $path,
            'is_cover' => $request->boolean('is_cover'),
            'sort_order' => $restaurant->photos()->count(),
        ]);

        if ($photo->is_cover) {
            $restaurant->photos()
                ->where('id', '!=', $photo->id)
                ->update(['is_cover' => false]);
        }

        return $this->success($photo, 'Photo uploaded successfully', 201);
    }

    /**
     * Delete a photo.
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function destroy(int $id, Request $request): JsonResponse
    {
        $restaurant = $request->user()->restaurant;
        $photo = $restaurant->photos()->find($id);

        if (!$photo) {
            return $this->error('Photo not found', 404);
        }

        \Illuminate\Support\Facades\Storage::disk('public')->delete($photo->photo_path);
        $photo->delete();

        return $this->success(null, 'Photo deleted successfully');
    }

    /**
     * Set a photo as cover.
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function setCover(int $id, Request $request): JsonResponse
    {
        $restaurant = $request->user()->restaurant;

        $photo = $restaurant->photos()->find($id);
        if (!$photo) {
            return $this->error('Photo not found', 404);
        }

        // Reset all covers
        $restaurant->photos()->update(['is_cover' => false]);

        $photo->update(['is_cover' => true]);

        return $this->success(null, 'Cover photo updated successfully');
    }
}
