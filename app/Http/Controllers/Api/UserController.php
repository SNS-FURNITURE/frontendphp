<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function __construct(
        private AuditService $audit,
    ) {}

    public function index(): JsonResponse
    {
        $users = User::query()
            ->with('roles')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (User $u) => [
                'id' => (int) $u->id,
                'username' => $u->username,
                'full_name' => $u->full_name,
                'email' => $u->email,
                'phone' => $u->phone,
                'status' => $u->status,
                'is_active' => (bool) $u->is_active,
                'last_login_at' => $u->last_login_at,
                'created_at' => $u->created_at,
                'updated_at' => $u->updated_at,
                'role_names' => $u->roles->pluck('name')->values()->all(),
            ]);

        return ApiResponse::success($users);
    }

    public function store(Request $request): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->attributes->get('auth_user') ?? $request->user();

        $fullName = trim((string) $request->input('full_name', ''));
        $email = strtolower(trim((string) $request->input('email', '')));
        if ($fullName === '' || $email === '') {
            return ApiResponse::error('Full name and email are required', 'INVALID_INPUT', 400);
        }
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ApiResponse::error('A valid email address is required', 'INVALID_INPUT', 400);
        }

        $password = (string) ($request->input('password') ?: 'password123');
        $phone = $request->input('phone');
        $phone = ($phone === '' || $phone === null) ? null : (string) $phone;

        $usernameRaw = $request->input('username');
        $normalizedUsername = $usernameRaw
            ? strtolower(trim((string) $usernameRaw))
            : preg_replace('/[^a-z0-9._-]/', '.', explode('@', $email)[0] ?? 'user');
        $normalizedUsername = substr((string) $normalizedUsername, 0, 64);
        if (strlen($normalizedUsername) < 3) {
            $normalizedUsername = 'user.'.substr((string) time(), -6);
        }

        $roleId = $request->input('role_id') ? (int) $request->input('role_id') : null;
        $roleName = is_string($request->input('role')) ? $request->input('role') : null;

        try {
            $created = DB::transaction(function () use (
                $fullName, $email, $normalizedUsername, $phone, $password, $roleId, $roleName, $actor, $request
            ) {
                $user = new User;
                $user->full_name = $fullName;
                $user->username = $normalizedUsername;
                $user->email = $email;
                $user->phone = $phone;
                $user->password_hash = Hash::make($password);
                $user->status = 'ACTIVE';
                $user->is_active = true;
                $user->save();

                $assignedRoleId = $roleId;
                $assignedRoleName = $roleName;

                if ($assignedRoleId) {
                    $role = Role::query()->find($assignedRoleId);
                    if (! $role) {
                        throw new \RuntimeException('Unknown role|INVALID_INPUT');
                    }
                    $assignedRoleName = $role->name;
                } elseif ($assignedRoleName) {
                    $role = Role::query()->where('name', $assignedRoleName)->first();
                    if (! $role) {
                        throw new \RuntimeException('Unknown role|INVALID_INPUT');
                    }
                    $assignedRoleId = (int) $role->id;
                    $assignedRoleName = $role->name;
                }

                if (strtolower((string) $assignedRoleName) === 'admin') {
                    throw new \RuntimeException('Admin role cannot be assigned from this screen|FORBIDDEN');
                }

                if ($assignedRoleId) {
                    DB::table('user_roles')->insert([
                        'user_id' => $user->id,
                        'role_id' => $assignedRoleId,
                        'assigned_at' => now(),
                    ]);
                }

                $this->audit->log($actor, 'user', (int) $user->id, 'CREATE_USER', [
                    'full_name' => $fullName,
                    'email' => $email,
                    'username' => $normalizedUsername,
                    'role' => $assignedRoleName,
                ], $request);

                return [
                    'id' => (int) $user->id,
                    'username' => $normalizedUsername,
                    'full_name' => $fullName,
                    'email' => $email,
                    'is_active' => true,
                ];
            });
        } catch (\RuntimeException $e) {
            [$message, $code] = array_pad(explode('|', $e->getMessage(), 2), 2, 'INVALID_INPUT');
            $status = $code === 'FORBIDDEN' ? 403 : 400;

            return ApiResponse::error($message, $code, $status);
        } catch (\Throwable $e) {
            if (str_contains($e->getMessage(), 'Duplicate') || str_contains($e->getMessage(), 'unique')) {
                return ApiResponse::error('Email or username is already in use', 'CONFLICT', 409);
            }
            throw $e;
        }

        return ApiResponse::success($created, null, 201);
    }
}
