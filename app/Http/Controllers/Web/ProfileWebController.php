<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ProfileWebController extends Controller
{
    public function edit(): View
    {
        return view('profile.edit', ['user' => auth()->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = auth()->user();
        $isAdmin = (bool) $user?->isAdmin();

        if (!$isAdmin) {
            return back()->withErrors(['general' => 'You do not have permission to update profile details.']);
        }

        $rules = [
            'username' => ['required', 'string', 'min:3', 'max:64', 'regex:/^[a-z0-9][a-z0-9_-]*$/i'],
            'email' => ['required', 'email'],
            'full_name' => ['required', 'string', 'min:2', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
        ];

        $validated = $request->validate($rules, [
            'username.regex' => 'Username must be 3–64 characters: letters, numbers, underscores, or hyphens',
            'email.email' => 'A valid email address is required',
        ]);

        $username = strtolower(ltrim($validated['username'], '@'));

        $usernameTaken = \App\Models\User::query()
            ->whereRaw('LOWER(username) = ?', [$username])
            ->where('id', '!=', $user->id)
            ->exists();
        if ($usernameTaken) {
            return back()->withErrors(['username' => 'Username is already in use'])->withInput();
        }

        $emailTaken = \App\Models\User::query()
            ->whereRaw('LOWER(email) = ?', [strtolower($validated['email'])])
            ->where('id', '!=', $user->id)
            ->exists();
        if ($emailTaken) {
            return back()->withErrors(['email' => 'Email is already in use'])->withInput();
        }

        $user->full_name = trim($validated['full_name']);
        $user->phone = ($validated['phone'] ?? null) !== null && $validated['phone'] !== ''
            ? $validated['phone']
            : null;
        
        $user->username = $username;
        $user->email = strtolower($validated['email']);
        $user->save();

        app(\App\Services\AuditService::class)->log(
            $user,
            'user',
            (int) $user->id,
            'UPDATE_PROFILE',
            ['full_name' => $user->full_name, 'username' => $user->username, 'email' => $user->email],
            $request,
        );

        return back()->with('status', 'Profile updated');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $user = auth()->user();
        $key = 'pwd_attempts:'.$user->id;
        $attempts = (int) Cache::get($key, 0);
        if ($attempts >= 5) {
            return back()->withErrors(['current_password' => 'Too many failed attempts. Please try again later.']);
        }

        $current = (string) $request->input('current_password', '');
        $new = (string) $request->input('new_password', '');
        $confirm = (string) $request->input('confirm_password', '');

        if ($current === '' || $new === '' || $confirm === '') {
            return back()->withErrors(['current_password' => 'Current password, new password and confirmation are required']);
        }

        if ($new !== $confirm) {
            return back()->withErrors(['confirm_password' => 'New password and confirmation do not match']);
        }

        if (strlen($new) < 8) {
            return back()->withErrors(['new_password' => 'Password must be at least 8 characters']);
        }
        if (! preg_match('/[A-Za-z]/', $new)) {
            return back()->withErrors(['new_password' => 'Password must contain at least one letter']);
        }
        if (! preg_match('/\d/', $new)) {
            return back()->withErrors(['new_password' => 'Password must contain at least one number']);
        }
        if ($new === $current) {
            return back()->withErrors(['new_password' => 'New password must be different from the current password']);
        }

        if (! app(\App\Services\JwtService::class)->verifyPassword($user, $current)) {
            Cache::put($key, $attempts + 1, now()->addMinutes(15));

            return back()->withErrors(['current_password' => 'Current password is incorrect']);
        }

        $user->password_hash = Hash::make($new);
        $user->save();
        Cache::forget($key);

        app(\App\Services\AuditService::class)->log(
            $user,
            'user',
            (int) $user->id,
            'CHANGE_PASSWORD',
            [],
            $request,
        );

        return back()->with('status', 'Password updated successfully');
    }
}
