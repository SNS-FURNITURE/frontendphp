<?php

namespace App\Services;

use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class JwtService
{
    public function secret(): string
    {
        return (string) env('JWT_SECRET', 'change-me-for-local-only');
    }

    public function issue(User $user, bool $rememberMe = false): string
    {
        $ttl = $rememberMe ? '30 days' : '24 hours';
        $now = time();
        $exp = $rememberMe ? $now + (30 * 24 * 60 * 60) : $now + (24 * 60 * 60);

        $payload = [
            'userId' => (int) $user->id,
            'sessionIssuedAt' => (int) round(microtime(true) * 1000),
            'rememberMe' => $rememberMe,
            'iat' => $now,
            'exp' => $exp,
        ];

        return JWT::encode($payload, $this->secret(), 'HS256');
    }

    /**
     * @return object{userId:int, sessionIssuedAt?:int, rememberMe?:bool}
     */
    public function decode(string $token): object
    {
        return JWT::decode($token, new Key($this->secret(), 'HS256'));
    }

    public function cookieMaxAgeMs(bool $rememberMe): ?int
    {
        if (! $rememberMe) {
            return null;
        }

        return (int) env('REMEMBER_SESSION_MAX_AGE_MS', 30 * 24 * 60 * 60 * 1000);
    }

    public function sessionCookieOptions(bool $rememberMe = false): array
    {
        $isProduction = app()->environment('production');
        $options = [
            'httponly' => true,
            'secure' => $isProduction,
            'samesite' => $isProduction ? 'none' : 'lax',
            'path' => '/',
        ];

        $maxAgeMs = $this->cookieMaxAgeMs($rememberMe);
        if ($maxAgeMs !== null) {
            $options['max_age'] = (int) floor($maxAgeMs / 1000);
        }

        return $options;
    }

    public function csrfCookieOptions(): array
    {
        $isProduction = app()->environment('production');

        return [
            'httponly' => false,
            'secure' => $isProduction,
            'samesite' => $isProduction ? 'none' : 'lax',
            'path' => '/',
            'max_age' => (int) floor(((int) env('SESSION_MAX_AGE_MS', 86400000)) / 1000),
        ];
    }

    public function findUserByIdentifier(string $identifier): ?User
    {
        $identifier = strtolower(ltrim(trim($identifier), '@'));
        if ($identifier === '') {
            return null;
        }

        // Older DBs may not have users.username — match Express auth.ts fallback.
        $hasUsername = Schema::hasColumn('users', 'username');

        try {
            return User::query()
                ->where(function ($q) use ($identifier, $hasUsername) {
                    $q->whereRaw('LOWER(email) = ?', [$identifier]);

                    if ($hasUsername) {
                        $q->orWhereRaw("LOWER(COALESCE(username, '')) = ?", [$identifier]);
                    }

                    if (! str_contains($identifier, '@')) {
                        $q->orWhereRaw("LOWER(SUBSTRING_INDEX(email, '@', 1)) = ?", [$identifier]);
                    }
                })
                ->first();
        } catch (QueryException $e) {
            if (! str_contains(strtolower($e->getMessage()), 'username')) {
                throw $e;
            }

            return User::query()
                ->where(function ($q) use ($identifier) {
                    $q->whereRaw('LOWER(email) = ?', [$identifier]);

                    if (! str_contains($identifier, '@')) {
                        $q->orWhereRaw("LOWER(SUBSTRING_INDEX(email, '@', 1)) = ?", [$identifier]);
                    }
                })
                ->first();
        }
    }

    public function verifyPassword(User $user, string $password): bool
    {
        return Hash::check($password, $user->password_hash);
    }

    public function loadUserWithRbac(int $userId): ?User
    {
        return User::query()
            ->with(['roles.permissions'])
            ->where('id', $userId)
            ->where('is_active', true)
            ->first();
    }
}
