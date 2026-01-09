<?php

namespace App\Support;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;

/**
 * Custom path generator that organizes media files into meaningful folder structures.
 * 
 * This creates paths like:
 * - users/{user_id}/avatars/{media_id}/{filename}
 * - restaurants/{restaurant_id}/photos/{media_id}/{filename}
 * - restaurants/{restaurant_id}/cover/{media_id}/{filename}
 */
class MediaPathGenerator implements PathGenerator
{
    /**
     * Get the path for the given media, relative to the root storage path.
     */
    public function getPath(Media $media): string
    {
        return $this->getBasePath($media) . '/';
    }

    /**
     * Get the path for conversions of the given media.
     */
    public function getPathForConversions(Media $media): string
    {
        return $this->getBasePath($media) . '/conversions/';
    }

    /**
     * Get the path for responsive images of the given media.
     */
    public function getPathForResponsiveImages(Media $media): string
    {
        return $this->getBasePath($media) . '/responsive-images/';
    }

    /**
     * Generate the base path for the media based on its model type and collection.
     */
    protected function getBasePath(Media $media): string
    {
        $modelType = $this->getModelFolder($media->model_type);
        $modelId = $media->model_id;
        $collection = $media->collection_name;
        $mediaId = $media->id;

        return "{$modelType}/{$modelId}/{$collection}/{$mediaId}";
    }

    /**
     * Convert model class name to a folder-friendly name.
     */
    protected function getModelFolder(string $modelClass): string
    {
        $map = [
            'App\Models\User' => 'users',
            'App\Models\Restaurant' => 'restaurants',
            'App\Models\RestaurantOwner' => 'owners',
            'App\Models\Admin' => 'admins',
        ];

        return $map[$modelClass] ?? strtolower(class_basename($modelClass)) . 's';
    }
}
