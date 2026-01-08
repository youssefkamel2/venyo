<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Api\V1\BaseController;
use App\Http\Resources\ReviewResource;
use App\Models\Reservation;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReviewController extends BaseController
{
    /**
     * Submit a review for a completed reservation.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $rawResId = $request->input('reservation_id');
        $reservationId = Reservation::decodeId($rawResId) ?? (is_numeric($rawResId) ? (int) $rawResId : null);

        $validator = Validator::make(array_merge($request->all(), [
            'reservation_id' => $reservationId,
        ]), [
            'reservation_id' => 'required|exists:reservations,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $reservation = Reservation::where('id', $reservationId)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$reservation) {
            return $this->error('Reservation not found or unauthorized', 404);
        }

        if ($reservation->status !== 'completed') {
            return $this->error('You can only review completed reservations', 422);
        }

        // Check if already reviewed
        if ($reservation->review) {
            return $this->error('You have already reviewed this reservation', 422);
        }

        $review = Review::create([
            'user_id' => $request->user()->id,
            'restaurant_id' => $reservation->restaurant_id,
            'reservation_id' => $reservation->id,
            'rating' => $request->rating,
            'comment' => $request->comment,
            'is_visible' => true,
        ]);

        return $this->success(new ReviewResource($review), 'Review submitted successfully', 201);
    }

    /**
     * List user's reviews.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $reviews = $request->user()->reviews()
            ->with('restaurant')
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('limit', 15));

        return $this->paginate(
            $reviews->setCollection(
                collect(ReviewResource::collection($reviews->getCollection()))
            ),
            'Reviews retrieved successfully'
        );
    }
}
