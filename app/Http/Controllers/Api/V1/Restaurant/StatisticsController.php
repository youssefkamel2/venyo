<?php

namespace App\Http\Controllers\Api\V1\Restaurant;

use App\Http\Controllers\Api\V1\BaseController;
use App\Models\Reservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatisticsController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $restaurant = $request->user()->restaurant;
        if (!$restaurant) return $this->error('Restaurant not found', 404);

        $stats = [
            'overview' => [
                'total_reservations' => $restaurant->reservations()->count(),
                'completed_reservations' => $restaurant->reservations()->where('status', 'completed')->count(),
                'canceled_reservations' => $restaurant->reservations()->where('status', 'canceled')->count(),
                'total_guests' => $restaurant->reservations()->where('status', 'completed')->sum('guests_count'),
            ],
            'status_distribution' => $restaurant->reservations()
                ->selectRaw('status, count(*) as count')
                ->groupBy('status')
                ->get()
                ->pluck('count', 'status'),
            'daily_trends' => $restaurant->reservations()
                ->selectRaw('DATE(reservation_date) as date, count(*) as count')
                ->where('reservation_date', '>=', now()->subDays(30))
                ->groupBy('date')
                ->orderBy('date')
                ->get(),
            'recent_reviews' => $restaurant->reviews()
                ->with('user')
                ->latest()
                ->limit(5)
                ->get(),
        ];

        return $this->success($stats, 'Restaurant statistics retrieved successfully');
    }
}
