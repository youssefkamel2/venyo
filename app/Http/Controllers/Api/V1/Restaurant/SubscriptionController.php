<?php

namespace App\Http\Controllers\Api\V1\Restaurant;

use App\Http\Controllers\Api\V1\BaseController;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SubscriptionController extends BaseController
{
    /**
     * List available plans.
     *
     * @return JsonResponse
     */
    public function plans(): JsonResponse
    {
        $plans = Plan::where('is_active', true)->orderBy('sort_order')->get();
        return $this->success($plans, 'Plans retrieved successfully');
    }

    /**
     * Subscribe to a plan.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function subscribe(Request $request): JsonResponse
    {
        $restaurant = $request->user()->restaurant;
        if (!$restaurant) {
            return $this->error('Restaurant profile not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'plan_id' => 'required|exists:plans,id',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $plan = Plan::find($request->input('plan_id'));

        return DB::transaction(function () use ($restaurant, $plan) {
            // Cancel active subscription if any
            $restaurant->subscriptions()->update(['status' => 'canceled']);

            $subscription = Subscription::create([
                'restaurant_id' => $restaurant->id,
                'plan_id' => $plan->id,
                'starts_at' => now(),
                'expires_at' => now()->addDays($plan->duration_days),
                'status' => 'active',
            ]);

            $invoice = Invoice::create([
                'subscription_id' => $subscription->id,
                'restaurant_id' => $restaurant->id,
                'amount' => $plan->price,
                'status' => 'unpaid',
            ]);

            return $this->success([
                'subscription' => $subscription,
                'invoice' => $invoice,
            ], 'Subscribed successfully. Please proceed to payment.', 201);
        });
    }

    /**
     * Get subscription history.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $restaurant = $request->user()->restaurant;
        $subscriptions = $restaurant->subscriptions()->with('plan')->orderBy('created_at', 'desc')->get();
        return $this->success($subscriptions, 'Subscription history retrieved successfully');
    }

    /**
     * Get invoices.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function invoices(Request $request): JsonResponse
    {
        $restaurant = $request->user()->restaurant;
        $invoices = $restaurant->owner->restaurant->invoices()->orderBy('created_at', 'desc')->get();
        return $this->success($invoices, 'Invoices retrieved successfully');
    }
}
