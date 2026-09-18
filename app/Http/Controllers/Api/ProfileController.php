<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function show(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->attributes->get('auth_user') ?? $request->user();
        if (! $user) {
            return ApiResponse::error('Profile not found', 'NOT_FOUND', 404);
        }

        return ApiResponse::success($this->profileArray($user));
    }

    public function update(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->attributes->get('auth_user') ?? $request->user();
        if (! $user) {
            return ApiResponse::error('Profile not found', 'NOT_FOUND', 404);
        }

        $fields = array_filter([
            'full_name' => $request->input('full_name'),
            'username' => $request->input('username'),
            'email' => $request->input('email'),
            'phone' => $request->input('phone'),
        ], fn ($v) => $v !== null);

        if ($fields === []) {
            return ApiResponse::error('No profile fields provided to update', 'INVALID_INPUT', 400);
        }

        if (array_key_exists('full_name', $fields) && strlen(trim((string) $fields['full_name'])) < 2) {
            return ApiResponse::error('Full name must be at least 2 characters', 'INVALID_INPUT', 400);
        }

        if (array_key_exists('username', $fields)) {
            $username = strtolower(ltrim(trim((string) $fields['username']), '@'));
            if (! preg_match('/^[a-z0-9][a-z0-9_-]{2,63}$/', $username)) {
                return ApiResponse::error(
                    'Username must be 3–64 characters: letters, numbers, underscores, or hyphens',
                    'INVALID_INPUT',
                    400,
                );
            }
            $taken = User::query()
                ->whereRaw('LOWER(username) = ?', [$username])
                ->where('id', '!=', $user->id)
                ->exists();
            if ($taken) {
                return ApiResponse::error('Username is already in use', 'USERNAME_TAKEN', 409);
            }
            $user->username = $username;
        }

        if (array_key_exists('email', $fields)) {
            $email = strtolower(trim((string) $fields['email']));
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return ApiResponse::error('A valid email address is required', 'INVALID_INPUT', 400);
            }
            $taken = User::query()
                ->whereRaw('LOWER(email) = ?', [$email])
                ->where('id', '!=', $user->id)
                ->exists();
            if ($taken) {
                return ApiResponse::error('Email is already in use', 'EMAIL_TAKEN', 409);
            }
            $user->email = $email;
        }

        if (array_key_exists('full_name', $fields)) {
            $user->full_name = trim((string) $fields['full_name']);
        }

        if (array_key_exists('phone', $fields)) {
            $phone = $fields['phone'];
            $user->phone = ($phone === '' || $phone === null) ? null : (string) $phone;
        }

        $user->save();
        $this->audit->log($user, 'user', (int) $user->id, 'UPDATE_PROFILE', $fields, $request);

        return ApiResponse::success($this->profileArray($user->fresh()));
    }

    public function updatePassword(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->attributes->get('auth_user') ?? $request->user();
        if (! $user) {
            return ApiResponse::error('Profile not found', 'NOT_FOUND', 404);
        }

        $key = 'pwd_attempts:'.$user->id;
        $attempts = (int) Cache::get($key, 0);
        if ($attempts >= 5) {
            return ApiResponse::error(
                'Too many failed attempts. Please try again later.',
                'TOO_MANY_ATTEMPTS',
                429,
            );
        }

        $current = (string) $request->input('current_password', '');
        $new = (string) $request->input('new_password', '');
        $confirm = (string) $request->input('confirm_password', '');

        if ($current === '' || $new === '' || $confirm === '') {
            return ApiResponse::error(
                'Current password, new password and confirmation are required',
                'INVALID_INPUT',
                400,
            );
        }

        if ($new !== $confirm) {
            return ApiResponse::error('New password and confirmation do not match', 'PASSWORD_MISMATCH', 400);
        }

        if (strlen($new) < 8) {
            return ApiResponse::error('Password must be at least 8 characters', 'WEAK_PASSWORD', 400);
        }
        if (! preg_match('/[A-Za-z]/', $new)) {
            return ApiResponse::error('Password must contain at least one letter', 'WEAK_PASSWORD', 400);
        }
        if (! preg_match('/\d/', $new)) {
            return ApiResponse::error('Password must contain at least one number', 'WEAK_PASSWORD', 400);
        }
        if ($new === $current) {
            return ApiResponse::error(
                'New password must be different from the current password',
                'SAME_PASSWORD',
                400,
            );
        }

        if (! Hash::check($current, $user->password_hash)) {
            Cache::put($key, $attempts + 1, now()->addMinutes(15));

            return ApiResponse::error('Current password is incorrect', 'INVALID_CREDENTIALS', 401);
        }

        $user->password_hash = Hash::make($new);
        $user->save();
        Cache::forget($key);
        $this->audit->log($user, 'user', (int) $user->id, 'CHANGE_PASSWORD', [], $request);

        return ApiResponse::success(['message' => 'Password updated successfully']);
    }

    private function profileArray(User $user): array
    {
        return [
            'id' => (int) $user->id,
            'username' => $user->username,
            'full_name' => $user->full_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'status' => $user->status,
            'is_active' => (bool) $user->is_active,
            'last_login_at' => $user->last_login_at,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ];
    }
}
