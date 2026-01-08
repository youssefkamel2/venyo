<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\BaseController;
use App\Models\User;
use App\Models\Restaurant;
use App\Models\Reservation;
use Illuminate\Http\JsonResponse;

class StatisticsController extends BaseController
{
    /**
     * Get dashboard summary statistics.
     *
     * @return JsonResponse
     */
    public function summary(): JsonResponse
    {
        $stats = [
            'overview' => [
                'total_users' => User::count(),
                'total_restaurants' => Restaurant::count(),
                'active_restaurants' => Restaurant::where('is_active', true)->count(),
                'total_reservations' => Reservation::count(),
                'total_revenue' => \App\Models\Invoice::where('status', 'paid')->sum('amount'),
            ],
            'reservations_by_status' => Reservation::selectRaw('status, count(*) as count')
                ->groupBy('status')
                ->get()
                ->pluck('count', 'status'),
            'monthly_trends' => Reservation::selectRaw('MONTH(created_at) as month, count(*) as count')
                ->whereYear('created_at', now()->year)
                ->groupBy('month')
                ->orderBy('month')
                ->get(),
            'top_restaurants' => Restaurant::withCount('reservations')
                ->orderBy('reservations_count', 'desc')
                ->limit(5)
                ->get(),
            'revenue_by_month' => \App\Models\Invoice::selectRaw('MONTH(created_at) as month, sum(amount) as total')
                ->where('status', 'paid')
                ->whereYear('created_at', now()->year)
                ->groupBy('month')
                ->orderBy('month')
                ->get(),
        ];

        return $this->success($stats, 'Advanced statistics retrieved successfully');
    }
}
