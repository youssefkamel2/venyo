<?php

namespace App\Http\Controllers\Api\V1\Restaurant;

use App\Http\Controllers\Api\V1\BaseController;
use App\Models\RestaurantOwner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends BaseController
{
    /**
     * Register a new restaurant owner.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:restaurant_owners',
            'phone' => 'required|string|max:20',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $owner = RestaurantOwner::create([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'phone' => $request->input('phone'),
            'password' => Hash::make($request->input('password')),
            'temp_password' => false,
        ]);

        $token = $owner->createToken('owner_token')->plainTextToken;

        return $this->success([
            'owner' => $owner,
            'token' => $token,
        ], 'Registration successful. Please complete your restaurant profile.', 201);
    }

    /**
     * Login a restaurant owner.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $owner = RestaurantOwner::where('email', $request->input('email'))->first();

        if (!$owner || !Hash::check($request->input('password'), $owner->password)) {
            return $this->error('Invalid credentials', 401);
        }

        $token = $owner->createToken('owner_token')->plainTextToken;

        return $this->success([
            'owner' => $owner,
            'token' => $token,
        ], 'Login successful');
    }

    /**
     * Logout a restaurant owner.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->success(null, 'Logged out successfully');
    }
}
