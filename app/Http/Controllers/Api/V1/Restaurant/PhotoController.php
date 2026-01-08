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

        $media = $restaurant->addMediaFromRequest('image')
            ->usingFileName(bin2hex(random_bytes(16)) . '.' . $request->file('image')->getClientOriginalExtension())
            ->toMediaCollection('photos');

        if ($request->input('is_cover')) {
            // Unset other cover photos in media if needed, 
            // or just use custom properties
            $media->setCustomProperty('is_cover', true);
            $media->save();
        }

        return $this->success($media, 'Photo uploaded successfully', 201);
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
        $media = $restaurant->media()->find($id);

        if (!$media) {
            return $this->error('Photo not found', 404);
        }

        $media->delete();

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

        // Reset all covers
        $restaurant->getMedia('photos')->each(function ($media) {
            $media->setCustomProperty('is_cover', false);
            $media->save();
        });

        $media = $restaurant->media()->find($id);
        if (!$media) {
            return $this->error('Photo not found', 404);
        }

        $media->setCustomProperty('is_cover', true);
        $media->save();

        return $this->success(null, 'Cover photo updated successfully');
    }
}
