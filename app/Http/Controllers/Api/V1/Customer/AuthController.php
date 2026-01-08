<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Api\V1\BaseController;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\VerificationCode;
use App\Notifications\VerifyEmailNotification;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthController extends BaseController
{
    /**
     * Register a new customer.
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'required|string|max:20',
            'password' => 'required|string|min:8|confirmed',
            'terms' => 'required|accepted',
        ], [
            'email.unique' => 'This email is already registered.',
            'password.confirmed' => 'Password confirmation does not match.',
            'password.min' => 'Password must be at least 8 characters.',
            'terms.accepted' => 'You must agree to the Terms of Service and Privacy Policy.',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422);
        }

        try {
            $user = User::create([
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'phone' => $request->input('phone'),
                'password' => Hash::make($request->input('password')),
                'is_active' => true,
            ]);

            // Generate verification code and send email
            $this->sendVerificationCode($user);

            $token = $user->createToken('customer_token')->plainTextToken;

            Log::channel('api')->info('User registered', ['user_id' => $user->id, 'email' => $user->email]);

            return $this->success([
                'user' => new UserResource($user),
                'token' => $token,
                'email_verified' => false,
            ], 'Registration successful! Please check your email for a verification code.', 201);

        } catch (\Exception $e) {
            Log::channel('api')->error('Registration failed', ['error' => $e->getMessage()]);
            return $this->error('Registration failed. Please try again.', 500);
        }
    }

    /**
     * Login a customer.
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error('Please enter a valid email and password.', 422);
        }

        $user = User::where('email', $request->input('email'))->first();

        if (!$user || !Hash::check($request->input('password'), $user->password)) {
            return $this->error('Invalid email or password.', 401);
        }

        if (!$user->is_active) {
            return $this->error('Your account has been deactivated. Please contact support.', 403);
        }

        $token = $user->createToken('customer_token')->plainTextToken;

        Log::channel('api')->info('User logged in', ['user_id' => $user->id]);

        return $this->success([
            'user' => new UserResource($user),
            'token' => $token,
            'email_verified' => $user->hasVerifiedEmail(),
        ], 'Welcome back!');
    }

    /**
     * Logout a customer.
     */
    public function logout(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $request->user()->currentAccessToken()->delete();

        Log::channel('api')->info('User logged out', ['user_id' => $userId]);

        return $this->success(null, 'Logged out successfully');
    }

    /**
     * Verify email with code.
     */
    public function verifyEmailCode(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return $this->error('Please enter a valid 6-digit code.', 422);
        }

        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return $this->success(['already_verified' => true], 'Your email is already verified.');
        }

        $verification = VerificationCode::where('user_id', $user->id)
            ->where('type', VerificationCode::TYPE_EMAIL_VERIFICATION)
            ->whereNull('used_at')
            ->latest()
            ->first();

        if (!$verification) {
            return $this->error('No active verification code found. Please request a new one.', 404);
        }

        if ($verification->expires_at->isPast()) {
            return $this->error('Verification code has expired. Please request a new one.', 400);
        }

        if ($verification->attempts >= VerificationCode::MAX_ATTEMPTS) {
            return $this->error('Too many incorrect attempts. Please request a new code.', 429);
        }

        $verification->incrementAttempts();

        if ($verification->code !== $request->input('code')) {
            $remaining = VerificationCode::MAX_ATTEMPTS - $verification->attempts;
            if ($remaining <= 0) {
                return $this->error('Too many attempts. Please request a new code.', 429);
            }
            return $this->error("Incorrect code. {$remaining} attempts remaining.", 400);
        }

        $verification->markAsUsed();
        $user->markEmailAsVerified();

        Log::channel('api')->info('Email verified', ['user_id' => $user->id]);

        return $this->success(['verified' => true], 'Email verified successfully!');
    }

    /**
     * Resend verification code.
     */
    public function resendVerificationCode(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return $this->success(['already_verified' => true], 'Your email is already verified.');
        }

        try {
            $this->sendVerificationCode($user);
            return $this->success(null, 'A new verification code has been sent to your email.');
        } catch (\Exception $e) {
            Log::channel('api')->error('Failed to send verification code', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            return $this->error('Unable to send verification code. Please try again.', 500);
        }
    }

    /**
     * Send password reset code.
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return $this->error('Please enter a valid email address.', 422);
        }

        $user = User::where('email', $request->input('email'))->first();

        if (!$user) {
            // Return success even if email doesn't exist (security best practice)
            return $this->success(null, 'If this email is registered, you will receive a reset code.');
        }

        try {
            $this->sendResetCode($user);
            Log::channel('api')->info('Password reset code sent', ['user_id' => $user->id]);
            return $this->success(null, 'Password reset code sent to your email!');
        } catch (\Exception $e) {
            Log::channel('api')->error('Failed to send reset code', ['email' => $request->input('email'), 'error' => $e->getMessage()]);
            return $this->error('Unable to send reset code. Please try again.', 500);
        }
    }

    /**
     * Verify password reset code.
     */
    public function verifyResetCode(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'code' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422);
        }

        $user = User::where('email', $request->input('email'))->first();

        if (!$user) {
            return $this->error('Invalid email address.', 400);
        }

        $verification = VerificationCode::where('user_id', $user->id)
            ->where('type', VerificationCode::TYPE_PASSWORD_RESET)
            ->whereNull('used_at')
            ->latest()
            ->first();

        if (!$verification) {
            return $this->error('No active reset code found. Please request a new one.', 404);
        }

        if ($verification->expires_at->isPast()) {
            return $this->error('Reset code has expired. Please request a new one.', 400);
        }

        if ($verification->attempts >= VerificationCode::MAX_ATTEMPTS) {
            return $this->error('Too many incorrect attempts. Please request a new code.', 429);
        }

        $verification->incrementAttempts();

        if ($verification->code !== $request->input('code')) {
            $remaining = VerificationCode::MAX_ATTEMPTS - $verification->attempts;
            if ($remaining <= 0) {
                return $this->error('Too many attempts. Please request a new code.', 429);
            }
            return $this->error("Incorrect code. {$remaining} attempts remaining.", 400);
        }

        // Don't mark as used yet - will be used when password is actually reset
        return $this->success(['valid' => true], 'Code verified. You can now reset your password.');
    }

    /**
     * Reset password with code.
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'code' => 'required|string|size:6',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'password.confirmed' => 'Password confirmation does not match.',
            'password.min' => 'Password must be at least 8 characters.',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422);
        }

        $user = User::where('email', $request->input('email'))->first();

        if (!$user) {
            return $this->error('Invalid email address.', 400);
        }

        $verification = VerificationCode::where('user_id', $user->id)
            ->where('type', VerificationCode::TYPE_PASSWORD_RESET)
            ->where('code', $request->input('code'))
            ->whereNull('used_at')
            ->first();

        if (!$verification) {
            return $this->error('Registration record not found or code already used.', 404);
        }

        if ($verification->expires_at->isPast()) {
            return $this->error('Reset code has expired. Please request a new one.', 400);
        }

        if ($verification->attempts > VerificationCode::MAX_ATTEMPTS) {
            return $this->error('Too many incorrect attempts. Please request a new code.', 429);
        }

        try {
            $user->forceFill([
                'password' => Hash::make($request->input('password')),
                'remember_token' => Str::random(60),
            ])->save();

            $verification->markAsUsed();

            Log::channel('api')->info('Password reset successful', ['user_id' => $user->id]);

            return $this->success(null, 'Password has been reset successfully!');
        } catch (\Exception $e) {
            Log::channel('api')->error('Password reset failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            return $this->error('Unable to reset password. Please try again.', 500);
        }
    }

    /**
     * Helper: Send verification code email.
     */
    private function sendVerificationCode(User $user): void
    {
        $verification = VerificationCode::generateFor($user, VerificationCode::TYPE_EMAIL_VERIFICATION);
        $user->notify(new VerifyEmailNotification($verification->code));
        Log::channel('api')->info('Verification code sent', ['user_id' => $user->id]);
    }

    /**
     * Helper: Send password reset code email.
     */
    private function sendResetCode(User $user): void
    {
        $verification = VerificationCode::generateFor($user, VerificationCode::TYPE_PASSWORD_RESET);
        $user->notify(new ResetPasswordNotification($verification->code));
    }
}
