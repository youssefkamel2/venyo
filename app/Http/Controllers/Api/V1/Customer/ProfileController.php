<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Api\V1\BaseController;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProfileController extends BaseController
{
    /**
     * Get user profile.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->load(['importantDates']);
        return $this->success(new UserResource($user), 'Profile retrieved successfully');
    }

    /**
     * Update user profile.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => ['nullable', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => 'required|string|max:20',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        // Email cannot be changed by user
        $user->update($request->only('name', 'phone'));

        return $this->success(new UserResource($user), 'Profile updated successfully');
    }

    /**
     * Update user password.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        if (!Hash::check($request->input('current_password'), $request->user()->password)) {
            return $this->error('The provided password does not match your current password.', 422);
        }

        $request->user()->update([
            'password' => Hash::make($request->input('password')),
        ]);

        return $this->success(null, 'Password updated successfully');
    }

    /**
     * Update user locale.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateLocale(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'locale' => 'required|string|in:ar,en',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $request->user()->update([
            'locale' => $request->input('locale'),
        ]);

        return $this->success(null, 'Language preference updated successfully');
    }

    /**
     * Update user avatar.
     */
    public function updateAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => 'required|image|max:2048', // 2MB Max
        ]);

        $user = $request->user();

        if ($request->hasFile('avatar')) {
            $user->clearMediaCollection('avatar');
            $user->addMediaFromRequest('avatar')
                ->usingFileName(bin2hex(random_bytes(16)) . '.' . $request->file('avatar')->getClientOriginalExtension())
                ->toMediaCollection('avatar');
        }

        return $this->success(new UserResource($user->fresh()), 'Avatar updated successfully');
    }

    /**
     * Delete user avatar.
     */
    public function deleteAvatar(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->clearMediaCollection('avatar');

        return $this->success(null, 'Avatar deleted successfully');
    }

    /**
     * Store a new important date.
     */
    public function storeDate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'type' => 'required|string',
            'label' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $date = $request->user()->importantDates()->create($request->all());

        return $this->success($date, 'Date added successfully');
    }

    /**
     * Delete an important date.
     */
    public function deleteDate($id): JsonResponse
    {
        // Using auth()->user() is easier.
        $date = auth()->user()->importantDates()->find($id);

        if (!$date) {
            return $this->error('Date not found', 404);
        }

        $date->delete();

        return $this->success(null, 'Date deleted successfully');
    }
}
