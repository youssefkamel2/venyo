<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RestaurantResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Use pre-loaded data to avoid N+1 queries
        $reviews = $this->relationLoaded('reviews')
            ? $this->reviews->where('is_visible', true)
            : collect();

        $avgRating = $reviews->isNotEmpty() ? $reviews->avg('rating') : null;
        $reviewsCount = $reviews->count();

        return [
            'id' => $this->hashed_id,
            'name_en' => $this->name_en,
            'name_ar' => $this->name_ar,
            'slug' => $this->slug,
            'description_en' => $this->description_en,
            'description_ar' => $this->description_ar,
            'address' => $this->address,
            'phone' => $this->phone,
            'menu_link' => $this->menu_link,
            'google_maps_link' => $this->google_maps_link,
            'opening_time' => $this->opening_time,
            'closing_time' => $this->closing_time,
            'is_reservable' => $this->is_reservable,
            'is_promoted' => $this->is_promoted,
            'rating' => $avgRating ? round($avgRating, 1) : 0,
            'reviews_count' => $reviewsCount,
            // Use cached media from eager loading
            'cover_photo_url' => $this->relationLoaded('media') && $this->media->isNotEmpty()
                ? $this->media->first()->getUrl()
                : null,
            'photos' => $this->relationLoaded('media')
                ? $this->media->map(fn($m) => [
                    'id' => app(\App\Services\HashidService::class)->encode($m->id),
                    'url' => $m->getUrl(),
                ])
                : [],
            'reviews' => $reviews->take(5)->map(fn($review) => [
                'id' => $review->hashed_id,
                'user_name' => $review->user->name ?? 'Guest',
                'user_avatar' => $review->user?->getFirstMediaUrl('avatar'),
                'rating' => $review->rating,
                'comment' => $review->comment,
                'date' => $review->created_at->diffForHumans(),
            ]),
            'area' => [
                'id' => $this->area?->hashed_id,
                'name_en' => $this->area?->name_en,
                'name_ar' => $this->area?->name_ar,
            ],
            'sub_area' => [
                'id' => $this->subArea?->hashed_id,
                'name_en' => $this->subArea?->name_en,
                'name_ar' => $this->subArea?->name_ar,
            ],
            'type' => [
                'id' => $this->type?->hashed_id,
                'name_en' => $this->type?->name_en,
                'name_ar' => $this->type?->name_ar,
            ],
            'cuisine' => [
                'id' => $this->cuisine?->hashed_id,
                'name_en' => $this->cuisine?->name_en,
                'name_ar' => $this->cuisine?->name_ar,
            ],
            // Use pre-computed favorites_exists from controller (no extra query)
            'is_favorite' => isset($this->favorites_exists) ? (bool) $this->favorites_exists : false,
        ];
    }
}
