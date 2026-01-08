<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\BaseController;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends BaseController
{
    /**
     * List all customers.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $users = User::orderBy('created_at', 'desc')->paginate($request->get('limit', 15));
        return $this->paginate($users, 'Users retrieved successfully');
    }

    /**
     * Show user details.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $user = User::with(['reservations', 'reviews', 'favorites'])->find($id);
        if (!$user) return $this->error('User not found', 404);
        return $this->success($user, 'User details retrieved successfully');
    }

    /**
     * Toggle user active status.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $user->update(['is_active' => !$user->is_active]);
        return $this->success($user, 'User status toggled successfully');
    }
}
